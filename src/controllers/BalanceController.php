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
        $rawFecha = $_GET['fecha'] ?? date('Y-m-d');
        $fecha = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFecha) && strtotime($rawFecha)) ? $rawFecha : date('Y-m-d');

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

        // Gastos recurrentes sin aplicar en el mes / día actual (si la columna existe)
        $gastos_recurrentes_pendientes = [];
        try {
            $mesActual = date('Y-m');
            $diaHoy    = (int)date('j');
            $stmt = $this->db->prepare(
                "SELECT DISTINCT g.id, g.descripcion, g.monto, g.frecuencia, g.dia_cobro
                 FROM gastos g
                 WHERE g.empresa_id = ?
                   AND g.es_recurrente = 1
                   AND g.frecuencia = 'mensual'
                   AND g.dia_cobro = ?
                   AND NOT EXISTS (
                       SELECT 1 FROM gastos g2
                       WHERE g2.empresa_id = g.empresa_id
                         AND g2.es_recurrente = 0
                         AND g2.descripcion = CONCAT('[R] ', g.descripcion)
                         AND DATE_FORMAT(g2.fecha, '%Y-%m') = ?
                   )
                 LIMIT 10"
            );
            $stmt->execute([$eid, $diaHoy, $mesActual]);
            $gastos_recurrentes_pendientes = $stmt->fetchAll();
        } catch (PDOException $e) {
            // Columna no existe: migración pendiente, ignorar
        }

        require VIEW_PATH . '/balance/index.php';
    }

    public function guardarGasto(): void {
        Auth::require();
        $eid = Auth::empresaId();
        $uid = Auth::userId();

        $descripcion   = trim($_POST['descripcion'] ?? '');
        $monto         = (float)($_POST['monto']    ?? 0);
        $fecha         = $_POST['fecha'] ?? date('Y-m-d');
        $esRecurrente  = !empty($_POST['es_recurrente']) ? 1 : 0;
        $frecuencia    = in_array($_POST['frecuencia'] ?? '', ['diario','semanal','mensual'])
                         ? $_POST['frecuencia'] : null;
        $diaCobro      = ($esRecurrente && $frecuencia === 'mensual')
                         ? max(1, min(31, (int)($_POST['dia_cobro'] ?? date('j'))))
                         : null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $fecha = date('Y-m-d');

        if (empty($descripcion) || $monto <= 0) {
            $_SESSION['flash_error'] = 'Descripción y monto válido son obligatorios.';
            header('Location: ' . BASE_URL . '/index.php?page=balance&fecha=' . $fecha);
            exit;
        }

        try {
            $this->db->prepare(
                "INSERT INTO gastos (empresa_id, usuario_id, descripcion, monto, fecha, es_recurrente, frecuencia, dia_cobro)
                 VALUES (?,?,?,?,?,?,?,?)"
            )->execute([$eid, $uid, $descripcion, $monto, $fecha, $esRecurrente, $frecuencia, $diaCobro]);
        } catch (PDOException $e) {
            // Columnas v1.6 no existen aún: insertar sin ellas
            $this->db->prepare(
                "INSERT INTO gastos (empresa_id, usuario_id, descripcion, monto, fecha)
                 VALUES (?,?,?,?,?)"
            )->execute([$eid, $uid, $descripcion, $monto, $fecha]);
        }

        // Registrar también en balance_movimientos
        $this->db->prepare(
            "INSERT INTO balance_movimientos (empresa_id, usuario_id, tipo, monto, descripcion)
             VALUES (?,?,'gasto',?,?)"
        )->execute([$eid, $uid, -$monto, $descripcion]);

        $_SESSION['flash_ok'] = 'Gasto registrado.';
        header('Location: ' . BASE_URL . '/index.php?page=balance&fecha=' . $fecha);
        exit;
    }

    public function aplicarRecurrentes(): void {
        Auth::require();
        $eid  = Auth::empresaId();
        $uid  = Auth::userId();
        $ids  = array_map('intval', (array)($_POST['ids'] ?? []));

        if (empty($ids)) {
            header('Location: ' . BASE_URL . '/index.php?page=balance');
            exit;
        }

        $aplicados = 0;
        foreach ($ids as $id) {
            $stmt = $this->db->prepare(
                "SELECT * FROM gastos WHERE id=? AND empresa_id=? AND es_recurrente=1"
            );
            $stmt->execute([$id, $eid]);
            $g = $stmt->fetch();
            if (!$g) continue;

            $fecha = date('Y-m-d');
            $desc  = '[R] ' . $g['descripcion'];

            $this->db->prepare(
                "INSERT INTO gastos (empresa_id, usuario_id, descripcion, monto, fecha, es_recurrente)
                 VALUES (?,?,?,?,?,0)"
            )->execute([$eid, $uid, $desc, $g['monto'], $fecha]);

            $this->db->prepare(
                "INSERT INTO balance_movimientos (empresa_id, usuario_id, tipo, monto, descripcion)
                 VALUES (?,?,'gasto',?,?)"
            )->execute([$eid, $uid, -(float)$g['monto'], $desc]);

            $aplicados++;
        }

        $_SESSION['flash_ok'] = "$aplicados gasto(s) recurrente(s) aplicado(s).";
        header('Location: ' . BASE_URL . '/index.php?page=balance');
        exit;
    }
}
