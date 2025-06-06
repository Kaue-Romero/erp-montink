<?php
require_once 'Model.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Pedido extends Model
{
    protected $table = 'pedidos';

    private function generateHashId($id)
    {
        return strtoupper(substr(md5($id . 'SALT'), 0, 8));
    }

    public function findAll()
    {
        $pedidos = parent::findAll();
        foreach ($pedidos as &$pedido) {
            $pedido['hash_id'] = $this->generateHashId($pedido['id']);
        }
        return $pedidos;
    }

    public function criarPedido($dados, $itens)
    {
        try {
            $this->conn->beginTransaction();

            // Create order
            $pedido_id = $this->create([
                'cliente_nome' => $dados['nome'],
                'cliente_email' => $dados['email'],
                'cep' => $dados['cep'],
                'endereco' => $dados['endereco'],
                'subtotal' => $dados['subtotal'],
                'frete' => $dados['frete'],
                'desconto' => $dados['desconto'],
                'total' => $dados['total'],
                'cupom_id' => $dados['cupom_id'] ?? null,
                'status' => 'pendente'
            ]);

            // Create order items
            foreach ($itens as $item) {
                $stmt = $this->conn->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) 
                                            VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $pedido_id,
                    $item['produto_id'],
                    $item['quantidade'],
                    $item['preco']
                ]);
            }

            $this->conn->commit();

            // Send order confirmation email
            $this->enviarEmailConfirmacao($pedido_id, $dados);

            return $pedido_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function enviarEmailConfirmacao($pedido_id, $dados)
    {
        $pedido = $this->getPedidoComItens($pedido_id);
        if (!$pedido) {
            error_log("Failed to send confirmation email: Order not found");
            return;
        }

        try {
            $config = require __DIR__ . '/../config/email.php';

            // Criar corpo do email em HTML
            $htmlBody = "
                <h2>Pedido Confirmado!</h2>
                <p>Olá {$dados['nome']},</p>
                <p>Seu pedido #{$pedido_id} foi recebido com sucesso!</p>
                
                <h3>Detalhes do Pedido:</h3>
                <table border='1' cellpadding='5' cellspacing='0'>
                    <tr>
                        <th>Item</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Subtotal</th>
                    </tr>";

            foreach ($pedido['itens'] as $item) {
                $htmlBody .= "
                    <tr>
                        <td>{$item['produto_nome']}</td>
                        <td>{$item['quantidade']}</td>
                        <td>R$ " . number_format($item['preco_unitario'], 2, ',', '.') . "</td>
                        <td>R$ " . number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') . "</td>
                    </tr>";
            }

            $htmlBody .= "
                </table>
                
                <h3>Resumo do Pedido:</h3>
                <p>Subtotal: R$ " . number_format($pedido['subtotal'], 2, ',', '.') . "</p>
                <p>Frete: R$ " . number_format($pedido['frete'], 2, ',', '.') . "</p>";

            if ($pedido['desconto'] > 0) {
                $htmlBody .= "<p>Desconto: R$ " . number_format($pedido['desconto'], 2, ',', '.') . "</p>";
            }

            $htmlBody .= "
                <p><strong>Total: R$ " . number_format($pedido['total'], 2, ',', '.') . "</strong></p>
                
                <h3>Endereço de Entrega:</h3>
                <p>
                    {$dados['endereco']}<br>
                    CEP: {$dados['cep']}
                </p>
                
                <p>Acompanhe seu pedido através do número: #{$pedido_id}</p>
                
                <p>Agradecemos sua compra!</p>";

            // Criar versão texto do email
            $textBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $htmlBody));

            if ($config['use_smtp']) {
                // Configurar e enviar email via SMTP
                $mail = new PHPMailer(true);

                // Configurações do servidor
                $mail->isSMTP();
                $mail->Host = $config['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['smtp_username'];
                $mail->Password = $config['smtp_password'];
                $mail->SMTPSecure = $config['smtp_encryption'];
                $mail->Port = $config['smtp_port'];
                $mail->CharSet = 'UTF-8';

                // Remetente e destinatário
                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($dados['email'], $dados['nome']);
                $mail->addReplyTo($config['reply_to']);

                // Conteúdo
                $mail->isHTML(true);
                $mail->Subject = "Confirmação do Pedido #{$pedido_id}";
                $mail->Body = $htmlBody;
                $mail->AltBody = $textBody;

                $mail->send();
            } else {
                // Enviar email usando PHP mail()
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: {$config['from_name']} <{$config['from_email']}>\r\n";
                $headers .= "Reply-To: {$config['reply_to']}\r\n";

                mail(
                    $dados['email'],
                    "Confirmação do Pedido #{$pedido_id}",
                    $htmlBody,
                    $headers
                );
            }

            error_log("Email de confirmação enviado com sucesso para {$dados['email']}");
        } catch (Exception $e) {
            error_log("Erro ao enviar email de confirmação: " . $e->getMessage());
        }
    }

    public function getPedidoComItens($id)
    {
        $pedido = $this->findById($id);
        if (!$pedido) {
            return null;
        }

        // Add hash_id to the order
        $pedido['hash_id'] = $this->generateHashId($pedido['id']);

        // Get order items
        $stmt = $this->conn->prepare("SELECT pi.*, p.nome as produto_nome 
                                    FROM pedido_itens pi 
                                    JOIN produtos p ON pi.produto_id = p.id 
                                    WHERE pi.pedido_id = ?");
        $stmt->execute([$id]);
        $pedido['itens'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get coupon information if there was a discount
        if ($pedido['desconto'] > 0) {
            $stmt = $this->conn->prepare("SELECT c.* FROM cupons c 
                                        JOIN pedidos p ON p.cupom_id = c.id 
                                        WHERE p.id = ?");
            $stmt->execute([$id]);
            $pedido['cupom'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $pedido;
    }

    public function atualizarStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    public function getItensPedido($id)
    {
        $stmt = $this->conn->prepare("SELECT produto_id, quantidade 
                                    FROM pedido_itens 
                                    WHERE pedido_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calcularFrete($subtotal)
    {
        if ($subtotal >= 200) {
            return 0;
        } elseif ($subtotal >= 52 && $subtotal <= 166.59) {
            return 15;
        }
        return 20;
    }
}
