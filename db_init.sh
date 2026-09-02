#!/bin/bash
echo "Executant script d'inicialització de l'administrador..."

mariadb -u "${MARIADB_USER:-root}" -p"${MARIADB_ROOT_PASSWORD}" "$MARIADB_DATABASE" <<-EOSQL
    INSERT INTO profesores (nombre, email, password) 
    VALUES ('Admin', '${ADMIN_EMAIL:-isaac.gonzalo@itb.cat}', 'OAUTH_LOGIN')
    ON DUPLICATE KEY UPDATE id=id;
EOSQL

echo "Usuari administrador inserit correctament!"