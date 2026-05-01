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

        // Detectar si las migraciones ya fueron aplicadas — se cachea en sesión
        // para no hacer 3 queries extra en cada carga de página
        if (!isset($_SESSION['_schema_caps'])) {
            $caps = ['v14' => false, 'precioRollo' => false, 'costoRollo' => false];
            try { $this->db->query("SELECT tipo FROM telas LIMIT 0");           $caps['v14']         = true; } catch (PDOException $e) {}
            try { $this->db->query("SELECT precio_rollo FROM variantes LIMIT 0"); $caps['precioRollo'] = true; } catch (PDOException $e) {}
            try { $this->db->query("SELECT costo FROM rollos LIMIT 0");           $caps['costoRollo']  = true; } catch (PDOException $e) {}
            $_SESSION['_schema_caps'] = $caps;
        }
        $migratedV14    = $_SESSION['_schema_caps']['v14'];
        $hasPrecioRollo = $_SESSION['_schema_caps']['precioRollo'];
        $hasCostoRollo  = $_SESSION['_schema_caps']['costoRollo'];

        $extraCols    = $migratedV14
            ? 't.tipo, t.rinde, t.unidad, t.imagen_url,'
            : "NULL AS tipo, NULL AS rinde, NULL AS unidad, NULL AS imagen_url,";
        // Metros = kilos x rinde (punto) + metros directos (plano)
        $colStockMetros = $migratedV14
            ? "COALESCE(SUM(CASE WHEN v.unidad='kilo' THEN v.stock * COALESCE(t.rinde, 0) WHEN v.unidad='metro' THEN v.stock ELSE 0 END), 0)"
            : "COALESCE(SUM(CASE WHEN v.unidad='metro' THEN v.stock ELSE 0 END), 0)";

        // ── Costo: si rollos.costo existe, promediamos desde rollos; si no, desde variantes.costo ──
        if ($hasCostoRollo) {
            $colCostoJoin = "LEFT JOIN (
                    SELECT variante_id,
                           AVG(CASE WHEN costo > 0 THEN costo END) AS avg_r_costo
                    FROM rollos GROUP BY variante_id
                ) r_avg ON r_avg.variante_id = v.id";
            $colAvgCosto = "COALESCE(AVG(
                    CASE WHEN COALESCE(r_avg.avg_r_costo, 0) > 0 THEN r_avg.avg_r_costo
                         WHEN v.costo > 0 THEN v.costo END
                ), 0)";
        } else {
            $colCostoJoin = "";
            $colAvgCosto  = "COALESCE(AVG(CASE WHEN v.costo > 0 THEN v.costo END), 0)";
        }

        // ── Por tela: stock + precios + costo promedio ────────
        $stmt = $this->db->prepare(
            "SELECT
                t.id, t.nombre, $extraCols
                COUNT(DISTINCT v.id) AS total_variantes,
                COALESCE(SUM(CASE WHEN v.unidad='kilo'  THEN v.stock ELSE 0 END), 0) AS stock_kilos,
                $colStockMetros AS stock_metros,
                COALESCE(AVG(CASE WHEN v.precio > 0 THEN v.precio END), 0) AS avg_precio_rollo,
                $colAvgCosto AS avg_costo
             FROM telas t
             LEFT JOIN variantes v ON v.tela_id = t.id AND v.activa = 1
             $colCostoJoin
             WHERE t.empresa_id = ? AND t.activa = 1
             GROUP BY t.id
             ORDER BY t.nombre"
        );
        $stmt->execute([$eid]);
        $productos = $stmt->fetchAll();

        // Precio por metro = precio de venta / rinde x 1.50 (+50%)
        foreach ($productos as &$p) {
            $rinde  = (float)($p['rinde'] ?? 0);
            $precio = (float)($p['avg_precio_rollo'] ?? 0);
            $p['avg_precio_metro'] = ($rinde > 0 && $precio > 0)
                ? round($precio / $rinde * 1.50, 2)
                : 0;
        }
        unset($p);

        // ── Totales globales (derivados del resultado) ────────
        $totales = [
            'productos'        => count($productos),
            'stock_kilos'      => array_sum(array_column($productos, 'stock_kilos')),
            'stock_metros'     => array_sum(array_column($productos, 'stock_metros')),
            'avg_precio_rollo' => 0,
            'avg_precio_metro' => 0,
            'avg_rinde'        => 0,
            'avg_costo'        => 0,
            'costo_por_kilo'   => 0,
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

        // Costo promedio por kilo = promedio ponderado de (rollos.costo ?? variante.costo) × stock
        if ($totales['stock_kilos'] > 0) {
            if ($hasCostoRollo) {
                $stmtCosto = $this->db->prepare(
                    "SELECT COALESCE(
                         SUM(COALESCE(NULLIF(r_avg.avg_r_costo, 0), NULLIF(v.costo, 0)) * v.stock) /
                         NULLIF(SUM(CASE WHEN COALESCE(NULLIF(r_avg.avg_r_costo,0), v.costo) > 0 THEN v.stock ELSE 0 END), 0),
                     0)
                     FROM variantes v
                     LEFT JOIN (
                         SELECT variante_id, AVG(CASE WHEN costo > 0 THEN costo END) AS avg_r_costo
                         FROM rollos GROUP BY variante_id
                     ) r_avg ON r_avg.variante_id = v.id
                     WHERE v.empresa_id = ? AND v.activa = 1 AND v.unidad = 'kilo'"
                );
            } else {
                $stmtCosto = $this->db->prepare(
                    "SELECT COALESCE(SUM(v.costo * v.stock) / NULLIF(SUM(v.stock), 0), 0)
                     FROM variantes v
                     WHERE v.empresa_id = ? AND v.activa = 1 AND v.unidad = 'kilo' AND v.costo > 0"
                );
            }
            $stmtCosto->execute([$eid]);
            $costoPorKilo = (float)$stmtCosto->fetchColumn();
            if ($costoPorKilo > 0) {
                $totales['costo_por_kilo'] = $costoPorKilo;
            }
        }

        $pageTitle   = 'Productos';
        $currentPage = 'productos';
        require VIEW_PATH . '/productos/index.php';
    }
}
