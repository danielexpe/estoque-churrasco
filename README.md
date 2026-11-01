# Estoque Churrasco

Sistema web interno de controle de estoque para uma loja de artigos e produtos para churrasco: carnes, carvão, fósforos, bebidas e utensílios.

> Status: **Projeto concluído (Fases 0 a 6)** — autenticação, cadastros, compras, vendas, ajustes, alertas, histórico, conciliação, relatórios, CSV, testes automatizados e documentação de instalação/backup.

## Resumo

O sistema é de uso exclusivo dos funcionários. Não existe área de cliente, pedido, pagamento ou emissão fiscal. O objetivo é manter o estoque confiável, registrar entradas por compra e saídas por venda, com histórico auditável.

- **Administrador (você):** cadastra produtos, fornecedores, compras, ajustes/perdas e usuários; registra e cancela vendas; acessa custos e relatórios.
- **Operadores (duas pessoas):** registram vendas e consultam produtos e histórico. Não fazem cadastros administrativos.
- **Estoque:** quantidades decimais com unidade de medida (un, kg, L etc.).
- **Vendas:** uma venda pode conter vários itens e baixa o estoque de forma atômica.
- **Rastreabilidade:** lotes e validade para carnes e outros perecíveis, com consumo FEFO (primeiro que vence, primeiro que sai).
- **Custos:** preço de venda e custo médio ponderado; valores históricos preservados nas vendas.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Servidor web | Apache 2.4 com `mod_rewrite` |
| Aplicação | PHP 8.2+ sem framework (MVC simplificado) |
| Banco | MySQL 8.0+ (InnoDB, utf8mb4) |
| Acesso a dados | PDO com prepared statements |
| Interface | Bootstrap 5 via CDN, HTML renderizado no servidor |
| Ambiente local | Docker Compose (Apache + PHP 8.2 + MySQL 8.0) |

Sem dependências PHP obrigatórias. Composer, se usado, fica restrito a autoload e ferramentas de teste.

## Como rodar com Docker

Pré-requisito: Docker com Compose.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec web php database/seed.php
```

Acesse `http://localhost:8080/login`.

Credenciais locais criadas pelo seed (troque em produção):

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | `admin@estoque.local` | `admin12345` |
| Operador 1 | `operador1@estoque.local` | `operador12345` |
| Operador 2 | `operador2@estoque.local` | `operador12345` |

O Adminer fica em `http://localhost:8081` (servidor `db`, usuário `estoque`, senha definida em `DB_PASS`). Para recriar o banco do zero, use `docker compose down -v` (apaga os dados) e repita os passos.

## Testes automatizados

O projeto inclui um runner sem dependências externas. Ele usa o banco de teste `estoque_churrasco_test`, criado automaticamente pelo Docker na primeira inicialização do volume, e nunca toca no banco de desenvolvimento.

```bash
docker compose exec web php tests/run.php
```

Resultado esperado: `OK: 49 asserções`. Em um volume já existente, crie ou atualize o banco de teste com:

```bash
docker compose exec db bash /docker-entrypoint-initdb.d/02-test-db.sh
```

## Backup e restauração

```bash
docker compose exec -T db sh -lc 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers estoque_churrasco > /tmp/estoque_backup.sql'
docker compose cp db:/tmp/estoque_backup.sql /tmp/estoque_backup.sql
```

Para restaurar, copie o arquivo de volta ao container e importe com `mysql`. Instruções completas, incluindo teste de restauração e instalação sem Docker, estão em [Instalação e backup](docs/08-instalacao-e-backup.md).

## Documentação

| Documento | Conteúdo |
| --- | --- |
| [Escopo e requisitos](docs/01-escopo-e-requisitos.md) | Atores, funcionalidades, requisitos e limites |
| [Regras de negócio](docs/02-regras-de-negocio.md) | Estoque, vendas, compras, lotes e permissões |
| [Modelo de dados](docs/03-modelo-de-dados.md) | Entidades, relacionamentos e DDL MySQL |
| [Arquitetura](docs/04-arquitetura.md) | Estrutura de pastas, camadas, segurança e transações |
| [Rotas e telas](docs/05-rotas-e-telas.md) | URLs, permissões, formulários e validações |
| [Roadmap](docs/06-roadmap.md) | Fases de implementação e critérios de aceite |
| [Decisões e perguntas](docs/07-decisoes-e-perguntas.md) | Decisões tomadas e perguntas respondidas |
| [Instalação e backup](docs/08-instalacao-e-backup.md) | Instalação, testes, backup, restauração e operação |

## Fora do escopo

- Loja virtual, cadastro de clientes, pedidos, delivery e pagamentos.
- Emissão de nota fiscal, integração fiscal ou com meios de pagamento.
- Integração com marketplaces ou ERPs.
- Aplicativo móvel nativo.
- Operação com múltiplas lojas/filiais.
- Controle financeiro (contas a pagar/receber, fluxo de caixa).
