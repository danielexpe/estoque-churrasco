# 05 - Rotas e telas

## 1. Convenções

- Rotas em português, minúsculas e com hífen quando necessário.
- Métodos HTTP reais; formulários usam POST com campo oculto `_method` apenas se necessário.
- Toda rota, exceto login, exige autenticação.
- Rotas administrativas exigem `role:admin`.
- Todo POST exige token CSRF.
- Respostas de sucesso usam redirect + flash; erros de validação retornam o formulário com os dados preenchidos.

## 2. Rotas

| Método | Caminho | Controller/ação | Permissão | Descrição |
| --- | --- | --- | --- | --- |
| GET | `/login` | AuthController@showLogin | Visitante | Formulário de login |
| POST | `/login` | AuthController@login | Visitante | Autenticar |
| POST | `/logout` | AuthController@logout | Autenticado | Encerrar sessão |
| GET | `/` | DashboardController@index | Autenticado | Painel |
| GET | `/produtos` | ProductController@index | Autenticado | Lista e busca de produtos |
| GET | `/produtos/{id}` | ProductController@show | Autenticado | Detalhe, lotes e histórico |
| GET | `/produtos/novo` | ProductController@create | Admin | Formulário de produto |
| POST | `/produtos` | ProductController@store | Admin | Criar produto |
| GET | `/produtos/{id}/editar` | ProductController@edit | Admin | Editar produto |
| POST | `/produtos/{id}` | ProductController@update | Admin | Atualizar produto |
| POST | `/produtos/{id}/desativar` | ProductController@deactivate | Admin | Desativar produto |
| POST | `/produtos/{id}/ativar` | ProductController@activate | Admin | Reativar produto |
| GET | `/categorias` | CategoryController@index | Admin | Lista de categorias |
| GET | `/categorias/nova` | CategoryController@create | Admin | Formulário |
| POST | `/categorias` | CategoryController@store | Admin | Criar |
| GET | `/categorias/{id}/editar` | CategoryController@edit | Admin | Editar |
| POST | `/categorias/{id}` | CategoryController@update | Admin | Atualizar |
| POST | `/categorias/{id}/desativar` | CategoryController@deactivate | Admin | Desativar |
| POST | `/categorias/{id}/ativar` | CategoryController@activate | Admin | Reativar |
| GET | `/fornecedores` | SupplierController@index | Admin | Lista de fornecedores |
| GET | `/fornecedores/novo` | SupplierController@create | Admin | Formulário |
| POST | `/fornecedores` | SupplierController@store | Admin | Criar |
| GET | `/fornecedores/{id}/editar` | SupplierController@edit | Admin | Editar |
| POST | `/fornecedores/{id}` | SupplierController@update | Admin | Atualizar |
| POST | `/fornecedores/{id}/desativar` | SupplierController@deactivate | Admin | Desativar |
| POST | `/fornecedores/{id}/ativar` | SupplierController@activate | Admin | Reativar |
| GET | `/compras` | PurchaseController@index | Admin | Lista de compras |
| GET | `/compras/nova` | PurchaseController@create | Admin | Registrar compra |
| POST | `/compras` | PurchaseController@store | Admin | Salvar compra e dar entrada |
| GET | `/compras/{id}` | PurchaseController@show | Admin | Detalhe da compra |
| GET | `/vendas` | SaleController@index | Autenticado | Lista de vendas |
| GET | `/vendas/nova` | SaleController@create | Autenticado | Registrar venda |
| POST | `/vendas` | SaleController@store | Autenticado | Salvar venda e dar baixa |
| GET | `/vendas/{id}` | SaleController@show | Autenticado | Detalhe da venda |
| POST | `/vendas/{id}/cancelar` | SaleController@cancel | Admin | Cancelar e estornar |
| GET | `/ajustes` | AdjustmentController@index | Admin | Lista de ajustes/perdas |
| GET | `/ajustes/novo` | AdjustmentController@create | Admin | Formulário |
| POST | `/ajustes` | AdjustmentController@store | Admin | Registrar ajuste/perda |
| GET | `/movimentacoes` | MovementController@index | Autenticado | Histórico filtrável |
| GET | `/relatorios/estoque` | ReportController@stock | Autenticado | Estoque atual |
| GET | `/relatorios/estoque.csv` | ReportController@stockCsv | Autenticado | CSV do estoque atual |
| GET | `/relatorios/estoque-baixo` | ReportController@lowStock | Autenticado | Estoque baixo |
| GET | `/relatorios/estoque-baixo.csv` | ReportController@lowStockCsv | Autenticado | CSV do estoque baixo |
| GET | `/relatorios/vencimentos` | ReportController@expiring | Autenticado | Lotes vencidos/a vencer |
| GET | `/relatorios/vencimentos.csv` | ReportController@expiringCsv | Autenticado | CSV de vencimentos |
| GET | `/relatorios/conciliacao` | ReportController@reconciliation | Admin | Conciliação de estoque |
| GET | `/relatorios/vendas` | ReportController@sales | Admin | Vendas por período |
| GET | `/relatorios/vendas.csv` | ReportController@salesCsv | Admin | CSV de vendas por período |
| GET | `/usuarios` | UserController@index | Admin | Lista de usuários |
| GET | `/usuarios/novo` | UserController@create | Admin | Formulário |
| POST | `/usuarios` | UserController@store | Admin | Criar usuário |
| GET | `/usuarios/{id}/editar` | UserController@edit | Admin | Editar |
| POST | `/usuarios/{id}` | UserController@update | Admin | Atualizar/redefinir senha |
| POST | `/usuarios/{id}/desativar` | UserController@deactivate | Admin | Desativar |
| POST | `/usuarios/{id}/ativar` | UserController@activate | Admin | Reativar |

