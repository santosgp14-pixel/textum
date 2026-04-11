<?php
/**
 * TEXTUM — ConfigController
 * Configuración de la empresa: catálogo público, WhatsApp, logo
 */
class ConfigController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(): void {
        Auth::require();
        Auth::requireAdmin();
        $eid = Auth::empresaId();

        $stmt = $this->db->prepare(
            "SELECT id, nombre, descripcion_catalogo, whatsapp, logo_url
             FROM empresas WHERE id = ?"
        );
        $stmt->execute([$eid]);
        $empresa = $stmt->fetch();

        // Token HMAC determinístico para el catálogo público (sin estado en DB)
        $catalogToken = hash_hmac('sha256', 'catalog:' . $eid, APP_SECRET);

        require VIEW_PATH . '/config/index.php';
    }

    public function guardar(): void {
        Auth::require();
        Auth::requireAdmin();
        $eid = Auth::empresaId();

        $descripcion = trim($_POST['descripcion_catalogo'] ?? '');
        $whatsapp    = preg_replace('/[^0-9+]/', '', $_POST['whatsapp'] ?? '');
        $logo_url    = filter_var(trim($_POST['logo_url'] ?? ''), FILTER_SANITIZE_URL);

        // Validar logo_url: debe ser https o vacío
        if ($logo_url !== '' && !preg_match('/^https?:\/\//i', $logo_url)) {
            $logo_url = '';
        }

        $this->db->prepare(
            "UPDATE empresas
             SET descripcion_catalogo = ?, whatsapp = ?, logo_url = ?
             WHERE id = ?"
        )->execute([$descripcion ?: null, $whatsapp ?: null, $logo_url ?: null, $eid]);

        $_SESSION['flash_ok'] = 'Configuración guardada.';
        header('Location: ' . BASE_URL . '/index.php?page=config');
        exit;
    }
}
