<?php
/**
 * TEXTUM - Productos: indicadores por tela
 */
class ProductosController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(): void {
        Auth::require();
        $eid = Auth::empresaId();

        // Detectar si la migración v1.4 ya fue aplicada (columnas nuevas en telas)
        $migratedV14 = false;
        try {
            $this->db->query("SELECT tipo FROM telas LIMIT 0");
            $migratedV14 = true;
        } catch (PDOException $e) { /* columna no existe aún */ }

        $hasPrecioRollo = false;
        try {
            $this->db->query("SELECT precio_rollo FROM variantes LIMIT 0");
            $hasPrecioRollo = true;
        } catch (PDOException $e) {}

        $hasCostoRollo = false;
        try {
            $this->db->query("SELECT costo FROM rollos LIMIT 0");
            $hasCostoRollo = true;
        } catch (PDOException $e) {}

        $extraCols    = $migratedV14
            ? 't.tipo, t.rinde, t.unidad, t.imagen_url,'
            : "NULL AS tipo, NULL AS rinde, NULL AS unidad, NULL AS imagen_url,";
        $colPrecioRollo = $hasPrecioRollo
            ? "COALESCE(AVG(CASE WHEN v.precio_rollo > 0 THEN v.precio_rollo END), 0)"
            : "0";
        $colCosto = $hasCostoRollo
            ? "COALESCE(AVG(CASE WHEN r.costo > 0 THEN r.costo END), 0)"
            : "0";
        $joinRollos = $hasCostoRollo
            ? "LEFT JOIN rollos r ON r.variante_id = v.id"
            : "";

        // ── Por tela: stock + precios + costo promedio ────────
        $stmt = $this->db->prepare(
            "SELECT
                t.id, t.nombre, $extraCols
                COUNT(DISTINCT v.id) AS total_variantes,
                COALESCE(SUM(CASE WHEN v.unidad='kilo'  THEN v.stock ELSE 0 END), 0) AS stock_kilos,
                COALESCE(SUM(CASE WHEN v.unidad='metro' THEN v.stock ELSE 0 END), 0) AS stock_metros,
                $colPrecioRollo AS avg_precio_rollo,
                COALESCE(AVG(CASE WHEN v.precio > 0 THEN v.precio END), 0) AS avg_precio_metro,
                $colCosto AS avg_costo
             FROM telas t
             LEFT JOIN variantes v ON v.tela_id = t.id AND v.activa = 1
             $joinRollos
             WHERE t.empresa_id = ? AND t.activa = 1
             GROUP BY t.id
             ORDER BY t.nombre"
        );
        $stmt->execute([$eid]);
        $productos = $stmt->fetchAll();

        // ── Totales globales (derivados del resultado) ────────
        $totales = [
            'productos'        => count($productos),
            'stock_kilos'      => array_sum(array_column($productos, 'stock_kilos')),
            'stock_metros'     => array_sum(array_column($productos, 'stock_metros')),
            'avg_precio_rollo' => 0,
            'avg_precio_metro' => 0,
            'avg_rinde'        => 0,
            'avg_costo'        => 0,
        ];

        $cnt_rollo  = 0; $cnt_metro = 0; $cnt_rinde = 0; $cnt_costo = 0;
        foreach ($productos as $p) {
            if ($p['avg_precio_rollo'] > 0) { $totales['avg_precio_rollo'] += $p['avg_precio_rollo']; $cnt_rollo++; }
            if ($p['avg_precio_metro'] > 0) { $totales['avg_precio_metro'] += $p['avg_precio_metro']; $cnt_metro++; }
            if ($p['rinde']            > 0) { $totales['avg_rinde']        += $p['rinde'];             $cnt_rinde++; }
            if ($p['avg_costo']        > 0) { $totales['avg_costo']        += $p['avg_costo'];         $cnt_costo++; }
        }
        if ($cnt_rollo) $totales['avg_precio_rollo'] /= $cnt_rollo;
        if ($cnt_metro) $totales['avg_precio_metro'] /= $cnt_metro;
        if ($cnt_rinde) $totales['avg_rinde']        /= $cnt_rinde;
        if ($cnt_costo) $totales['avg_costo']        /= $cnt_costo;

        $pageTitle   = 'Productos';
        $currentPage = 'productos';
        require VIEW_PATH . '/productos/index.php';
    }
}
