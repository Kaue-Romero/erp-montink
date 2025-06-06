<div class="row mb-4">
    <div class="col">
        <h2>Novo Produto</h2>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?route=produtos&action=create">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Produto</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>

            <div class="mb-3">
                <label for="preco" class="form-label">Preço</label>
                <input type="number" class="form-control" id="preco" name="preco" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="quantidade" class="form-label">Quantidade em Estoque</label>
                <input type="number" class="form-control" id="quantidade" name="quantidade" required>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label">Variações</label>
                    <button type="button" class="btn btn-sm btn-secondary" id="addVariation">Adicionar Variação</button>
                </div>
                <div id="variationsContainer">
                    <!-- Variações serão adicionadas aqui -->
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?route=produtos" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const variationsContainer = document.getElementById('variationsContainer');
        const addVariationButton = document.getElementById('addVariation');
        let variationCount = 0;

        addVariationButton.addEventListener('click', function() {
            const variationDiv = document.createElement('div');
            variationDiv.className = 'row mb-2 variation-item';
            variationDiv.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" name="variacoes[${variationCount}][nome]" 
                       placeholder="Nome da Variação" required>
            </div>
            <div class="col-md-5">
                <input type="number" class="form-control" name="variacoes[${variationCount}][quantidade]" 
                       placeholder="Quantidade" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-variation">Remover</button>
            </div>
        `;

            variationsContainer.appendChild(variationDiv);
            variationCount++;

            // Add event listener to remove button
            variationDiv.querySelector('.remove-variation').addEventListener('click', function() {
                variationDiv.remove();
            });
        });
    });
</script>