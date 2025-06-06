<div class="row mb-4">
    <div class="col">
        <h2>Cupons de Desconto</h2>
    </div>
    <div class="col text-end">
        <a href="index.php?route=cupons&action=create" class="btn btn-primary">Novo Cupom</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Desconto</th>
                        <th>Valor Mínimo</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cupons as $cupom): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cupom['codigo']); ?></td>
                            <td>R$ <?php echo number_format($cupom['desconto'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($cupom['valor_minimo'], 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cupom['data_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cupom['data_fim'])); ?></td>
                            <td>
                                <?php
                                $hoje = new DateTime();
                                $inicio = new DateTime($cupom['data_inicio']);
                                $fim = new DateTime($cupom['data_fim']);

                                if ($hoje < $inicio) {
                                    echo '<span class="badge bg-warning">Pendente</span>';
                                } elseif ($hoje > $fim) {
                                    echo '<span class="badge bg-danger">Expirado</span>';
                                } else {
                                    echo '<span class="badge bg-success">Ativo</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>