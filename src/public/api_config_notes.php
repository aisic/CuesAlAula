<?php
// api_config_notes.php
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

$id_modul = 1; // Mòdul per defecte en el teu entorn
$accio = $_GET['accio'] ?? '';

// OBTENIR CONFIGURACIÓ ACTUAL
if ($accio === 'llegir') {
    $stmt = $pdo->prepare("SELECT id, CodiModul_RA, hores_lectives FROM RAs WHERE id_modul = ?");
    $stmt->execute([$id_modul]);
    echo json_encode(['success' => true, 'ras' => $stmt->fetchAll()]);
    exit;
}

// DESAR CONFIGURACIÓ DE PESOS
if ($accio === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pesos = $input['pesos'] ?? []; // Array d'objectes [{id: 1, hores: 20}, ...]

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE RAs SET hores_lectives = ? WHERE id = ? AND id_modul = ?");
        foreach ($pesos as $p) {
            $stmt->execute([intval($p['hores']), intval($p['id']), $id_modul]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}