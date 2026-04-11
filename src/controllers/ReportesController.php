<?php
/**
 * TEXTUM — ReportesController
 * Reportes de ventas y análisis del negocio
 */
class ReportesController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(): void {
        Auth::require();
        $eid = Auth::empresaId();

        // ── Ventas por día — últimos 30 días ──────────────────
        $stmt = $this->db->prepare(
            "SELECT DATE(p.confirmado_at) AS dia,
                    SUM(p.total)           AS total,
                    COUNT(p.id)            AS cantidad
             FROM pedidos p
             WHERE p.empresa_id = ?
               AND p.estado = 'confirmado'
               AND p.confirmado_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
             GROUP BY dia
             ORDER BY dia ASC"
        );
        $stmt->execute([$eid]);
        $ventasDiarias = $stmt->fetchAll();

        // Rellenar días sin ventas con 0
        $ventasDiariasMap = [];
        foreach ($ventasDiarias as $row) {
            $ventasDiariasMap[$row['dia']] = $row;
        }
        $diasLabels  = [];
        $diasTotales = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $diasLabels[]  = date('d/m', strtotime($d));
            $diasTotales[] = isset($ventasDiariasMap[$d]) ? (float)$ventasDiariasMap[$d]['total'] : 0;
        }

        // ── Top 10 telas por ingresos (mes actual) ────────────
        $stmt = $this->db->prepare(
            "SELECT t.nombre AS tela, SUM(pi.subtotal) AS total
             FROM pedido_items pi
             JOIN variantes v  ON v.id = pi.variante_id
             JOIN telas t      ON t.id = v.tela_id
             JOIN pedidos p    ON p.id = pi.pedido_id
             WHERE p.empresa_id = ?
               AND p.estado = 'confirmado'
               AND YEAR(p.confirmado_at) = YEAR(CURDATE())
               AND MONTH(p.confirmado_at) = MONTH(CURDATE())
             GROUP BY t.id
             ORDER BY total DESC
             LIMIT 10"
        );
        $stmt->execute([$eid]);
        $topTelas = $stmt->fetchAll();

        // ── Ingresos vs Gastos — últimos 6 meses ─────────────
        $stmt = $this->db->prepare(
            "SELECT YEAR(confirmado_at) AS anio, MONTH(confirmado_at) AS mes,
                    SUM(total) AS ingresos
             FROM pedidos
             WHERE empresa_id = ?
               AND estado = 'confirmado'
               AND confirmado_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
             GROUP BY anio, mes
             ORDER BY anio, mes"
        );
        $stmt->execute([$eid]);
        $ingresosMeses = $stmt->fetchAll();

        $stmt = $this->db->prepare(
            "SELECT YEAR(fecha) AS anio, MONTH(fecha) AS mes,
                    SUM(monto) AS gastos
             FROM gastos
             WHERE empresa_id = ?
               AND fecha >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
             GROUP BY anio, mes
             ORDER BY anio, mes"
        );
        $stmt->execute([$eid]);
        $gastosMeses = $stmt->fetchAll();

        // Construir mapa para los últimos 6 meses
        $mesesLabels     = [];
        $mesesIngresos   = [];
        $mesesGastos     = [];
        $ingresosMap = [];
        foreach ($ingresosMeses as $r) $ingresosMap["{$r['anio']}-{$r['mes']}"] = (float)$r['ingresos'];
        $gastosMap = [];
        foreach ($gastosMeses   as $r) $gastosMap["{$r['anio']}-{$r['mes']}"] = (float)$r['gastos'];
        for ($i = 5; $i >= 0; $i--) {
            $t = strtotime("-$i months");
            $k = date('Y', $t) . '-' . (int)date('n', $t);
            $mesesLabels[]   = date('M y', $t);
            $mesesIngresos[] = $ingresosMap[$k] ?? 0;
            $mesesGastos[]   = $gastosMap[$k]   ?? 0;
        }

        // ── KPIs del mes actual ───────────────────────────────
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total_pedidos,
                    COALESCE(SUM(total), 0) AS total_ventas,
                    COALESCE(AVG(total), 0) AS ticket_promedio
             FROM pedidos
             WHERE empresa_id = ?
               AND estado = 'confirmado'
               AND YEAR(confirmado_at) = YEAR(CURDATE())
               AND MONTH(confirmado_at) = MONTH(CURDATE())"
        );
        $stmt->execute([$eid]);
        $kpis = $stmt->fetch();

        // Cliente con más compras este mes
        $stmt = $this->db->prepare(
            "SELECT c.nombre, SUM(p.total) AS total
             FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
             WHERE p.empresa_id = ?
               AND p.estado = 'confirmado'
               AND YEAR(p.confirmado_at) = YEAR(CURDATE())
               AND MONTH(p.confirmado_at) = MONTH(CURDATE())
             GROUP BY c.id
             ORDER BY total DESC
             LIMIT 1"
        );
        $stmt->execute([$eid]);
        $topCliente = $stmt->fetch();

        require VIEW_PATH . '/reportes/index.php';
    }
}
