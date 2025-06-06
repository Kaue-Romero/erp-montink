# Mini ERP Montink

Um sistema simples de ERP para controle de Pedidos, Produtos, Cupons e Estoque para Montink.

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)

## Instalação

1. Clone o repositório:

```bash
git clone https://github.com/seu-usuario/mini-erp.git
cd mini-erp
```

2. Crie o banco de dados e importe a estrutura:

```bash
mysql -u root -p < database.sql
```

3. Configure o arquivo `config/database.php` com suas credenciais do banco de dados:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'mini_erp');
```

4. Configure seu servidor web para apontar para o diretório do projeto.

5. Adicione o projeto no seu host virtual apache.

6. Acesse o projeto através da url: http://seu_dominio/mini-erp

Exemplo:

```bash
sudo a2ensite montink.conf
sudo systemctl restart apache2
```
ou

Adicione o projeto em /var/www/html/

```bash
sudo cp -r mini-erp /var/www/html/
```

E rode com o comando:

```bash
php -S localhost:8080
```

E acesse o projeto através da url: http://localhost:8080/mini-erp

## Funcionalidades

### Produtos

- Cadastro de produtos com nome, preço e estoque
- Suporte a variações de produtos
- Controle de estoque
- Edição de produtos

### Carrinho de Compras

- Adição de produtos ao carrinho
- Atualização de quantidades
- Remoção de produtos
- Cálculo automático de frete:
  - Grátis para pedidos acima de R$ 200,00
  - R$ 15,00 para pedidos entre R$ 52,00 e R$ 166,59
  - R$ 20,00 para outros valores

### Cupons

- Cadastro de cupons de desconto
- Validação de data de validade
- Regras de valor mínimo
- Aplicação de desconto no carrinho

### Pedidos

- Finalização de pedido com dados do cliente
- Integração com ViaCEP para validação de endereço
- Envio de e-mail de confirmação
- Webhook para atualização de status
- Restauração de estoque em caso de cancelamento

## Estrutura do Projeto

```
mini-erp/
├── config/
│   └── database.php
├── controllers/
│   ├── CarrinhoController.php
│   ├── CupomController.php
│   ├── PedidoController.php
│   └── ProdutoController.php
├── models/
├── views/
│   ├── carrinho/
│   ├── cupons/
│   ├── pedidos/
│   └── produtos/
├── public/
│   ├── css/
│   ├── js/
│   └── img/
├── database.sql
└── index.php
```

## Webhook

O sistema possui um endpoint de webhook para atualização de status de pedidos:

```
POST /index.php?route=pedidos&action=webhook
```

Exemplo de payload:

```json
{
  "id": 123,
  "status": "aprovado"
}
```

Status disponíveis:

- pendente
- aprovado
- cancelado
- entregue

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.
