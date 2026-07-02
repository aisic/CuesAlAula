<?php
// =========================================================================
// 📑 API_GESTION.PHP - ENDPOINT CENTRALITZAT DE CONTROL DE CUES I INTENTS
// =========================================================================
session_start();

// Control de depuració en desenvolupament
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Capçaleres obligatòries de seguretat i definició de tipus de contingut
require_once 'seguridad_profesor.php'; 
header('Content-Type: application/json');

// Connexió centralitzada i configurada a la Base de Dades
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

// Identificadors de control per defecte de l'assignatura o l'aula activa
$id_activitat_global = 1; 
$accio = $_GET['accio'] ?? '';

// Captura i descodificació dinàmica del cos (BODY) de peticions POST en format JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// =========================================================================
// 🔍 ACCIÓ 1: OBTENIR ESTAT ACTUAL DEL PANELL (POLLING SINCRO DES DE FRONTEND)
// =========================================================================
if ($accio === 'estat') {
    // 1. Obtenir informació del mòdul, la RA i la Pràctica Activa triada pel professor
    $stmt = $pdo->prepare("
        SELECT 
            r.CodiModul_RA, 
            r.cola_abierta, 
            r.id_activitat_activa,
            m.nom_modul,
            act.nom_activitat AS nom_practica_activa
        FROM RAs r
        INNER JOIN moduls m ON r.id_modul = m.id_modul
        LEFT JOIN activitats_ra act ON r.id_activitat_activa = act.id_activitat_conceptual
        WHERE r.id = ?
    ");
    $stmt->execute([$id_activitat_global]);
    $asignatura = $stmt->fetch();

    // 2. Alumne actual en estat d'atenció
    $stmt = $pdo->prepare("
        SELECT 
            t.id AS id_turno, 
            t.turno_numero, 
            t.id_alumne, 
            t.id_check_evaluacio,
            CONCAT(a.nom_alumne, ' ', a.cognoms_alumne) AS nombre_alumno,
            act.nom_activitat,
            c.titol_check
        FROM turnos t
        INNER JOIN alumnes a ON t.id_alumne = a.id_alumne
        LEFT JOIN checks_activitat c ON t.id_check_evaluacio = c.id_check
        LEFT JOIN activitats_ra act ON c.id_activitat_conceptual = act.id_activitat_conceptual
        WHERE t.estado = 'atendiendo' 
        LIMIT 1
    ");
    $stmt->execute();
    $atendiendo = $stmt->fetch();

    if (!$atendiendo) {
        $atendiendo = [
            'id_turno' => null, 
            'turno_numero' => '--', 
            'id_alumne' => null, 
            'id_check_evaluacio' => null,
            'nombre_alumno' => 'Buscant...',
            'nom_activitat' => '-',
            'titol_check' => '-'
        ];
    }

    // 3. Recompte numèric dels alumnes totals a la cua d'espera
    $stmt = $pdo->query("SELECT COUNT(*) FROM turnos WHERE estado = 'esperando'");
    $en_espera = $stmt->fetchColumn();

    // 4. Llistat complet de la cua
    $stmt = $pdo->query("
        SELECT 
            t.id AS id_turno, 
            t.turno_numero, 
            CONCAT(a.nom_alumne, ' ', a.cognoms_alumne) AS nombre_alumno,
            c.titol_check
        FROM turnos t
        INNER JOIN alumnes a ON t.id_alumne = a.id_alumne
        LEFT JOIN checks_activitat c ON t.id_check_evaluacio = c.id_check
        WHERE t.estado = 'esperando' 
        ORDER BY t.posicion_cola ASC
    ");
    $cua_llista = $stmt->fetchAll();

    // 🌟 Retornem els camps dinàmics per a que els llegeixin index.php, gestion.php i alumno.php
    echo json_encode([
        'success' => true,
        'asignatura' => $asignatura['CodiModul_RA'] ?? '',
        'nom_modul' => $asignatura['nom_modul'] ?? '',
        'cola_abierta' => $asignatura['cola_abierta'] ?? 0,
        'id_activitat_activa' => $asignatura['id_activitat_activa'] ?? null,
        'nom_practica_activa' => $asignatura['nom_practica_activa'] ?? 'Cap pràctica seleccionada',
        'atendiendo' => $atendiendo,
        'en_espera' => $en_espera,
        'cua_llista' => $cua_llista
    ]);
    exit;
}

// =========================================================================
// 🎛️ ACCIÓ 2: COMMUTAR PERMÍS DE CUA (BLOQUEJAR / OBRIR ENTRADES)
// =========================================================================
if ($accio === 'toggle_cua' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nou_estat = !empty($input['estat']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE RAs SET cola_abierta = ? WHERE id = ?");
    $stmt->execute([$nou_estat, $id_activitat_global]);
    
    echo json_encode(['success' => true]);
    exit;
}

// =========================================================================
// 📢 ACCIÓ 3: CRIDAR SEGÜENT CANDIDAT
// =========================================================================
if ($accio === 'siguiente' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->query("
        UPDATE turnos 
        SET estado = 'atendido', resultat_prova = 'no_apte', hora_fin_atencion = NOW(), posicion_cola = 0 
        WHERE estado = 'atendiendo'
    ");

    $stmt = $pdo->query("SELECT id FROM turnos WHERE estado = 'esperando' ORDER BY posicion_cola ASC LIMIT 1");
    $proxim_id = $stmt->fetchColumn();

    if ($proxim_id) {
        $stmt = $pdo->prepare("UPDATE turnos SET estado = 'atendiendo', hora_inicio_atencion = NOW() WHERE id = ?");
        $stmt->execute([$proxim_id]);
        echo json_encode(['success' => true, 'quedaven_alumnes' => true, 'hora_inici' => date('Y-m-d H:i:s')]);
    } else {
        echo json_encode(['success' => true, 'quedaven_alumnes' => false]);
    }
    exit;
}

// =========================================================================
// 💾 ACCIÓ 4: DESAR INTENT D'AVALUACIÓ (HISTÒRIC SENSE UPDATE) I TANCAR TORN
// =========================================================================
if ($accio === 'finalitzar_apte_individual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_turno = intval($input['id_turno'] ?? 0);
    $id_check = intval($input['id_check'] ?? 0);
    
    $resultat_prova = trim($input['resultat_prova'] ?? ''); 
    $pregunta = htmlspecialchars(trim($input['pregunta'] ?? ''), ENT_QUOTES, 'UTF-8');
    $respuesta = htmlspecialchars(trim($input['respuesta'] ?? ''), ENT_QUOTES, 'UTF-8');

    if ($id_turno <= 0 || $id_check <= 0 || !in_array($resultat_prova, ['apte', 'no_apte'])) {
        echo json_encode(['success' => false, 'error' => 'Falten dades obligatòries o el resultat triat és invàlid.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Cercar l'id de l'alumne del torn actiu
        $stmt_t = $pdo->prepare("SELECT id_alumne FROM turnos WHERE id = ?");
        $stmt_t->execute([$id_turno]);
        $id_alumne = $stmt_t->fetchColumn();

        if (!$id_alumne) { 
            throw new Exception("L'identificador del torn no correspon a cap alumne."); 
        }

        // 2. CAS 🟢: L'ALUMNE ÉS APTE -> Es calcula nota per degradació i s'insereix un nou intent completat
        if ($resultat_prova === 'apte') {
            // Recompte de quants alumnes diferents han aprovat ja aquest check abans per aplicar penalització de cua
            $stmt_count = $pdo->prepare("SELECT COUNT(DISTINCT id_alumne) FROM notes_checks_alumne WHERE id_check = ? AND completat = 1");
            $stmt_count->execute([$id_check]);
            $alumnes_abans = intval($stmt_count->fetchColumn());

            if ($alumnes_abans < 5)        $pct = 100;
            else if ($alumnes_abans < 10)  $pct = 90;
            else if ($alumnes_abans < 15)  $pct = 80;
            else if ($alumnes_abans < 20)  $pct = 70;
            else if ($alumnes_abans < 25)  $pct = 60;
            else                           $pct = 50;

            // 🌟 MODIFICACIÓ: INSERT pur sense "ON DUPLICATE KEY UPDATE" per guardar l'intent com a línia nova
            $stmt_ins = $pdo->prepare("
                INSERT INTO notes_checks_alumne 
                    (id_alumne, id_check, completat, percentatge_aplicat, pregunta_realitzada, resposta_observacions) 
                VALUES (?, ?, 1, ?, ?, ?)
            ");
            $stmt_ins->execute([$id_alumne, $id_check, $pct, $pregunta, $respuesta]);

        } else {
            // 3. CAS ❌: L'ALUMNE ÉS NO APTE -> S'insereix l'intent fallit (completat = 0) per desar l'historial i el text
            // 🌟 MODIFICACIÓ: INSERT pur sense "ON DUPLICATE KEY UPDATE"
            $stmt_ins = $pdo->prepare("
                INSERT INTO notes_checks_alumne 
                    (id_alumne, id_check, completat, percentatge_aplicat, pregunta_realitzada, resposta_observacions) 
                VALUES (?, ?, 0, 0, ?, ?)
            ");
            $stmt_ins->execute([$id_alumne, $id_check, $pregunta, $respuesta]);
        }

        // 4. Cloure definitivament el torn de cua associat
        $stmt_f = $pdo->prepare("
            UPDATE turnos 
            SET estado = 'atendido', 
                resultat_prova = ?, 
                hora_fin_atencion = NOW(), 
                posicion_cola = 0 
            WHERE id = ?
        ");
        $stmt_f->execute([$resultat_prova, $id_turno]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Error transaccional del servidor: ' . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// 🛠️ ACCIÓ 5: ESTABLIR CONFIGURACIÓ DE CLASSE ACTIVA (MÒDUL / RA / PRÀCTICA)
// =========================================================================
if ($accio === 'configurar_classe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_practica = intval($input['id_practica'] ?? 0);

    if ($id_practica <= 0) {
        echo json_encode(['success' => false, 'error' => 'La pràctica triada no és vàlida.']);
        exit;
    }

    // Guardem a la base de dades quina pràctica ha fixat el professor per a la sessió actual
    $stmt = $pdo->prepare("UPDATE RAs SET id_activitat_activa = ? WHERE id = ?");
    $stmt->execute([$id_practica, $id_activitat_global]);

    echo json_encode(['success' => true]);
    exit;
}

// =========================================================================
// 🎯 ACCIÓ 6: LLISTAR CHECKS DE LA PRÀCTICA ACTIVA PER AL SELECTOR DE L'ALUMNE
// =========================================================================
if ($accio === 'llistar_checks_alumne') {
    $id_activitat = intval($_GET['id_activitat'] ?? 0);

    if ($id_activitat <= 0) {
        echo json_encode(['success' => false, 'checks' => []]);
        exit;
    }

    try {
        // Busquem els checks de l'activitat conceptual corresponent
        // ⚠️ REVISA: Assegura't que els teus camps a la taula es diuen 'id_check', 'titol_check' i 'id_activitat_conceptual'
        $stmt = $pdo->prepare("
            SELECT id_check, titol_check 
            FROM checks_activitat 
            WHERE id_activitat_conceptual = ?
            ORDER BY id_check ASC
        ");
        $stmt->execute([$id_activitat]);
        $checks = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'checks' => $checks
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'checks' => [], 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
exit;