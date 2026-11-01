# 02 - Regras de negócio

Estas regras valem para toda a aplicação e devem ser implementadas preferencialmente na camada de serviços, nunca apenas na interface.

## 1. Unidades, quantidades e valores

- **RN-01** Todo produto possui uma unidade de medida: `un`, `kg`, `g`, `L`, `ml`, `pacote` ou `caixa`.
- **RN-02** Quantidades são armazenadas com 3 casas decimais (`DECIMAL(12,3)`). Exemplos: `2.500 kg`, `12.000 un`, `1.500 L`.
- **RN-03** Valores monetários são armazenados com 2 casas decimais (`DECIMAL(10,2)` ou `DECIMAL(12,2)`) em reais (BRL).
- **RN-04** Cálculos monetários em PHP usam `bcmath` ou aritmética em centavos; não usar `float` para totais.
- **RN-05** O total de uma linha é `quantidade x valor unitário`, arredondado para 2 casas. O total do documento é a soma das linhas.
- **RN-06** O fuso horário da aplicação é `America/Sao_Paulo`.

## 2. Estoque

- **RN-07** O estoque nunca pode ficar negativo.
- **RN-08** O saldo atual fica em `products.current_stock` para leitura rápida, mas é sempre atualizado na mesma transação da movimentação.
- **RN-09** Toda alteração de saldo gera um registro em `stock_movements`, com quantidade positiva (entrada) ou negativa (saída), saldo após o movimento, usuário, data e origem.
- **RN-10** Movimentações não são editadas nem excluídas. Correções são feitas por novos movimentos.
- **RN-11** A soma das movimentações de um produto deve ser igual ao seu `current_stock`. Divergências são reportadas na conciliação.
- **RN-12** Produtos desativados não podem ser vendidos nem receber compras, mas permanecem no histórico.
- **RN-13** Produtos que já possuem movimentações nunca são excluídos fisicamente; são desativados (`is_active = 0`).

## 3. Compras de reposição

- **RN-14** Somente o administrador registra compras.
- **RN-15** Uma compra possui data, fornecedor (opcional), documento (opcional), observações e um ou mais itens.
- **RN-16** Cada item possui produto, quantidade e custo unitário. O total da compra é calculado pelo sistema.
- **RN-17** A compra gera, na mesma transação: itens da compra, entrada de estoque, lote (quando o produto controla lote) e atualização do custo médio.
- **RN-18** Produto com `controls_lots = 1` exige código de lote e data de validade em cada item de compra.
- **RN-19** Um item de compra gera exatamente um lote. Se a mesma nota tiver dois lotes do mesmo produto, registrar duas linhas com códigos de lote diferentes.
- **RN-20** O custo médio ponderado é recalculado a cada entrada:

```
novo_custo = (saldo_atual * custo_atual + quantidade_entrada * custo_unitario) / (saldo_atual + quantidade_entrada)
```

Se `saldo_atual + quantidade_entrada = 0`, o custo permanece o anterior. O resultado é arredondado para 2 casas.

## 4. Vendas

- **RN-21** Administrador e operador podem registrar vendas.
- **RN-22** Uma venda contém um ou mais itens, cada um com produto, quantidade, preço unitário e custo unitário no momento da venda.
- **RN-23** O preço unitário é fixo e copiado de `products.sale_price` no momento da venda; não pode ser alterado na tela. Alterações futuras no cadastro não mudam vendas passadas.
- **RN-24** O custo unitário registrado na venda é o custo médio do produto no momento da venda.
- **RN-25** A venda é registrada em transação. O saldo é bloqueado/verificado antes da baixa; se não houver quantidade suficiente, a venda inteira é recusada.
- **RN-26** Para produtos com lote, a baixa consome primeiro os lotes que vencem primeiro (FEFO).
- **RN-27** Lotes vencidos não podem ser vendidos. Eles permanecem no estoque até serem baixados por perda/ajuste pelo administrador.
- **RN-28** Vendas não possuem cliente, pagamento, desconto, imposto ou frete nesta versão.
- **RN-29** O total da venda é a soma das linhas; não há desconto no MVP.
- **RN-30** Se um produto aparecer repetido na venda, o sistema consolida a quantidade em uma única linha ou impede a duplicidade; a regra deve ser consistente na interface e no servidor.

## 5. Cancelamento de venda

