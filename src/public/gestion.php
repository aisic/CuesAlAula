<?php
require_once 'seguridad_profesor.php'; // Protegeix la vista HTML
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panell de Gestió - Professor</title>
    <link href="css/gestion.css" rel="stylesheet">
</head>
<body>

<?php
require_once 'seguridad_profesor.php'; // Protegeix la vista HTML
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panell de Gestió - Professor</title>
    <link href="css/gestion.css" rel="stylesheet">
    <script src="js/gestion.js" defer></script>
</head>
<body>

<div class="wrapper">
    <header>
        <div>
            <h1 id="nom-asignatura">Carregant assignatura...</h1>
            <p class="header-subtitle">Gestió de l'aula en temps real</p>
        </div>
        <div class="header-actions">
            <button id="btn-lock" class="btn btn-toggle">Carregant estat...</button>
            <a href="estadisticas.php" class="btn btn-admin">📊 Estadístiques</a>
            <a href="logout.php" class="btn btn-logout">🚪 Tancar Sessió</a>
        </div>
    </header>

    <div class="main-action">
        <button id="btn-siguiente" class="btn btn-success">🔔 CRIDAR SEGÜENT ALUMNE</button>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-title">Atenent ara mateix</div>
            <div class="big-info info-atendiendo" id="num-actual">--</div>
            <p id="nom-actual" class="student-name">Buscant...</p>
            
            <div id="zona-temps" class="timer-zone hidden">
                <p class="timer-text">Temps restant per presentar-se: <span id="comptador-enrere">20</span>s</p>
                <div class="progress-container">
                    <div id="barra-progres" class="progress-bar"></div>
                </div>
                <div style="margin-top: 15px;">
                    <button id="btn-presentat" class="btn" style="background-color: #2563eb; color: white; width: 100%; padding: 10px;">
                        🙋‍♂️ L'alumne s'ha presentat
                     </button>
                </div>
            </div>

            <div id="zona-avalua" class="evaluate-zone hidden">
                <button id="btn-apte" class="btn btn-apte">✅ APTE</button>
                <button id="btn-no-apte" class="btn btn-no-apte">❌ NO APTE</button>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Alumnes en espera</div>
            <div class="big-info info-espera" id="total-espera">0</div>
            <p class="text-muted">alumnes a la cua d'espera</p>
        </div>
    </div>

    <div class="card list-card">
        <div class="card-title list-title">Pròxims alumnes ordenats a la cua</div>
        <div id="llista-alumnes"></div>
    </div>
</div>

</body>
</html>