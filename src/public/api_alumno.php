<?php
// api_alumno.php
session_start();

$lang = $_SESSION['lang'] ?? 'ca';
$lang_file = __DIR__ . "/lang/{$lang}.json";
$translations = file_exists($lang_file) ? json_decode(file_get_contents($lang_file), true) : [];

function __api($key, $fallback) {
    global $translations;
    return $translations[$key] ?? $fallback;
}

header('Content-Type: application/json');

if (!isset($_SESSION['alumno_email'])) {
    echo json_encode(['error' => 'No autoritzat']);
    exit;
}

require_once __DIR__ . '/config/db.php'; 

try {
     $pdo = new PDO($dsn, $user, $password, [
         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     ]);
} catch (\PDOException $e) {
     echo json_encode([
        'success' => false,
        'error' => __api('connection_error', 'Error de conexión: ' . $e->getMessage())
        ]);
     exit;
}

$email = $_SESSION['alumno_email'];
$accio = $_GET['accio'] ?? 'estat';

// 🟢 Busquem el DNI real utilitzant la variable $id_alumne
$stmt_alumne = $pdo->prepare("SELECT id_alumne FROM alumnes WHERE email = ? LIMIT 1");
$stmt_alumne->execute([$email]);
$id_alumne = $stmt_alumne->fetchColumn();

if (!$id_alumne) {
    echo json_encode([
        'success' => false,
        'error' => __api('student_not_found', 'L\'alumne no està registrat a la base de dades.')
    ]);
    exit;
}

// Busquem la cua/RA activa
$stmt_id_real = $pdo->query("SELECT id FROM RAs LIMIT 1");
$id_activitat = $stmt_id_real->fetchColumn();

if (!$id_activitat) {
    echo json_encode([
        'success' => false,
        'error' => __api('empty_module_table', 'La taula RAs està buida a la base de dades.')
    ]);
    exit;
}

// --- ACCIÓ 1: OBTENIR ESTAT ---
if ($accio === 'estat') {
    // 1. Mirem l'estat general de la cua
    $stmt_cua = $pdo->prepare("SELECT cola_abierta FROM RAs WHERE id = ?");
    $stmt_cua->execute([$id_activitat]);
    $cola_abierta = $stmt_cua->fetchColumn();

    // 2. 🟢 CORREGIT: Canviat $id_alumno per $id_alumne (amb 'e') per lligar la variable de dalt
    $stmt = $pdo->prepare("
        SELECT t.* FROM turnos t
        WHERE t.id_alumne = ? AND t.id_activitat = ? AND t.estado IN ('esperando', 'atendiendo') 
        LIMIT 1
    ");
    $stmt->execute([$id_alumne, $id_activitat]);
    $turno_actual = $stmt->fetch();

    // 3. Cas A: Si l'alumne NO està a la cua
    if (!$turno_actual) {
        echo json_encode([
            'success' => true,
            'en_cua' => false,
            'lang' => $lang,
            'cola_abierta' => (int)$cola_abierta
        ]);
        exit;
    }

    // 4. Cas B: Si l'alumne SÍ que està a la cua
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM turnos 
        WHERE id_activitat = ? AND estado = 'esperando' AND turno_numero < ?
    ");
    $stmt->execute([$id_activitat, $turno_actual['turno_numero']]);
    $alumnes_davant = $stmt->fetchColumn();

    $temps_mig_unitari = 7; 
    $temps_estimat = $alumnes_davant * $temps_mig_unitari;

    echo json_encode([
        'success' => true,
        'en_cua' => true,
        'lang' => $lang,
        'el_meu_torn' => $turno_actual['turno_numero'],
        'estat_actual' => $turno_actual['estado'],
        'alumnes_davant' => $alumnes_davant,
        'temps_estimat' => $temps_estimat,
        'cola_abierta' => (int)$cola_abierta
    ]);
    exit;
}

// --- ACCIÓ 2: APUNTAR-SE ---
if ($accio === 'apuntarse' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $stmt = $pdo->prepare("SELECT cola_abierta FROM RAs WHERE id = ?");
    $stmt->execute([$id_activitat]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode([
            'success' => false,
            'error' => __api('queue_closed', 'La cua està tancada pel professor')
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE id_alumne = ? AND id_activitat = ? AND estado IN ('espera', 'atendiendo')");
    $stmt->execute([$id_alumne, $id_activitat]);
    if ($stmt->fetchColumn() > 0) {
         echo json_encode([
            'success' => false, 
            'error' => __api('already_in_queue', 'Ja estàs a la cua')
        ]);
         exit;
    }

    $stmt = $pdo->prepare("SELECT MAX(turno_numero) FROM turnos WHERE id_activitat = ? AND DATE(fecha_registro) = CURDATE()");
    $stmt->execute([$id_activitat]);
    $ultim_torn = $stmt->fetchColumn() ?: 0;
    $nou_torn = $ultim_torn + 1;

    $stmt = $pdo->prepare("SELECT MAX(posicion_cola) FROM turnos WHERE id_activitat = ? AND estado = 'esperando'");
    $stmt->execute([$id_activitat]);
    $ultima_posicion = $stmt->fetchColumn() ?: 0;
    $nova_posicio = $ultima_posicion + 1;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO turnos (id_alumne, id_activitat, turno_numero, posicion_cola, estado, fecha_registro) 
            VALUES (?, ?, ?, ?, 'esperando', NOW())
        ");
        
        $stmt->execute([
            $id_alumne,     
            $id_activitat,  
            $nou_torn,       
            $nova_posicio    
        ]);

        // 🟢 OPTIMITZAT: Retornem l'èxit i l'estat perquè el JS forci l'actualització visual immediata
        echo json_encode([
            'success' => true,
            'en_cua' => true,
            'el_meu_torn' => $nou_torn,
            'estat_actual' => 'esperando'
        ]);
        exit;
        
    } catch (\PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => __api('db_error', 'Error a la BD: ' . $e->getMessage())
        ]);
        exit;
    }
}

// --- ACCIÓ 3: DESAPUNTAR-SE (CANCEL·LAR) ---
if ($accio === 'desapuntarse' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE turnos SET estado = 'cancelado' WHERE id_alumne = ? AND id_activitat = ? AND estado = 'esperando'");
    $stmt->execute([$id_alumne, $id_activitat]);
    echo json_encode(['success' => true]);
    exit;
}