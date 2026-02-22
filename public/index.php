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
$router->add('GET',  'dashboard', fn() => (new DashboardController())->index());

// Stock - telas
$router->add('GET',  'stock',          fn() => (new StockController())->index());
$router->add('GET',  'tela_nueva',     fn() => (new StockController())->nuevaTela());
$router->add('GET',  'tela_editar',    fn() => (new StockController())->editarTela());
$router->add('POST', 'tela_guardar',   fn() => (new StockController())->guardarTela());

// Stock - variantes
$router->add('GET',  'variantes',         fn() => (new StockController())->variantes());
$router->add('GET',  'variante_nueva',    fn() => (new StockController())->nuevaVariante());
$router->add('GET',  'variante_editar',   fn() => (new StockController())->editarVariante());
$router->add('POST', 'variante_guardar',  fn() => (new StockController())->guardarVariante());
$router->add('GET',  'barcode_buscar',    fn() => (new StockController())->buscarBarcode());

// Pedidos
$router->add('GET',  'pedidos',          fn() => (new PedidosController())->index());
$router->add('GET',  'pedido_nuevo',     fn() => (new PedidosController())->nuevo());
$router->add('GET',  'pedido_abierto',   fn() => (new PedidosController())->pedidoAbierto());
$router->add('POST', 'pedido_item_add',  fn() => (new PedidosController())->agregarItem());
$router->add('POST', 'pedido_item_del',  fn() => (new PedidosController())->eliminarItem());
$router->add('POST', 'pedido_confirmar', fn() => (new PedidosController())->confirmar());
$router->add('POST', 'pedido_anular',    fn() => (new PedidosController())->anular());
$router->add('GET',  'pedido_detalle',   fn() => (new PedidosController())->detalle());

// Balance
$router->add('GET',  'balance',        fn() => (new BalanceController())->index());
$router->add('POST', 'gasto_guardar',  fn() => (new BalanceController())->guardarGasto());

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
