<?php
// api_admin_academico.php
session_start();
require_once 'seguridad_profesor.php';
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de connexió a fons']); exit;
}

$accio = $_GET['accio'] ?? '';

// 1. OBTENIR TOTS ELS MÒDULS
if ($accio === 'llistar_moduls') {
    $stmt = $pdo->query("SELECT id_modul, nom_modul, cicle_formatiu, curs FROM moduls ORDER BY cicle_formatiu, curs, nom_modul");
    echo json_encode(['success' => true, 'moduls' => $stmt->fetchAll()]);
    exit;
}

// 2. OBTENIR RAs D'UN MÒDUL CONCRET
if ($accio === 'llistar_ras') {
    $id_modul = intval($_GET['id_modul'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT r.id, r.CodiModul_RA, r.nom_ra, r.hores_lectives, m.cicle_formatiu, m.curs 
        FROM RAs r
        INNER JOIN moduls m ON r.id_modul = m.id_modul
        WHERE r.id_modul = ?
    ");
    $stmt->execute([$id_modul]);
    echo json_encode(['success' => true, 'ras' => $stmt->fetchAll()]);
    exit;
}

// 3. CREAR NOU MÒDUL
if ($accio === 'crear_modulo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $nom = $input['nom'] ?? '';
    $cicle = $input['cicle'] ?? '';
    $curs = $input['curs'] ?? '1r';

    if(empty($nom) || empty($cicle)) {
        echo json_encode(['success' => false, 'error' => 'Dades incompletes']); exit;
    }

    $stmt = $pdo->prepare("INSERT INTO moduls (nom_modul, cicle_formatiu, curs) VALUES (?, ?, ?)");
    $stmt->execute([$nom, $cicle, $curs]);
    echo json_encode(['success' => true]);
    exit;
}

// 4. CREAR I ASSIGNAR RA A UN MÒDUL
if ($accio === 'crear_ra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_modul = intval($input['id_modul'] ?? 0);
    $codi_ra = $input['codi_ra'] ?? '';
    $nom_ra = $input['nom_ra'] ?? '';
    $hores = intval($input['hores'] ?? 10);

    if($id_modul <= 0 || empty($codi_ra)) {
        echo json_encode(['success' => false, 'error' => 'Dades de RA no vàlides']); exit;
    }

    $stmt = $pdo->prepare("INSERT INTO RAs (id_modul, CodiModul_RA, nom_ra, hores_lectives, cola_abierta) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$id_modul, $codi_ra, $nom_ra, $hores]);
    echo json_encode(['success' => true]);
    exit;
}

// 5. DESAR CONFIGURACIÓ GLOBAL DE PESOS (HORES) DES DE LA TAULA
if ($accio === 'guardar_pesos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pesos = $input['pesos'] ?? [];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE RAs SET hores_lectives = ? WHERE id = ?");
        foreach ($pesos as $p) {
            $stmt->execute([intval($p['hores_lectives']), intval($p['id'])]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}