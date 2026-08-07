#!/bin/bash
echo "Executant script d'inicialització de l'administrador..."

mariadb -u root -p"$DB_PASSWORD" <<-EOSQL
    USE \`$DB_NAME\`;
    INSERT INTO profesores (nombre, email, password) 
    VALUES ('Admin', '${ADMIN_EMAIL:-isaac.gonzalo@itb.cat}', 'OAUTH_LOGIN')
    ON DUPLICATE KEY UPDATE id=id;
EOSQL

echo "Usuari administrador inserit correctament!"



mariadb -u root -p"$MARIADB_ROOT_PASSWORD" <<-EOSQL
    USE \`$DB_NAME\`;

    CREATE TABLE IF NOT EXISTS profesores (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(100) NOT NULL,
      email VARCHAR(150) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    INSERT INTO profesores (nombre, email, password) 
    VALUES ('Administrador OAuth', '${ADMIN_EMAIL:-admin@aula.com}', 'OAUTH_LOGIN')
    ON DUPLICATE KEY UPDATE id=id;
EOSQL

echo "Usuari administrador inicialitzat correctament!"