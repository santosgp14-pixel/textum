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
        $eid       = Auth::empresaId();
        $catFiltro = (int)($_GET['cat'] ?? 0);

        $where  = "WHERE t.empresa_id = :eid AND t.activa = 1";
        $params = ['eid' => $eid];
        if ($catFiltro) {
            $where .= " AND t.categoria_id = :cat";
            $params['cat'] = $catFiltro;
        }

        $stmt = $this->db->prepare(
            "SELECT t.*, cat.nombre AS categoria_nombre,
                    COUNT(v.id) AS total_variantes,
                    SUM(CASE WHEN v.activa=1 THEN 1 ELSE 0 END) AS variantes_activas
             FROM telas t
             LEFT JOIN categorias cat ON cat.id = t.categoria_id
             LEFT JOIN variantes v ON v.tela_id = t.id
             $where
             GROUP BY t.id
             ORDER BY cat.orden, cat.nombre, t.nombre"
        );
        $stmt->execute($params);
        $telas = $stmt->fetchAll();

        $categorias = $this->getCategorias($eid);
        require VIEW_PATH . '/stock/index.php';
    }

    public function nuevaTela(): void {
        Auth::require();
        $tela       = null;
        $categorias = $this->getCategorias(Auth::empresaId());
        require VIEW_PATH . '/stock/tela_form.php';
    }

    public function guardarTela(): void {
        Auth::require();
        $eid  = Auth::empresaId();
        $id   = (int)($_POST['id'] ?? 0);
        $catId = (int)($_POST['categoria_id'] ?? 0) ?: null;
        $data = [
            'nombre'       => trim($_POST['nombre']       ?? ''),
            'descripcion'  => trim($_POST['descripcion']  ?? ''),
            'composicion'  => trim($_POST['composicion']  ?? ''),
            'categoria_id' => $catId,
        ];

        if (empty($data['nombre'])) {
            $this->flashError('El nombre es obligatorio.');
            header('Location: ' . BASE_URL . '/index.php?page=stock');
            exit;
        }

        if ($id > 0) {
            $stmt = $this->db->prepare(
                "UPDATE telas SET nombre=?, descripcion=?, composicion=?, categoria_id=?
                 WHERE id=? AND empresa_id=?"
            );
            $stmt->execute([$data['nombre'], $data['descripcion'], $data['composicion'],
                            $data['categoria_id'], $id, $eid]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO telas (empresa_id, nombre, descripcion, composicion, categoria_id)
                 VALUES (?,?,?,?,?)"
            );
            $stmt->execute([$eid, $data['nombre'], $data['descripcion'],
                            $data['composicion'], $data['categoria_id']]);
        }

        $this->flashOk('Producto guardado correctamente.');
        header('Location: ' . BASE_URL . '/index.php?page=stock');
        exit;
    }

    public function editarTela(): void {
        Auth::require();
        $id         = (int)($_GET['id'] ?? 0);
        $eid        = Auth::empresaId();
        $tela       = $this->findTela($id, $eid);
        $categorias = $this->getCategorias($eid);
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
            'costo'         => (float)($_POST['costo']         ?? 0),
            'precio_rollo'  => (float)($_POST['precio_rollo']  ?? 0),
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
                     minimo_venta=?, costo=?, precio_rollo=?, precio=?, stock=?
                 WHERE id=? AND empresa_id=?"
            );
            $stmt->execute([
                $data['descripcion'], $data['codigo_barras'], $data['unidad'],
                $data['minimo_venta'], $data['costo'], $data['precio_rollo'],
                $data['precio'], $data['stock'],
                $id, $eid
            ]);
        } else {
            try {
                $stmt = $this->db->prepare(
                    "INSERT INTO variantes
                     (tela_id, empresa_id, descripcion, codigo_barras, unidad, minimo_venta, costo, precio_rollo, precio, stock)
                     VALUES (?,?,?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([
                    $tid, $eid,
                    $data['descripcion'], $data['codigo_barras'], $data['unidad'],
                    $data['minimo_venta'], $data['costo'], $data['precio_rollo'],
                    $data['precio'], $data['stock']
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

        // 1) Buscar en variantes (barcode del producto+color)
        $stmt = $this->db->prepare(
            "SELECT v.*, t.nombre AS tela_nombre
             FROM variantes v
             JOIN telas t ON t.id = v.tela_id
             WHERE v.codigo_barras = ? AND v.empresa_id = ? AND v.activa = 1
             LIMIT 1"
        );
        $stmt->execute([$barcode, $eid]);
        $variante = $stmt->fetch();

        if ($variante) {
            echo json_encode(['ok' => true, 'variante' => $variante]);
            exit;
        }

        // 2) Buscar en rollos (barcode individual por rollo físico)
        $stmt = $this->db->prepare(
            "SELECT v.*, t.nombre AS tela_nombre,
                    r.id AS rollo_id, r.nro_rollo, r.metros AS rollo_metros
             FROM rollos r
             JOIN variantes v ON v.id = r.variante_id
             JOIN telas t ON t.id = v.tela_id
             WHERE r.codigo_barras = ? AND r.empresa_id = ?
               AND r.estado = 'disponible' AND v.activa = 1
             LIMIT 1"
        );
        $stmt->execute([$barcode, $eid]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['ok' => false, 'msg' => 'Código no encontrado']);
            exit;
        }

        $rollo = [
            'id'       => $row['rollo_id'],
            'nro_rollo'=> $row['nro_rollo'],
            'metros'   => $row['rollo_metros'],
        ];
        echo json_encode(['ok' => true, 'variante' => $row, 'rollo' => $rollo]);
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // ROLLOS (rollos físicos individuales de cada variante)
    // ──────────────────────────────────────────────────────────

    public function rollos(): void {
        Auth::require();
        $variante_id = (int)($_GET['variante_id'] ?? 0);
        $eid         = Auth::empresaId();
        $variante    = $this->findVariante($variante_id, $eid);

        $stmt = $this->db->prepare(
            "SELECT * FROM rollos
             WHERE variante_id = ? AND empresa_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$variante_id, $eid]);
        $rollos = $stmt->fetchAll();
        require VIEW_PATH . '/stock/rollos.php';
    }

    public function nuevoRollo(): void {
        Auth::require();
        $variante_id = (int)($_GET['variante_id'] ?? 0);
        $eid         = Auth::empresaId();
        $variante    = $this->findVariante($variante_id, $eid);
        $rollo       = null;
        require VIEW_PATH . '/stock/rollo_form.php';
    }

    public function editarRollo(): void {
        Auth::require();
        $id  = (int)($_GET['id'] ?? 0);
        $eid = Auth::empresaId();
        $stmt = $this->db->prepare(
            "SELECT r.*, r.variante_id
             FROM rollos r
             WHERE r.id = ? AND r.empresa_id = ?"
        );
        $stmt->execute([$id, $eid]);
        $rollo = $stmt->fetch();
        if (!$rollo) { $this->notFound(); return; }
        $variante = $this->findVariante($rollo['variante_id'], $eid);
        require VIEW_PATH . '/stock/rollo_form.php';
    }

    public function guardarRollo(): void {
        Auth::require();
        $eid         = Auth::empresaId();
        $uid         = Auth::userId();
        $id          = (int)($_POST['id']          ?? 0);
        $variante_id   = (int)($_POST['variante_id'] ?? 0);
        $metros        = (float)($_POST['metros']    ?? 0);
        $nro_rollo     = trim($_POST['nro_rollo']    ?? '');
        $codigo_barras = trim($_POST['codigo_barras'] ?? '') ?: null;

        if ($metros <= 0) {
            $this->flashError('La cantidad debe ser mayor a 0.');
            header('Location: ' . BASE_URL . "/index.php?page=rollos&variante_id=$variante_id");
            exit;
        }

        $this->db->beginTransaction();
        try {
            if ($id > 0) {
                $stmt = $this->db->prepare(
                    "SELECT metros FROM rollos WHERE id = ? AND empresa_id = ?"
                );
                $stmt->execute([$id, $eid]);
                $old  = $stmt->fetch();
                $diff = $metros - (float)$old['metros'];

                $this->db->prepare(
                    "UPDATE rollos SET nro_rollo = ?, codigo_barras = ?, metros = ? WHERE id = ? AND empresa_id = ?"
                )->execute([$nro_rollo, $codigo_barras, $metros, $id, $eid]);

                if (abs($diff) > 0.001) {
                    $stmt = $this->db->prepare(
                        "SELECT stock FROM variantes WHERE id = ? AND empresa_id = ? FOR UPDATE"
                    );
                    $stmt->execute([$variante_id, $eid]);
                    $antes  = (float)$stmt->fetchColumn();
                    $despues = max(0, $antes + $diff);
                    $this->db->prepare(
                        "UPDATE variantes SET stock = ? WHERE id = ? AND empresa_id = ?"
                    )->execute([$despues, $variante_id, $eid]);
                    $this->db->prepare(
                        "INSERT INTO movimientos_stock
                         (empresa_id, variante_id, usuario_id, tipo, cantidad, stock_antes, stock_despues)
                         VALUES (?,?,?,'ajuste_entrada',?,?,?)"
                    )->execute([$eid, $variante_id, $uid, $diff, $antes, $despues]);
                }
            } else {
                $stmt = $this->db->prepare(
                    "SELECT stock FROM variantes WHERE id = ? AND empresa_id = ? FOR UPDATE"
                );
                $stmt->execute([$variante_id, $eid]);
                $antes   = (float)$stmt->fetchColumn();
                $despues = $antes + $metros;

                $this->db->prepare(
                    "INSERT INTO rollos (variante_id, empresa_id, nro_rollo, codigo_barras, metros)
                     VALUES (?,?,?,?,?)"
                )->execute([$variante_id, $eid, $nro_rollo, $codigo_barras, $metros]);

                $this->db->prepare(
                    "UPDATE variantes SET stock = ? WHERE id = ? AND empresa_id = ?"
                )->execute([$despues, $variante_id, $eid]);

                $this->db->prepare(
                    "INSERT INTO movimientos_stock
                     (empresa_id, variante_id, usuario_id, tipo, cantidad, stock_antes, stock_despues)
                     VALUES (?,?,?,'ajuste_entrada',?,?,?)"
                )->execute([$eid, $variante_id, $uid, $metros, $antes, $despues]);
            }

            $this->db->commit();
            $this->flashOk('Rollo guardado. Stock actualizado.');
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->flashError('Error al guardar el rollo: ' . $e->getMessage());
        }

        header('Location: ' . BASE_URL . "/index.php?page=rollos&variante_id=$variante_id");
        exit;
    }

    public function eliminarRollo(): void {
        Auth::requireAdmin();
        $eid         = Auth::empresaId();
        $uid         = Auth::userId();
        $id          = (int)($_POST['id']          ?? 0);
        $variante_id = (int)($_POST['variante_id'] ?? 0);

        $stmt = $this->db->prepare(
            "SELECT metros FROM rollos WHERE id = ? AND empresa_id = ?"
        );
        $stmt->execute([$id, $eid]);
        $rollo = $stmt->fetch();
        if (!$rollo) {
            $this->flashError('Rollo no encontrado.');
            header('Location: ' . BASE_URL . "/index.php?page=rollos&variante_id=$variante_id");
            exit;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT stock FROM variantes WHERE id = ? AND empresa_id = ? FOR UPDATE"
            );
            $stmt->execute([$variante_id, $eid]);
            $antes   = (float)$stmt->fetchColumn();
            $despues = max(0, $antes - (float)$rollo['metros']);

            $this->db->prepare(
                "DELETE FROM rollos WHERE id = ? AND empresa_id = ?"
            )->execute([$id, $eid]);

            $this->db->prepare(
                "UPDATE variantes SET stock = ? WHERE id = ? AND empresa_id = ?"
            )->execute([$despues, $variante_id, $eid]);

            $this->db->prepare(
                "INSERT INTO movimientos_stock
                 (empresa_id, variante_id, usuario_id, tipo, cantidad, stock_antes, stock_despues)
                 VALUES (?,?,?,'ajuste_salida',?,?,?)"
            )->execute([$eid, $variante_id, $uid, -$rollo['metros'], $antes, $despues]);

            $this->db->commit();
            $this->flashOk('Rollo eliminado y stock actualizado.');
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->flashError('Error al eliminar el rollo.');
        }

        header('Location: ' . BASE_URL . "/index.php?page=rollos&variante_id=$variante_id");
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // CATEGORÍAS
    // ──────────────────────────────────────────────────────────

    public function categorias(): void {
        Auth::require();
        $eid  = Auth::empresaId();
        $stmt = $this->db->prepare(
            "SELECT c.*, COUNT(t.id) AS total_productos
             FROM categorias c
             LEFT JOIN telas t ON t.categoria_id = c.id AND t.activa = 1
             WHERE c.empresa_id = ? AND c.activa = 1
             GROUP BY c.id
             ORDER BY c.orden, c.nombre"
        );
        $stmt->execute([$eid]);
        $categorias = $stmt->fetchAll();
        require VIEW_PATH . '/stock/categorias.php';
    }

    public function nuevaCategoria(): void {
        Auth::require();
        $categoria = null;
        require VIEW_PATH . '/stock/categoria_form.php';
    }

    public function editarCategoria(): void {
        Auth::require();
        $id  = (int)($_GET['id'] ?? 0);
        $eid = Auth::empresaId();
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE id=? AND empresa_id=?");
        $stmt->execute([$id, $eid]);
        $categoria = $stmt->fetch();
        if (!$categoria) { $this->notFound(); return; }
        require VIEW_PATH . '/stock/categoria_form.php';
    }

    public function guardarCategoria(): void {
        Auth::require();
        $eid    = Auth::empresaId();
        $id     = (int)($_POST['id']    ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $orden  = (int)($_POST['orden'] ?? 0);

        if (empty($nombre)) {
            $this->flashError('El nombre es obligatorio.');
            header('Location: ' . BASE_URL . '/index.php?page=categorias');
            exit;
        }

        if ($id > 0) {
            $this->db->prepare(
                "UPDATE categorias SET nombre=?, orden=? WHERE id=? AND empresa_id=?"
            )->execute([$nombre, $orden, $id, $eid]);
        } else {
            $this->db->prepare(
                "INSERT INTO categorias (empresa_id, nombre, orden) VALUES (?,?,?)"
            )->execute([$eid, $nombre, $orden]);
        }

        $this->flashOk('Categoría guardada.');
        header('Location: ' . BASE_URL . '/index.php?page=categorias');
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────

    private function getCategorias(int $eid): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM categorias WHERE empresa_id=? AND activa=1 ORDER BY orden, nombre"
        );
        $stmt->execute([$eid]);
        return $stmt->fetchAll();
    }

    private function findVariante(int $id, int $eid): array {
        $stmt = $this->db->prepare(
            "SELECT v.*, t.nombre AS tela_nombre
             FROM variantes v JOIN telas t ON t.id = v.tela_id
             WHERE v.id = ? AND v.empresa_id = ? AND v.activa = 1"
        );
        $stmt->execute([$id, $eid]);
        $variante = $stmt->fetch();
        if (!$variante) { $this->notFound(); exit; }
        return $variante;
    }

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
