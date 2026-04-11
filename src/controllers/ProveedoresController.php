<?php
/**
 * TEXTUM — ProveedoresController
 * CRUD de proveedores textiles, aislado por empresa_id
 */
class ProveedoresController {
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
            "SELECT * FROM proveedores WHERE empresa_id = ? ORDER BY nombre ASC"
        );
        $stmt->execute([$eid]);
        $proveedores = $stmt->fetchAll();
        require VIEW_PATH . '/proveedores/index.php';
    }

    // ──────────────────────────────────────────────────────────
    // Alta / edición
    // ──────────────────────────────────────────────────────────

    public function nuevo(): void {
        Auth::require();
        $proveedor = null;
        require VIEW_PATH . '/proveedores/form.php';
    }

    public function editar(): void {
        Auth::require();
        $eid = Auth::empresaId();
        $id  = (int)($_GET['id'] ?? 0);

        $stmt = $this->db->prepare(
            "SELECT * FROM proveedores WHERE id = ? AND empresa_id = ?"
        );
        $stmt->execute([$id, $eid]);
        $proveedor = $stmt->fetch();

        if (!$proveedor) {
            $_SESSION['flash_error'] = 'Proveedor no encontrado.';
            header('Location: ' . BASE_URL . '/index.php?page=proveedores');
            exit;
        }
        require VIEW_PATH . '/proveedores/form.php';
    }

    public function guardar(): void {
        Auth::require();
        $eid = Auth::empresaId();
        $id  = (int)($_POST['id'] ?? 0);

        $nombre   = trim($_POST['nombre']   ?? '');
        $cuit     = preg_replace('/[^0-9\-]/', '', $_POST['cuit']    ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;
        $notas    = trim($_POST['notas']    ?? '');
        $activo   = isset($_POST['activo']) ? 1 : 0;

        if (empty($nombre)) {
            $_SESSION['flash_error'] = 'El nombre del proveedor es obligatorio.';
            $redir = $id ? "page=proveedor_editar&id=$id" : 'page=proveedor_nuevo';
            header('Location: ' . BASE_URL . "/index.php?$redir");
            exit;
        }

        if ($id) {
            // Verificar que pertenece a la empresa
            $stmt = $this->db->prepare(
                "SELECT id FROM proveedores WHERE id = ? AND empresa_id = ?"
            );
            $stmt->execute([$id, $eid]);
            if (!$stmt->fetch()) {
                $_SESSION['flash_error'] = 'Proveedor no encontrado.';
                header('Location: ' . BASE_URL . '/index.php?page=proveedores');
                exit;
            }

            $this->db->prepare(
                "UPDATE proveedores
                 SET nombre=?, cuit=?, telefono=?, email=?, notas=?, activo=?
                 WHERE id=? AND empresa_id=?"
            )->execute([$nombre, $cuit ?: null, $telefono ?: null, $email, $notas ?: null, $activo, $id, $eid]);
            $_SESSION['flash_ok'] = 'Proveedor actualizado.';
        } else {
            $this->db->prepare(
                "INSERT INTO proveedores (empresa_id, nombre, cuit, telefono, email, notas, activo)
                 VALUES (?,?,?,?,?,?,?)"
            )->execute([$eid, $nombre, $cuit ?: null, $telefono ?: null, $email, $notas ?: null, $activo]);
            $_SESSION['flash_ok'] = 'Proveedor creado.';
        }

        header('Location: ' . BASE_URL . '/index.php?page=proveedores');
        exit;
    }

    public function eliminar(): void {
        Auth::require();
        Auth::requireAdmin();
        $eid = Auth::empresaId();
        $id  = (int)($_POST['id'] ?? 0);

        $this->db->prepare(
            "DELETE FROM proveedores WHERE id = ? AND empresa_id = ?"
        )->execute([$id, $eid]);

        $_SESSION['flash_ok'] = 'Proveedor eliminado.';
        header('Location: ' . BASE_URL . '/index.php?page=proveedores');
        exit;
    }
}
