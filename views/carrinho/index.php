<div class="row mb-4">
    <div class="col">
        <h2>Carrinho de Compras</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <?php if (empty($carrinho)): ?>
            <div class="alert alert-info">
                Seu carrinho está vazio. <a href="index.php?route=produtos">Continue comprando</a>.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Preço</th>
                                    <th>Quantidade</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carrinho as $id => $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                                        <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm quantity-input"
                                                value="<?php echo $item['quantidade']; ?>"
                                                min="1"
                                                data-id="<?php echo $id; ?>"
                                                style="width: 80px;">
                                        </td>
                                        <td>R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?></td>
                                        <td>
                                            <button class="btn btn-danger btn-sm remove-item" data-id="<?php echo $id; ?>">
                                                Remover
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Resumo do Pedido</h5>
                <div class="mb-3">
                    <label class="form-label">Nome Completo*</label>
                    <input type="text" class="form-control" id="nome" placeholder="Digite seu nome completo" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mail*</label>
                    <input type="email" class="form-control" id="email" placeholder="Digite seu e-mail" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CEP*</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="cep" maxlength="9" placeholder="00000-000">
                        <button class="btn btn-outline-secondary" type="button" id="checkCep">Buscar</button>
                    </div>
                    <div id="cepError" class="mt-2" style="display: none;">
                        <small class="text-danger">
                            <i class="fas fa-exclamation-circle"></i> Formato inválido. Use 00000-000
                        </small>
                    </div>
                </div>

                <div id="addressForm" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Endereço*</label>
                        <input type="text" class="form-control" id="endereco" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bairro*</label>
                        <input type="text" class="form-control" id="bairro" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cidade/UF*</label>
                        <input type="text" class="form-control" id="cidade_uf" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cupom de Desconto</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="cupom">
                        <button class="btn btn-outline-secondary" type="button" id="applyCupom">Aplicar</button>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Frete:</span>
                    <span>R$ <?php echo number_format($frete, 2, ',', '.'); ?></span>
                </div>
                <?php if (isset($_SESSION['cupom'])): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Desconto:</span>
                        <div>
                            <span class="badge bg-success">
                                <i class="fas fa-ticket-alt me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['cupom']); ?>
                                (-R$ <?php echo number_format($desconto, 2, ',', '.'); ?>)
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong>
                </div>
                <div id="cupomSuccessMessage" class="alert alert-success mb-3" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="cupomSuccessText"></span>
                </div>
                <form id="checkoutForm" action="index.php?route=pedidos&action=create" method="POST">
                    <input type="hidden" name="nome" id="formNome">
                    <input type="hidden" name="email" id="formEmail">
                    <input type="hidden" name="cep" id="formCep">
                    <input type="hidden" name="endereco" id="formEndereco">
                    <input type="hidden" name="cupom" value="<?php echo isset($_SESSION['cupom']) ? $_SESSION['cupom'] : ''; ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        Finalizar Compra
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to attach event listeners
        function attachEventListeners() {
            // CEP validation and address fetch
            const cepInput = document.getElementById('cep');
            const checkCepBtn = document.getElementById('checkCep');
            const addressForm = document.getElementById('addressForm');
            const cepError = document.getElementById('cepError');

            // Function to apply CEP mask
            function applyCepMask(value) {
                // Remove all non-digits
                const numbers = value.replace(/\D/g, '');

                // Apply mask
                if (numbers.length <= 5) {
                    return numbers;
                } else {
                    return numbers.slice(0, 5) + '-' + numbers.slice(5, 8);
                }
            }

            // Function to validate CEP input
            function validateCepInput(input) {
                const value = input.value;
                const cleanValue = value.replace(/\D/g, '');
                const isValid = /^\d{8}$/.test(cleanValue);

                if (!isValid) {
                    cepError.style.display = 'block';
                } else {
                    cepError.style.display = 'none';
                }
            }

            // Add input event listener for real-time masking
            if (cepInput) {
                cepInput.addEventListener('input', function(e) {
                    const value = e.target.value;
                    const maskedValue = applyCepMask(value);
                    e.target.value = maskedValue;
                    validateCepInput(this);
                });

                // Add paste event listener to handle pasted content
                cepInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    const maskedValue = applyCepMask(pastedText);
                    this.value = maskedValue;
                    validateCepInput(this);
                });
            }

            // Add CEP search functionality
            if (checkCepBtn) {
                checkCepBtn.addEventListener('click', function() {
                    const cep = cepInput.value.replace(/\D/g, '');
                    if (cep.length !== 8) {
                        alert('CEP inválido');
                        return;
                    }

                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.erro) {
                                alert('CEP não encontrado');
                                return;
                            }

                            document.getElementById('endereco').value = `${data.logradouro}`;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade_uf').value = `${data.localidade}/${data.uf}`;
                            addressForm.style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Erro ao buscar CEP');
                        });
                });
            }

            // Reattach quantity input listeners
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const id = this.dataset.id;
                    const quantity = this.value;
                    const originalValue = this.defaultValue;

                    console.log('Quantity update attempt:', {
                        product_id: id,
                        new_quantity: quantity,
                        original_quantity: originalValue
                    });

                    // Validate quantity
                    if (quantity < 1) {
                        console.warn('Invalid quantity:', quantity);
                        alert('A quantidade deve ser maior que zero');
                        this.value = 1;
                        return;
                    }

                    // Disable input while updating
                    this.disabled = true;

                    console.log('Sending update request...');
                    fetch('index.php?route=carrinho&action=update', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'Accept': 'application/json'
                            },
                            body: `product_id=${id}&quantity=${quantity}`
                        })
                        .then(async response => {
                            console.log('Response status:', response.status);
                            const contentType = response.headers.get('content-type');
                            console.log('Response content type:', contentType);

                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }

                            // Check if the response is JSON
                            if (!contentType || !contentType.includes('application/json')) {
                                const text = await response.text();
                                console.error('Invalid response format:', text);
                                throw new Error('Resposta inválida do servidor');
                            }

                            return response.json();
                        })
                        .then(data => {
                            console.log('Server response:', data);
                            if (data.success) {
                                console.log('Update successful, updating cart...');
                                // Update cart content dynamically
                                fetch('index.php?route=carrinho&action=getCartContent')
                                    .then(response => response.text())
                                    .then(html => {
                                        // Update the cart table
                                        const parser = new DOMParser();
                                        const doc = parser.parseFromString(html, 'text/html');
                                        const newCartTable = doc.querySelector('.table-responsive');
                                        const currentCartTable = document.querySelector('.table-responsive');
                                        if (newCartTable && currentCartTable) {
                                            currentCartTable.innerHTML = newCartTable.innerHTML;
                                        }

                                        // Update order summary
                                        const newSummary = doc.querySelector('.col-md-4 .card-body');
                                        const currentSummary = document.querySelector('.col-md-4 .card-body');
                                        if (newSummary && currentSummary) {
                                            currentSummary.innerHTML = newSummary.innerHTML;
                                        }

                                        // Reattach event listeners
                                        attachEventListeners();
                                    })
                                    .catch(error => {
                                        console.error('Error updating cart:', error);
                                        alert('Erro ao atualizar carrinho. Por favor, recarregue a página.');
                                    });
                            } else {
                                console.error('Update failed:', {
                                    error: data.message,
                                    product_id: id,
                                    quantity: quantity
                                });
                                alert(data.message || 'Erro ao atualizar quantidade');
                                // Restore previous value on error
                                this.value = originalValue;
                            }
                        })
                        .catch(error => {
                            console.error('Error details:', {
                                message: error.message,
                                stack: error.stack,
                                product_id: id,
                                quantity: quantity,
                                original_quantity: originalValue
                            });
                            alert('Erro ao atualizar quantidade. Por favor, tente novamente. Detalhes no console (F12)');
                            // Restore previous value on error
                            this.value = originalValue;
                        })
                        .finally(() => {
                            console.log('Update process completed');
                            // Re-enable input
                            this.disabled = false;
                        });
                });
            });

            // Reattach remove item listeners
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Tem certeza que deseja remover este item?')) {
                        const id = this.dataset.id;
                        console.log('Removing item with cart_key:', id);

                        // Disable the button to prevent double clicks
                        this.disabled = true;

                        fetch('index.php?route=carrinho&action=remove', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: `cart_key=${encodeURIComponent(id)}`
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log('Response:', data);
                                if (data.success) {
                                    // Remove the row from the table
                                    const row = this.closest('tr');
                                    if (row) {
                                        row.remove();
                                    }

                                    // Update cart content dynamically
                                    fetch('index.php?route=carrinho&action=getCartContent')
                                        .then(response => response.text())
                                        .then(html => {
                                            // Update the cart table
                                            const parser = new DOMParser();
                                            const doc = parser.parseFromString(html, 'text/html');
                                            const newCartTable = doc.querySelector('.table-responsive');
                                            const currentCartTable = document.querySelector('.table-responsive');
                                            if (newCartTable && currentCartTable) {
                                                currentCartTable.innerHTML = newCartTable.innerHTML;
                                            }

                                            // Update order summary
                                            const newSummary = doc.querySelector('.col-md-4 .card-body');
                                            const currentSummary = document.querySelector('.col-md-4 .card-body');
                                            if (newSummary && currentSummary) {
                                                currentSummary.innerHTML = newSummary.innerHTML;
                                            }

                                            // Reattach event listeners
                                            attachEventListeners();
                                        })
                                        .catch(error => {
                                            console.error('Error updating cart:', error);
                                            alert('Erro ao atualizar carrinho. Por favor, recarregue a página.');
                                        });
                                } else {
                                    alert(data.message || 'Erro ao remover item');
                                    // Re-enable the button if there was an error
                                    this.disabled = false;
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Erro ao remover item: ' + error.message);
                                // Re-enable the button if there was an error
                                this.disabled = false;
                            });
                    }
                });
            });

            // Reattach apply coupon listener
            const applyCupomBtn = document.getElementById('applyCupom');
            if (applyCupomBtn) {
                applyCupomBtn.addEventListener('click', function() {
                    const cupom = document.getElementById('cupom').value;
                    const cupomSuccessMessage = document.getElementById('cupomSuccessMessage');
                    const cupomSuccessText = document.getElementById('cupomSuccessText');

                    // Disable button while processing
                    this.disabled = true;

                    fetch('index.php?route=carrinho&action=applyCupom', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `cupom=${encodeURIComponent(cupom)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                cupomSuccessMessage.style.display = 'block';
                                cupomSuccessText.textContent = data.message || 'Cupom aplicado com sucesso!';

                                // Update only the relevant parts of the summary
                                const summaryCard = document.querySelector('.col-md-4 .card-body');
                                if (summaryCard) {
                                    // Update subtotal
                                    const subtotalElement = Array.from(summaryCard.querySelectorAll('.d-flex')).find(el =>
                                        el.querySelector('span')?.textContent.includes('Subtotal')
                                    );
                                    if (subtotalElement) {
                                        subtotalElement.lastElementChild.textContent = `R$ ${data.subtotal.toFixed(2).replace('.', ',')}`;
                                    }

                                    // Update discount if present
                                    const discountElement = Array.from(summaryCard.querySelectorAll('.d-flex')).find(el =>
                                        el.querySelector('span')?.textContent.includes('Desconto')
                                    );
                                    if (data.desconto > 0) {
                                        if (!discountElement) {
                                            // Create discount element if it doesn't exist
                                            const newDiscountElement = document.createElement('div');
                                            newDiscountElement.className = 'd-flex justify-content-between mb-2';
                                            newDiscountElement.innerHTML = `
                                                <span>Desconto:</span>
                                                <div>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-ticket-alt me-1"></i>
                                                        ${cupom}
                                                        (-R$ ${Number(data.desconto).toFixed(2).replace('.', ',')})
                                                    </span>
                                                </div>
                                            `;
                                            // Insert before total
                                            const totalElement = Array.from(summaryCard.querySelectorAll('.d-flex')).find(el =>
                                                el.querySelector('strong')?.textContent.includes('Total')
                                            );
                                            if (totalElement) {
                                                totalElement.parentNode.insertBefore(newDiscountElement, totalElement);
                                            }
                                        } else {
                                            // Update existing discount element
                                            discountElement.querySelector('.badge').innerHTML = `
                                                <i class="fas fa-ticket-alt me-1"></i>
                                                ${cupom}
                                                (-R$ ${Number(data.desconto).toFixed(2).replace('.', ',')})
                                            `;
                                        }
                                    } else if (discountElement) {
                                        // Remove discount element if no discount
                                        discountElement.remove();
                                    }

                                    // Update total
                                    const totalElement = Array.from(summaryCard.querySelectorAll('.d-flex')).find(el =>
                                        el.querySelector('strong')?.textContent.includes('Total')
                                    );
                                    if (totalElement) {
                                        totalElement.lastElementChild.textContent = `R$ ${data.total.toFixed(2).replace('.', ',')}`;
                                    }
                                }
                            } else {
                                alert(data.message || 'Erro ao aplicar cupom');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Erro ao aplicar cupom');
                        })
                        .finally(() => {
                            // Re-enable button
                            this.disabled = false;
                        });
                });
            }

            // Add checkout form validation and submission
            const checkoutForm = document.getElementById('checkoutForm');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Get form values
                    const nome = document.getElementById('nome').value;
                    const email = document.getElementById('email').value;
                    const cep = document.getElementById('cep').value;
                    const endereco = document.getElementById('endereco').value;

                    // Validate required fields
                    if (!nome || !email || !cep || !endereco) {
                        alert('Por favor, preencha todos os campos obrigatórios.');
                        return;
                    }

                    // Validate email format
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        alert('Por favor, insira um e-mail válido.');
                        return;
                    }

                    // Validate CEP format
                    const cepRegex = /^\d{5}-\d{3}$/;
                    if (!cepRegex.test(cep)) {
                        alert('Por favor, insira um CEP válido no formato 00000-000.');
                        return;
                    }

                    // Set hidden form values
                    document.getElementById('formNome').value = nome;
                    document.getElementById('formEmail').value = email;
                    document.getElementById('formCep').value = cep.replace(/\D/g, '');
                    document.getElementById('formEndereco').value = endereco;

                    // Submit the form
                    this.submit();
                });
            }
        }

        // Initial attachment of event listeners
        attachEventListeners();
    });
</script>