<?php
/**
 * TEXTUM - BalanceController
 * Ingresos, gastos y resultado neto
 */
class BalanceController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(): void {
        Auth::require();
        $eid  = Auth::empresaId();
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        // Ingresos del día (ventas confirmadas)
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0) AS total
             FROM balance_movimientos
             WHERE empresa_id = ? AND tipo = 'ingreso_venta' AND DATE(created_at) = ?"
        );
        $stmt->execute([$eid, $fecha]);
        $ingresos = (float)$stmt->fetchColumn();

        // Anulaciones del día
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(ABS(monto)), 0) AS total
             FROM balance_movimientos
             WHERE empresa_id = ? AND tipo = 'anulacion_venta' AND DATE(created_at) = ?"
        );
        $stmt->execute([$eid, $fecha]);
        $anulaciones = (float)$stmt->fetchColumn();

        // Gastos del día
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0) AS total
             FROM gastos WHERE empresa_id = ? AND fecha = ?"
        );
        $stmt->execute([$eid, $fecha]);
        $gastos = (float)$stmt->fetchColumn();

        $neto = $ingresos - $anulaciones - $gastos;

        // Pedidos confirmados del día (detalle)
        $stmt = $this->db->prepare(
            "SELECT p.id, p.total, p.confirmado_at, u.nombre AS vendedor
             FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.empresa_id = ? AND p.estado = 'confirmado' AND DATE(p.confirmado_at) = ?
             ORDER BY p.confirmado_at DESC"
        );
        $stmt->execute([$eid, $fecha]);
        $pedidos_dia = $stmt->fetchAll();

        // Gastos del día (detalle)
        $stmt = $this->db->prepare(
            "SELECT g.*, u.nombre AS usuario_nombre
             FROM gastos g JOIN usuarios u ON u.id = g.usuario_id
             WHERE g.empresa_id = ? AND g.fecha = ?
             ORDER BY g.created_at DESC"
        );
        $stmt->execute([$eid, $fecha]);
        $gastos_lista = $stmt->fetchAll();

        require VIEW_PATH . '/balance/index.php';
    }

    public function guardarGasto(): void {
        Auth::require();
        $eid = Auth::empresaId();
        $uid = Auth::userId();

        $descripcion = trim($_POST['descripcion'] ?? '');
        $monto       = (float)($_POST['monto']    ?? 0);
        $fecha       = $_POST['fecha'] ?? date('Y-m-d');

        if (empty($descripcion) || $monto <= 0) {
            $_SESSION['flash_error'] = 'Descripción y monto válido son obligatorios.';
            header('Location: ' . BASE_URL . '/index.php?page=balance&fecha=' . $fecha);
            exit;
        }

        $this->db->prepare(
            "INSERT INTO gastos (empresa_id, usuario_id, descripcion, monto, fecha)
             VALUES (?,?,?,?,?)"
        )->execute([$eid, $uid, $descripcion, $monto, $fecha]);

        // Registrar también en balance_movimientos
        $this->db->prepare(
            "INSERT INTO balance_movimientos (empresa_id, usuario_id, tipo, monto, descripcion)
             VALUES (?,?,'gasto',?,?)"
        )->execute([$eid, $uid, -$monto, $descripcion]);

        $_SESSION['flash_ok'] = 'Gasto registrado.';
        header('Location: ' . BASE_URL . '/index.php?page=balance&fecha=' . $fecha);
        exit;
    }
}
