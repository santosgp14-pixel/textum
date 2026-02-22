<?php
/**
 * TEXTUM - StockController
 * CRUD de telas y variantes
 */
class StockController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ──────────────────────────────────────────────────────────
    // TELAS
    // ──────────────────────────────────────────────────────────

    public function index(): void {
        Auth::require();
        $eid   = Auth::empresaId();
        $telas = $this->db->prepare(
            "SELECT t.*, COUNT(v.id) AS total_variantes,
                    SUM(CASE WHEN v.activa=1 THEN 1 ELSE 0 END) AS variantes_activas
             FROM telas t
             LEFT JOIN variantes v ON v.tela_id = t.id
             WHERE t.empresa_id = ? AND t.activa = 1
             GROUP BY t.id
             ORDER BY t.nombre"
        );
        $telas->execute([$eid]);
        $telas = $telas->fetchAll();
        require VIEW_PATH . '/stock/index.php';
    }

    public function nuevaTela(): void {
        Auth::require();
        require VIEW_PATH . '/stock/tela_form.php';
    }

    public function guardarTela(): void {
        Auth::require();
        $eid  = Auth::empresaId();
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'nombre'      => trim($_POST['nombre']      ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'composicion' => trim($_POST['composicion'] ?? ''),
        ];

        if (empty($data['nombre'])) {
            $this->flashError('El nombre es obligatorio.');
            header('Location: ' . BASE_URL . '/index.php?page=stock');
            exit;
        }

        if ($id > 0) {
            $stmt = $this->db->prepare(
                "UPDATE telas SET nombre=?, descripcion=?, composicion=?
                 WHERE id=? AND empresa_id=?"
            );
            $stmt->execute([$data['nombre'], $data['descripcion'], $data['composicion'], $id, $eid]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO telas (empresa_id, nombre, descripcion, composicion)
                 VALUES (?,?,?,?)"
            );
            $stmt->execute([$eid, $data['nombre'], $data['descripcion'], $data['composicion']]);
        }

        $this->flashOk('Tela guardada correctamente.');
        header('Location: ' . BASE_URL . '/index.php?page=stock');
        exit;
    }

    public function editarTela(): void {
        Auth::require();
        $id   = (int)($_GET['id'] ?? 0);
        $eid  = Auth::empresaId();
        $tela = $this->findTela($id, $eid);
        require VIEW_PATH . '/stock/tela_form.php';
    }

    // ──────────────────────────────────────────────────────────
    // VARIANTES
    // ──────────────────────────────────────────────────────────

    public function variantes(): void {
        Auth::require();
        $tela_id = (int)($_GET['tela_id'] ?? 0);
        $eid     = Auth::empresaId();
        $tela    = $this->findTela($tela_id, $eid);

        $stmt = $this->db->prepare(
            "SELECT * FROM variantes
             WHERE tela_id = ? AND empresa_id = ?
             ORDER BY descripcion"
        );
        $stmt->execute([$tela_id, $eid]);
        $variantes = $stmt->fetchAll();
        require VIEW_PATH . '/stock/variantes.php';
    }

    public function nuevaVariante(): void {
        Auth::require();
        $tela_id = (int)($_GET['tela_id'] ?? 0);
        $eid     = Auth::empresaId();
        $tela    = $this->findTela($tela_id, $eid);
        $variante = null;
        require VIEW_PATH . '/stock/variante_form.php';
    }

    public function editarVariante(): void {
        Auth::require();
        $id   = (int)($_GET['id'] ?? 0);
        $eid  = Auth::empresaId();
        $stmt = $this->db->prepare(
            "SELECT v.*, t.nombre AS tela_nombre
             FROM variantes v JOIN telas t ON t.id = v.tela_id
             WHERE v.id = ? AND v.empresa_id = ?"
        );
        $stmt->execute([$id, $eid]);
        $variante = $stmt->fetch();
        if (!$variante) { $this->notFound(); return; }

        $tela_id = $variante['tela_id'];
        $tela    = $this->findTela($tela_id, $eid);
        require VIEW_PATH . '/stock/variante_form.php';
    }

    public function guardarVariante(): void {
        Auth::require();
        $eid  = Auth::empresaId();
        $id   = (int)($_POST['id']      ?? 0);
        $tid  = (int)($_POST['tela_id'] ?? 0);

        $data = [
            'descripcion'   => trim($_POST['descripcion']   ?? ''),
            'codigo_barras' => trim($_POST['codigo_barras'] ?? ''),
            'unidad'        => in_array($_POST['unidad'] ?? '', ['metro','kilo']) ? $_POST['unidad'] : 'metro',
            'minimo_venta'  => (float)($_POST['minimo_venta']  ?? 0.1),
            'precio'        => (float)($_POST['precio']        ?? 0),
            'stock'         => (float)($_POST['stock']         ?? 0),
        ];

        if (empty($data['descripcion']) || empty($data['codigo_barras'])) {
            $this->flashError('Descripción y código de barras son obligatorios.');
            header('Location: ' . BASE_URL . "/index.php?page=variantes&tela_id=$tid");
            exit;
        }

        if ($id > 0) {
            $stmt = $this->db->prepare(
                "UPDATE variantes
                 SET descripcion=?, codigo_barras=?, unidad=?,
                     minimo_venta=?, precio=?, stock=?
                 WHERE id=? AND empresa_id=?"
            );
            $stmt->execute([
                $data['descripcion'], $data['codigo_barras'], $data['unidad'],
                $data['minimo_venta'], $data['precio'], $data['stock'],
                $id, $eid
            ]);
        } else {
            try {
                $stmt = $this->db->prepare(
                    "INSERT INTO variantes
                     (tela_id, empresa_id, descripcion, codigo_barras, unidad, minimo_venta, precio, stock)
                     VALUES (?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([
                    $tid, $eid,
                    $data['descripcion'], $data['codigo_barras'], $data['unidad'],
                    $data['minimo_venta'], $data['precio'], $data['stock']
                ]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $this->flashError('El código de barras ya existe para otra variante.');
                    header('Location: ' . BASE_URL . "/index.php?page=variantes&tela_id=$tid");
                    exit;
                }
                throw $e;
            }
        }

        $this->flashOk('Variante guardada correctamente.');
        header('Location: ' . BASE_URL . "/index.php?page=variantes&tela_id=$tid");
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // API - búsqueda por código de barras (JSON)
    // ──────────────────────────────────────────────────────────

    public function buscarBarcode(): void {
        Auth::require();
        header('Content-Type: application/json');
        $eid     = Auth::empresaId();
        $barcode = trim($_GET['barcode'] ?? '');

        if (empty($barcode)) {
            echo json_encode(['ok' => false, 'msg' => 'Código vacío']);
            exit;
        }

        $stmt = $this->db->prepare(
            "SELECT v.*, t.nombre AS tela_nombre
             FROM variantes v
             JOIN telas t ON t.id = v.tela_id
             WHERE v.codigo_barras = ? AND v.empresa_id = ? AND v.activa = 1
             LIMIT 1"
        );
        $stmt->execute([$barcode, $eid]);
        $variante = $stmt->fetch();

        if (!$variante) {
            echo json_encode(['ok' => false, 'msg' => 'Código no encontrado']);
            exit;
        }

        echo json_encode(['ok' => true, 'variante' => $variante]);
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────

    private function findTela(int $id, int $eid): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM telas WHERE id = ? AND empresa_id = ? AND activa = 1"
        );
        $stmt->execute([$id, $eid]);
        $tela = $stmt->fetch();
        if (!$tela) { $this->notFound(); exit; }
        return $tela;
    }

    private function flashOk(string $msg): void    { $_SESSION['flash_ok']    = $msg; }
    private function flashError(string $msg): void { $_SESSION['flash_error'] = $msg; }
    private function notFound(): void {
        http_response_code(404);
        require VIEW_PATH . '/errors/404.php';
        exit;
    }
}
