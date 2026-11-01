#!/bin/bash
set -e

TEST_DB="estoque_churrasco_test"

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "
DROP DATABASE IF EXISTS \`${TEST_DB}\`;
CREATE DATABASE \`${TEST_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"

sed "s/estoque_churrasco/${TEST_DB}/g" /docker-entrypoint-initdb.d/01-schema.sql \
    | mysql -uroot -p"${MYSQL_ROOT_PASSWORD}"

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "
GRANT ALL PRIVILEGES ON \`${TEST_DB}\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
"
