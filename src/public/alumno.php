<?php
session_start();

// var_dump($_SESSION);
// Si no està loguejat amb Google, el podríes redirigir aquí al login
if (!isset($_SESSION['alumno_email'])) {
    header("Location: login.php");
    exit;
}
$asignatura_nombre = "C037"; // Això vindrà de la teva BD
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cua d'Alumnes - El meu Torn</title>

    <link href="css/alumno.css" rel="stylesheet">

    <script src="js/alumno.js" defer></script>
</head>

<body>

<div class="container">
    <h1><?= htmlspecialchars($asignatura_nombre) ?></h1>
    
    <div class="user-info">
        <span>Connectat com: <strong><?= htmlspecialchars($_SESSION['alumno_nombre']) ?></strong></span>
        <a href="logout.php" class="logout-btn">🚪 Sortir</a>
    </div>

    <div id="seccio-apuntarse" class="hidden">
        <p class="info-text">Actualment no estàs a la cua d'espera d'aquesta assignatura.</p>
        <button id="apuntarse-btn" class="btn btn-primary">Apuntar-me a la Cua</button>
    </div>

    <div id="seccio-espera" class="hidden">
        <div class="status-box">
            <p id="text-estat-torn">El teu número de torn és:</p>
            <div class="big-number" id="el-meu-torn">--</div>
        </div>
        
        <div class="status-container">
            <p class="status-label">Status de la cua:</p>
            <div id="estat-cua-contenidor" class="estat-cua-box">
                <span id="estat-cua-text">🔄 Comprovant estat de la cua...</span>
            </div>
            <ul>
                <li>Alumnes per davant teu: <strong id="alumnes-davant">--</strong></li>
                <li>Temps estimat d'espera: <strong id="temps-estimat">-- min</strong></li>
            </ul>
        </div>

        <button id="desapuntarse-btn" class="btn btn-danger">Sortir de la Cua</button>
    </div>
</div>

</body>
</html>

