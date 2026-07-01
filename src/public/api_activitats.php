<?php
// api_activitats.php
session_start();
require_once 'seguridad_profesor.php';
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de BD']); exit;
}

$accio = $_GET['accio'] ?? '';

if ($accio === 'llistar_moduls') {
    $stmt = $pdo->query("SELECT id_modul, nom_modul, cicle_formatiu FROM moduls ORDER BY nom_modul ASC");
    echo json_encode(['success' => true, 'moduls' => $stmt->fetchAll()]);
    exit;
}

if ($accio === 'llistar_ras') {
    $id_modul = intval($_GET['id_modul'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, CodiModul_RA, nom_ra FROM RAs WHERE id_modul = ?");
    $stmt->execute([$id_modul]);
    echo json_encode(['success' => true, 'ras' => $stmt->fetchAll()]);
    exit;
}

if ($accio === 'llistar_activitats') {
    $id_ra = intval($_GET['id_ra'] ?? 0);
    $stmt = $pdo->prepare("SELECT id_activitat_conceptual, nom_activitat FROM activitats_ra WHERE id_ra = ?");
    $stmt->execute([$id_ra]);
    echo json_encode(['success' => true, 'activitats' => $stmt->fetchAll()]);
    exit;
}

if ($accio === 'crear_activitat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_ra = intval($input['id_ra'] ?? 0);
    $nom_activitat = $input['nom_activitat'] ?? '';

    if($id_ra <= 0 || empty($nom_activitat)) {
        echo json_encode(['success' => false, 'error' => 'Dades incompletes.']); exit;
    }

    $stmt = $pdo->prepare("INSERT INTO activitats_ra (id_ra, nom_activitat) VALUES (?, ?)");
    $stmt->execute([$id_ra, $nom_activitat]);
    echo json_encode(['success' => true]);
    exit;
}

if ($accio === 'eliminar_activitat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_act = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM activitats_ra WHERE id_activitat_conceptual = ?");
    $stmt->execute([$id_act]);
    echo json_encode(['success' => true]);
    exit;
}