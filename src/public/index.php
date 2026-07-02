<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Cua - Projector</title>
    <link href="css/index.css" rel="stylesheet">
    <script src="js/pantalla.js" defer></script>
</head>
<body>

    <div id="banner-estat-projector" class="banner-projector">
        <span id="text-estat-projector">Comprovant estat de la sessió...</span>
    </div>

    <header class="projector-header" style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
        <h1 id="nombre-asignatura" style="font-size: 2.2rem; color: #1e3a8a; margin: 0; font-weight: 800; line-height: 1.3;">
            🔄 Carregant assignatura i sessió de classe...
        </h1>
        <p style="margin: 5px 0 0 0; color: #64748b; font-size: 1.1rem;">Estat de la cua de consultes i defenses en viu</p>
    </header>

    <main>
        <div class="card">
            <div class="card-title">Torn Actual</div>
            <div class="card-value" id="turno-actual">--</div>
        </div>

        <div class="sidebar-cards">
            <div class="card next-card flex-center">
                <div class="card-title">Pròxim</div>
                <div class="card-value" id="turno-proximo">--</div>
            </div>
            <div class="card time-card flex-center">
                <div class="card-title">Temps Mitjà d'Espera</div>
                <div class="card-value" id="tiempo-espera">--</div>
            </div>
        </div>
    </main>

    <footer>
        Sistema de Gestió de Cues © 2026 - Actualització automàtica en temps real
    </footer>

</body>
</html>