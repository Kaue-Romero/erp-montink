<div class="row mb-4">
    <div class="col">
        <h2>Editar Produto #<?php echo isset($produto['id']) ? $produto['id'] : ''; ?></h2>
    </div>
    <div class="col text-end">
        <a href="index.php?route=produtos" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
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

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($produto) && isset($produto['id'])): ?>
            <form method="POST" action="index.php?route=produtos&action=update&id=<?php echo $produto['id']; ?>">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Produto</label>
                    <input type="text" class="form-control" id="nome" name="nome"
                        value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Preço</label>
                    <input type="number" class="form-control" id="preco" name="preco" step="0.01"
                        value="<?php echo $produto['preco']; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="quantidade" class="form-label">Quantidade em Estoque</label>
                    <input type="number" class="form-control" id="quantidade" name="quantidade"
                        value="<?php echo $produto['quantidade']; ?>" required>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label">Variações</label>
                        <button type="button" class="btn btn-sm btn-secondary" id="addVariation">Adicionar Variação</button>
                    </div>
                    <div id="variationsContainer">
                        <?php
                        // Get existing variants
                        $stmt = $conn->prepare("SELECT v.*, e.quantidade 
                                             FROM variacoes v 
                                             LEFT JOIN estoque e ON v.id = e.variacao_id 
                                             WHERE v.produto_id = ?");
                        $stmt->execute([$produto['id']]);
                        $variacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($variacoes as $index => $variacao): ?>
                            <div class="row mb-2 variation-item">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="variacoes[<?php echo $index; ?>][nome]"
                                        value="<?php echo htmlspecialchars($variacao['nome']); ?>"
                                        placeholder="Nome da Variação" required>
                                    <input type="hidden" name="variacoes[<?php echo $index; ?>][id]"
                                        value="<?php echo $variacao['id']; ?>">
                                </div>
                                <div class="col-md-5">
                                    <input type="number" class="form-control" name="variacoes[<?php echo $index; ?>][quantidade]"
                                        value="<?php echo $variacao['quantidade']; ?>"
                                        placeholder="Quantidade" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger remove-variation">Remover</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php?route=produtos" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                Produto não encontrado ou ID inválido.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const variationsContainer = document.getElementById('variationsContainer');
        const addVariationButton = document.getElementById('addVariation');
        let variationCount = document.querySelectorAll('.variation-item').length;

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

        // Add event listeners to existing remove buttons
        document.querySelectorAll('.remove-variation').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.variation-item').remove();
            });
        });
    });
</script>