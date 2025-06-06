<?php
require_once 'models/Pedido.php';
require_once 'models/Carrinho.php';
require_once 'models/Cupom.php';

class PedidoController
{
    private $model;
    private $carrinhoModel;
    private $cupomModel;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->model = new Pedido($conn);
        $this->carrinhoModel = new Carrinho($conn);
        $this->cupomModel = new Cupom($conn);
    }

    public function index()
    {
        $pedidos = $this->model->findAll();
        include 'views/pedidos/index.php';
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nome = htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8');
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
                $endereco = htmlspecialchars($_POST['endereco'] ?? '', ENT_QUOTES, 'UTF-8');
                $cupom = htmlspecialchars($_POST['cupom'] ?? '', ENT_QUOTES, 'UTF-8');

                if (!$nome || !$email || !$cep || !$endereco) {
                    throw new Exception("Todos os campos são obrigatórios");
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("E-mail inválido");
                }

                if (strlen($cep) !== 8) {
                    throw new Exception("CEP inválido");
                }

                $carrinho = $this->carrinhoModel->getCarrinho();
                if (empty($carrinho)) {
                    throw new Exception("Carrinho vazio");
                }

                $subtotal = $this->carrinhoModel->calcularSubtotal();
                $frete = $this->calcularFrete($subtotal);
                $desconto = 0;
                $cupom_id = null;

                if ($cupom) {
                    $result = $this->cupomModel->validarCupom($cupom, $subtotal);
                    if ($result['valid']) {
                        $desconto = $result['desconto'];
                        // Get coupon ID
                        $stmt = $this->conn->prepare("SELECT id FROM cupons WHERE codigo = ?");
                        $stmt->execute([$cupom]);
                        $cupom_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        $cupom_id = $cupom_data['id'];
                    }
                }

                $total = $subtotal + $frete - $desconto;

                // If this is the final submission (after confirmation)
                if (isset($_POST['confirm'])) {
                    $pedido_id = $this->model->criarPedido([
                        'nome' => $nome,
                        'email' => $email,
                        'cep' => $cep,
                        'endereco' => $endereco,
                        'subtotal' => $subtotal,
                        'frete' => $frete,
                        'desconto' => $desconto,
                        'total' => $total,
                        'cupom_id' => $cupom_id
                    ], $carrinho);

                    if ($pedido_id) {
                        $this->carrinhoModel->limpar();
                        header('Location: index.php?route=pedidos&success=1');
                        exit;
                    } else {
                        throw new Exception("Erro ao criar pedido");
                    }
                }

                // If this is the initial submission, show confirmation page
                include 'views/pedidos/create.php';
                return;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: index.php?route=carrinho');
                exit;
            }
        }

        // If not POST, redirect to cart
        header('Location: index.php?route=carrinho');
        exit;
    }

    public function view()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: index.php?route=pedidos');
            exit;
        }

        $pedido = $this->model->getPedidoComItens($id);
        if (!$pedido) {
            header('Location: index.php?route=pedidos');
            exit;
        }

        include 'views/pedidos/view.php';
    }

    public function details()
    {
        $id = htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8');
        if (!$id) {
            header('Location: index.php?route=pedidos');
            exit;
        }

        // Get all orders to find the one with matching hash_id
        $pedidos = $this->model->findAll();
        $pedido_id = null;

        foreach ($pedidos as $pedido) {
            if ($pedido['hash_id'] === $id) {
                $pedido_id = $pedido['id'];
                break;
            }
        }

        if (!$pedido_id) {
            header('Location: index.php?route=pedidos');
            exit;
        }

        $pedido = $this->model->getPedidoComItens($pedido_id);
        if (!$pedido) {
            header('Location: index.php?route=pedidos');
            exit;
        }

        include 'views/pedidos/view.php';
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

    public function webhook()
    {
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }

        // Validate required fields
        if (!isset($input['id']) || !isset($input['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        try {
            $pedido_id = $input['id'];
            $status = strtolower($input['status']);

            // Validate status
            $valid_statuses = ['pendente', 'aprovado', 'cancelado', 'entregue'];
            if (!in_array($status, $valid_statuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status']);
                exit;
            }

            // Get order
            $pedido = $this->model->findById($pedido_id);
            if (!$pedido) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                exit;
            }

            // Handle status update
            if ($status === 'cancelado') {
                // Delete order items first
                $stmt = $this->conn->prepare("DELETE FROM pedido_itens WHERE pedido_id = ?");
                $stmt->execute([$pedido_id]);

                // Delete order
                $stmt = $this->conn->prepare("DELETE FROM pedidos WHERE id = ?");
                $stmt->execute([$pedido_id]);

                echo json_encode(['success' => true, 'message' => 'Order cancelled and deleted']);
            } else {
                // Update order status
                $this->model->atualizarStatus($pedido_id, $status);
                echo json_encode(['success' => true, 'message' => 'Order status updated']);
            }
        } catch (Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
}
