# 01 - Escopo e requisitos

## 1. Objetivo

Implantar um sistema web interno para controlar o estoque de uma loja de artigos para churrasco. O sistema deve registrar entradas (compras de reposição), saídas (vendas), ajustes de inventário e perdas, mantendo o saldo atual e o histórico de cada movimentação.

O sistema não atende clientes, não processa pedidos e não processa pagamentos. Ele é uma ferramenta operacional para três funcionários.

## 2. Atores e permissões

| Ator | Quem é | Responsabilidades |
| --- | --- | --- |
| Administrador | Você, dono/gestor da loja | Cadastros de produtos, categorias, fornecedores e usuários; compras de reposição; ajustes/perdas; cancelamento de vendas; relatórios; também pode registrar vendas |
| Operador | Outras duas pessoas da empresa | Registrar vendas, consultar produtos, estoque, alertas e histórico de movimentações |
| Sistema | Aplicação | Validar regras, calcular saldos, bloquear operações indevidas e registrar auditoria |

Premissas confirmadas:

- Operadores não visualizam custo nem margem.
- Ajustes, perdas e cancelamentos são exclusivos do administrador.
- O administrador também pode registrar vendas.
- O preço unitário da venda é fixo, copiado do cadastro.
- Lotes vencidos não podem ser vendidos.
- Produtos de Carnes nascem com controle de lote ativado; bebidas, não.
- Não haverá desconto nem devolução parcial no MVP.
- Não haverá importação de estoque inicial; a carga será por compra ou ajuste.
- O nome do sistema é "Estoque Churrasco".
- O ambiente local usa Docker Compose.

## 3. Histórias de usuário

- Como administrador, quero cadastrar produtos com unidade de medida, preço de venda, custo e estoque mínimo para controlá-los corretamente.
- Como administrador, quero registrar uma compra de reposição com vários itens, fornecedor e dados de lote/validade para repor o estoque com rastreabilidade.
- Como operador, quero registrar uma venda com vários itens para dar baixa no estoque de uma só vez.
- Como administrador, quero cancelar uma venda para devolver os itens ao estoque e manter o histórico.
- Como administrador, quero registrar ajustes de inventário e perdas com justificativa para corrigir divergências.
- Como usuário, quero ver produtos abaixo do estoque mínimo e lotes próximos do vencimento.
- Como usuário, quero consultar o histórico de movimentações por produto, tipo e período.
- Como administrador, quero consultar relatórios de estoque e vendas para acompanhar a operação.

## 4. Requisitos funcionais

Prioridade: **E** = essencial para o MVP; **D** = desejável para uma etapa seguinte.

| ID | Requisito | Ator | Prioridade |
| --- | --- | --- | --- |
| RF-01 | Autenticar usuário por e-mail e senha, criar sessão e permitir logout | Todos | E |
| RF-02 | Autorizar ações por perfil (`admin` e `operador`) no servidor | Sistema | E |
| RF-03 | Gerenciar usuários internos (criar, editar, ativar/desativar, redefinir senha) | Admin | E |
| RF-04 | Gerenciar categorias de produtos | Admin | E |
| RF-05 | Cadastrar, editar, desativar e consultar produtos com unidade, preço de venda, custo, estoque mínimo e controle de lote | Admin | E |
| RF-06 | Consultar produtos com busca, filtros, paginação e indicação de estoque baixo | Todos | E |
| RF-07 | Gerenciar fornecedores | Admin | E |
| RF-08 | Registrar compra de reposição com vários itens, fornecedor, documento, data e custo unitário | Admin | E |
| RF-09 | Gerar entrada de estoque e lote/validade a partir da compra | Sistema | E |
| RF-10 | Registrar venda com vários itens, usando preço fixo do cadastro e custo no momento da venda | Admin e operador | E |
| RF-11 | Dar baixa atômica no estoque, impedindo saldo negativo | Sistema | E |
| RF-12 | Consumir lotes por FEFO e bloquear venda de lote vencido | Sistema | E |
| RF-13 | Cancelar venda concluída, devolvendo as quantidades aos lotes de origem | Admin | E |
| RF-14 | Registrar ajuste positivo, ajuste negativo e perda com motivo e responsável | Admin | E |
| RF-15 | Manter livro de movimentações com usuário, data, tipo, origem e saldo após o movimento | Sistema | E |
| RF-16 | Exibir alerta de produtos com saldo igual ou abaixo do mínimo | Todos | E |
| RF-17 | Exibir alerta de lotes vencidos e próximos do vencimento | Todos | E |
| RF-18 | Exibir painel com totais, alertas e movimentações recentes | Todos | E |
| RF-19 | Emitir relatórios de estoque atual, estoque baixo, vencimentos, vendas por período e movimentações | Admin | D |
| RF-20 | Exportar relatórios em CSV | Admin | D |
| RF-21 | Conciliar o saldo do produto com a soma das movimentações | Admin | E |
| RF-22 | Permitir configurar os dias de antecedência do alerta de vencimento | Admin | D |

