# 08 - Instalação, testes e backup

## 1. Requisitos

- Docker com Compose (recomendado).
- Alternativa sem Docker: Apache 2.4 com `mod_rewrite` e `mod_headers`, PHP 8.2+ com `pdo_mysql`, `bcmath` e `mbstring`, e MySQL 8.0+.
- Portas locais: `8080` para a aplicação e `8081` para o Adminer.

## 2. Instalação com Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec web php database/seed.php
```

Acesse `http://localhost:8080/login` com as credenciais do seed documentadas no README.

Na primeira inicialização do volume MySQL, o container aplica `database/schema.sql` e executa `database/init-test-db.sh`, que cria o banco isolado `estoque_churrasco_test`.

Para recriar tudo do zero (apaga os dados):

```bash
docker compose down -v
docker compose up -d --build
docker compose exec web php database/seed.php
```

## 3. Banco de teste

O banco de teste é separado do banco de desenvolvimento. O runner de testes usa `estoque_churrasco_test` e nunca altera o banco `estoque_churrasco`.

Em um volume já existente, execute o script manualmente para criar ou recriar o banco de teste:

```bash
docker compose exec db bash /docker-entrypoint-initdb.d/02-test-db.sh
```

O script remove e recria o banco de teste, aplica o schema e concede acesso ao usuário da aplicação. Ele não afeta o banco de desenvolvimento.

## 4. Testes automatizados

```bash
docker compose exec web php tests/run.php
```

Resultado esperado:

```text
OK: 49 asserções
```

O runner:

- Usa `estoque_churrasco_test`.
- Limpa e popula o banco de teste antes de executar.
- Testa `Money`, compras, custo médio, lotes, FEFO, vendas, consolidação de itens, cancelamento, ajustes, perdas, relatórios e conciliação.
- Não depende de Composer ou PHPUnit.

Se o banco de teste não existir, execute o script da seção 3.

## 5. Backup

Backup consistente do banco de desenvolvimento:

```bash
docker compose exec -T db sh -lc \
  'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers estoque_churrasco > /tmp/estoque_backup.sql'

docker compose cp db:/tmp/estoque_backup.sql /tmp/estoque_backup.sql
```

Guardar o arquivo fora do repositório e com acesso restrito. Recomendações:

- Fazer backup diário.
- Manter pelo menos 7 versões.
- Testar a restauração periodicamente.
- Fazer backup antes de alterações de schema.
- Nunca versionar arquivos de backup no Git.

## 6. Restauração

A restauração sobrescreve os dados atuais. Confirme o arquivo e faça um backup antes.

```bash
docker compose cp /tmp/estoque_backup.sql db:/tmp/estoque_backup.sql

docker compose exec -T db sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" estoque_churrasco < /tmp/estoque_backup.sql'
```

### Teste de restauração

O procedimento abaixo restaura em um banco descartável, sem tocar no banco principal:

```bash
docker compose exec -T db sh -lc \
  'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers estoque_churrasco_test > /tmp/estoque_test_backup.sql'

docker compose exec -T db sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS estoque_restore_test; CREATE DATABASE estoque_restore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'

docker compose exec -T db sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" estoque_restore_test < /tmp/estoque_test_backup.sql'

docker compose exec -T db sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '\''estoque_restore_test'\'';"'

docker compose exec -T db sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE estoque_restore_test;"'
```

Resultado validado: 12 tabelas restauradas e dados de produtos/vendas presentes no banco de teste.

## 7. Instalação sem Docker

1. Criar o banco e importar `database/schema.sql`.
2. Executar `php database/seed.php` com as variáveis de ambiente do banco configuradas.
3. Configurar o virtual host do Apache com `DocumentRoot` em `public/`.
4. Habilitar `mod_rewrite` e `mod_headers`.
5. Garantir escrita em `storage/logs/`.
6. Acessar `/login`.

O banco de teste só é criado automaticamente pelo fluxo Docker. Sem Docker, crie `estoque_churrasco_test` manualmente e ajuste o script `database/init-test-db.sh` para o ambiente local.

## 8. Segurança e operação

- Trocar as senhas padrão do seed após o primeiro acesso.
- Em produção, usar `APP_ENV=production`, HTTPS e cookies seguros.
- Não expor `app/`, `config/`, `database/`, `storage/` ou `docs/` no document root.
- Manter `.env` fora do Git.
- Restringir o Adminer ou não publicá-lo em produção.
- Monitorar `storage/logs/` ou o log alternativo em `/tmp/estoque-churrasco.log`.
- Executar testes e conciliação após alterações de regras de estoque.

## 9. Checklist final

- [x] Instalação limpa com Docker validada.
- [x] Schema e banco de teste criados automaticamente em volume novo.
- [x] Seed executado com sucesso.
- [x] Testes automatizados com 49 asserções aprovadas.
- [x] Relatórios e exportação CSV validados.
- [x] Backup e restauração testados em banco descartável.
- [x] Acesso de operador restrito a relatórios financeiros e administração.
