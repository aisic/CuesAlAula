<?php
// api_oauth.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php'; 

try {
     $pdo = new PDO($dsn, $user, $password, [
         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES   => false,
     ]);
} catch (\PDOException $e) {
     echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
     exit;
}

// Rebre les dades JSON del Frontend (Llegit aquí una sola vegada)
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'error' => 'Manca el token de seguretat.']);
    exit;
}

//$perfil = $input['perfil'] ?? 'alumno'; // 🟢 LLEGIM EL PERFIL AQUÍ SENSE TREPITJAR L'INPUT


// 1. Preguntar a Google si el Token és vàlid aportant la credencial
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($token);
$resposta_google = @file_get_contents($url);

if ($resposta_google === false) {
    echo json_encode(['success' => false, 'error' => 'Token de Google invàlid o caducat.']);
    exit;
}

$dades_usuari = json_decode($resposta_google, true);

// 2. Validar que el destí del token sigui el nostre Client ID (Seguretat crítica)
$el_meu_client_id = "569428212376-8bnfus0c5tal7q4d45j9c9sl8t8064oj.apps.googleusercontent.com";
if ($dades_usuari['aud'] !== $el_meu_client_id) {
    echo json_encode(['success' => false, 'error' => 'Petició no autoritzada.']);
    exit;
}

// 3. ⚠️ FILTRE DE DOMINI DEL CENTRE
$domini_autoritzat = "itb.cat"; 
if (isset($dades_usuari['hd']) && $dades_usuari['hd'] !== $domini_autoritzat) {
    echo json_encode(['success' => false, 'error' => "Només permès per a comptes de @$domini_autoritzat"]);
    exit;
}

// 4. Tot és correcte: Guardem les dades de l'usuari a la Sessió de PHP
$email = $dades_usuari['email'] ?? '';
$nombre = $dades_usuari['name'] ?? '';

$_SESSION['alumno_email'] = $email;
$_SESSION['alumno_nombre'] = $nombre;

// 5. Decidim la ruta de destí segons el perfil sol·licitat
// Control de la taula 'profesores'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM profesores WHERE email = ?");
    $stmt->execute([$email]);
    $es_profesor = $stmt->fetchColumn();
        if ($es_profesor > 0) {

           // if ($stmt->fetchColumn() == 0) {
           //     echo json_encode([
           //         'success' => false, 
           //         'error' => 'Accés denegat: El teu correu no està registrat com a docent al centre.'
           //     ]);
           //     exit;
           //} 
            
            $ruta_desti = 'gestion.php';
        } else {
           // 🟢 ACCIÓ ESTUDIANT: Integració amb Google Sheets i Auto-alta a MySQL
    $spreadsheetId = "17MPPTHw9RopnpGcJE9a8nfWSRLnbp-u1leGwft9MyGk";
    $gid = "330754504"; // Pestanya 'estudiants'
    $csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv&gid={$gid}";

    $dni_trobat = null;

    // Llegim el Google Sheet en format CSV des del flux web
    if (($handle = fopen($csvUrl, "r")) !== FALSE) {
        fgetcsv($handle, 1000, ","); // Saltem capçaleres
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Índex 1 (2a columna): Correu de l'alumne
            // Índex 6 (7a columna / G): DNI o NIE
            if (isset($data[1]) && trim($data[1]) === trim($email)) {
                $dni_trobat = trim($data[6]);
                break;
            }
        }
        fclose($handle);
    }

    // Fallback de contingència si el Sheets falla o l'alumne no hi és present
    if (empty($dni_trobat)) {
        $dni_trobat = "TEMP_" . substr(md5($email), 0, 7);
    }

    try {
        // Comprovem si l'alumne ja existia a MySQL
        $stmt_cerca = $pdo->prepare("SELECT id_alumne FROM alumnes WHERE email = ? LIMIT 1");
        $stmt_cerca->execute([$email]);
        $id_existent = $stmt_cerca->fetchColumn();

        if (!$id_existent) {
            // Si és nou, fem l'INSERT a la taula d'alumnes usant el seu DNI/NIE real del Sheet
            $sql_insert = "INSERT INTO alumnes (id_alumne, nom, cognoms, email, data_alta) 
                           VALUES (?, ?, ?, ?, NOW())";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                $dni_trobat,
                $nombre,
                $apellidos,
                $email
            ]);
            $_SESSION['id_alumne'] = $dni_trobat;
        } else {
            // Si ja existia, passem el seu id_alumne real de la BD cap a la sessió
            $_SESSION['id_alumne'] = $id_existent;
        }
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error de sincronització interna: ' . $e->getMessage()]);
        exit;
    }

    $ruta_desti = 'alumno.php';
}

// Retornem l'èxit i la URL a la qual s'ha de dirigir el JavaScript
echo json_encode([
    'success' => true,
    'redireccio' => $ruta_desti
]);
exit;