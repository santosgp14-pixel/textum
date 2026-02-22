<?php
/**
 * TEXTUM - Helpers de autenticación y sesión
 */
class Auth {

    public static function init(): void {
        ini_set('session.name', SESSION_NAME);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_start();
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

        $_SESSION['user_id']        = $user['id'];
        $_SESSION['empresa_id']     = $user['empresa_id'];
        $_SESSION['empresa_nombre'] = $user['empresa_nombre'];
        $_SESSION['user_nombre']    = $user['nombre'];
        $_SESSION['user_email']     = $user['email'];
        $_SESSION['user_rol']       = $user['rol'];

        // Registrar último login
        $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")
           ->execute([$user['id']]);

        return true;
    }

    public static function logout(): void {
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
}
