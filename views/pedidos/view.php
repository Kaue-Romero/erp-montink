<?php
if (!isset($pedido)) {
    header('Location: index.php?route=pedidos');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="mb-0">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                    Detalhes do Pedido
                </h1>
            </div>
            <div class="col text-end">
                <a href="index.php?route=pedidos" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar para Pedidos
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-9">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-light">
                        <h2 class="h5 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informações do Pedido
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-hashtag text-muted me-2"></i>
                                    <strong>Número do Pedido:</strong> <?php echo htmlspecialchars($pedido['hash_id']); ?>
                                </p>
                                <p class="mb-2">
                                    <i class="far fa-calendar-alt text-muted me-2"></i>
                                    <strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-0">
                                    <i class="fas fa-tag text-muted me-2"></i>
                                    <strong>Status:</strong>
                                    <span class="badge bg-<?php
                                                            echo match ($pedido['status']) {
                                                                'pendente' => 'warning',
                                                                'aprovado' => 'success',
                                                                'cancelado' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                            ?> p-2">
                                        <i class="fas <?php
                                                        echo match ($pedido['status']) {
                                                            'pendente' => 'fa-clock',
                                                            'aprovado' => 'fa-check-circle',
                                                            'cancelado' => 'fa-times-circle',
                                                            default => 'fa-info-circle'
                                                        };
                                                        ?> me-1"></i>
                                        <?php echo ucfirst($pedido['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-light">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-user me-2"></i>
                            Informações do Cliente
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-user-circle text-muted me-2"></i>
                                    <strong>Nome:</strong> <?php echo htmlspecialchars($pedido['cliente_nome']); ?>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-envelope text-muted me-2"></i>
                                    <strong>E-mail:</strong> <?php echo htmlspecialchars($pedido['cliente_email']); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                    <strong>CEP:</strong> <?php echo htmlspecialchars($pedido['cep']); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-home text-muted me-2"></i>
                                    <strong>Endereço:</strong> <?php echo htmlspecialchars($pedido['endereco']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-box me-2"></i>
                            Itens do Pedido
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-center">Quantidade</th>
                                        <th class="text-end">Preço Unitário</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedido['itens'] as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['produto_nome']); ?></td>
                                            <td class="text-center"><?php echo $item['quantidade']; ?></td>
                                            <td class="text-end">R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                            <td class="text-end">R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm sticky-top" style="top: 1rem;">
                    <div class="card-header bg-light">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-receipt me-2"></i>
                            Resumo do Pedido
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-shopping-basket text-muted me-2"></i>Subtotal:</span>
                            <span>R$ <?php echo number_format($pedido['subtotal'], 2, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-truck text-muted me-2"></i>Frete:</span>
                            <span>R$ <?php echo number_format($pedido['frete'], 2, ',', '.'); ?></span>
                        </div>
                        <?php if ($pedido['desconto'] > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-tag text-muted me-2"></i>Desconto:</span>
                                <span class="text-success">- R$ <?php echo number_format($pedido['desconto'], 2, ',', '.'); ?></span>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between mb-0">
                            <strong><i class="fas fa-dollar-sign text-muted me-2"></i>Total:</strong>
                            <strong class="text-primary">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>