## 3. Telas

### 3.1 Login

- E-mail, senha e botão entrar.
- Mensagem de erro genérica: "E-mail ou senha inválidos."
- Não informar se o e-mail existe.
- Bloquear usuário inativo.

### 3.2 Painel

- Cartões: total de produtos ativos, produtos com estoque baixo, lotes vencidos, lotes a vencer em 7 dias.
- Para administrador: vendas de hoje, total vendido hoje (opcional).
- Lista curta de produtos com estoque baixo.
- Lista curta de lotes a vencer.
- Últimas movimentações.

### 3.3 Produtos

Lista:

- Colunas: nome, categoria, unidade, saldo atual, estoque mínimo, preço de venda, custo (admin), status.
- Busca por nome, SKU ou código de barras; filtro por categoria, status e estoque baixo.
- Paginação de 20 itens por página, preservando os filtros.
- Badge "estoque baixo" quando `current_stock <= min_stock`.
- Ações administrativas: editar, desativar/ativar e link para detalhe.
- Operador vê a lista sem custo e sem ações de cadastro.

Formulário:

- Nome, categoria, SKU, código de barras, unidade, preço de venda, custo atual, estoque mínimo e controla lote.
- Categoria e controle de lote: a categoria ativa o padrão; o controle de lote não pode ser alterado após o primeiro movimento.
- O status é alterado pelas ações de ativar/desativar, não pelo formulário.
- O saldo inicial não é editável diretamente no cadastro; entradas e ajustes geram movimentações. Produto novo começa com saldo zero.

Detalhe:

- Dados do produto, saldo, custo (admin), lotes e últimas movimentações.
- Tabelas de lotes e movimentações com estados vazios enquanto não houver compras ou ajustes.

### 3.4 Compras

Lista:

- Data, fornecedor, documento, itens, total e responsável.
- Filtro por período e fornecedor; paginação de 20 itens.
- Acesso somente admin.

Nova compra:

