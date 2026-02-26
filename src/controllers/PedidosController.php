<?php
/**
 * TEXTUM - PedidosController
 * Flujo completo: crear → agregar items → confirmar / anular
 *
 * REGLA FUNDAMENTAL:
 *   - Pedido "abierto"    → NO toca stock ni balance
 *   - Pedido "confirmado" → descuenta stock, registra ingreso (transacción atómica)
 *   - Pedido "anulado"    → repone stock, revierte ingreso (solo admin, solo confirmados)
 */
class PedidosController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ──────────────────────────────────────────────────────────
    // Listado
    // ──────────────────────────────────────────────────────────

    public function index(): void {
        Auth::require();
        $eid = Auth::empresaId();

        $stmt = $this->db->prepare(
            "SELECT p.*, u.nombre AS vendedor,
                    c.nombre AS cliente_nombre,
                    (SELECT COUNT(*) FROM pedido_items pi WHERE pi.pedido_id = p.id) AS total_items
             FROM pedidos p
             JOIN usuarios u ON u.id = p.usuario_id
             LEFT JOIN clientes c ON c.id = p.cliente_id
             WHERE p.empresa_id = ?
             ORDER BY p.created_at DESC
             LIMIT 200"
        );
        $stmt->execute([$eid]);
        $pedidos = $stmt->fetchAll();
        require VIEW_PATH . '/pedidos/index.php';
    }

    // ──────────────────────────────────────────────────────────
    // Crear pedido nuevo
    // ──────────────────────────────────────────────────────────

    public function nuevo(): void {
        Auth::require();
        $eid = Auth::empresaId();
        $uid = Auth::userId();

        $stmt = $this->db->prepare(
            "INSERT INTO pedidos (empresa_id, usuario_id, estado, total)
             VALUES (?, ?, 'abierto', 0.00)"
        );
        $stmt->execute([$eid, $uid]);
        $pedido_id = (int)$this->db->lastInsertId();

        header('Location: ' . BASE_URL . "/index.php?page=pedido_abierto&id=$pedido_id");
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Pantalla principal de pedido abierto
    // ──────────────────────────────────────────────────────────

    public function pedidoAbierto(): void {
        Auth::require();
        $id  = (int)($_GET['id'] ?? 0);
        $eid = Auth::empresaId();

        $pedido = $this->findPedido($id, $eid);
        if ($pedido['estado'] !== 'abierto') {
            header('Location: ' . BASE_URL . "/index.php?page=pedido_detalle&id=$id");
            exit;
        }

        $items = $this->getItems($id);
        require VIEW_PATH . '/pedidos/abierto.php';
    }

    // ──────────────────────────────────────────────────────────
    // Agregar / actualizar item (AJAX)
    // ──────────────────────────────────────────────────────────

    public function agregarItem(): void {
        Auth::require();
        header('Content-Type: application/json');

        $eid        = Auth::empresaId();
        $pedido_id  = (int)($_POST['pedido_id']  ?? 0);
        $variante_id= (int)($_POST['variante_id']?? 0);
        $cantidad   = (float)($_POST['cantidad'] ?? 0);

        // Validaciones básicas
        $pedido = $this->findPedido($pedido_id, $eid);
        if ($pedido['estado'] !== 'abierto') {
            echo json_encode(['ok' => false, 'msg' => 'El pedido no está abierto.']);
            exit;
        }

        // Buscar variante
        $stmt = $this->db->prepare(
            "SELECT * FROM variantes WHERE id = ? AND empresa_id = ? AND activa = 1"
        );
        $stmt->execute([$variante_id, $eid]);
        $variante = $stmt->fetch();

        if (!$variante) {
            echo json_encode(['ok' => false, 'msg' => 'Variante no encontrada.']);
            exit;
        }

        if ($cantidad < $variante['minimo_venta']) {
            echo json_encode([
                'ok'  => false,
                'msg' => "Mínimo de venta: {$variante['minimo_venta']} {$variante['unidad']}"
            ]);
            exit;
        }

        // Verificar si ya existe el item en el pedido
        $stmt = $this->db->prepare(
            "SELECT id FROM pedido_items WHERE pedido_id = ? AND variante_id = ?"
        );
        $stmt->execute([$pedido_id, $variante_id]);
        $existente = $stmt->fetch();

        $subtotal = round($cantidad * $variante['precio'], 2);

        if ($existente) {
            $this->db->prepare(
                "UPDATE pedido_items SET cantidad = ?, subtotal = ? WHERE id = ?"
            )->execute([$cantidad, $subtotal, $existente['id']]);
        } else {
            $this->db->prepare(
                "INSERT INTO pedido_items (pedido_id, variante_id, cantidad, precio_unit, subtotal)
                 VALUES (?,?,?,?,?)"
            )->execute([$pedido_id, $variante_id, $cantidad, $variante['precio'], $subtotal]);
        }

        // Recalcular total del pedido
        $this->recalcularTotal($pedido_id);

        $items = $this->getItems($pedido_id);
        $total = array_sum(array_column($items, 'subtotal'));

        echo json_encode([
            'ok'    => true,
            'items' => $items,
            'total' => $total,
        ]);
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Eliminar item del pedido (AJAX)
    // ──────────────────────────────────────────────────────────

    public function eliminarItem(): void {
        Auth::require();
        header('Content-Type: application/json');

        $eid       = Auth::empresaId();
        $item_id   = (int)($_POST['item_id']   ?? 0);
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);

        $pedido = $this->findPedido($pedido_id, $eid);
        if ($pedido['estado'] !== 'abierto') {
            echo json_encode(['ok' => false, 'msg' => 'Pedido no editable.']);
            exit;
        }

        $this->db->prepare(
            "DELETE FROM pedido_items WHERE id = ? AND pedido_id = ?"
        )->execute([$item_id, $pedido_id]);

        $this->recalcularTotal($pedido_id);
        $items = $this->getItems($pedido_id);
        $total = array_sum(array_column($items, 'subtotal'));

        echo json_encode(['ok' => true, 'items' => $items, 'total' => $total]);
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Asignar / quitar cliente de pedido abierto (AJAX)
    // ──────────────────────────────────────────────────────────

    public function setCliente(): void {
        Auth::require();
        header('Content-Type: application/json');
        $eid        = Auth::empresaId();
        $pedido_id  = (int)($_POST['pedido_id']  ?? 0);
        $cliente_id = (int)($_POST['cliente_id'] ?? 0);

        $pedido = $this->findPedido($pedido_id, $eid);
        if ($pedido['estado'] !== 'abierto') {
            echo json_encode(['ok' => false, 'msg' => 'Solo se puede modificar un pedido abierto.']);
            exit;
        }

        if ($cliente_id > 0) {
            $stmt = $this->db->prepare(
                "SELECT id, nombre FROM clientes WHERE id=? AND empresa_id=? AND activo=1"
            );
            $stmt->execute([$cliente_id, $eid]);
            $cliente = $stmt->fetch();
            if (!$cliente) {
                echo json_encode(['ok' => false, 'msg' => 'Cliente no encontrado.']);
                exit;
            }
            $this->db->prepare(
                "UPDATE pedidos SET cliente_id=? WHERE id=? AND empresa_id=?"
            )->execute([$cliente_id, $pedido_id, $eid]);
            echo json_encode(['ok' => true, 'cliente' => $cliente]);
        } else {
            $this->db->prepare(
                "UPDATE pedidos SET cliente_id=NULL WHERE id=? AND empresa_id=?"
            )->execute([$pedido_id, $eid]);
            echo json_encode(['ok' => true, 'cliente' => null]);
        }
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // CONFIRMAR PEDIDO (transacción atómica)
    // ──────────────────────────────────────────────────────────

    public function confirmar(): void {
        Auth::require();
        header('Content-Type: application/json');

        $eid       = Auth::empresaId();
        $uid       = Auth::userId();
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);

        $pedido = $this->findPedido($pedido_id, $eid);

        if ($pedido['estado'] !== 'abierto') {
            echo json_encode(['ok' => false, 'msg' => 'Solo se pueden confirmar pedidos abiertos.']);
            exit;
        }

        $items = $this->getItems($pedido_id);
        if (empty($items)) {
            echo json_encode(['ok' => false, 'msg' => 'El pedido no tiene productos.']);
            exit;
        }

        // ── Iniciar transacción ───────────────────────────────
        $this->db->beginTransaction();
        try {
            foreach ($items as $item) {
                // Leer stock actual (con lock de fila)
                $stmt = $this->db->prepare(
                    "SELECT stock FROM variantes WHERE id = ? AND empresa_id = ? FOR UPDATE"
                );
                $stmt->execute([$item['variante_id'], $eid]);
                $v = $stmt->fetch();

                if (!$v) {
                    throw new RuntimeException("Variante #{$item['variante_id']} no encontrada.");
                }
                if ($v['stock'] < $item['cantidad']) {
                    throw new RuntimeException(
                        "Stock insuficiente para \"{$item['descripcion']}\". " .
                        "Disponible: {$v['stock']} {$item['unidad']}"
                    );
                }

                $stock_antes   = $v['stock'];
                $stock_despues = $stock_antes - $item['cantidad'];

                // Descontar stock
                $this->db->prepare(
                    "UPDATE variantes SET stock = ? WHERE id = ?"
                )->execute([$stock_despues, $item['variante_id']]);

                // Registrar movimiento de stock
                $this->db->prepare(
                    "INSERT INTO movimientos_stock
                     (empresa_id, variante_id, pedido_id, usuario_id, tipo, cantidad, stock_antes, stock_despues)
                     VALUES (?,?,?,?,'venta',?,?,?)"
                )->execute([
                    $eid, $item['variante_id'], $pedido_id, $uid,
                    -$item['cantidad'], $stock_antes, $stock_despues
                ]);
            }

            // Registrar ingreso en balance
            $this->db->prepare(
                "INSERT INTO balance_movimientos
                 (empresa_id, pedido_id, usuario_id, tipo, monto, descripcion)
                 VALUES (?,?,?,'ingreso_venta',?,?)"
            )->execute([
                $eid, $pedido_id, $uid,
                $pedido['total'],
                "Pedido #$pedido_id confirmado"
            ]);

            // Cerrar pedido
            $this->db->prepare(
                "UPDATE pedidos SET estado='confirmado', confirmado_at=NOW() WHERE id=?"
            )->execute([$pedido_id]);

            $this->db->commit();

            echo json_encode([
                'ok'         => true,
                'msg'        => 'Pedido confirmado correctamente.',
                'redirect'   => BASE_URL . "/index.php?page=pedido_detalle&id=$pedido_id",
            ]);

        } catch (RuntimeException $e) {
            $this->db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->db->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Error interno al confirmar pedido.']);
        }
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // ANULAR PEDIDO (solo admin, solo confirmados, transacción)
    // ──────────────────────────────────────────────────────────

    public function anular(): void {
        Auth::requireAdmin();
        header('Content-Type: application/json');

        $eid       = Auth::empresaId();
        $uid       = Auth::userId();
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);
        $motivo    = trim($_POST['motivo']     ?? '');

        if (empty($motivo)) {
            echo json_encode(['ok' => false, 'msg' => 'Se requiere un motivo de anulación.']);
            exit;
        }

        $pedido = $this->findPedido($pedido_id, $eid);

        if ($pedido['estado'] !== 'confirmado') {
            echo json_encode(['ok' => false, 'msg' => 'Solo se pueden anular pedidos confirmados.']);
            exit;
        }

        $items = $this->getItems($pedido_id);

        $this->db->beginTransaction();
        try {
            foreach ($items as $item) {
                $stmt = $this->db->prepare(
                    "SELECT stock FROM variantes WHERE id = ? FOR UPDATE"
                );
                $stmt->execute([$item['variante_id']]);
                $v = $stmt->fetch();

                $stock_antes   = $v['stock'];
                $stock_despues = $stock_antes + $item['cantidad'];

                // Reponer stock
                $this->db->prepare(
                    "UPDATE variantes SET stock = ? WHERE id = ?"
                )->execute([$stock_despues, $item['variante_id']]);

                // Registrar movimiento de reposición
                $this->db->prepare(
                    "INSERT INTO movimientos_stock
                     (empresa_id, variante_id, pedido_id, usuario_id, tipo, cantidad, stock_antes, stock_despues)
                     VALUES (?,?,?,?,'anulacion_venta',?,?,?)"
                )->execute([
                    $eid, $item['variante_id'], $pedido_id, $uid,
                    $item['cantidad'], $stock_antes, $stock_despues
                ]);
            }

            // Revertir ingreso en balance
            $this->db->prepare(
                "INSERT INTO balance_movimientos
                 (empresa_id, pedido_id, usuario_id, tipo, monto, descripcion)
                 VALUES (?,?,?,'anulacion_venta',?,?)"
            )->execute([
                $eid, $pedido_id, $uid,
                -$pedido['total'],
                "Anulación pedido #$pedido_id: $motivo"
            ]);

            // Marcar pedido como anulado
            $this->db->prepare(
                "UPDATE pedidos
                 SET estado='anulado', anulado_por=?, motivo_anulacion=?, anulado_at=NOW()
                 WHERE id=?"
            )->execute([$uid, $motivo, $pedido_id]);

            $this->db->commit();

            echo json_encode([
                'ok'       => true,
                'msg'      => 'Pedido anulado y stock repuesto.',
                'redirect' => BASE_URL . "/index.php?page=pedido_detalle&id=$pedido_id",
            ]);

        } catch (Throwable $e) {
            $this->db->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Error al anular pedido.']);
        }
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Detalle de pedido (cualquier estado)
    // ──────────────────────────────────────────────────────────

    public function detalle(): void {
        Auth::require();
        $id     = (int)($_GET['id'] ?? 0);
        $eid    = Auth::empresaId();
        $pedido = $this->findPedido($id, $eid);
        $items  = $this->getItems($id);
        require VIEW_PATH . '/pedidos/detalle.php';
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    private function findPedido(int $id, int $eid): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.nombre AS vendedor_nombre,
                    c.nombre AS cliente_nombre
             FROM pedidos p
             JOIN usuarios u ON u.id = p.usuario_id
             LEFT JOIN clientes c ON c.id = p.cliente_id
             WHERE p.id = ? AND p.empresa_id = ?"
        );
        $stmt->execute([$id, $eid]);
        $pedido = $stmt->fetch();
        if (!$pedido) {
            http_response_code(404);
            die('Pedido no encontrado.');
        }
        return $pedido;
    }

    private function getItems(int $pedido_id): array {
        $stmt = $this->db->prepare(
            "SELECT pi.*, v.descripcion, v.unidad, v.codigo_barras, t.nombre AS tela_nombre
             FROM pedido_items pi
             JOIN variantes v ON v.id = pi.variante_id
             JOIN telas t ON t.id = v.tela_id
             WHERE pi.pedido_id = ?
             ORDER BY t.nombre, v.descripcion"
        );
        $stmt->execute([$pedido_id]);
        return $stmt->fetchAll();
    }

    private function recalcularTotal(int $pedido_id): void {
        $stmt = $this->db->prepare(
            "UPDATE pedidos p
             SET total = (SELECT COALESCE(SUM(subtotal),0) FROM pedido_items WHERE pedido_id = p.id)
             WHERE p.id = ?"
        );
        $stmt->execute([$pedido_id]);
    }
}
