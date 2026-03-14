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
        $tela               = null;
        $variantesExistentes = [];
        $categorias         = $this->getCategorias(Auth::empresaId());
        require VIEW_PATH . '/stock/tela_form.php';
    }

    public function guardarTela(): void {
        Auth::require();
        $eid  = Auth::empresaId();
        $uid  = Auth::userId();
        $id   = (int)($_POST['id'] ?? 0);

        $catId    = (int)($_POST['categoria_id']  ?? 0) ?: null;
        $subcatId = (int)($_POST['subcategoria_id'] ?? 0) ?: null;

        $unidad = in_array($_POST['unidad'] ?? '', ['metro','kilo','rollo'])
                  ? $_POST['unidad'] : 'metro';

        $data = [
            'nombre'       => trim($_POST['nombre']       ?? ''),
            'descripcion'  => trim($_POST['descripcion']  ?? ''),
            'rinde'        => (float)($_POST['rinde']      ?? 0) ?: null,
            'tipo'         => in_array($_POST['tipo'] ?? '', ['punto','plano']) ? $_POST['tipo'] : null,
            'subcategoria' => in_array($_POST['subcategoria'] ?? '', ['atemporal','invierno','verano']) ? $_POST['subcategoria'] : null,
            'precio'       => (float)($_POST['precio']    ?? 0),
            'unidad'       => $unidad,
            'minimo_venta' => max(0.001, (float)($_POST['minimo_venta'] ?? 1)),
        ];

        if (empty($data['nombre'])) {
            $this->flashError('El nombre es obligatorio.');
            header('Location: ' . BASE_URL . '/index.php?page=stock');
            exit;
        }

        // ── Imagen ───────────────────────────────────────────
        $imagenUrl = null;
        if (!empty($_FILES['imagen']['tmp_name'])) {
            $imagenUrl = $this->subirImagen($_FILES['imagen']);
            if ($imagenUrl === false) {
                $this->flashError('Error al guardar la imagen. Verifique formato y tamaño (máx 2 MB).');
                $redirect = $id > 0 ? "index.php?page=tela_editar&id=$id" : 'index.php?page=tela_nueva';
                header('Location: ' . BASE_URL . '/' . $redirect);
                exit;
            }
        }

        $this->db->beginTransaction();
        try {
            if ($id > 0) {
                $sql = "UPDATE telas
                        SET nombre=?, descripcion=?, rinde=?,
                            tipo=?, subcategoria=?,
                            precio=?, unidad=?, minimo_venta=?"
                      . ($imagenUrl !== null ? ', imagen_url=?' : '')
                      . " WHERE id=? AND empresa_id=?";
                $params = [
                    $data['nombre'], $data['descripcion'], $data['rinde'],
                    $data['tipo'], $data['subcategoria'],
                    $data['precio'], $data['unidad'], $data['minimo_venta'],
                ];
                if ($imagenUrl !== null) $params[] = $imagenUrl;
                $params[] = $id;
                $params[] = $eid;
                $this->db->prepare($sql)->execute($params);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO telas
                     (empresa_id, nombre, descripcion, rinde,
                      tipo, subcategoria,
                      precio, unidad, minimo_venta, imagen_url)
                     VALUES (?,?,?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([
                    $eid,
                    $data['nombre'], $data['descripcion'], $data['rinde'],
                    $data['tipo'], $data['subcategoria'],
                    $data['precio'], $data['unidad'], $data['minimo_venta'], $imagenUrl,
                ]);
                $id = (int)$this->db->lastInsertId();

                // ── Variantes inline ──────────────────────────
                $stockInicial = (float)($_POST['stock_inicial'] ?? 0);
                $variantes    = $_POST['variantes'] ?? [];

                if (!empty($variantes) && is_array($variantes)) {
                    foreach ($variantes as $vKey => $v) {
                        $vDesc   = trim($v['descripcion'] ?? '');
                        $vUnidad = in_array($v['unidad'] ?? '', ['metro','kilo','rollo'])
                                   ? $v['unidad'] : $data['unidad'];
                        $vPrecio = (float)($v['precio'] ?? $data['precio']);

                        if (empty($vDesc)) continue;

                        // Barcode moves to rollo level; generate a unique placeholder for the variant
                        $vBarcode = 'T' . $id . '-V' . $vKey;

                        $this->db->prepare(
                            "INSERT INTO variantes
                             (tela_id, empresa_id, descripcion, codigo_barras,
                              unidad, precio, precio_rollo, minimo_venta, stock)
                             VALUES (?,?,?,?,?,?,0,?,0)"
                        )->execute([$id, $eid, $vDesc, $vBarcode, $vUnidad, $vPrecio, $data['minimo_venta']]);
                        $varId = (int)$this->db->lastInsertId();

                        // Rollos de esta variante
                        $rollos = $v['rollos'] ?? [];
                        if (is_array($rollos)) {
                            $stockTotal = 0.0;
                            foreach ($rollos as $r) {
                                $metros   = (float)($r['metros']        ?? 0);
                                $nroRollo = trim($r['nro_rollo']        ?? '');
                                $rBarcode = trim($r['codigo_barras']    ?? '') ?: null;
                                $rCosto   = (float)($r['costo']         ?? 0);
                                if ($metros <= 0) continue;
                                $this->db->prepare(
                                    "INSERT INTO rollos (variante_id, empresa_id, nro_rollo, codigo_barras, costo, metros)
                                     VALUES (?,?,?,?,?,?)"
                                )->execute([$varId, $eid, $nroRollo, $rBarcode, $rCosto, $metros]);
                                $stockTotal += $metros;
                            }
                            if ($stockTotal > 0) {
                                $this->db->prepare(
                                    "UPDATE variantes SET stock=? WHERE id=?"
                                )->execute([$stockTotal, $varId]);
                                $this->db->prepare(
                                    "INSERT INTO movimientos_stock
                                     (empresa_id, variante_id, usuario_id, tipo,
                                      cantidad, stock_antes, stock_despues)
                                     VALUES (?,?,?,'ajuste_entrada',?,0,?)"
                                )->execute([$eid, $varId, $uid, $stockTotal, $stockTotal]);
                            }
                        }
                    }
                } elseif ($stockInicial > 0) {
                    // Sin variantes: insertar variante genérica con stock inicial
                    $this->db->prepare(
                        "INSERT INTO variantes
                         (tela_id, empresa_id, descripcion, codigo_barras,
                          unidad, precio, precio_rollo, minimo_venta, stock)
                         VALUES (?,?,?,?,?,?,0,?,?)"
                    )->execute([
                        $id, $eid, 'General',
                        'GEN-' . $id,
                        $data['unidad'], $data['precio'],
                        $data['minimo_venta'], $stockInicial,
                    ]);
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->flashError('Error al guardar: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/index.php?page=stock');
            exit;
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

        $stmt = $this->db->prepare(
            "SELECT id, descripcion, codigo_barras, unidad, stock
             FROM variantes WHERE tela_id=? AND empresa_id=? AND activa=1
             ORDER BY descripcion"
        );
        $stmt->execute([$id, $eid]);
        $variantesExistentes = $stmt->fetchAll();

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
            echo json_encode(['ok' => true, 'variante' => $this->sanitizeVariante($variante)]);
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
        echo json_encode(['ok' => true, 'variante' => $this->sanitizeVariante($row), 'rollo' => $rollo]);
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
        $categoria       = null;
        $todasCategorias = $this->getCategorias(Auth::empresaId());
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
        $todasCategorias = $this->getCategorias($eid);
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

        $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
        $tipo     = in_array($_POST['tipo'] ?? '', ['punto','plano']) ? $_POST['tipo'] : null;

        if ($id > 0) {
            $this->db->prepare(
                "UPDATE categorias SET nombre=?, orden=?, parent_id=?, tipo=? WHERE id=? AND empresa_id=?"
            )->execute([$nombre, $orden, $parentId, $tipo, $id, $eid]);
        } else {
            $this->db->prepare(
                "INSERT INTO categorias (empresa_id, nombre, orden, parent_id, tipo) VALUES (?,?,?,?,?)"
            )->execute([$eid, $nombre, $orden, $parentId, $tipo]);
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
        $rows = $stmt->fetchAll();
        // Sort: roots first, then subs grouped under their parent (safe before/after migration)
        $roots = array_filter($rows, fn($c) => empty($c['parent_id'] ?? null));
        $subs  = array_filter($rows, fn($c) => !empty($c['parent_id'] ?? null));
        $sorted = [];
        foreach ($roots as $r) {
            $sorted[] = $r;
            foreach ($subs as $s) {
                if ((int)$s['parent_id'] === (int)$r['id']) $sorted[] = $s;
            }
        }
        // Append any subs whose parent wasn't found
        foreach ($subs as $s) {
            if (!in_array($s, $sorted, true)) $sorted[] = $s;
        }
        return $sorted;
    }

    /** Guarda imagen subida y devuelve la ruta relativa, o false si hay error */
    private function subirImagen(array $file): string|false {
        $maxBytes  = 2 * 1024 * 1024;
        $allowed   = ['image/jpeg','image/png','image/webp','image/gif'];
        $ext       = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

        if ($file['error'] !== UPLOAD_ERR_OK)    return false;
        if ($file['size'] > $maxBytes)           return false;

        // Validate MIME via finfo (don't trust $_FILES['type'])
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowed, true))    return false;

        $dir = PUBLIC_PATH . '/assets/uploads/productos';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = bin2hex(random_bytes(12)) . '.' . $ext[$mime];
        $dest     = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

        return 'assets/uploads/productos/' . $filename;
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

    /** Devuelve solo los campos necesarios de una variante para el frontend (no expone costo) */
    private function sanitizeVariante(array $v): array {
        return [
            'id'          => $v['id'],
            'tela_id'     => $v['tela_id'],
            'tela_nombre' => $v['tela_nombre'],
            'descripcion' => $v['descripcion'],
            'unidad'      => $v['unidad'],
            'precio'      => $v['precio'],
            'precio_rollo'=> $v['precio_rollo'],
            'minimo_venta'=> $v['minimo_venta'],
            'stock'       => $v['stock'],
            'codigo_barras'=> $v['codigo_barras'],
        ];
    }

    private function notFound(): void {
        http_response_code(404);
        require VIEW_PATH . '/errors/404.php';
        exit;
    }
}
