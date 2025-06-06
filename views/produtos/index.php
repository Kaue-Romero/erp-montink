<div class="row mb-4">
    <div class="col">
        <h2>Produtos</h2>
    </div>
    <div class="col text-end">
        <a href="index.php?route=produtos&action=create" class="btn btn-primary">Novo Produto</a>
    </div>
</div>

<div class="row">
    <?php foreach ($produtos as $produto): ?>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                    <p class="card-text">
                        Preço: R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?><br>
                        Estoque: <?php echo $produto['quantidade']; ?> unidades
                    </p>
                    <?php
                    // Get variants for this product
                    $stmt = $conn->prepare("SELECT v.*, e.quantidade 
                                         FROM variacoes v 
                                         LEFT JOIN estoque e ON v.id = e.variacao_id 
                                         WHERE v.produto_id = ?");
                    $stmt->execute([$produto['id']]);
                    $variacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($variacoes)): ?>
                        <div class="variants-section mt-2">
                            <small class="text-muted">Variações disponíveis:</small>
                            <ul class="list-unstyled mb-2">
                                <?php foreach ($variacoes as $variacao): ?>
                                    <li>
                                        <small>
                                            <?php echo htmlspecialchars($variacao['nome']); ?>
                                            (<?php echo $variacao['quantidade']; ?> em estoque)
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between">
                        <a href="index.php?route=produtos&action=update&id=<?php echo $produto['id']; ?>"
                            class="btn btn-warning">Editar</a>
                        <button class="btn btn-success add-to-cart"
                            data-id="<?php echo $produto['id']; ?>"
                            data-nome="<?php echo htmlspecialchars($produto['nome']); ?>"
                            data-preco="<?php echo $produto['preco']; ?>">
                            Comprar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal do Carrinho -->
<div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar ao Carrinho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addToCartForm">
                    <input type="hidden" id="productId" name="product_id">
                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <input type="text" class="form-control" id="productName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantidade</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1">
                    </div>
                    <div class="mb-3" id="variantsContainer" style="display: none;">
                        <label class="form-label">Variação</label>
                        <select class="form-control" id="variation" name="variation_id">
                            <option value="">Selecione uma variação</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preço Unitário</label>
                        <input type="text" class="form-control" id="unitPrice" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total</label>
                        <input type="text" class="form-control" id="totalPrice" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="confirmAddToCart">Adicionar ao Carrinho</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
        const quantityInput = document.getElementById('quantity');
        const unitPriceInput = document.getElementById('unitPrice');
        const totalPriceInput = document.getElementById('totalPrice');
        const variantsContainer = document.getElementById('variantsContainer');
        const variationSelect = document.getElementById('variation');

        // Add to cart button click
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const nome = this.dataset.nome;
                const preco = parseFloat(this.dataset.preco);

                document.getElementById('productId').value = id;
                document.getElementById('productName').value = nome;
                unitPriceInput.value = preco.toFixed(2);

                // Reset variant selection
                variantsContainer.style.display = 'none';
                variationSelect.innerHTML = '<option value="">Selecione uma variação</option>';

                // Fetch variants for this product
                fetch(`index.php?route=produtos&action=getVariants&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.variants && data.variants.length > 0) {
                            variantsContainer.style.display = 'block';
                            variationSelect.innerHTML = '<option value="">Selecione uma variação</option>';
                            data.variants.forEach(variant => {
                                const option = document.createElement('option');
                                option.value = variant.id;
                                option.textContent = `${variant.nome} (${variant.quantidade} em estoque)`;
                                variationSelect.appendChild(option);
                            });
                        } else {
                            variantsContainer.style.display = 'none';
                            variationSelect.innerHTML = '<option value="">Selecione uma variação</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        variantsContainer.style.display = 'none';
                        variationSelect.innerHTML = '<option value="">Selecione uma variação</option>';
                    });

                updateTotal();
                cartModal.show();
            });
        });

        // Update total when quantity changes
        quantityInput.addEventListener('input', updateTotal);

        function updateTotal() {
            const quantity = parseInt(quantityInput.value) || 0;
            const unitPrice = parseFloat(unitPriceInput.value) || 0;
            const total = quantity * unitPrice;
            totalPriceInput.value = total.toFixed(2);
        }

        // Confirm add to cart
        document.getElementById('confirmAddToCart').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('addToCartForm'));
            const variationSelect = document.getElementById('variation');

            // If variants are shown and none is selected, show error
            if (variantsContainer.style.display === 'block' && !variationSelect.value) {
                alert('Por favor, selecione uma variação');
                return;
            }

            fetch('index.php?route=carrinho&action=add', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cartModal.hide();
                        alert('Produto adicionado ao carrinho!');
                    } else {
                        alert('Erro ao adicionar produto: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao adicionar produto ao carrinho');
                });
        });

        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Tem certeza que deseja excluir este produto?')) {
                    const id = this.dataset.id;
                    const button = this;

                    fetch('index.php?route=produtos&action=delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id=${id}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert(data.message || 'Erro ao excluir produto');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Erro ao excluir produto');
                        });
                }
            });
        });
    });
</script>