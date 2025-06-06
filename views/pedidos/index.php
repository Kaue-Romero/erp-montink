<div class="row mb-4">
    <div class="col">
        <h2>Pedidos</h2>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Pedido criado com sucesso!
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo $pedido['hash_id']; ?></td>
                            <td><?php echo htmlspecialchars($pedido['cliente_nome']); ?></td>
                            <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge bg-<?php
                                                        echo match ($pedido['status']) {
                                                            'pendente' => 'warning',
                                                            'aprovado' => 'success',
                                                            'cancelado' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                        ?>">
                                    <?php echo ucfirst($pedido['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></td>
                            <td>
                                <a href="index.php?route=pedidos&action=details&id=<?php echo $pedido['hash_id']; ?>"
                                    class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do Pedido -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="orderDetails">
                    <!-- Detalhes do pedido serão carregados aqui -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));

        document.querySelectorAll('.view-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.id;

                fetch(`index.php?route=pedidos&action=details&id=${orderId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('orderDetails').innerHTML = html;
                        orderModal.show();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erro ao carregar detalhes do pedido');
                    });
            });
        });
    });
</script>