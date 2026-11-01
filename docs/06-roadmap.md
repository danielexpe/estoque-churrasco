# 06 - Roadmap

## 1. Estratégia

Construir em fatias verticais: cada fase entrega telas, regras, persistência e testes manuais de um fluxo completo. O MVP termina quando for possível cadastrar produto, comprar, vender, cancelar, ajustar e consultar o histórico com saldo consistente.

Esforço é relativo e serve apenas para planejamento de estudo, não é compromisso de prazo. A implantação começa sem estoque; a carga inicial será feita por compra de reposição ou ajuste de inventário.

## 2. Fases

### Fase 0 - Fundação

Esforço: pequeno.

Entregáveis:

- Estrutura de pastas, autoloader, bootstrap e configuração.
- Front controller e roteador com placeholders.
- Conexão PDO e tratamento de erros/logs.
- Layout base com Bootstrap 5, navegação e mensagens flash.
- Ambiente Docker Compose (Apache + PHP + MySQL) e `schema.sql` aplicado.
- Seed inicial de usuários, categorias e produtos de exemplo com saldo zero.

Critérios de aceite:

- [x] `/login` responde e renderiza o layout.
- [x] Rota inexistente retorna 404 amigável.
- [x] Erro inesperado é logado sem expor stack trace em produção.
- [x] Banco criado com todas as tabelas e FKs.
- [x] `docker compose up` sobe a aplicação e o banco local.

### Fase 1 - Autenticação e permissões

Esforço: pequeno.

Progresso: login, logout, sessão segura e CSRF já foram implementados junto com a Fase 0. Restam a gestão de usuários, o menu por perfil e a proteção do último administrador.

Entregáveis:

- Login, logout, sessão segura e CSRF.
- Middleware `auth` e `role:admin`.
- Menu adaptado ao perfil.
- Página de usuários (criar, editar, desativar, redefinir senha) para admin.

Critérios de aceite:

- [x] Usuário inativo não entra.
- [ ] Operador recebe 403 ao tentar rota administrativa.
- [x] Sessão é regenerada no login.
- [x] POST sem token CSRF é rejeitado.
- [ ] Último admin ativo não pode ser desativado.

### Fase 2 - Produtos, categorias e fornecedores

Esforço: médio.

Entregáveis:

- CRUD de categorias e fornecedores com desativação lógica.
- CRUD de produtos com unidade, preço, custo, mínimo e controle de lote.
- Padrão de lote por categoria (Carnes ligado, Bebidas desligado).
- Busca, filtros e paginação.
- Detalhe do produto com lotes e movimentações.
- Ocultação de custo para operador.

Critérios de aceite:

- [ ] Produto criado aparece na lista com saldo zero.
- [ ] Produto novo de Carnes já vem com controle de lote marcado.
- [ ] SKU e código de barras duplicados são recusados.
- [ ] Produto com movimentações não pode ser excluído, apenas desativado.
- [ ] Operador não acessa formulários administrativos.
- [ ] Valores monetários e quantidades são exibidos com a formatação correta.

### Fase 3 - Compras, lotes e custo médio

Esforço: médio/alto.

Entregáveis:

- Tela de compra com múltiplos itens.
- Entrada de estoque transacional.
- Criação de lotes e validade.
- Custo médio ponderado.
- Histórico de movimentações de entrada.

Critérios de aceite:

- [ ] Compra com dois itens gera duas entradas e soma corretamente o total.
- [ ] Produto com lote exige código e validade.
- [ ] Saldo do produto e soma dos lotes coincidem.
- [ ] Custo médio é recalculado corretamente em entradas sucessivas.
- [ ] Compra inválida não deixa dados parciais.

### Fase 4 - Vendas e cancelamento

Esforço: alto.

Entregáveis:

- Tela de venda com múltiplos itens e busca de produto.
- Baixa transacional com bloqueio de linha.
- Consumo FEFO e bloqueio de lote vencido.
- Preço fixo do cadastro, sem edição na venda.
- Snapshot de preço e custo.
- Detalhe da venda e cancelamento com estorno.

Critérios de aceite:

- [ ] Venda não permite saldo negativo, nem em requisições simultâneas.
- [ ] Produto com lote consome primeiro o que vence primeiro.
- [ ] Lote vencido não é vendido.
- [ ] Preço unitário não pode ser alterado na venda.
- [ ] Venda cancelada devolve exatamente as quantidades aos lotes de origem.
- [ ] Venda cancelada não pode ser cancelada novamente.
- [ ] Operador não consegue cancelar venda.

### Fase 5 - Ajustes, perdas, alertas e histórico

Esforço: médio.

Entregáveis:

- Ajuste positivo, ajuste negativo e perda com motivo.
- Alerta de estoque mínimo no painel e na lista de produtos.
- Alerta de lotes vencidos e a vencer.
- Histórico de movimentações com filtros.
- Conciliação de saldo.

Critérios de aceite:

- [ ] Ajuste não deixa saldo negativo.
- [ ] Perda reduz o saldo e registra motivo e responsável.
- [ ] Estoque baixo aparece no painel.
- [ ] Lote vencido aparece destacado e bloqueado para venda.
- [ ] Conciliação não encontra divergências após os fluxos testados.

### Fase 6 - Relatórios, acabamento e testes

Esforço: médio.

Entregáveis:

- Relatórios de estoque, estoque baixo, vencimentos e vendas por período.
- Exportação CSV (desejável).
- Revisão de acessibilidade, responsividade e mensagens.
- Testes automatizados dos services críticos.
- Documentação de instalação e backup.

Critérios de aceite:

- [ ] Relatórios batem com os dados das telas.
- [ ] Exportação CSV abre corretamente em planilha.
- [ ] Testes de venda, compra, ajuste e cancelamento passam.
- [ ] Instalação do zero funciona seguindo o README.
- [ ] Backup e restauração foram testados.

## 3. MVP mínimo

O MVP pode ser considerado entregue ao final da Fase 5, com:

- Login, perfis e gestão de usuários.
- Produtos, categorias e fornecedores.
- Compras com lote e custo médio.
- Vendas com FEFO e cancelamento.
- Ajustes e perdas.
- Alertas de estoque e validade.
- Histórico de movimentações.
- Saldo conciliado.

Relatórios completos e CSV ficam para a Fase 6.

## 4. Definição de pronto

Uma funcionalidade só é considerada pronta quando:

- [ ] Regra de negócio implementada no service, não só na tela.
- [ ] Permissão verificada no servidor.
- [ ] Entrada validada e saída escapada.
- [ ] Operação crítica em transação.
- [ ] Mensagens de sucesso e erro claras.
- [ ] Histórico/movimentação gerado quando altera estoque.
- [ ] Teste manual ou automatizado executado.
- [ ] Nenhuma credencial ou dado sensível no repositório.

## 5. Riscos e mitigação

| Risco | Impacto | Mitigação |
| --- | --- | --- |
| Complexidade de lotes e FEFO | Alto | Implementar e testar lotes antes das vendas; manter alocação isolada em `SaleService` |
| Concorrência entre operadores | Alto | Transação, `FOR UPDATE`, ordem de bloqueio e testes de venda simultânea |
| Erros de arredondamento | Médio | `DECIMAL` no banco e `bcmath` no PHP; testes de totais |
| Escopo crescer para pedidos/pagamentos | Alto | Manter a lista de fora do escopo e revisar antes de cada fase |
| Divergência entre saldo e livro | Médio | Movimentações imutáveis, conciliação e testes de sequência |
| Exposição de custos a operadores | Médio | Ocultar na interface e negar no backend |
| Perda de dados | Alto | Backup diário e restauração testada |
