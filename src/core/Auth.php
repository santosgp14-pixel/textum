<?php
/**
 * TEXTUM - Helpers de autenticación y sesión
 */
class Auth {

    private const REMEMBER_COOKIE   = 'textum_rm';
    private const REMEMBER_LIFETIME = 2592000; // 30 días en segundos

    public static function init(): void {
        ini_set('session.name', SESSION_NAME);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_start();

        // Auto-login por cookie "Recordarme" si no hay sesión activa
        if (!self::check()) {
            self::checkRememberCookie();
        }
    }

    /** Loguea al usuario. Retorna true si OK. */
    public static function login(string $email, string $password): bool {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT u.*, e.nombre AS empresa_nombre
             FROM usuarios u
             JOIN empresas e ON e.id = u.empresa_id
             WHERE u.email = ? AND u.activo = 1 AND e.activa = 1
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        // Renovar sesión por seguridad
        session_regenerate_id(true);

        self::fillSession($user);

        // Registrar último login
        $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")
           ->execute([$user['id']]);

        return true;
    }

    /** Emite y persiste un token "Recordarme" en cookie + DB. */
    public static function setRememberCookie(int $userId): void {
        $db       = Database::getInstance()->getConnection();
        $selector = rtrim(base64_encode(random_bytes(16)), '=');
        $verifier = bin2hex(random_bytes(32));
        $hash     = hash('sha256', $verifier);
        $expires  = date('Y-m-d H:i:s', time() + self::REMEMBER_LIFETIME);

        // Limpiar tokens expirados del usuario (mantenimiento silencioso)
        $db->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND expires_at < NOW()")
           ->execute([$userId]);

        $db->prepare(
            "INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
             VALUES (?, ?, ?, ?)"
        )->execute([$userId, $selector, $hash, $expires]);

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        setcookie(
            self::REMEMBER_COOKIE,
            $selector . ':' . $verifier,
            [
                'expires'  => time() + self::REMEMBER_LIFETIME,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $secure,
            ]
        );
    }

    /** Verifica la cookie "Recordarme" y restaura la sesión si es válida. */
    private static function checkRememberCookie(): void {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if (!$cookie || substr_count($cookie, ':') !== 1) {
            return;
        }

        [$selector, $verifier] = explode(':', $cookie, 2);
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare(
            "SELECT rt.*, u.*, e.nombre AS empresa_nombre
             FROM remember_tokens rt
             JOIN usuarios u ON u.id = rt.user_id
             JOIN empresas e ON e.id = u.empresa_id
             WHERE rt.selector = ? AND rt.expires_at > NOW()
               AND u.activo = 1 AND e.activa = 1
             LIMIT 1"
        );
        $stmt->execute([$selector]);
        $row = $stmt->fetch();

        if (!$row || !hash_equals($row['token_hash'], hash('sha256', $verifier))) {
            // Token inválido o robado: borrar cookie y registro
            self::clearRememberCookie();
            if ($row) {
                $db->prepare("DELETE FROM remember_tokens WHERE selector = ?")
                   ->execute([$selector]);
            }
            return;
        }

        // Token válido → restaurar sesión + rolling refresh del token
        session_regenerate_id(true);
        self::fillSession($row);

        // Rotar: emitir nuevo token y borrar el viejo
        $db->prepare("DELETE FROM remember_tokens WHERE selector = ?")
           ->execute([$selector]);
        self::setRememberCookie((int)$row['user_id']);
    }

    public static function logout(): void {
        // Invalidar token de cookie en DB
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if ($cookie && substr_count($cookie, ':') === 1) {
            [$selector] = explode(':', $cookie, 2);
            try {
                Database::getInstance()->getConnection()
                    ->prepare("DELETE FROM remember_tokens WHERE selector = ?")
                    ->execute([$selector]);
            } catch (Throwable) {}
        }
        self::clearRememberCookie();
        session_destroy();
        setcookie(SESSION_NAME, '', time() - 3600, '/');
    }

    public static function check(): bool {
        return !empty($_SESSION['user_id']);
    }

    /** Redirige al login si no hay sesión */
    public static function require(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::require();
        if ($_SESSION['user_rol'] !== 'admin') {
            http_response_code(403);
            die('Acceso denegado. Se requiere rol administrador.');
        }
    }

    public static function userId(): int    { return (int)($_SESSION['user_id']     ?? 0); }
    public static function empresaId(): int { return (int)($_SESSION['empresa_id']  ?? 0); }
    public static function rol(): string    { return $_SESSION['user_rol']           ?? ''; }
    public static function isAdmin(): bool  { return self::rol() === 'admin'; }
    public static function nombre(): string { return $_SESSION['user_nombre']        ?? ''; }
    public static function empresaNombre(): string { return $_SESSION['empresa_nombre'] ?? ''; }

    // ── Privados ──────────────────────────────────────────────

    private static function fillSession(array $user): void {
        $_SESSION['user_id']        = $user['id'];
        $_SESSION['empresa_id']     = $user['empresa_id'];
        $_SESSION['empresa_nombre'] = $user['empresa_nombre'];
        $_SESSION['user_nombre']    = $user['nombre'];
        $_SESSION['user_email']     = $user['email'];
        $_SESSION['user_rol']       = $user['rol'];
    }

    private static function clearRememberCookie(): void {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
