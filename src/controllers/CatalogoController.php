<?php
/**
 * TEXTUM — CatalogoController
 * Catálogo público accesible sin autenticación (token HMAC en URL)
 */
class CatalogoController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(): void {
        $eid   = (int)($_GET['empresa_id'] ?? 0);
        $token = $_GET['t'] ?? '';

        if (!$eid || !$this->validarToken($eid, $token)) {
            http_response_code(403);
            echo '<h2 style="font-family:sans-serif;text-align:center;margin-top:60px">Enlace de catálogo inválido.</h2>';
            exit;
        }

        // Datos de la empresa
        $stmt = $this->db->prepare(
            "SELECT nombre, descripcion_catalogo, whatsapp, logo_url FROM empresas WHERE id = ? AND activa = 1"
        );
        $stmt->execute([$eid]);
        $empresa = $stmt->fetch();

        if (!$empresa) {
            http_response_code(404);
            echo '<h2 style="font-family:sans-serif;text-align:center;margin-top:60px">Catálogo no encontrado.</h2>';
            exit;
        }

        // Categorías activas con sus telas + variantes
        $stmt = $this->db->prepare(
            "SELECT DISTINCT cat.id, cat.nombre, cat.orden
             FROM categorias cat
             INNER JOIN telas t ON t.categoria_id = cat.id AND t.empresa_id = ? AND t.activa = 1
             INNER JOIN variantes v ON v.tela_id = t.id AND v.activa = 1
             WHERE cat.empresa_id = ?
             ORDER BY cat.orden, cat.nombre"
        );
        $stmt->execute([$eid, $eid]);
        $categorias = $stmt->fetchAll();

        // Telas activas con variantes disponibles
        $stmt = $this->db->prepare(
            "SELECT t.id, t.nombre, t.descripcion, t.imagen_url, t.precio, t.unidad, t.categoria_id,
                    t.tipo, t.subcategoria,
                    GROUP_CONCAT(DISTINCT v.descripcion ORDER BY v.descripcion SEPARATOR ' · ') AS variantes_desc,
                    MIN(v.precio) AS precio_desde,
                    SUM(CASE WHEN v.unidad = 'rollo' THEN
                            (SELECT COUNT(*) FROM rollos r WHERE r.variante_id = v.id AND r.estado = 'disponible')
                        ELSE v.stock
                    END) AS stock_total
             FROM telas t
             INNER JOIN variantes v ON v.tela_id = t.id AND v.activa = 1
             WHERE t.empresa_id = ? AND t.activa = 1
             GROUP BY t.id
             ORDER BY t.nombre"
        );
        $stmt->execute([$eid]);
        $telas = $stmt->fetchAll();

        // Agrupar telas por categoría
        $telasPorCategoria = [];
        foreach ($telas as $t) {
            $telasPorCategoria[$t['categoria_id']][] = $t;
        }
        // Telas sin categoría
        $sinCategoria = $telasPorCategoria[null] ?? $telasPorCategoria[0] ?? [];

        require VIEW_PATH . '/catalogo/index.php';
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    private function validarToken(int $empresaId, string $token): bool {
        if (strlen($token) < 10) return false;
        $esperado = hash_hmac('sha256', 'catalog:' . $empresaId, APP_SECRET);
        return hash_equals($esperado, $token);
    }
}
