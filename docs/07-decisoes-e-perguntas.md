# 07 - Decisões e perguntas respondidas

## 1. Decisões tomadas

| ID | Decisão | Justificativa |
| --- | --- | --- |
| ADR-01 | PHP 8.2+ puro com MVC simplificado e PDO | Estudo, simplicidade e controle total do código |
| ADR-02 | MySQL 8.0+ com InnoDB e utf8mb4 | Transações, integridade referencial e suporte a acentos |
| ADR-03 | Bootstrap 5 via CDN | Interface responsiva sem build de frontend |
| ADR-04 | Identificadores de código e banco em inglês; interface em português | Convenção comum e legibilidade para manutenção |
| ADR-05 | Quantidades em `DECIMAL(12,3)` com unidade por produto | Carne em kg, bebida em L/un e carvão em pacote |
| ADR-06 | Venda com múltiplos itens | Atende o fluxo real de balcão |
| ADR-07 | Snapshot de preço e custo nos itens de venda | Preserva o histórico mesmo após mudanças de cadastro |
| ADR-08 | Custo médio ponderado | Reflete melhor o custo real do estoque |
| ADR-09 | `current_stock` desnormalizado + livro `stock_movements` | Leitura rápida com auditoria completa |
| ADR-10 | FEFO e bloqueio de venda de lote vencido | Segurança alimentar e controle de perecíveis |
| ADR-11 | Exclusão lógica de cadastros | Preserva histórico e integridade referencial |
| ADR-12 | Cálculos monetários com `bcmath` | Evita erros de ponto flutuante |
| ADR-13 | Rotas em português | URLs mais claras para os usuários internos |
| ADR-14 | Ajustes, perdas e cancelamento exclusivos do administrador | Decisão confirmada; segregação de responsabilidade |
| ADR-15 | Operadores não veem custo nem margem | Decisão confirmada; protege informação sensível |
| ADR-16 | Sem cliente, pedido, pagamento ou nota fiscal | Fora do escopo declarado |
| ADR-17 | Preço unitário fixo no momento da venda | Copiado de `products.sale_price`, sem edição; evita desvios |
| ADR-18 | Docker Compose como ambiente local | Reprodutibilidade e instalação simples para estudo |
| ADR-19 | Carnes com lote por padrão; bebidas sem lote | `categories.default_controls_lots` reduz erro operacional e permite exceção por produto |
| ADR-20 | Fornecedor opcional na compra | Permite compras informais; o campo continua disponível |
| ADR-21 | Sem desconto e sem devolução parcial no MVP | Mantém o fluxo de venda simples e auditável |
| ADR-22 | Gestão de usuários pela interface | Admin cria, edita, desativa e redefine senha sem acessar o banco |
| ADR-23 | Sem importação de estoque inicial | Carga inicial por compra de reposição ou ajuste de inventário |
| ADR-24 | Nome definitivo "Estoque Churrasco" | Nome curto e claro para o contexto da loja |
| ADR-25 | CSV na fase 6 | MVP entrega relatórios na tela; exportação vem no acabamento |

## 2. Perguntas respondidas

| ID | Pergunta | Resposta confirmada |
| --- | --- | --- |
| Q-01 | Operadores podem ver custo, margem e relatórios financeiros? | Não; apenas preço de venda e relatórios operacionais |
| Q-02 | Operadores podem registrar ajustes, perdas ou cancelar vendas? | Não; somente o administrador |
| Q-03 | A venda deve bloquear lote vencido? | Sim; o lote vencido só sai por perda/ajuste do admin |
| Q-04 | O preço unitário da venda pode ser alterado? | Não; é fixo, copiado do cadastro |
| Q-05 | Carnes devem nascer com controle de lote e validade? | Sim, padrão ligado; o admin pode desativar em casos específicos |
| Q-06 | Qual ambiente local será usado? | Docker Compose com Apache + PHP + MySQL |
| Q-07 | Relatórios em CSV entram no MVP? | Não; ficam para a fase 6 |
| Q-08 | Como os usuários internos serão gerenciados? | CRUD na interface pelo administrador |
| Q-09 | Fornecedor é obrigatório na compra? | Não; compras informais podem ficar sem fornecedor |
| Q-10 | Deve existir desconto na venda? | Não no MVP |
| Q-11 | Deve existir devolução parcial de venda? | Não; apenas cancelamento total |
| Q-12 | Qual o nome definitivo do sistema? | Estoque Churrasco |
| Q-13 | Bebidas devem usar lote e validade por padrão? | Não; configurável por produto |
| Q-14 | Qual o limite de dias para alerta de vencimento? | 7 dias, configurável |
| Q-15 | Existe estoque atual para importar? | Não; começar vazio e carregar por compra ou ajuste |

## 3. Pendências

Nenhuma pergunta de escopo está aberta no momento. Novas dúvidas devem ser registradas aqui e respondidas antes de alterar o escopo ou o modelo de dados.

## 4. Premissas confirmadas

- Apenas três usuários internos, sem cadastro público.
- Um administrador principal. Se houver mais de um, a regra do último admin ativo continua valendo.
- Uma única loja.
- Vendas são registradas no momento da venda, não importadas de outro sistema.
- O preço de venda é único por produto, sem tabela de preços por cliente.
- O custo usado na venda é o custo médio do produto no momento.
- Operadores não veem custo nem margem.
- Ajustes, perdas e cancelamento são exclusivos do administrador.
- O preço unitário da venda é fixo e não editável.
- Lotes vencidos não podem ser vendidos.
- Produtos de Carnes usam lote por padrão; bebidas não.
- Não há desconto nem devolução parcial no MVP.
- Não há importação de estoque inicial; a carga é feita por compra ou ajuste.
- Fornecedor é opcional na compra.
- Usuários são gerenciados pela interface do administrador.
- O nome do sistema é "Estoque Churrasco".
- Ambiente local com Docker Compose.
- Não há integração com balança, leitor de código de barras ou impressora.
- A moeda é o real brasileiro e o fuso é `America/Sao_Paulo`.

## 5. Próximos passos

1. Criar o esqueleto do projeto, o `Dockerfile`, o `docker-compose.yml` e o `schema.sql`.
2. Implementar a Fase 0 e a Fase 1 do roadmap.
3. Revisar o modelo de dados com um fluxo real de compra, venda e cancelamento antes de avançar.
4. Manter estes documentos sincronizados com as decisões tomadas durante a implementação.
