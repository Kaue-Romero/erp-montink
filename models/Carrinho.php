<?php
require_once 'Model.php';
require_once 'Produto.php';

class Carrinho
{
    private $produtoModel;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->produtoModel = new Produto($conn);
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }
    }

    public function getCarrinho()
    {
        return $_SESSION['carrinho'];
    }

    public function adicionarItem($produto_id, $quantidade, $variacao_id = null)
    {
        // Get product details
        $produto = $this->produtoModel->findById($produto_id);
        if (!$produto) {
            return ['success' => false, 'message' => 'Produto não encontrado'];
        }

        // Check stock
        if ($variacao_id) {
            $stmt = $this->conn->prepare("SELECT quantidade FROM estoque WHERE produto_id = ? AND variacao_id = ?");
            $stmt->execute([$produto_id, $variacao_id]);
            $estoque = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$estoque || $estoque['quantidade'] < $quantidade) {
                return ['success' => false, 'message' => 'Quantidade indisponível em estoque para esta variação'];
            }
        } else {
            if (!$this->produtoModel->verificarEstoque($produto_id, $quantidade)) {
                return ['success' => false, 'message' => 'Quantidade indisponível em estoque'];
            }
        }

        // Add to cart
        $cart_key = $variacao_id ? "{$produto_id}_{$variacao_id}" : $produto_id;

        if (isset($_SESSION['carrinho'][$cart_key])) {
            $new_quantity = $_SESSION['carrinho'][$cart_key]['quantidade'] + $quantidade;

            // Check stock again for the new total
            if ($variacao_id) {
                if (!$estoque || $estoque['quantidade'] < $new_quantity) {
                    return ['success' => false, 'message' => 'Quantidade indisponível em estoque para esta variação'];
                }
            } else {
                if (!$this->produtoModel->verificarEstoque($produto_id, $new_quantity)) {
                    return ['success' => false, 'message' => 'Quantidade indisponível em estoque'];
                }
            }

            $_SESSION['carrinho'][$cart_key]['quantidade'] = $new_quantity;
        } else {
            $variacao_nome = '';
            if ($variacao_id) {
                $stmt = $this->conn->prepare("SELECT nome FROM variacoes WHERE id = ?");
                $stmt->execute([$variacao_id]);
                $variacao = $stmt->fetch(PDO::FETCH_ASSOC);
                $variacao_nome = $variacao ? $variacao['nome'] : '';
            }

            $_SESSION['carrinho'][$cart_key] = [
                'produto_id' => $produto_id,
                'variacao_id' => $variacao_id,
                'nome' => $produto['nome'] . ($variacao_nome ? " - $variacao_nome" : ''),
                'preco' => $produto['preco'],
                'quantidade' => $quantidade
            ];
        }

        return ['success' => true];
    }

    public function removerItem($cart_key)
    {
        // Log to error log instead of output
        error_log("Attempting to remove item with cart_key: " . $cart_key);
        error_log("Current cart contents: " . print_r($_SESSION['carrinho'], true));

        if (isset($_SESSION['carrinho'][$cart_key])) {
            unset($_SESSION['carrinho'][$cart_key]);
            error_log("Item removed successfully");
            return ['success' => true];
        }

        error_log("Item not found in cart");
        return ['success' => false, 'message' => 'Item não encontrado no carrinho'];
    }

    public function atualizarQuantidade($produto_id, $quantidade)
    {
        try {
            error_log("=== Starting quantity update ===");
            error_log("Product ID: " . $produto_id);
            error_log("Requested quantity: " . $quantidade);
            error_log("Current cart contents: " . print_r($_SESSION['carrinho'], true));

            // Validate input
            if (!is_numeric($produto_id) || !is_numeric($quantidade)) {
                error_log("Invalid input - product_id: " . $produto_id . ", quantity: " . $quantidade);
                return ['success' => false, 'message' => 'Dados inválidos: ID do produto ou quantidade inválidos'];
            }

            if ($quantidade < 1) {
                error_log("Invalid quantity: " . $quantidade);
                return ['success' => false, 'message' => 'A quantidade deve ser maior que zero'];
            }

            // First try to find the exact product_id
            if (isset($_SESSION['carrinho'][$produto_id])) {
                error_log("Found exact product_id match");
                $current_quantity = $_SESSION['carrinho'][$produto_id]['quantidade'];

                // Get product details for logging
                $produto = $this->produtoModel->findById($produto_id);
                error_log("Product details: " . print_r($produto, true));

                if (!$this->produtoModel->verificarEstoque($produto_id, $quantidade, $current_quantity)) {
                    error_log("Stock check failed for product_id: " . $produto_id);
                    return ['success' => false, 'message' => 'Quantidade indisponível em estoque. Produto: ' . $produto['nome']];
                }

                $_SESSION['carrinho'][$produto_id]['quantidade'] = $quantidade;
                error_log("Successfully updated quantity for product_id: " . $produto_id);
                return ['success' => true];
            }

            // If not found, look for product_id_variation_id format
            foreach ($_SESSION['carrinho'] as $key => $item) {
                if (strpos($key, $produto_id . '_') === 0) {
                    error_log("Found product_id with variation match: " . $key);
                    // For variations, we need to check stock with variation_id
                    $variacao_id = $item['variacao_id'];
                    $current_quantity = $item['quantidade'];

                    // Get variation details for logging
                    $stmt = $this->conn->prepare("SELECT v.*, e.quantidade as estoque_quantidade 
                                                FROM variacoes v 
                                                LEFT JOIN estoque e ON v.id = e.variacao_id 
                                                WHERE v.id = ?");
                    $stmt->execute([$variacao_id]);
                    $variacao = $stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Variation details: " . print_r($variacao, true));

                    $stmt = $this->conn->prepare("SELECT quantidade FROM estoque WHERE produto_id = ? AND variacao_id = ?");
                    $stmt->execute([$produto_id, $variacao_id]);
                    $estoque = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$estoque) {
                        error_log("No stock record found for variation_id: " . $variacao_id);
                        return ['success' => false, 'message' => 'Produto não encontrado em estoque. Variação: ' . $variacao['nome']];
                    }

                    $available_quantity = $estoque['quantidade'] + $current_quantity;
                    error_log("Stock check for variation - Product ID: " . $produto_id . ", Variation ID: " . $variacao_id .
                        ", Requested: " . $quantidade . ", Available: " . $available_quantity .
                        ", Current in cart: " . $current_quantity);

                    if ($available_quantity < $quantidade) {
                        error_log("Stock check failed for variation_id: " . $variacao_id);
                        return ['success' => false, 'message' => 'Quantidade indisponível em estoque para a variação: ' . $variacao['nome']];
                    }

                    $_SESSION['carrinho'][$key]['quantidade'] = $quantidade;
                    error_log("Successfully updated quantity for variation: " . $key);
                    return ['success' => true];
                }
            }

            error_log("Item not found in cart for product_id: " . $produto_id);
            return ['success' => false, 'message' => 'Item não encontrado no carrinho. ID: ' . $produto_id];
        } catch (Exception $e) {
            error_log("=== Exception in atualizarQuantidade ===");
            error_log("Error message: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Product ID: " . $produto_id);
            error_log("Quantity: " . $quantidade);
            error_log("Session state: " . print_r($_SESSION, true));
            return ['success' => false, 'message' => 'Erro ao atualizar quantidade: ' . $e->getMessage()];
        }
    }

    public function calcularSubtotal()
    {
        $subtotal = 0;
        foreach ($_SESSION['carrinho'] as $item) {
            $subtotal += $item['preco'] * $item['quantidade'];
        }
        return $subtotal;
    }

    public function limpar()
    {
        $_SESSION['carrinho'] = [];
        unset($_SESSION['cupom']);
    }
}
