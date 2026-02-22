<?php
/**
 * TEXTUM - DashboardController
 */
class DashboardController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(): void {
        Auth::require();
        $eid = Auth::empresaId();

        // Pedidos abiertos
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pedidos WHERE empresa_id=? AND estado='abierto'"
        );
        $stmt->execute([$eid]);
        $pedidos_abiertos = (int)$stmt->fetchColumn();

        // Ventas hoy
        $stmt = $this->db->prepare(
            "SELECT COUNT(*), COALESCE(SUM(total),0)
             FROM pedidos WHERE empresa_id=? AND estado='confirmado' AND DATE(confirmado_at)=CURDATE()"
        );
        $stmt->execute([$eid]);
        [$ventas_hoy_qty, $ventas_hoy_monto] = $stmt->fetch(PDO::FETCH_NUM);

        // Variantes con stock bajo (menos de 5 metros/kilos)
        $stmt = $this->db->prepare(
            "SELECT v.descripcion, v.stock, v.unidad, t.nombre AS tela
             FROM variantes v JOIN telas t ON t.id=v.tela_id
             WHERE v.empresa_id=? AND v.activa=1 AND v.stock < 5
             ORDER BY v.stock ASC LIMIT 10"
        );
        $stmt->execute([$eid]);
        $stock_bajo = $stmt->fetchAll();

        // Últimos pedidos
        $stmt = $this->db->prepare(
            "SELECT p.*, u.nombre AS vendedor
             FROM pedidos p JOIN usuarios u ON u.id=p.usuario_id
             WHERE p.empresa_id=?
             ORDER BY p.created_at DESC LIMIT 8"
        );
        $stmt->execute([$eid]);
        $ultimos_pedidos = $stmt->fetchAll();

        require VIEW_PATH . '/dashboard/index.php';
    }
}
