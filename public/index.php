<?php
/**
 * TEXTUM - Front Controller
 * Único punto de entrada: public/index.php
 */

// ── Autoload & configuración ────────────────────────────────────
require_once __DIR__ . '/../config/config.php';

// Autoload simple de clases
spl_autoload_register(function (string $class): void {
    $dirs = [
        SRC_PATH . '/core/',
        SRC_PATH . '/controllers/',
        SRC_PATH . '/models/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Sesión ──────────────────────────────────────────────────────
Auth::init();

// ── Router ──────────────────────────────────────────────────────
$router = new Router();

// Auth
$router->add('GET',  'login',   fn() => (new AuthController())->loginPage());
$router->add('POST', 'login',   fn() => (new AuthController())->loginPost());
$router->add('GET',  'logout',  fn() => (new AuthController())->logout());

// Dashboard
$router->add('GET',  'dashboard',  fn() => (new DashboardController())->index());
$router->add('GET',  'productos',  fn() => (new ProductosController())->index());

// Stock - telas
$router->add('GET',  'stock',          fn() => (new StockController())->index());
$router->add('GET',  'tela_nueva',     fn() => (new StockController())->nuevaTela());
$router->add('GET',  'tela_editar',    fn() => (new StockController())->editarTela());
$router->add('POST', 'tela_guardar',   fn() => (new StockController())->guardarTela());
$router->add('POST', 'tela_eliminar',  fn() => (new StockController())->eliminarTela());

// Stock - variantes
$router->add('GET',  'variantes',         fn() => (new StockController())->variantes());
$router->add('GET',  'variante_nueva',    fn() => (new StockController())->nuevaVariante());
$router->add('GET',  'variante_editar',   fn() => (new StockController())->editarVariante());
$router->add('POST', 'variante_guardar',  fn() => (new StockController())->guardarVariante());
$router->add('GET',  'barcode_buscar',    fn() => (new StockController())->buscarBarcode());

// Stock - categorías
$router->add('GET',  'categorias',        fn() => (new StockController())->categorias());
$router->add('GET',  'categoria_nueva',   fn() => (new StockController())->nuevaCategoria());
$router->add('GET',  'categoria_editar',  fn() => (new StockController())->editarCategoria());
$router->add('POST', 'categoria_guardar',  fn() => (new StockController())->guardarCategoria());
$router->add('POST', 'categoria_eliminar', fn() => (new StockController())->eliminarCategoria());

// Stock - rollos (sub-variantes: rollos físicos individuales)
$router->add('GET',  'rollos',              fn() => (new StockController())->rollos());
$router->add('GET',  'rollo_nuevo',         fn() => (new StockController())->nuevoRollo());
$router->add('GET',  'rollo_editar',        fn() => (new StockController())->editarRollo());
$router->add('POST', 'rollo_guardar',       fn() => (new StockController())->guardarRollo());
$router->add('POST', 'rollo_eliminar',      fn() => (new StockController())->eliminarRollo());
$router->add('POST', 'rollo_restaurar',     fn() => (new StockController())->restaurarRollo());
$router->add('GET',  'variante_rollos',     fn() => (new StockController())->rollosPorVariante());

// Pedidos
$router->add('GET',  'pedidos',          fn() => (new PedidosController())->index());
$router->add('GET',  'pedido_nuevo',     fn() => (new PedidosController())->nuevo());
$router->add('GET',  'pedido_abierto',   fn() => (new PedidosController())->pedidoAbierto());
$router->add('POST', 'pedido_item_add',  fn() => (new PedidosController())->agregarItem());
$router->add('POST', 'pedido_item_del',  fn() => (new PedidosController())->eliminarItem());
$router->add('POST', 'pedido_confirmar', fn() => (new PedidosController())->confirmar());
$router->add('POST', 'pedido_reaplicar_stock',      fn() => (new PedidosController())->reaplicarStock());
$router->add('POST', 'pedido_anular',               fn() => (new PedidosController())->anular());
$router->add('POST', 'pedido_anular_todos',         fn() => (new PedidosController())->anularTodosAbiertos());
$router->add('POST', 'pedido_anular_seleccionados', fn() => (new PedidosController())->anularSeleccionados());
$router->add('GET',  'pedido_detalle',     fn() => (new PedidosController())->detalle());
$router->add('POST', 'pedido_cliente_set', fn() => (new PedidosController())->setCliente());
$router->add('GET',  'pedido_catalogo',    fn() => (new PedidosController())->catalogoVariantes());
$router->add('GET',  'variantes_buscar',   fn() => (new PedidosController())->buscarVariantes());

// Clientes
$router->add('GET',  'clientes',        fn() => (new ClientesController())->index());
$router->add('GET',  'cliente_nuevo',   fn() => (new ClientesController())->nuevo());
$router->add('GET',  'cliente_editar',  fn() => (new ClientesController())->editar());
$router->add('POST', 'cliente_guardar', fn() => (new ClientesController())->guardar());
$router->add('GET',  'cliente_perfil',  fn() => (new ClientesController())->perfil());
$router->add('GET',  'clientes_buscar', fn() => (new ClientesController())->buscar());

// Balance
$router->add('GET',  'balance',                   fn() => (new BalanceController())->index());
$router->add('POST', 'gasto_guardar',              fn() => (new BalanceController())->guardarGasto());
$router->add('POST', 'gasto_aplicar_recurrentes',  fn() => (new BalanceController())->aplicarRecurrentes());

// Reportes
$router->add('GET',  'reportes',                   fn() => (new ReportesController())->index());

// Proveedores
$router->add('GET',  'proveedores',                fn() => (new ProveedoresController())->index());
$router->add('GET',  'proveedor_nuevo',            fn() => (new ProveedoresController())->nuevo());
$router->add('GET',  'proveedor_editar',           fn() => (new ProveedoresController())->editar());
$router->add('POST', 'proveedor_guardar',          fn() => (new ProveedoresController())->guardar());
$router->add('POST', 'proveedor_eliminar',         fn() => (new ProveedoresController())->eliminar());

// Configuración de empresa
$router->add('GET',  'config',                     fn() => (new ConfigController())->index());
$router->add('POST', 'config_guardar',             fn() => (new ConfigController())->guardar());

// Catálogo público (sin autenticación)
$router->add('GET',  'catalogo',                   fn() => (new CatalogoController())->index());

// Recibo público (sin autenticación)
$router->add('GET',  'recibo_pub',                 fn() => (new PedidosController())->reciboPub());

// Importación CSV de stock
$router->add('GET',  'stock_importar_csv',         fn() => (new StockController())->importarCSVForm());
$router->add('POST', 'stock_importar_csv',         fn() => (new StockController())->importarCSV());
$router->add('GET',  'stock_csv_template',         fn() => (new StockController())->csvTemplate());

// Ruta raíz → dashboard o login
if (!isset($_GET['page'])) {
    if (Auth::check()) {
        header('Location: ' . BASE_URL . '/index.php?page=dashboard');
    } else {
        header('Location: ' . BASE_URL . '/index.php?page=login');
    }
    exit;
}

$router->dispatch();
