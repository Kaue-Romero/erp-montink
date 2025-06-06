<?php
require_once 'Model.php';

class Produto extends Model
{
    protected $table = 'produtos';

    public function getProdutosComEstoque($id = null)
    {
        $sql = "SELECT p.*, COALESCE(e.quantidade, 0) as quantidade 
                FROM produtos p 
                LEFT JOIN estoque e ON p.id = e.produto_id AND e.variacao_id IS NULL";

        if ($id) {
            $sql .= " WHERE p.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function criarProdutoComEstoque($dados)
    {
        try {
            $this->conn->beginTransaction();

            // Insert produto
            $produto_id = $this->create([
                'nome' => $dados['nome'],
                'preco' => $dados['preco']
            ]);

            // Insert estoque
            $stmt = $this->conn->prepare("INSERT INTO estoque (produto_id, quantidade) VALUES (?, ?)");
            $stmt->execute([$produto_id, $dados['quantidade']]);

            // Handle variações if any
            if (!empty($dados['variacoes'])) {
                foreach ($dados['variacoes'] as $variacao) {
                    $stmt = $this->conn->prepare("INSERT INTO variacoes (produto_id, nome) VALUES (?, ?)");
                    $stmt->execute([$produto_id, $variacao['nome']]);
                    $variacao_id = $this->conn->lastInsertId();

                    $stmt = $this->conn->prepare("INSERT INTO estoque (produto_id, variacao_id, quantidade) VALUES (?, ?, ?)");
                    $stmt->execute([$produto_id, $variacao_id, $variacao['quantidade']]);
                }
            }

            $this->conn->commit();
            return $produto_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function atualizarProdutoComEstoque($id, $dados)
    {
        try {
            $this->conn->beginTransaction();

            // Update produto
            $this->update($id, [
                'nome' => $dados['nome'],
                'preco' => $dados['preco']
            ]);

            // Update estoque
            $stmt = $this->conn->prepare("UPDATE estoque SET quantidade = ? WHERE produto_id = ? AND variacao_id IS NULL");
            $stmt->execute([$dados['quantidade'], $id]);

            // Handle variações
            if (!empty($dados['variacoes'])) {
                // Get existing variants
                $stmt = $this->conn->prepare("SELECT id FROM variacoes WHERE produto_id = ?");
                $stmt->execute([$id]);
                $existingVariants = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Track which variants are being updated
                $updatedVariantIds = [];

                foreach ($dados['variacoes'] as $variacao) {
                    if (isset($variacao['id'])) {
                        // Update existing variant
                        $stmt = $this->conn->prepare("UPDATE variacoes SET nome = ? WHERE id = ? AND produto_id = ?");
                        $stmt->execute([$variacao['nome'], $variacao['id'], $id]);

                        $stmt = $this->conn->prepare("UPDATE estoque SET quantidade = ? WHERE variacao_id = ?");
                        $stmt->execute([$variacao['quantidade'], $variacao['id']]);

                        $updatedVariantIds[] = $variacao['id'];
                    } else {
                        // Insert new variant
                        $stmt = $this->conn->prepare("INSERT INTO variacoes (produto_id, nome) VALUES (?, ?)");
                        $stmt->execute([$id, $variacao['nome']]);
                        $variacao_id = $this->conn->lastInsertId();

                        $stmt = $this->conn->prepare("INSERT INTO estoque (produto_id, variacao_id, quantidade) VALUES (?, ?, ?)");
                        $stmt->execute([$id, $variacao_id, $variacao['quantidade']]);
                    }
                }

                // Delete variants that were removed
                $variantsToDelete = array_diff($existingVariants, $updatedVariantIds);
                if (!empty($variantsToDelete)) {
                    $placeholders = str_repeat('?,', count($variantsToDelete) - 1) . '?';
                    $stmt = $this->conn->prepare("DELETE FROM estoque WHERE variacao_id IN ($placeholders)");
                    $stmt->execute($variantsToDelete);

                    $stmt = $this->conn->prepare("DELETE FROM variacoes WHERE id IN ($placeholders)");
                    $stmt->execute($variantsToDelete);
                }
            } else {
                // If no variants provided, delete all existing variants
                $stmt = $this->conn->prepare("DELETE FROM estoque WHERE produto_id = ? AND variacao_id IS NOT NULL");
                $stmt->execute([$id]);

                $stmt = $this->conn->prepare("DELETE FROM variacoes WHERE produto_id = ?");
                $stmt->execute([$id]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function verificarEstoque($id, $quantidade, $current_cart_quantity = 0)
    {
        $stmt = $this->conn->prepare("SELECT e.quantidade 
                                    FROM estoque e 
                                    WHERE e.produto_id = ? AND e.variacao_id IS NULL");
        $stmt->execute([$id]);
        $estoque = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no stock record found, return false
        if (!$estoque) {
            error_log("No stock record found for product_id: " . $id);
            return false;
        }

        // Calculate available quantity by adding current cart quantity
        $available_quantity = $estoque['quantidade'] + $current_cart_quantity;
        error_log("Stock check - Product ID: " . $id . ", Requested: " . $quantidade . ", Available: " . $available_quantity . ", Current in cart: " . $current_cart_quantity);

        return $available_quantity >= $quantidade;
    }

    public function atualizarEstoque($id, $quantidade)
    {
        $stmt = $this->conn->prepare("UPDATE estoque 
                                    SET quantidade = quantidade - ? 
                                    WHERE produto_id = ? AND variacao_id IS NULL");
        return $stmt->execute([$quantidade, $id]);
    }

    public function restaurarEstoque($id, $quantidade)
    {
        $stmt = $this->conn->prepare("UPDATE estoque 
                                    SET quantidade = quantidade + ? 
                                    WHERE produto_id = ? AND variacao_id IS NULL");
        return $stmt->execute([$quantidade, $id]);
    }
}
