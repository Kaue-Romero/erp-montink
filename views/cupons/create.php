<div class="row mb-4">
    <div class="col">
        <h2>Novo Cupom</h2>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?route=cupons&action=create">
            <div class="mb-3">
                <label for="codigo" class="form-label">Código do Cupom</label>
                <input type="text" class="form-control" id="codigo" name="codigo" required>
            </div>

            <div class="mb-3">
                <label for="desconto" class="form-label">Valor do Desconto (R$)</label>
                <input type="number" class="form-control" id="desconto" name="desconto" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="valor_minimo" class="form-label">Valor Mínimo do Pedido (R$)</label>
                <input type="number" class="form-control" id="valor_minimo" name="valor_minimo" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="data_inicio" class="form-label">Data de Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
            </div>

            <div class="mb-3">
                <label for="data_fim" class="form-label">Data de Término</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" required>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?route=cupons" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Criar Cupom</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set minimum date for date inputs
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_inicio').min = today;
        document.getElementById('data_fim').min = today;

        // Validate date range
        document.getElementById('data_inicio').addEventListener('change', function() {
            document.getElementById('data_fim').min = this.value;
        });

        document.getElementById('data_fim').addEventListener('change', function() {
            document.getElementById('data_inicio').max = this.value;
        });
    });
</script>