<?php
require_once 'models/Cupom.php';

class CupomController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new Cupom($conn);
    }

    public function index()
    {
        $cupons = $this->model->findAll();
        include 'views/cupons/index.php';
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->model->create([
                    'codigo' => $_POST['codigo'],
                    'desconto' => $_POST['desconto'],
                    'valor_minimo' => $_POST['valor_minimo'],
                    'data_inicio' => $_POST['data_inicio'],
                    'data_fim' => $_POST['data_fim']
                ]);
                header('Location: index.php?route=cupons');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Código de cupom já existe";
                } else {
                    $error = "Erro ao criar cupom: " . $e->getMessage();
                }
            }
        }
        include 'views/cupons/create.php';
    }

    public function validate($codigo, $subtotal)
    {
        return $this->model->validarCupom($codigo, $subtotal);
    }
}
