<?php
require_once 'seguridad_profesor.php';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestió d'Activitats per RA</title>
    <link rel="stylesheet" href="css/activitats.css">
    <script src="js/activitats.js" defer></script>
</head>
<body>

<div class="act-wrapper">
    <header class="act-header">
        <div>
            <h1>📝 Gestió d'Activitats de l'Aula</h1>
            <p>Defineix tasques o exàmens per a cada un dels RAs configurats</p>
        </div>
        <div>
            <a href="gestio_academica.php" class="btn btn-back">↩️ Tornar a l'Administració</a>
        </div>
    </header>

    <div class="act-grid">
        <div class="act-card">
            <h2>Crear Nova Activitat</h2>
            <form id="form-activitat" class="act-form">
                <div class="form-group">
                    <label for="select-modulo">1. Selecciona el Mòdul:</label>
                    <select id="select-modulo" required>
                        <option value="">Carregant mòduls...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="select-ra">2. Selecciona el RA destí:</label>
                    <select id="select-ra" required disabled>
                        <option value="">Primer tria un mòdul...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="input-nom-activitat">3. Nom de l'activitat:</label>
                    <input type="text" id="input-nom-activitat" placeholder="Ex: Pràctica Docker, Examen Final..." required>
                </div>
                <button type="submit" class="btn btn-submit">Crear i Assignar Activitat</button>
            </form>
        </div>

        <div class="act-card table-card">
            <h2>Llistat d'Activitats i Pes Equitatiu ($1/N$)</h2>
            <div class="filter-box">
                <label for="filtre-ra">Filtrar per veure distribucions del RA:</label>
                <select id="filtre-ra" disabled>
                    <option value="">Selecciona un mòdul primer...</option>
                </select>
            </div>

            <table class="act-table">
                <thead>
                    <tr>
                        <th>Nom de l'Activitat</th>
                        <th>Pes Calculat (Proporcional)</th>
                        <th>Acció</th>
                    </tr>
                </thead>
                <tbody id="taula-activitats-body">
                    <tr>
                        <td colspan="3" class="text-center">Selecciona un RA per veure les seves activitats estructurades.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>