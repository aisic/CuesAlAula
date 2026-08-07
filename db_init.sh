#!/bin/bash
# MariaDB executa automàticament aquest script durant el primer setup

mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$DB_NAME" <<-EOSQL
    INSERT INTO profesores (nombre, email, password) 
    VALUES ('Admin', '${ADMIN_EMAIL:-isaac.gonzalo@itb.cat}', 'OAUTH_LOGIN')
    ON DUPLICATE KEY UPDATE id=id;
EOSQL