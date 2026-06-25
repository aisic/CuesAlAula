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
            // Si és un alumne, va cap a la seva cua
            $ruta_desti = 'alumno.php';
}

// Retornem l'èxit i la URL a la qual s'ha de dirigir el JavaScript
echo json_encode([
    'success' => true,
    'redireccio' => $ruta_desti
]);
exit;