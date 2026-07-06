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
header('Content-Type: application/json; charset=utf-8');

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

// 🌟 Envoltem TOTA l'execució en un try-catch global per evitar que qualsevol error de BD trenqui el JSON
try {

    // Identificadors de control per defecte de l'assignatura o l'aula activa
    $id_activitat_global = 1; 
    $accio = $_GET['accio'] ?? '';

    // Captura del cos (BODY) - Només s'utilitzarà si és un JSON pur
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // =========================================================================
    // 🔍 ACCIÓ 1: OBTENIR ESTAT ACTUAL DEL PANELL (POLLING SINCRO DES DE FRONTEND)
    // =========================================================================
    if ($accio === 'estat') {
        // 1. Obtenir informació del mòdul, la RA i la Pràctica Activa
        $stmt = $pdo->prepare("
            SELECT r.CodiModul_RA, r.cola_abierta, r.id_activitat_activa, m.nom_modul, act.nom_activitat AS nom_practica_activa
            FROM RAs r
            INNER JOIN moduls m ON r.id_modul = m.id_modul
            LEFT JOIN activitats_ra act ON r.id_activitat_activa = act.id_activitat_conceptual
            WHERE r.id = ?
        ");
        $stmt->execute([$id_activitat_global]);
        $asignatura = $stmt->fetch();

        // 2. Alumne actual en estat d'atencion
        $stmt = $pdo->prepare("
            SELECT t.id AS id_turno, t.turno_numero, t.id_alumne, t.id_check_evaluacio,
                CONCAT(a.nom_alumne, ' ', a.cognoms_alumne) AS nombre_alumno, act.nom_activitat, c.titol_check
            FROM turnos t
            INNER JOIN alumnes a ON t.id_alumne = a.id_alumne
            LEFT JOIN checks_activitat c ON t.id_check_evaluacio = c.id_check
            LEFT JOIN activitats_ra act ON c.id_activitat_conceptual = act.id_activitat_conceptual
            WHERE t.estado = 'atendiendo' LIMIT 1
        ");
        $stmt->execute();
        $atendiendo = $stmt->fetch();

        if (!$atendiendo) {
            $atendiendo = [
                'id_turno' => null, 'turno_numero' => '--', 'id_alumne' => null, 'id_check_evaluacio' => null,
                'nombre_alumno' => 'Buscant...', 'nom_activitat' => '-', 'titol_check' => '-'
            ];
        }

        // 3. Recompte numèric dels alumnes totals a la cua d'espera
        $stmt = $pdo->query("SELECT COUNT(*) FROM turnos WHERE estado = 'esperando'");
        $en_espera = $stmt->fetchColumn();

        // 4. Llistat complet de la cua
        $stmt = $pdo->query("
            SELECT t.id AS id_turno, t.turno_numero, CONCAT(a.nom_alumne, ' ', a.cognoms_alumne) AS nombre_alumno, c.titol_check
            FROM turnos t
            INNER JOIN alumnes a ON t.id_alumne = a.id_alumne
            LEFT JOIN checks_activitat c ON t.id_check_evaluacio = c.id_check
            WHERE t.estado = 'esperando' ORDER BY t.posicion_cola ASC
        ");
        $cua_llista = $stmt->fetchAll();

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
    // 🎛️ ACCIÓ 2: COMMUTAR PERMÍS DE CUA
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
        $pdo->query("UPDATE turnos SET estado = 'atendido', resultat_prova = 'no_apte', hora_fin_atencion = NOW(), posicion_cola = 0 WHERE estado = 'atendiendo'");
        $proxim_id = $pdo->query("SELECT id FROM turnos WHERE estado = 'esperando' ORDER BY posicion_cola ASC LIMIT 1")->fetchColumn();

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
    // 💾 ACCIÓ 4: DESAR INTENT (UNIFICAT AMB TEXT, NOTA I FITXER BLOB)
    // =========================================================================
    if ($accio === 'finalitzar_apte_individual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // 🌟 Ara llegim de $_POST perquè viatja com a FormData multimèdia
        $id_turno = intval($_POST['id_turno'] ?? 0);
        $id_check = intval($_POST['id_check'] ?? 0);
        $resultat_prova = trim($_POST['resultat_prova'] ?? ''); 
        $pregunta = htmlspecialchars(trim($_POST['pregunta'] ?? ''), ENT_QUOTES, 'UTF-8');
        $respuesta = htmlspecialchars(trim($_POST['respuesta'] ?? ''), ENT_QUOTES, 'UTF-8');

        if ($id_turno <= 0 || $id_check <= 0 || !in_array($resultat_prova, ['apte', 'no_apte'])) {
            echo json_encode(['success' => false, 'error' => 'Falten dades obligatòries o el resultat triat és invàlid.']);
            exit;
        }

        // Processament de la captura d'imatge o vídeo arrossegat
        $fitxer_binari = null;
        $fitxer_mime = null;
        if (isset($_FILES['resposta_fitxer']) && $_FILES['resposta_fitxer']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['resposta_fitxer']['tmp_name'];
            $fitxer_mime = $_FILES['resposta_fitxer']['type'];
            $fitxer_binari = fopen($tmp_name, 'rb'); 
        }

        $pdo->beginTransaction();

        // 1. Cercar l'id de l'alumne del torn actiu
        $stmt_t = $pdo->prepare("SELECT id_alumne FROM turnos WHERE id = ?");
        $stmt_t->execute([$id_turno]);
        $id_alumne = $stmt_t->fetchColumn();

        if (!$id_alumne) { 
            throw new Exception("L'identificador del torn no correspon a cap alumne."); 
        }

        // 2. Càlcul de degradació de nota si és apte
        $pct = 0;
        if ($resultat_prova === 'apte') {
            $stmt_count = $pdo->prepare("SELECT COUNT(DISTINCT id_alumne) FROM notes_checks_alumne WHERE id_check = ? AND completat = 1");
            $stmt_count->execute([$id_check]);
            $alumnes_abans = intval($stmt_count->fetchColumn());

            if ($alumnes_abans < 5)        $pct = 100;
            else if ($alumnes_abans < 10)  $pct = 90;
            else if ($alumnes_abans < 15)  $pct = 80;
            else if ($alumnes_abans < 20)  $pct = 70;
            else if ($alumnes_abans < 25)  $pct = 60;
            else                           $pct = 50;
        }

        $completat = ($resultat_prova === 'apte') ? 1 : 0;

        // 3. Inserim el registre complet a l'històric (incloent els nous camps BLOB de la lliçó anterior)
        $stmt_ins = $pdo->prepare("
            INSERT INTO notes_checks_alumne 
                (id_alumne, id_check, completat, percentatge_aplicat, pregunta_realitzada, resposta_observacions, resposta_text, resposta_fitxer_binari, resposta_fitxer_mime) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt_ins->bindValue(1, $id_alumne, PDO::PARAM_INT);
        $stmt_ins->bindValue(2, $id_check, PDO::PARAM_INT);
        $stmt_ins->bindValue(3, $completat, PDO::PARAM_INT);
        $stmt_ins->bindValue(4, $pct, PDO::PARAM_INT);
        $stmt_ins->bindValue(5, $pregunta, PDO::PARAM_STR);
        $stmt_ins->bindValue(6, $respuesta, PDO::PARAM_STR);
        $stmt_ins->bindValue(7, $respuesta, PDO::PARAM_STR); // Guardem a 'resposta_text' el mateix feedback per seguretat
        $stmt_ins->bindValue(8, $fitxer_binari, PDO::PARAM_LOB);
        $stmt_ins->bindValue(9, $fitxer_mime, PDO::PARAM_STR);
        $stmt_ins->execute();

        if (is_resource($fitxer_binari)) {
            fclose($fitxer_binari);
        }

        // 4. Cloure el torn
        $stmt_f = $pdo->prepare("UPDATE turnos SET estado = 'atendido', resultat_prova = ?, hora_fin_atencion = NOW(), posicion_cola = 0 WHERE id = ?");
        $stmt_f->execute([$resultat_prova, $id_turno]);

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    // =========================================================================
    // 🛠️ ACCIÓ 5: ESTABLIR CONFIGURACIÓ DE CLASSE ACTIVA
    // =========================================================================
    if ($accio === 'configurar_classe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_practica = intval($input['id_practica'] ?? 0);
        if ($id_practica <= 0) {
            echo json_encode(['success' => false, 'error' => 'La pràctica triada no és vàlida.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE RAs SET id_activitat_activa = ? WHERE id = ?");
        $stmt->execute([$id_practica, $id_activitat_global]);
        echo json_encode(['success' => true]);
        exit;
    }

    // =========================================================================
    // 🎯 ACCIÓ 6: LLISTAR CHECKS DE LA PRÀCTICA ACTIVA
    // =========================================================================
    if ($accio === 'llistar_checks_alumne') {
        $id_activitat = intval($_GET['id_activitat'] ?? 0);
        if ($id_activitat <= 0) {
            echo json_encode(['success' => false, 'checks' => []]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id_check, titol_check FROM checks_activitat WHERE id_activitat_conceptual = ? ORDER BY id_check ASC");
        $stmt->execute([$id_activitat]);
        echo json_encode(['success' => true, 'checks' => $stmt->fetchAll()]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
    exit;

} catch (Exception $e) {
    // Si qualsevol consulta falla, ens assegurem de retornar JSON vàlid explicant el motiu exacte!
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Error intern del backend: ' . $e->getMessage()]);
    exit;
}