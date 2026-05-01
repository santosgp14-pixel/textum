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
                        $vDesc      = trim($v['descripcion'] ?? '');
                        $vUnidad    = in_array($v['unidad'] ?? '', ['metro','kilo','rollo'])
                                      ? $v['unidad'] : $data['unidad'];
                        $vCosto     = (float)($v['costo'] ?? 0);
                        $vPrecio    = (float)($v['precio'] ?? $data['precio']);
                        $vPrecioFrac= (float)($v['precio_fraccionado'] ?? 0);
                        if ($vPrecioFrac <= 0 && $vPrecio > 0) $vPrecioFrac = round($vPrecio * 1.15, 2);

                        if (empty($vDesc)) continue;

                        // Barcode moves to rollo level; generate a unique placeholder for the variant
                        $vBarcode = 'T' . $id . '-V' . $vKey;

                        $this->db->prepare(
                            "INSERT INTO variantes
                             (tela_id, empresa_id, descripcion, codigo_barras,
                              unidad, costo, precio, precio_fraccionado, precio_rollo, minimo_venta, stock)
                             VALUES (?,?,?,?,?,?,?,?,0,?,0)"
                        )->execute([$id, $eid, $vDesc, $vBarcode, $vUnidad, $vCosto, $vPrecio, $vPrecioFrac, $data['minimo_venta']]);
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
            $msg = ($e->getCode() === '23000')
                ? 'Código de barras duplicado: dos rollos tienen el mismo código. Cada rollo debe tener un código único (ej: V1-001, V2-001).'
                : 'Error al guardar: ' . $e->getMessage();
            $this->flashError($msg);
            header('Location: ' . BASE_URL . '/index.php?page=stock');
            exit;
        }

        $this->flashOk('Producto guardado correctamente.');
        header('Location: ' . BASE_URL . '/index.php?page=stock');
        exit;
    }

    public function eliminarTela(): void {
        Auth::require();
        $eid = Auth::empresaId();
        $id  = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $this->flashError('Producto no encontrado.');
            header('Location: ' . BASE_URL . '/index.php?page=stock');
            exit;
        }

        // Verificar que pertenece a esta empresa
        $stmt = $this->db->prepare("SELECT id FROM telas WHERE id=? AND empresa_id=? AND activa=1");
        $stmt->execute([$id, $eid]);
        if (!$stmt->fetch()) {
            $this->flashError('Producto no encontrado.');
            header('Location: ' . BASE_URL . '/index.php?page=stock');
            exit;
        }

        // Soft delete: desactivar tela y todas sus variantes
        $this->db->prepare("UPDATE telas    SET activa=0 WHERE id=? AND empresa_id=?")->execute([$id, $eid]);
        $this->db->prepare("UPDATE variantes SET activa=0 WHERE tela_id=? AND empresa_id=?")->execute([$id, $eid]);

        $this->flashOk('Producto eliminado correctamente.');
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

        // Detectar si rollos.costo existe (migración v1.4)
        if (!isset($_SESSION['_schema_caps'])) {
            $_SESSION['_schema_caps'] = ['v14' => false, 'precioRollo' => false, 'costoRollo' => false];
        }
        if (!$_SESSION['_schema_caps']['costoRollo']) {
            try { $this->db->query("SELECT costo FROM rollos LIMIT 0"); $_SESSION['_schema_caps']['costoRollo'] = true; } catch (PDOException $e) {}
        }
        $hasCostoRollo = $_SESSION['_schema_caps']['costoRollo'];

        if ($hasCostoRollo) {
            $stmt = $this->db->prepare(
                "SELECT v.*,
                        COUNT(r.id) AS total_rollos,
                        COALESCE(
                            NULLIF(SUM(r.costo * CASE WHEN r.costo > 0 THEN 1 ELSE 0 END) /
                                   NULLIF(SUM(CASE WHEN r.costo > 0 THEN 1 ELSE 0 END), 0), 0),
                            v.costo
                        ) AS avg_costo_rollos
                 FROM variantes v
                 LEFT JOIN rollos r ON r.variante_id = v.id
                 WHERE v.tela_id = ? AND v.empresa_id = ?
                 GROUP BY v.id
                 ORDER BY v.descripcion"
            );
        } else {
            $stmt = $this->db->prepare(
                "SELECT v.*, COUNT(r.id) AS total_rollos, v.costo AS avg_costo_rollos
                 FROM variantes v
                 LEFT JOIN rollos r ON r.variante_id = v.id
                 WHERE v.tela_id = ? AND v.empresa_id = ?
                 GROUP BY v.id
                 ORDER BY v.descripcion"
            );
        }
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

        $tela = $this->findTela($tid, $eid);

        $data = [
            'descripcion'   => trim($_POST['descripcion']   ?? ''),
            'codigo_barras' => trim($_POST['codigo_barras'] ?? ''),
            'unidad'        => ($tela['tipo'] === 'punto') ? 'kilo'
                               : (in_array($_POST['unidad'] ?? '', ['metro','kilo']) ? $_POST['unidad'] : 'metro'),
            'minimo_venta'  => (float)($_POST['minimo_venta']  ?? 0.1),
            'costo'         => (float)($_POST['costo']         ?? 0),
            'precio_rollo'       => (float)($_POST['precio_rollo']       ?? 0),
            'precio'             => (float)($_POST['precio']             ?? 0),
            'precio_fraccionado' => (float)($_POST['precio_fraccionado'] ?? 0),
            'stock'              => (float)($_POST['stock']              ?? 0),
        ];

        // Si no ingresaron fraccionado pero sí base, calcular automáticamente
        if ($data['precio_fraccionado'] <= 0 && $data['precio'] > 0) {
            $data['precio_fraccionado'] = round($data['precio'] * 1.15, 2);
        }

        // Barcode es opcional: auto-generar si no se ingresó
        if (empty($data['codigo_barras'])) {
            $data['codigo_barras'] = 'VAR-' . $tid . '-' . time();
        }

        if (empty($data['descripcion'])) {
            $this->flashError('La descripción es obligatoria.');
            header('Location: ' . BASE_URL . "/index.php?page=variantes&tela_id=$tid");
            exit;
        }

        if ($id > 0) {
            try {
                $stmt = $this->db->prepare(
                    "UPDATE variantes
                     SET descripcion=?, codigo_barras=?, unidad=?,
                         minimo_venta=?, costo=?, precio_rollo=?, precio=?, precio_fraccionado=?, stock=?
                     WHERE id=? AND empresa_id=?"
                );
                $stmt->execute([
                    $data['descripcion'], $data['codigo_barras'], $data['unidad'],
                    $data['minimo_venta'], $data['costo'], $data['precio_rollo'],
                    $data['precio'], $data['precio_fraccionado'], $data['stock'],
                    $id, $eid
                ]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $this->flashError('El código de barras ya está en uso por otra variante.');
                    header('Location: ' . BASE_URL . "/index.php?page=variante_editar&id=$id");
                    exit;
                }
                throw $e;
            }
        } else {
            try {
                $stmt = $this->db->prepare(
                    "INSERT INTO variantes
                     (tela_id, empresa_id, descripcion, codigo_barras, unidad, minimo_venta, costo, precio_rollo, precio, precio_fraccionado, stock)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([
                    $tid, $eid,
                    $data['descripcion'], $data['codigo_barras'], $data['unidad'],
                    $data['minimo_venta'], $data['costo'], $data['precio_rollo'],
                    $data['precio'], $data['precio_fraccionado'], $data['stock']
                ]);
                $varId = (int)$this->db->lastInsertId();

                // Rollos opcionales enviados desde el form
                $rollos = $_POST['rollos'] ?? [];
                if (is_array($rollos)) {
                    $stockTotal = 0.0;
                    foreach ($rollos as $r) {
                        $metros   = (float)($r['metros']     ?? 0);
                        $nroRollo = trim($r['nro_rollo']     ?? '');
                        $rBarcode = trim($r['codigo_barras'] ?? '') ?: null;
                        $rCosto   = (float)($r['costo']      ?? 0);
                        if ($metros <= 0) continue;
                        $this->db->prepare(
                            "INSERT INTO rollos (variante_id, empresa_id, nro_rollo, codigo_barras, costo, metros)
                             VALUES (?,?,?,?,?,?)"
                        )->execute([$varId, $eid, $nroRollo, $rBarcode, $rCosto, $metros]);
                        $stockTotal += $metros;
                    }
                    if ($stockTotal > 0) {
                        $this->db->prepare("UPDATE variantes SET stock=stock+? WHERE id=?")->execute([$stockTotal, $varId]);
                    }
                }
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
            'precio'             => $v['precio'],
            'precio_fraccionado' => $v['precio_fraccionado'] ?? 0,
            'precio_rollo'       => $v['precio_rollo'],
            'minimo_venta'       => $v['minimo_venta'],
            'stock'       => $v['stock'],
            'codigo_barras'=> $v['codigo_barras'],
        ];
    }

    private function notFound(): void {
        http_response_code(404);
        require VIEW_PATH . '/errors/404.php';
        exit;
    }

    // ──────────────────────────────────────────────────────────
    // IMPORTACIÓN MASIVA CSV
    // ──────────────────────────────────────────────────────────

    public function importarCSVForm(): void {
        Auth::require();
        $resultado = null;
        require VIEW_PATH . '/stock/importar_csv.php';
    }

    public function importarCSV(): void {
        Auth::require();
        $eid            = Auth::empresaId();
        $updateExisting = !empty($_POST['update_existing']);

        // Validar upload
        $file = $_FILES['csv_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->flashError('Error al subir el archivo.');
            header('Location: ' . BASE_URL . '/index.php?page=stock_importar_csv');
            exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->flashError('El archivo supera 2 MB.');
            header('Location: ' . BASE_URL . '/index.php?page=stock_importar_csv');
            exit;
        }
        // Verificar extensión y MIME
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv' || !in_array($mime, ['text/csv', 'text/plain', 'application/csv', 'application/octet-stream'], true)) {
            $this->flashError('Solo se aceptan archivos CSV.');
            header('Location: ' . BASE_URL . '/index.php?page=stock_importar_csv');
            exit;
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $this->flashError('No se pudo leer el archivo.');
            header('Location: ' . BASE_URL . '/index.php?page=stock_importar_csv');
            exit;
        }

        // Leer cabecera
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->flashError('El archivo CSV está vacío o tiene formato incorrecto.');
            header('Location: ' . BASE_URL . '/index.php?page=stock_importar_csv');
            exit;
        }

        // Normalizar nombres de columna (minúsculas, sin espacios)
        $colMap = [];
        foreach ($header as $i => $col) {
            $colMap[trim(strtolower($col))] = $i;
        }

        $required = ['nombre_tela', 'precio'];
        foreach ($required as $col) {
            if (!isset($colMap[$col])) {
                fclose($handle);
                $this->flashError("El CSV no tiene la columna requerida: $col");
                header('Location: ' . BASE_URL . '/index.php?page=stock_importar_csv');
                exit;
            }
        }

        // Cache de telas y categorías ya creadas en esta sesión de importación
        $telasCache = [];
        $catCache   = [];

        $filas = [];
        $ok = $errores = $duplicados = 0;
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            // Ignorar filas vacías
            if (empty(array_filter($row))) continue;

            // Extraer campos con defaults
            $get = function(string $col) use ($row, $colMap): string {
                return isset($colMap[$col]) && isset($row[$colMap[$col]])
                    ? trim((string)$row[$colMap[$col]])
                    : '';
            };

            $nombreTela = $get('nombre_tela');
            $categoria  = $get('categoria');
            $descVar    = $get('descripcion_variante');
            $precioRaw  = $get('precio');
            $unidad     = $get('unidad');
            $stockRaw   = $get('stock');
            $costoRaw   = $get('costo');

            if (empty($nombreTela) || !is_numeric($precioRaw)) {
                $filas[] = ['row' => $rowNum, 'tela' => $nombreTela ?: '(vacío)', 'variante' => $descVar, 'status' => 'error', 'msg' => 'Nombre o precio inválido'];
                $errores++;
                continue;
            }

            $precio = (float)$precioRaw;
            $stock  = is_numeric($stockRaw) ? (float)$stockRaw : 0;
            $costo  = is_numeric($costoRaw) ? (float)$costoRaw : 0;
            $unidad = in_array($unidad, ['metro','kilo','rollo']) ? $unidad : 'metro';

            // Resolver categoría
            $catId = null;
            if (!empty($categoria)) {
                $catKey = strtolower($categoria);
                if (isset($catCache[$catKey])) {
                    $catId = $catCache[$catKey];
                } else {
                    $stmt = $this->db->prepare(
                        "SELECT id FROM categorias WHERE empresa_id=? AND LOWER(nombre)=?"
                    );
                    $stmt->execute([$eid, $catKey]);
                    $catRow = $stmt->fetch();
                    if ($catRow) {
                        $catId = (int)$catRow['id'];
                    } else {
                        $this->db->prepare(
                            "INSERT INTO categorias (empresa_id, nombre, orden, activa) VALUES (?,?,0,1)"
                        )->execute([$eid, $categoria]);
                        $catId = (int)$this->db->lastInsertId();
                    }
                    $catCache[$catKey] = $catId;
                }
            }

            // Resolver tela
            $telaKey = strtolower($nombreTela);
            if (isset($telasCache[$telaKey])) {
                $telaId = $telasCache[$telaKey];
            } else {
                $stmt = $this->db->prepare(
                    "SELECT id FROM telas WHERE empresa_id=? AND LOWER(nombre)=? AND activa=1"
                );
                $stmt->execute([$eid, $telaKey]);
                $telaRow = $stmt->fetch();
                if ($telaRow) {
                    $telaId = (int)$telaRow['id'];
                } else {
                    $this->db->prepare(
                        "INSERT INTO telas (empresa_id, nombre, categoria_id, activa) VALUES (?,?,?,1)"
                    )->execute([$eid, $nombreTela, $catId]);
                    $telaId = (int)$this->db->lastInsertId();
                }
                $telasCache[$telaKey] = $telaId;
            }

            // Resolver variante (upsert)
            $stmt = $this->db->prepare(
                "SELECT id FROM variantes WHERE tela_id=? AND empresa_id=? AND LOWER(descripcion)=?"
            );
            $stmt->execute([$telaId, $eid, strtolower($descVar ?: 'default')]);
            $varRow = $stmt->fetch();

            if ($varRow) {
                if ($updateExisting) {
                    $this->db->prepare(
                        "UPDATE variantes SET precio=?, costo=?, stock=? WHERE id=?"
                    )->execute([$precio, $costo, $stock, $varRow['id']]);
                    $filas[] = ['row' => $rowNum, 'tela' => $nombreTela, 'variante' => $descVar, 'status' => 'updated'];
                    $ok++;
                } else {
                    $filas[] = ['row' => $rowNum, 'tela' => $nombreTela, 'variante' => $descVar, 'status' => 'duplicado'];
                    $duplicados++;
                }
            } else {
                $this->db->prepare(
                    "INSERT INTO variantes (tela_id, empresa_id, descripcion, precio, costo, unidad, stock, minimo_venta, activa)
                     VALUES (?,?,?,?,?,?,?,1,1)"
                )->execute([$telaId, $eid, $descVar ?: 'default', $precio, $costo, $unidad, $stock]);
                $filas[] = ['row' => $rowNum, 'tela' => $nombreTela, 'variante' => $descVar, 'status' => 'ok'];
                $ok++;
            }
        }

        fclose($handle);

        $resultado = compact('ok', 'errores', 'duplicados', 'filas');
        require VIEW_PATH . '/stock/importar_csv.php';
    }

    public function csvTemplate(): void {
        Auth::require();
        $filename = 'template_telas.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['nombre_tela','categoria','descripcion_variante','precio','unidad','stock','costo']);
        fputcsv($out, ['Algodón Jersey','Algodones','Blanco 150cm',2500,'metro',50,1500]);
        fputcsv($out, ['Algodón Jersey','Algodones','Negro 150cm',2500,'metro',30,1500]);
        fputcsv($out, ['Lana Frisé','Lanas','Azul 140cm',4800,'metro',0,3000]);
        fclose($out);
        exit;
    }
}
