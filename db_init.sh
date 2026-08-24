#!/bin/bash
echo "Executant script d'inicialització de l'administrador..."
echo DB_PASSWORD: $MARIADB_ROOT_PASSWORD
echo DB_USER: $MARIADB_USER
echo DB_NAME: $MARIADB_DATABASE

mariadb -u "${MARIADB_USER:-root}" -p"${MARIADB_ROOT_PASSWORD}" "$MARIADB_DATABASE" <<-EOSQL
    INSERT INTO profesores (nombre, email, password) 
    VALUES ('Admin', '${ADMIN_EMAIL:-isaac.gonzalo@itb.cat}', 'OAUTH_LOGIN')
    ON DUPLICATE KEY UPDATE id=id;
EOSQL

echo "Usuari administrador inserit correctament!"