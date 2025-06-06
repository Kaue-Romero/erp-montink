<?php
require_once 'models/Produto.php';

class ProdutoController
{
    private $model;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->model = new Produto($conn);
    }

    public function index()
    {
        $produtos = $this->model->getProdutosComEstoque();
        $conn = $this->conn;
        include 'views/produtos/index.php';
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validate input
                $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
                $preco = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
                $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

                if (!$nome || !$preco || !$quantidade) {
                    throw new Exception("Dados inválidos");
                }

                $this->model->criarProdutoComEstoque([
                    'nome' => $nome,
                    'preco' => $preco,
                    'quantidade' => $quantidade,
                    'variacoes' => isset($_POST['variacoes']) ? $_POST['variacoes'] : []
                ]);
                header('Location: index.php?route=produtos');
                exit;
            } catch (Exception $e) {
                $error = "Erro ao criar produto: " . $e->getMessage();
            }
        }
        include 'views/produtos/create.php';
    }

    public function update()
    {
        // Validate ID parameter
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            $_SESSION['error'] = "ID de produto inválido";
            header('Location: index.php?route=produtos');
            exit;
        }

        try {
            $produto = $this->model->getProdutosComEstoque($id);

            if (!$produto) {
                $_SESSION['error'] = "Produto não encontrado";
                header('Location: index.php?route=produtos');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate input
                $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
                $preco = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
                $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

                if (!$nome || !$preco || !$quantidade) {
                    throw new Exception("Dados inválidos");
                }

                $this->model->atualizarProdutoComEstoque($id, [
                    'nome' => $nome,
                    'preco' => $preco,
                    'quantidade' => $quantidade,
                    'variacoes' => isset($_POST['variacoes']) ? $_POST['variacoes'] : []
                ]);

                $_SESSION['success'] = "Produto atualizado com sucesso";
                header('Location: index.php?route=produtos');
                exit;
            }

            // Pass the database connection to the view
            $conn = $this->conn;
            include 'views/produtos/update.php';
        } catch (Exception $e) {
            $_SESSION['error'] = "Erro ao atualizar produto: " . $e->getMessage();
            header('Location: index.php?route=produtos');
            exit;
        }
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

                if (!$id || $id <= 0) {
                    throw new Exception("ID de produto inválido");
                }

                // Verify if product exists before deleting
                $produto = $this->model->getProdutosComEstoque($id);
                if (!$produto) {
                    throw new Exception("Produto não encontrado");
                }

                $this->model->delete($id);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    public function getVariants()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            try {
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id || $id <= 0) {
                    throw new Exception("ID de produto inválido");
                }

                $stmt = $this->conn->prepare("SELECT v.*, e.quantidade 
                                            FROM variacoes v 
                                            LEFT JOIN estoque e ON v.id = e.variacao_id 
                                            WHERE v.produto_id = ?");
                $stmt->execute([$id]);
                $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Ensure no output before this point
                if (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'variants' => $variants
                ]);
                exit;
            } catch (Exception $e) {
                // Ensure no output before this point
                if (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
        }
        // If not GET request, return error
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Método não permitido'
        ]);
        exit;
    }
}
