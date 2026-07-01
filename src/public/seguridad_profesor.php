<?php
// seguridad_profesor.php

// 1. Assegurem que la sessió estigui iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Si ni tan sols està loguejat amb Google, directament al login
if (!isset($_SESSION['alumno_email'])) {
    header("Location: login_profesor.php");
    exit;
}

// 3. Connectem a la base de dades de manera segura
require_once __DIR__ . '/config/db.php';

try {
    $pdo_seguridad = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Seguretat extra contra SQLi
    ]);
} catch (\PDOException $e) {
    echo json_encode(['error' => 'Error de connexió de seguretat']);
    exit;
}

// 4. Busquem el correu de la sessió a la taula de professors autoritzats
$email_a_comprovar = $_SESSION['alumno_email'];

$stmt_seg = $pdo_seguridad->prepare("SELECT COUNT(*) FROM profesores WHERE email = ?");
$stmt_seg->execute([$email_a_comprovar]);
$es_profesor = $stmt_seg->fetchColumn();

// 5. Si el recompte és 0, significa que no és un professor autoritzat
if ($es_profesor == 0) {
    session_unset();
    session_destroy();
    // Redirigim a la pantalla de l'alumne o mostrem un error d'accés denegat
    header("Location: login_profesor.php?error=no_autoritzat");
    exit;
}