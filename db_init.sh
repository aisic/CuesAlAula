#!/bin/bash
echo "Executant script d'inicialització de l'administrador..."

mariadb -u root -proot<<-EOSQL
    USE $DB_NAME;
    INSERT INTO profesores (nombre, email, password) 
    VALUES ('Admin', '${ADMIN_EMAIL:-isaac.gonzalo@itb.cat}', 'OAUTH_LOGIN')
    ON DUPLICATE KEY UPDATE id=id;
EOSQL

echo "Usuari administrador inserit correctament!"