#!/bin/bash
echo "Executant script d'inicialització de l'administrador..."
echo DB_PASSWORD: $DB_PASSWORD
echo DB_USER: $DB_USER
echo DB_NAME: $DB_NAME

mariadb -u "${DB_USER:-root}" -p"${DB_PASSWORD}" "$DB_NAME" <<-EOSQL    USE gestion_colas;
    INSERT INTO profesores (nombre, email, password) 
    VALUES ('Admin', '${ADMIN_EMAIL:-isaac.gonzalo@itb.cat}', 'OAUTH_LOGIN')
    ON DUPLICATE KEY UPDATE id=id;
EOSQL

echo "Usuari administrador inserit correctament!"