<?php
// Disable error display in production
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'config/database.php';

// Autoload de classes
spl_autoload_register(function ($class) {
    $file = str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Start output buffering
ob_start();

// Simple router
$route = $_GET['route'] ?? 'produtos';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get the content first
$content = '';
switch ($route) {
    case 'produtos':
        include 'controllers/ProdutoController.php';
        $controller = new ProdutoController($conn);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
            try {
                $controller->create();
                ob_end_clean(); // Clear the buffer
                header('Location: index.php?route=produtos');
                exit;
            } catch (Exception $e) {
                $error = "Erro ao criar produto: " . $e->getMessage();
                ob_start();
                $controller->create();
                $content = ob_get_clean();
            }
        } else if ($action === 'update' && $id) {
            $controller->update();
            $content = ob_get_clean();
        } else if ($action === 'getVariants' && $isAjax) {
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            // Set JSON header
            header('Content-Type: application/json');
            $controller->getVariants();
            exit;
        } else {
            $controller->$action();
            $content = ob_get_clean();
        }
        break;
    case 'carrinho':
        include 'controllers/CarrinhoController.php';
        $controller = new CarrinhoController($conn);
        if ($isAjax) {
            // Set JSON header for AJAX requests
            header('Content-Type: application/json');
            if ($action === 'applyCupom' || $action === 'updateSession') {
                $controller->$action();
            } else {
                $controller->$action();
            }
            exit;
        } else {
            $controller->$action();
            $content = ob_get_clean();
        }
        break;
    case 'cupons':
        include 'controllers/CupomController.php';
        $controller = new CupomController($conn);
        if ($isAjax) {
            $controller->$action();
            exit;
        } else {
            $controller->$action();
            $content = ob_get_clean();
        }
        break;
    case 'pedidos':
        include 'controllers/PedidoController.php';
        $controller = new PedidoController($conn);
        if ($isAjax) {
            $controller->$action();
            exit;
        } else {
            if ($action === 'details' && $id) {
                $controller->details($id);
            } else {
                $controller->$action();
            }
            $content = ob_get_clean();
        }
        break;
    default:
        include 'controllers/ProdutoController.php';
        $controller = new ProdutoController($conn);
        $controller->index();
        $content = ob_get_clean();
}

// Only include header and footer for non-AJAX requests
if (!$isAjax) {
    include 'views/header.php';
    echo $content;
    include 'views/footer.php';
}