## 5. Requisitos não funcionais

| ID | Requisito |
| --- | --- |
| RNF-01 | Segurança: senhas com `password_hash`, prepared statements, proteção CSRF, cookies de sessão `HttpOnly` e `SameSite`, autorização no servidor e HTTPS em produção |
| RNF-02 | Integridade: compras, vendas, cancelamentos e ajustes executados em transação; estoque nunca negativo |
| RNF-03 | Desempenho: telas comuns abaixo de 1 segundo em uso local; índices nas consultas frequentes; paginação nas listas |
| RNF-04 | Usabilidade: interface em português do Brasil, responsiva, mensagens de erro claras e confirmação em ações destrutivas |
| RNF-05 | Manutenibilidade: MVC simplificado, controllers finos, regras de estoque centralizadas em services, identificadores de código e banco em inglês |
| RNF-06 | Compatibilidade: navegadores modernos (Chrome, Firefox, Edge); PHP 8.2+; MySQL 8.0+ |
| RNF-07 | Privacidade: somente dados dos funcionários (nome, e-mail e senha); nenhum dado de cliente |
| RNF-08 | Operação: logs de erro, backup periódico do banco e restauração documentada |
| RNF-09 | Acessibilidade: formulários com `label`, foco visível, contraste adequado e navegação por teclado |
| RNF-10 | Rastreabilidade: nenhuma movimentação de estoque é editada ou apagada; correções são novos movimentos |

## 6. Limites do escopo

Fora do escopo nesta versão:

- Área pública, cadastro de clientes, carrinho, pedidos, delivery e pagamentos.
- Nota fiscal, impostos, integrações fiscais e meios de pagamento.
- Integração com e-commerce, marketplace, ERP ou aplicativo móvel.
- Múltiplas lojas/filiais e transferência entre lojas.
- Contas a pagar/receber, fluxo de caixa e controle financeiro completo.
- Leitor de código de barras integrado (o campo pode existir, mas sem hardware).
- Fotos de produtos, etiquetas e impressão.

## 7. Glossário

| Termo | Significado |
| --- | --- |
| Produto | Item vendido ou estocado (ex.: picanha, saco de carvão, refrigerante) |
| Unidade | Forma de medir o produto: `un`, `kg`, `g`, `L`, `ml`, `pacote`, `caixa` |
| Lote | Quantidade de um produto recebida em uma compra, com código e validade |
| Validade | Data-limite de uso/venda de um lote |
| FEFO | First Expire, First Out: sai primeiro o lote que vence primeiro |
| Movimentação | Registro de entrada, saída, estorno, ajuste ou perda de estoque |
| Ajuste | Correção de saldo após contagem física ou erro operacional |
| Perda | Saída sem venda, por avaria, vencimento, quebra ou outro motivo |
| Estoque mínimo | Quantidade de referência para alertar reposição |
| Custo médio ponderado | Custo unitário recalculado a cada entrada, ponderado pelo saldo existente |
| Snapshot | Cópia de um valor (preço ou custo) no momento da venda, preservada no histórico |
