<?php
require_once 'Model.php';

class Cupom extends Model
{
    protected $table = 'cupons';

    public function validarCupom($codigo, $subtotal)
    {
        $stmt = $this->conn->prepare("SELECT * FROM cupons 
                                    WHERE codigo = ? 
                                    AND data_inicio <= CURDATE() 
                                    AND data_fim >= CURDATE()");
        $stmt->execute([$codigo]);
        $cupom = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cupom) {
            return ['valid' => false, 'message' => 'Cupom inválido ou expirado'];
        }

        if ($subtotal < $cupom['valor_minimo']) {
            return [
                'valid' => false,
                'message' => "Valor mínimo para este cupom é R$ " . number_format($cupom['valor_minimo'], 2, ',', '.')
            ];
        }

        return [
            'valid' => true,
            'desconto' => $cupom['desconto']
        ];
    }

    public function getCuponsAtivos()
    {
        $stmt = $this->conn->query("SELECT * FROM cupons 
                                   WHERE data_inicio <= CURDATE() 
                                   AND data_fim >= CURDATE() 
                                   ORDER BY data_inicio DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
