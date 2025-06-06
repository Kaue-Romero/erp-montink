<?php
require_once 'models/Carrinho.php';
require_once 'models/Cupom.php';

class CarrinhoController
{
    private $model;
    private $cupomModel;

    public function __construct($conn)
    {
        $this->model = new Carrinho($conn);
        $this->cupomModel = new Cupom($conn);
    }

    public function index()
    {
        $carrinho = $this->model->getCarrinho();
        $subtotal = $this->model->calcularSubtotal();
        $frete = $this->calcularFrete($subtotal);

        // Calculate discount if coupon is applied
        $desconto = 0;
        $total = $subtotal + $frete;

        if (isset($_SESSION['cupom'])) {
            $result = $this->cupomModel->validarCupom($_SESSION['cupom'], $subtotal);
            if ($result['valid']) {
                $desconto = $result['desconto'];
                $total = $subtotal + $frete - $desconto;
            } else {
                unset($_SESSION['cupom']);
            }
        }

        include 'views/carrinho/index.php';
    }

    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $produto_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
                $quantidade = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
                $variacao_id = filter_input(INPUT_POST, 'variation_id', FILTER_VALIDATE_INT);

                if (!$produto_id || !$quantidade) {
                    throw new Exception("Dados inválidos");
                }

                $result = $this->model->adicionarItem($produto_id, $quantidade, $variacao_id);
                echo json_encode($result);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    public function remove()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        try {
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }

            if (!isset($_POST['cart_key'])) {
                throw new Exception('Chave do carrinho não fornecida');
            }

            $cart_key = $_POST['cart_key'];
            if (empty($cart_key)) {
                throw new Exception('Chave do carrinho inválida');
            }

            $result = $this->model->removerItem($cart_key);

            // Set JSON header
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function update()
    {
        // Prevent any output before JSON response
        ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                error_log("=== CarrinhoController::update started ===");
                error_log("POST data: " . print_r($_POST, true));

                $produto_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
                $quantidade = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

                if (!$produto_id || !$quantidade) {
                    error_log("Invalid input - product_id: " . $produto_id . ", quantity: " . $quantidade);
                    throw new Exception("Dados inválidos: ID do produto ou quantidade inválidos");
                }

                if ($quantidade < 1) {
                    error_log("Invalid quantity: " . $quantidade);
                    throw new Exception("A quantidade deve ser maior que zero");
                }

                error_log("Calling model->atualizarQuantidade with product_id: " . $produto_id . ", quantity: " . $quantidade);
                $result = $this->model->atualizarQuantidade($produto_id, $quantidade);
                error_log("Update result: " . print_r($result, true));

                echo json_encode($result);
                exit;
            } catch (Exception $e) {
                error_log("=== Exception in CarrinhoController::update ===");
                error_log("Error message: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                error_log("POST data: " . print_r($_POST, true));
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        } else {
            error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
    }

    public function applyCupom()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Clear any previous output
                if (ob_get_level()) {
                    ob_end_clean();
                }

                $cupom = filter_input(INPUT_POST, 'cupom', FILTER_SANITIZE_STRING);
                if (!$cupom) {
                    throw new Exception("Cupom inválido");
                }

                $subtotal = $this->model->calcularSubtotal();
                $result = $this->cupomModel->validarCupom($cupom, $subtotal);

                if ($result['valid']) {
                    $frete = $this->calcularFrete($subtotal);
                    $total = $subtotal + $frete - $result['desconto'];
                    $_SESSION['cupom'] = $cupom;

                    // Return JSON response
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'desconto' => $result['desconto'],
                        'total' => $total,
                        'subtotal' => $subtotal,
                        'frete' => $frete
                    ]);
                    exit;
                } else {
                    unset($_SESSION['cupom']);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message']
                    ]);
                    exit;
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }

        // If not POST, return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }

    public function updateSession()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Clear any previous output
                if (ob_get_level()) {
                    ob_end_clean();
                }

                $cupom = filter_input(INPUT_POST, 'cupom', FILTER_SANITIZE_STRING);
                $desconto = filter_input(INPUT_POST, 'desconto', FILTER_VALIDATE_FLOAT);
                $total = filter_input(INPUT_POST, 'total', FILTER_VALIDATE_FLOAT);

                if (!$cupom || !$desconto || !$total) {
                    throw new Exception("Dados inválidos");
                }

                $_SESSION['cupom'] = $cupom;
                $_SESSION['desconto'] = $desconto;
                $_SESSION['total'] = $total;

                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }

        // If not POST, return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }

    private function calcularFrete($subtotal)
    {
        if ($subtotal >= 200) {
            return 0;
        } elseif ($subtotal >= 52 && $subtotal <= 166.59) {
            return 15;
        }
        return 20;
    }

    public function getCartContent()
    {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            $carrinho = $this->model->getCarrinho();
            $subtotal = $this->model->calcularSubtotal();
            $frete = $this->calcularFrete($subtotal);

            // Calculate discount if coupon is applied
            $desconto = 0;
            $total = $subtotal + $frete;

            if (isset($_SESSION['cupom'])) {
                $result = $this->cupomModel->validarCupom($_SESSION['cupom'], $subtotal);
                if ($result['valid']) {
                    $desconto = $result['desconto'];
                    $total = $subtotal + $frete - $desconto;
                } else {
                    unset($_SESSION['cupom']);
                }
            }

            // Include the view to get the HTML
            ob_start();
            include 'views/carrinho/index.php';
            $html = ob_get_clean();

            // Return the HTML
            echo $html;
            exit;
        } catch (Exception $e) {
            error_log("Error in getCartContent: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao obter conteúdo do carrinho']);
            exit;
        }
    }
}