- Fornecedor (opcional), data, documento e observações.
- Linhas de item: produto, quantidade, custo unitário, lote e validade quando o produto controla lote.
- Lote e validade são exigidos pelo servidor para produtos com controle de lote.
- Subtotal por linha e total da compra calculados no servidor com `bcmath`.
- Botão adicionar/remover linha; a interface atualiza o total enquanto o usuário digita.
- Ao salvar: entrada de estoque, criação de lote, movimentação e atualização do custo médio na mesma transação.

Detalhe:

- Cabeçalho, itens com lote/validade, total e movimentações geradas.
- Lotes vencidos ou próximos do vencimento são destacados.

### 3.5 Vendas

Lista:

- Data, responsável, itens, total e status.
- Filtro por período e status; paginação de 20 itens.

Nova venda:

- Seleção de produto ativo com preço, unidade e quantidade disponível para venda.
- A disponibilidade ignora lotes vencidos; o servidor revalida com bloqueio de linha.
- Linhas com produto, quantidade, preço fixo e subtotal.
- Preço preenchido a partir do cadastro e não editável na venda.
- Itens repetidos do mesmo produto são consolidados antes da baixa.
- Total calculado no servidor com `bcmath`.
- Aviso quando a quantidade pedida excede o disponível.
- Ao salvar: baixa FEFO, movimentações e redirecionamento para o detalhe.

Detalhe:

- Itens, preços, custo (admin), lotes consumidos, status e dados de cancelamento.
- Botão cancelar (admin) com motivo obrigatório de 5 a 500 caracteres.
- Operador não vê custo nem botão de cancelamento.

### 3.6 Ajustes e perdas

Lista:

- Data, produto, tipo, quantidade, custo unitário (quando informado), motivo e responsável.
- Filtro por período e tipo; paginação de 20 itens.

Formulário:

- Produto, tipo (`ajuste_positivo`, `ajuste_negativo`, `perda`), quantidade, lote/validade (ajuste positivo de produto com lote), custo unitário (opcional no ajuste positivo) e motivo.
- Ajuste positivo de produto com lote cria um lote manual com validade.
- Saídas por ajuste ou perda consomem os lotes por FEFO, incluindo vencidos.
- Custo informado no ajuste positivo entra no custo médio ponderado.
- Confirmação antes de salvar.

### 3.7 Movimentações

- Filtros: produto, tipo, período e usuário; paginação de 20 itens.
- Colunas: data, produto, lote, tipo, quantidade, saldo após, origem, responsável e observações.
- Operador não vê valores financeiros.
- Admin acessa a conciliação de estoque a partir desta tela.

### 3.8 Relatórios

- Navegação por abas: Estoque, Estoque baixo, Vencimentos e, para admin, Vendas e Conciliação.
- Estoque atual: produto, categoria, unidade, saldo, mínimo, preço e controle de lote.
- Estoque baixo: ordenado pela diferença para o mínimo.
- Vencimentos: lotes vencidos e a vencer, com validade, saldo e situação.
- Conciliação: saldo do produto, soma das movimentações e soma dos lotes, com divergências destacadas.
- Vendas por período: filtro de datas com padrão no mês atual; cards de vendas concluídas, total vendido, linhas de itens e canceladas; tabelas separadas de concluídas e canceladas.
- Exportação CSV em todos os relatórios, com BOM UTF-8, separador `;` e nomes de arquivo estáveis.
- Operador acessa estoque, estoque baixo, vencimentos e seus CSVs; vendas e conciliação são exclusivas do admin.

### 3.9 Usuários

- Lista: nome, e-mail, perfil, status, data de criação e ações.
- Formulário: nome, e-mail, perfil, senha e confirmação; na edição, a senha é opcional.
- Ações de ativar e desativar, com confirmação na desativação.
- Não permitir desativar nem remover o perfil do último administrador ativo.
- Redefinição de senha pelo administrador.
- Menu "Usuários" visível apenas para administradores.

## 4. Validações