- **RN-31** Somente o administrador cancela vendas.
- **RN-32** Apenas venda com status `concluida` pode ser cancelada; o cancelamento é único.
- **RN-33** O motivo do cancelamento é obrigatório.
- **RN-34** O cancelamento devolve exatamente as quantidades aos lotes de origem (`sale_item_lots`) e recalcula o saldo.
- **RN-35** O cancelamento gera movimentações de estorno e altera a venda para `cancelada`, mantendo os itens e valores originais para auditoria.
- **RN-36** Se o lote devolvido estiver vencido, ele volta ao estoque, mas continua bloqueado para venda e aparece nos alertas de vencimento.
- **RN-59** Não existe devolução parcial de venda no MVP; o cancelamento é sempre total.

## 6. Ajustes e perdas

- **RN-37** Somente o administrador registra ajustes e perdas.
- **RN-38** Todo ajuste/perda exige produto, tipo, quantidade e motivo.
- **RN-39** Tipos: `ajuste_positivo` (entrada), `ajuste_negativo` (saída) e `perda` (saída).
- **RN-40** Nenhum ajuste pode deixar o saldo negativo.
- **RN-41** Ajuste de inventário compara a quantidade contada com o saldo do sistema e registra apenas a diferença.
- **RN-42** Em produto com lote, saídas por ajuste/perda consomem lotes por FEFO, salvo se o administrador escolher um lote específico. Entradas por ajuste criam um lote manual com código e validade informados.
- **RN-43** Ajustes e perdas não alteram o custo médio do produto, exceto ajuste positivo em que o administrador informe um custo unitário (útil na carga inicial); nesse caso, o valor entra no cálculo do custo médio.

## 7. Lotes e validade

- **RN-44** O controle de lote é configurável por produto (`products.controls_lots`).
- **RN-45** A categoria define o padrão de controle de lote (`categories.default_controls_lots`): Carnes usa 1, bebidas e demais categorias usam 0. O padrão apenas pré-seleciona o campo no cadastro; o administrador pode alterar.
- **RN-46** Para produto com lote, a soma de `product_lots.current_quantity` deve ser igual ao `products.current_stock`.
- **RN-47** O alerta padrão de vencimento considera lotes com saldo e validade em até 7 dias; o número é configurável.
- **RN-48** Lotes vencidos aparecem destacados e não entram no FEFO de venda.
- **RN-49** O código do lote é obrigatório e deve ser único dentro da compra para o mesmo produto.
- **RN-58** O campo `controls_lots` só pode ser alterado enquanto o produto não tiver movimentações. Depois da primeira movimentação, ele é imutável para preservar a consistência entre saldo e lotes.

## 8. Preços e custos

- **RN-50** `products.sale_price` é o preço de venda atual.
- **RN-51** `products.cost_price` é o custo médio ponderado atual.
- **RN-52** Vendas guardam o preço e o custo praticados no momento; relatórios históricos usam esses valores.
- **RN-53** Operadores não visualizam custo nem margem.

## 9. Permissões

| Ação | Administrador | Operador |
| --- | --- | --- |
| Entrar e sair do sistema | Sim | Sim |
| Ver painel, alertas e produtos | Sim | Sim |
| Ver custo e margem | Sim | Não |
| Cadastrar/editar/desativar produto | Sim | Não |
| Gerenciar categorias e fornecedores | Sim | Não |
| Registrar compra de reposição | Sim | Não |
| Ver compras e custos de compra | Sim | Não |
| Registrar venda | Sim | Sim |
| Cancelar venda | Sim | Não |
| Registrar ajuste ou perda | Sim | Não |
| Ver histórico de movimentações | Sim | Sim, sem valores financeiros |
| Ver relatórios financeiros | Sim | Não |
| Gerenciar usuários | Sim | Não |

## 10. Auditoria e integridade

- **RN-54** Toda operação que altera estoque registra usuário, data/hora, tipo, origem e saldo resultante.
- **RN-55** As operações críticas usam transação e bloqueio de linha (`SELECT ... FOR UPDATE`) na ordem crescente de `product_id` para evitar deadlocks.
- **RN-56** O servidor é a autoridade final: validações de tela podem ser contornadas e não substituem as regras no backend.
- **RN-57** Mensagens de erro não expõem SQL, credenciais ou stack trace para o usuário.
