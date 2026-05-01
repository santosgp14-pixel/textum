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

        // Si ya existe un pedido abierto de este usuario, redirigir a él
        $stmt = $this->db->prepare(
            "SELECT id FROM pedidos WHERE empresa_id = ? AND usuario_id = ? AND estado = 'abierto' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$eid, $uid]);
        $existente = $stmt->fetchColumn();
        if ($existente) {
            header('Location: ' . BASE_URL . "/index.php?page=pedido_abierto&id=$existente");
            exit;
        }

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
        $precioCustom = (float)($_POST['precio_unit'] ?? 0);
        $rollo_id   = (int)($_POST['rollo_id'] ?? 0) ?: null;

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
            "SELECT id, precio_unit FROM pedido_items WHERE pedido_id = ? AND variante_id = ?"
        );
        $stmt->execute([$pedido_id, $variante_id]);
        $existente = $stmt->fetch();

        if ($existente) {
            // Actualizar; si viene precio_unit, actualizarlo también
            $precio   = $precioCustom > 0 ? $precioCustom : (float)$existente['precio_unit'];
            $subtotal = round($cantidad * $precio, 2);
            $this->db->prepare(
                "UPDATE pedido_items SET cantidad = ?, precio_unit = ?, subtotal = ?, rollo_id = ? WHERE id = ?"
            )->execute([$cantidad, $precio, $subtotal, $rollo_id, $existente['id']]);
        } else {
            $precio   = $precioCustom > 0 ? $precioCustom : (float)$variante['precio'];
            $subtotal = round($cantidad * $precio, 2);
            $this->db->prepare(
                "INSERT INTO pedido_items (pedido_id, variante_id, rollo_id, cantidad, precio_unit, subtotal)
                 VALUES (?,?,?,?,?,?)"
            )->execute([$pedido_id, $variante_id, $rollo_id, $cantidad, $precio, $subtotal]);
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

                // Marcar rollo como agotado si este ítem vino de un rollo específico
                if (!empty($item['rollo_id'])) {
                    $this->db->prepare(
                        "UPDATE rollos SET estado = 'agotado' WHERE id = ? AND empresa_id = ?"
                    )->execute([$item['rollo_id'], $eid]);
                }

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
    // ──────────────────────────────────────────────────────────
    // ANULAR PEDIDO (solo admin, solo confirmados, transacción)
    // ──────────────────────────────────────────────────────────

    public function anularTodosAbiertos(): void {
        Auth::requireAdmin();
        header('Content-Type: application/json');
        $eid = Auth::empresaId();
        $uid = Auth::userId();
        $stmt = $this->db->prepare("SELECT id FROM pedidos WHERE empresa_id=? AND estado='abierto'");
        $stmt->execute([$eid]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            $this->db->prepare(
                "UPDATE pedidos SET estado='anulado', anulado_por=?, motivo_anulacion='Anulación masiva de pedidos abiertos', anulado_at=NOW() WHERE id=?"
            )->execute([$uid, $id]);
        }
        echo json_encode(['ok' => true, 'count' => count($ids), 'redirect' => BASE_URL . '/index.php?page=pedidos']);
        exit;
    }

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

        // Pedido abierto: anular sin tocar stock ni balance (no se confirmó)
        if ($pedido['estado'] === 'abierto') {
            $this->db->prepare(
                "UPDATE pedidos SET estado='anulado', anulado_por=?, motivo_anulacion=?, anulado_at=NOW() WHERE id=?"
            )->execute([$uid, $motivo, $pedido_id]);
            echo json_encode([
                'ok'       => true,
                'msg'      => 'Pedido anulado.',
                'redirect' => BASE_URL . "/index.php?page=pedidos",
            ]);
            exit;
        }

        if ($pedido['estado'] !== 'confirmado') {
            echo json_encode(['ok' => false, 'msg' => 'Solo se pueden anular pedidos abiertos o confirmados.']);
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

    // ──────────────────────────────────────────────────────────    // Catálogo de variantes activas con stock (AJAX GET)
    // ──────────────────────────────────────────────────────

    public function catalogoVariantes(): void {
        Auth::require();
        header('Content-Type: application/json');
        $eid = Auth::empresaId();
        $stmt = $this->db->prepare(
            "SELECT v.id, v.descripcion, v.unidad, v.precio, v.precio_fraccionado, v.precio_rollo,
                    v.minimo_venta, v.stock,
                    t.id AS tela_id, t.nombre AS tela_nombre, t.imagen_url, t.tipo
             FROM variantes v
             JOIN telas t ON t.id = v.tela_id
             WHERE v.empresa_id = ? AND v.activa = 1 AND v.stock > 0
             ORDER BY t.nombre, v.descripcion"
        );
        $stmt->execute([$eid]);
        echo json_encode(['ok' => true, 'variantes' => $stmt->fetchAll()]);
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Buscar variantes por nombre / descripción (AJAX)
    // ──────────────────────────────────────────────────────────

    public function buscarVariantes(): void {
        Auth::require();
        header('Content-Type: application/json');
        $eid = Auth::empresaId();
        $q   = trim($_GET['q'] ?? '');

        if (strlen($q) < 2) {
            echo json_encode(['ok' => true, 'variantes' => []]);
            exit;
        }

        $like = '%' . $q . '%';
        $stmt = $this->db->prepare(
            "SELECT v.id, v.descripcion, v.unidad, v.precio, v.precio_fraccionado, v.precio_rollo,
                    v.minimo_venta, v.stock, v.codigo_barras,
                    t.id AS tela_id, t.nombre AS tela_nombre, t.imagen_url, t.tipo
             FROM variantes v
             JOIN telas t ON t.id = v.tela_id
             WHERE v.empresa_id = ? AND v.activa = 1 AND v.stock > 0
               AND (t.nombre LIKE ? OR v.descripcion LIKE ?)
             ORDER BY t.nombre, v.descripcion
             LIMIT 15"
        );
        $stmt->execute([$eid, $like, $like]);
        echo json_encode(['ok' => true, 'variantes' => $stmt->fetchAll()]);
        exit;
    }

    // ──────────────────────────────────────────────────────    // Detalle de pedido (cualquier estado)
    // ──────────────────────────────────────────────────────────

    public function detalle(): void {
        Auth::require();
        $id     = (int)($_GET['id'] ?? 0);
        $eid    = Auth::empresaId();
        $pedido = $this->findPedido($id, $eid);
        $items  = $this->getItems($id);
        // Token para recibo público compartible (sin DB)
        $receiptToken = hash_hmac('sha256', 'receipt:' . $id, APP_SECRET);
        require VIEW_PATH . '/pedidos/detalle.php';
    }

    // ──────────────────────────────────────────────────────────
    // Recibo público (sin autenticación)
    // ──────────────────────────────────────────────────────────

    public function reciboPub(): void {
        $id    = (int)($_GET['pedido'] ?? 0);
        $token = $_GET['t'] ?? '';

        if (!$id || !$this->validarTokenRecibo($id, $token)) {
            http_response_code(403);
            echo '<h2 style="font-family:sans-serif;text-align:center;margin-top:60px">Enlace inválido o expirado.</h2>';
            exit;
        }

        $stmt = $this->db->prepare(
            "SELECT p.*, u.nombre AS vendedor_nombre, c.nombre AS cliente_nombre,
                    e.nombre AS empresa_nombre, e.whatsapp, e.logo_url
             FROM pedidos p
             JOIN usuarios u ON u.id = p.usuario_id
             JOIN empresas e ON e.id = p.empresa_id
             LEFT JOIN clientes c ON c.id = p.cliente_id
             WHERE p.id = ? AND p.estado = 'confirmado'"
        );
        $stmt->execute([$id]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            http_response_code(404);
            echo '<h2 style="font-family:sans-serif;text-align:center;margin-top:60px">Recibo no encontrado.</h2>';
            exit;
        }

        $empresa = [
            'nombre'   => $pedido['empresa_nombre'],
            'whatsapp' => $pedido['whatsapp']  ?? '',
            'logo_url' => $pedido['logo_url']  ?? '',
        ];

        $items        = $this->getItems($id);
        $receiptToken = $token;

        require VIEW_PATH . '/pedidos/recibo_pub.php';
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

    private function validarTokenRecibo(int $pedidoId, string $token): bool {
        if (strlen($token) < 10) return false;
        $esperado = hash_hmac('sha256', 'receipt:' . $pedidoId, APP_SECRET);
        return hash_equals($esperado, $token);
    }
}
