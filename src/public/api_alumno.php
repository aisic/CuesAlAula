<?php
// api_alumno.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['alumno_email'])) {
    echo json_encode(['error' => 'No autoritzat']);
    exit;
}

require_once __DIR__ . '/config/db.php'; // Assegura't que aquest fitxer defineix $dsn, $user, $password

try {
     $pdo = new PDO($dsn, $user, $password, [
         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     ]);
} catch (\PDOException $e) {
     echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
     exit;
}

$email = $_SESSION['alumno_email'];
$nombre = $_SESSION['alumno_nombre'];
$asignatura_id = 1;
$accio = $_GET['accio'] ?? 'estat';

// --- ACCIÓ 1: OBTENIR ESTAT ---
// --- ACCIÓ 1: OBTENIR ESTAT (Corregida) ---
if ($accio === 'estat') {
    // 1. Primer de tot mirem l'estat general de la cua (Independent de l'alumne)
    $stmt_cua = $pdo->prepare("SELECT cola_abierta FROM RAs WHERE id = ?");
    $stmt_cua->execute([$asignatura_id]);
    $cola_abierta = $stmt_cua->fetchColumn();

    // 2. Després mirem si l'alumne té un torn actiu
    $stmt = $pdo->prepare("SELECT * FROM turnos WHERE email_alumno = ? AND asignatura_id = ? AND estado IN ('esperando', 'atendiendo') LIMIT 1");
    $stmt->execute([$email, $asignatura_id]);
    $turno_actual = $stmt->fetch();

    // 3. Cas A: Si l'alumne NO està a la cua, enviem l'estat de 'cola_abierta' igualment!
    if (!$turno_actual) {
        echo json_encode([
            'en_cua' => false,
            'success' => true,
            'cola_abierta' => (int)$cola_abierta // 👈 Ara el JS sí que rebrà la dada per habilitar el botó!
        ]);
        exit;
    }

    // 4. Cas B: Si l'alumne SÍ que està a la cua, enviem tota la informació dels torns
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE asignatura_id = ? AND estado = 'esperando' AND posicion_cola < ?");
    $stmt->execute([$asignatura_id, $turno_actual['posicion_cola']]);
    $alumnes_davant = $stmt->fetchColumn();

    // Calcular temps estimat d'espera (p.ex: Temps de la teva consulta de proyector multiplicat per alumnes_davant)
    // Suposem un de defecte de 7 minuts per alumne si no hi ha prou dades històriques.
    $temps_mig_unitari = 7; 
    $temps_estimat = $alumnes_davant * $temps_mig_unitari;

    echo json_encode([
        'en_cua' => true,
        'el_meu_torn' => $turno_actual['turno_numero'],
        'estat_actual' => $turno_actual['estado'],
        'alumnes_davant' => $alumnes_davant,
	    'temps_estimat' => $temps_estimat,
	    'success' => true,
        'cola_abierta' => (int)$cola_abierta, // 1 si està oberta, 0 si està tancada
    // ... la resta de dades que ja enviaves (turno_actual, el_meu_torn, posicion, etc.) ...
    ]);
}

// --- ACCIÓ 2: APUNTAR-SE (VERSIÓ COMODÍ DINÀMIC) ---
if ($accio === 'apuntarse' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 🟢 1. BRÚIXOLA: Busquem quin ID real té el teu primer registre a la taula RAs
    $stmt_id_real = $pdo->query("SELECT id FROM RAs LIMIT 1");
    $id_real_ra = $stmt_id_real->fetchColumn();

    // Si la taula RAs està completament buida, avisem de seguida
    if (!$id_real_ra) {
        echo json_encode(['success' => false, 'error' => 'La taula RAs està buida a la base de dades. Insereix un mòdul abans.']);
        exit;
    }

    // A partir d'aquí utilitzem l'ID real que hem trobat a la BD, sigui el que sigui (1, 2, o un text)
    $asignatura_id = $id_real_ra;

    // 2. Validar que la cua estigui oberta
    $stmt = $pdo->prepare("SELECT cola_abierta FROM RAs WHERE id = ?");
    $stmt->execute([$asignatura_id]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'error' => 'La cua està tancada pel professor']);
        exit;
    }

    // 3. Evitar duplicats actius
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE email_alumno = ? AND asignatura_id = ? AND estado IN ('esperando', 'atendiendo')");
    $stmt->execute([$email, $asignatura_id]);
    if ($stmt->fetchColumn() > 0) {
         echo json_encode(['success' => false, 'error' => 'Ja estàs a la cua']);
         exit;
    }

    // 4. Generar el següent número de torn i posició
    $stmt = $pdo->prepare("SELECT MAX(turno_numero) FROM turnos WHERE asignatura_id = ? AND DATE(fecha_registro) = CURDATE()");
    $stmt->execute([$asignatura_id]);
    $ultim_torn = $stmt->fetchColumn() ?: 0;
    $nou_torn = $ultim_torn + 1;

    $stmt = $pdo->prepare("SELECT MAX(posicion_cola) FROM turnos WHERE asignatura_id = ? AND estado = 'esperando'");
    $stmt->execute([$asignatura_id]);
    $ultima_posicion = $stmt->fetchColumn() ?: 0;
    $nova_posicio = $ultima_posicion + 1;

    try {
        // 5. L'INSERT DINÀMIC SEGUR (Té 5 interrogants, tornant a posar el ? a asignatura_id)
        $stmt = $pdo->prepare("
            INSERT INTO turnos (asignatura_id, nombre_alumno, codigo_alumno, email_alumno, turno_numero, posicion_cola, estado, fecha_registro) 
            VALUES (?, ?, 'ALUMNE', ?, ?, ?, 'esperando', NOW())
        ");
        
        // Passem el valor real i exacte que hem llegit directament de la teva base de dades
        $stmt->execute([
            $asignatura_id,  // 1r '?' -> El valor real detectat automàticament
            $nombre,         // 2n '?'
            $email,          // 3r '?'
            $nou_torn,       // 4t '?'
            $nova_posicio    // 5è '?'
        ]);

        echo json_encode(['success' => true]);
        exit;
        
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error a la BD: ' . $e->getMessage()]);
        exit;
    }
}

// --- ACCIÓ 3: DESAPUNTAR-SE (CANCEL·LAR) ---
if ($accio === 'desapuntarse' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE turnos SET estado = 'cancelado' WHERE email_alumno = ? AND asignatura_id = ? AND estado = 'esperando'");
    $stmt->execute([$email, $asignatura_id]);
    echo json_encode(['success' => true]);
}

