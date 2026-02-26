<?php
/**
 * TEXTUM - ClientesController
 * CRUD completo + búsqueda JSON para el widget de pedidos
 */
class ClientesController {
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
            "SELECT c.*,
                    COUNT(p.id)                AS total_pedidos,
                    COALESCE(SUM(p.total), 0)  AS total_comprado,
                    MAX(p.confirmado_at)        AS ultima_compra
             FROM clientes c
             LEFT JOIN pedidos p ON p.cliente_id = c.id AND p.estado = 'confirmado'
             WHERE c.empresa_id = ? AND c.activo = 1
             GROUP BY c.id
             ORDER BY total_comprado DESC, c.nombre ASC"
        );
        $stmt->execute([$eid]);
        $clientes = $stmt->fetchAll();
        require VIEW_PATH . '/clientes/index.php';
    }

    // ──────────────────────────────────────────────────────────
    // Alta / edición
    // ──────────────────────────────────────────────────────────

    public function nuevo(): void {
        Auth::require();
        $cliente = null;
        require VIEW_PATH . '/clientes/form.php';
    }

    public function editar(): void {
        Auth::require();
        $id      = (int)($_GET['id'] ?? 0);
        $eid     = Auth::empresaId();
        $cliente = $this->findCliente($id, $eid);
        require VIEW_PATH . '/clientes/form.php';
    }

    public function guardar(): void {
        Auth::require();
        $eid    = Auth::empresaId();
        $id     = (int)($_POST['id']       ?? 0);
        $nombre = trim($_POST['nombre']    ?? '');
        $tel    = trim($_POST['telefono']  ?? '');
        $email  = trim($_POST['email']     ?? '');
        $notas  = trim($_POST['notas']     ?? '');

        if (empty($nombre)) {
            $this->flashError('El nombre del cliente es obligatorio.');
            $redir = $id ? "cliente_editar&id=$id" : 'cliente_nuevo';
            header('Location: ' . BASE_URL . "/index.php?page=$redir");
            exit;
        }

        if ($id > 0) {
            $this->db->prepare(
                "UPDATE clientes SET nombre=?, telefono=?, email=?, notas=?
                 WHERE id=? AND empresa_id=?"
            )->execute([$nombre, $tel, $email, $notas, $id, $eid]);
            $this->flashOk('Cliente actualizado correctamente.');
        } else {
            $this->db->prepare(
                "INSERT INTO clientes (empresa_id, nombre, telefono, email, notas)
                 VALUES (?,?,?,?,?)"
            )->execute([$eid, $nombre, $tel, $email, $notas]);
            $this->flashOk('Cliente creado correctamente.');
        }

        header('Location: ' . BASE_URL . '/index.php?page=clientes');
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Perfil del cliente (historial completo + top productos)
    // ──────────────────────────────────────────────────────────

    public function perfil(): void {
        Auth::require();
        $id      = (int)($_GET['id'] ?? 0);
        $eid     = Auth::empresaId();
        $cliente = $this->findCliente($id, $eid);

        // Stats generales (solo pedidos confirmados)
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)               AS total_pedidos,
                    COALESCE(SUM(total), 0) AS total_comprado,
                    MAX(confirmado_at)      AS ultima_compra
             FROM pedidos
             WHERE cliente_id=? AND empresa_id=? AND estado='confirmado'"
        );
        $stmt->execute([$id, $eid]);
        $stats = $stmt->fetch();

        // Historial de pedidos (todos los estados)
        $stmt = $this->db->prepare(
            "SELECT p.*, u.nombre AS vendedor_nombre
             FROM pedidos p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.cliente_id=? AND p.empresa_id=?
             ORDER BY p.created_at DESC
             LIMIT 50"
        );
        $stmt->execute([$id, $eid]);
        $pedidos = $stmt->fetchAll();

        // Productos más comprados (solo confirmados)
        $stmt = $this->db->prepare(
            "SELECT t.nombre AS tela, v.descripcion, v.unidad,
                    SUM(pi.cantidad)      AS total_cantidad,
                    SUM(pi.subtotal)      AS total_monto,
                    COUNT(DISTINCT p.id)  AS veces
             FROM pedido_items pi
             JOIN pedidos   p ON p.id = pi.pedido_id
             JOIN variantes v ON v.id = pi.variante_id
             JOIN telas     t ON t.id = v.tela_id
             WHERE p.cliente_id=? AND p.empresa_id=? AND p.estado='confirmado'
             GROUP BY v.id
             ORDER BY total_cantidad DESC
             LIMIT 10"
        );
        $stmt->execute([$id, $eid]);
        $productos_top = $stmt->fetchAll();

        require VIEW_PATH . '/clientes/perfil.php';
    }

    // ──────────────────────────────────────────────────────────
    // API JSON: búsqueda rápida para widget de pedido
    // ──────────────────────────────────────────────────────────

    public function buscar(): void {
        Auth::require();
        header('Content-Type: application/json');
        $eid  = Auth::empresaId();
        $q    = trim($_GET['q'] ?? '');

        if (strlen($q) < 2) { echo json_encode([]); exit; }

        $like = "%$q%";
        $stmt = $this->db->prepare(
            "SELECT id, nombre, telefono, email
             FROM clientes
             WHERE empresa_id=? AND activo=1
               AND (nombre LIKE ? OR telefono LIKE ? OR email LIKE ?)
             ORDER BY nombre
             LIMIT 8"
        );
        $stmt->execute([$eid, $like, $like, $like]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────

    private function findCliente(int $id, int $eid): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM clientes WHERE id=? AND empresa_id=? AND activo=1"
        );
        $stmt->execute([$id, $eid]);
        $c = $stmt->fetch();
        if (!$c) {
            http_response_code(404);
            require VIEW_PATH . '/errors/404.php';
            exit;
        }
        return $c;
    }

    private function flashOk(string $msg): void    { $_SESSION['flash_ok']    = $msg; }
    private function flashError(string $msg): void { $_SESSION['flash_error'] = $msg; }
}