| Tela | Campo | Regra |
| --- | --- | --- |
| Login | e-mail | Obrigatório, formato válido |
| Login | senha | Obrigatória |
| Produto | nome | Obrigatório, 3 a 160 caracteres |
| Produto | categoria | Deve existir e estar ativa, se informada |
| Produto | SKU | Único, se informado, até 60 caracteres |
| Produto | código de barras | Único, se informado, até 60 caracteres |
| Produto | unidade | Um dos valores permitidos |
| Produto | preço de venda | Maior ou igual a zero, 2 casas |
| Produto | custo | Maior ou igual a zero, 2 casas |
| Produto | estoque mínimo | Maior ou igual a zero, 3 casas |
| Produto | controla lote | Booleano, pré-selecionado por `categories.default_controls_lots`; alterável somente enquanto o produto não tiver movimentações |
| Fornecedor | nome | Obrigatório, até 160 caracteres |
| Fornecedor | e-mail | Formato válido, se informado |
| Compra | fornecedor | Deve existir e estar ativo, se informado |
| Compra | data | Data válida, não futura |
| Compra | itens | Pelo menos um item |
| Compra | produto do item | Deve existir e estar ativo |
| Compra | quantidade | Maior que zero, 3 casas |
| Compra | custo unitário | Maior ou igual a zero, 2 casas |
| Compra | lote/validade | Obrigatórios quando o produto controla lote |
| Compra | lote | Código único por produto dentro da compra |
| Venda | itens | Pelo menos um item |
| Venda | produto | Ativo, com saldo suficiente |
| Venda | quantidade | Maior que zero, 3 casas |
| Venda | preço unitário | Fixo, copiado do cadastro; não editável na venda |
| Venda | lote | Deve haver lote válido e não vencido quando o produto controla lote |
| Cancelamento | motivo | Obrigatório, 5 a 500 caracteres; venda deve estar concluída |
| Ajuste | tipo | Um dos tipos permitidos |
| Ajuste | quantidade | Maior que zero; saída não pode zerar negativamente |
| Ajuste | motivo | Obrigatório, 5 a 500 caracteres |
| Ajuste | lote/validade | Obrigatórios para produto com lote, na entrada e na saída; na entrada, criam um lote manual |
| Ajuste | custo unitário | Opcional apenas no ajuste positivo; se informado, entra no custo médio |
| Usuário | nome | Obrigatório, até 120 caracteres |
| Usuário | e-mail | Obrigatório, válido e único |
| Usuário | perfil | `admin` ou `operador` |
| Usuário | senha | Mínimo 8 caracteres, com confirmação |
| Filtros | período | Data inicial menor ou igual à final |

## 5. Fluxo de venda

1. Usuário abre "Nova venda".
2. Busca o produto e adiciona à lista.
3. Informa a quantidade respeitando a unidade (ex.: 1,250 kg).
4. Repete para os demais itens.
5. Confere o total.
6. Confirma.
7. Servidor abre transação, bloqueia produtos, valida saldo, aloca lotes FEFO, grava venda, itens, lotes, movimentações e atualiza saldos.
8. Em caso de sucesso, redireciona para o detalhe da venda com mensagem de sucesso.
9. Em caso de erro, desfaz tudo e mostra a mensagem, preservando a seleção.

## 6. Fluxo de compra

1. Administrador abre "Nova compra".
2. Informa fornecedor, data, documento e observações.
3. Adiciona itens com produto, quantidade e custo.
4. Para produtos com lote, informa código e validade.
5. Confere o total.
6. Confirma.
7. Servidor grava compra, itens, lotes, movimentações, saldo e custo médio na mesma transação.
8. Redireciona para o detalhe da compra.

## 7. Mensagens e estados vazios

- "Nenhum produto encontrado para os filtros informados."
- "Nenhuma venda registrada no período."
- "Estoque insuficiente para o produto X. Disponível: Y."
- "O lote X está vencido e não pode ser vendido."
- "Venda cancelada com sucesso. Estoque devolvido."
- "Não é possível desativar o último administrador ativo."
- "Registro desativado. O histórico foi preservado."
