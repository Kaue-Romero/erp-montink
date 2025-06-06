<?php
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Confirmação do Pedido</h5>
                </div>
                <div class="card-body">
                    <h6>Itens do Pedido</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Preço Unitário</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carrinho as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                                        <td><?php echo $item['quantidade']; ?></td>
                                        <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informações do Cliente</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($_POST['nome']); ?></p>
                    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($_POST['email']); ?></p>
                    <p><strong>CEP:</strong> <?php echo htmlspecialchars($_POST['cep']); ?></p>
                    <p><strong>Endereço:</strong> <?php echo htmlspecialchars($_POST['endereco']); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resumo do Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Frete:</span>
                        <span>R$ <?php echo number_format($frete, 2, ',', '.'); ?></span>
                    </div>
                    <?php if (isset($_SESSION['cupom'])): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Desconto:</span>
                            <span>-R$ <?php echo number_format($desconto, 2, ',', '.'); ?></span>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong>
                    </div>
                    <form action="index.php?route=pedidos&action=create" method="POST">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($_POST['nome']); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email']); ?>">
                        <input type="hidden" name="cep" value="<?php echo htmlspecialchars($_POST['cep']); ?>">
                        <input type="hidden" name="endereco" value="<?php echo htmlspecialchars($_POST['endereco']); ?>">
                        <input type="hidden" name="cupom" value="<?php echo isset($_POST['cupom']) ? htmlspecialchars($_POST['cupom']) : ''; ?>">
                        <input type="hidden" name="confirm" value="1">
                        <button type="submit" class="btn btn-success w-100">Confirmar Pedido</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>