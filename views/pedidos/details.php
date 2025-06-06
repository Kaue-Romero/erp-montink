<div class="row mb-4">
    <div class="col">
        <h2 class="mb-0">
            <i class="fas fa-shopping-cart text-primary me-2"></i>
            Pedido #<?php echo $pedido['hash_id']; ?>
        </h2>
        <small class="text-muted">
            <i class="far fa-calendar-alt me-1"></i>
            <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
        </small>
    </div>
    <div class="col text-end">
        <a href="index.php?route=pedidos" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-box me-2"></i>
                    Itens do Pedido
                </h5>
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

    <div class="col-md-4">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Informações do Cliente
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <i class="fas fa-user-circle text-muted me-2"></i>
                    <strong>Nome:</strong> <?php echo htmlspecialchars($pedido['cliente_nome']); ?>
                </p>
                <p class="mb-2">
                    <i class="fas fa-envelope text-muted me-2"></i>
                    <strong>E-mail:</strong> <?php echo htmlspecialchars($pedido['cliente_email']); ?>
                </p>
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

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-receipt me-2"></i>
                    Resumo do Pedido
                </h5>
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
                    <?php if (isset($pedido['cupom'])): ?>
                        <div class="alert alert-success mb-2 py-2">
                            <small>
                                <i class="fas fa-ticket-alt me-1"></i>
                                Cupom aplicado: <strong><?php echo htmlspecialchars($pedido['cupom']['codigo']); ?></strong>
                            </small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong><i class="fas fa-dollar-sign text-muted me-2"></i>Total:</strong>
                    <strong class="text-primary">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
                </div>
                <div class="mt-3">
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
                </div>
            </div>
        </div>
    </div>
</div>