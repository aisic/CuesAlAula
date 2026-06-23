<?php
session_start();
// Opcional: Si ja hi ha sessions actives, podríem redirigir automàticament,
// però és millor deixar el portal obert com a node central.
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Cues - Portal d'Accés</title>
    <link href="/public/css/landing.css" rel="stylesheet">
</head>
<body>

<div class="landing-wrapper">
    <header class="landing-header">
        <h1>⏱️ Sistema de Gestió de Cues</h1>
        <p>Benvingut al portal d'accés en temps real. Selecciona el teu perfil per continuar.</p>
    </header>

    <main class="landing-grid">
        <div class="landing-card card-alumno">
            <div class="card-icon">👨‍🎓</div>
            <h2>Espai Alumnes</h2>
            <p>Apunta't a la cua, consulta el teu torn actual i rep notificacions quan el professor et cridi.</p>
            <a href="public/alumno.php" class="landing-btn btn-alumno">Entrar com Alumne</a>
        </div>

        <div class="landing-card card-profesor">
            <div class="card-icon">👨‍🏫</div>
            <h2>Panell de Gestió</h2>
            <p>Accedeix per obrir/tancar la cua, cridar al següent alumne, avaluar i consultar estadístiques.</p>
            <a href="public/gestion.php" class="landing-btn btn-profesor">Panell Professor</a>
        </div>

        <div class="landing-card card-projector">
            <div class="card-icon">📺</div>
            <h2>Pantalla de l'Aula</h2>
            <p>Obre la vista pública dissenyada per a projectors. Mostra el torn actual i el temps d'espera.</p>
            <a href="public/index.php" class="landing-btn btn-projector">Obre el Projector</a>
        </div>
    </main>

    <footer class="landing-footer">
        © 2026 Sistema d'Organització de Cues Acadèmiques • Desenvolupat per a entorns de rendiment en temps real.
    </footer>
</div>

</body>
</html>