<?php
require_once 'seguridad_profesor.php';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panell d'Administració Acadèmica</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/gestion.css">
    <script src="js/admin.js" defer></script>
</head>
<body>

<div class="admin-wrapper">
    <header class="admin-header">
        <div>
            <h1 style="margin: 0;">📊 Panell de Gestió Acadèmica</h1>
            <p style="margin: 5px 0 0 0; color: #64748b;">Configuració de Cicles, Mòduls, RAs i Ponderacions Lectives</p>
        </div>
        <div>
            <a href="gestio_activitats.php" class="btn btn-activitats">📋 Gestionar Activitats</a>
            <a href="gestion.php" class="btn btn-gestio">↩️ Tornar al Panell de Gestió</a>
        </div>
    </div>
         
  </header>

    <div class="admin-grid">
        <div class="admin-card">
            <h2>📦 1. Crear Nou Mòdul</h2>
            <form id="form-modulo" class="admin-form">
                <div class="form-group">
                    <label for="mod-cicle">Cicle Formatiu:</label>
                    <input type="text" id="mod-cicle" placeholder="Ex: ASIX, DAM, DAW..." required>
                </div>
                <div class="form-group">
                    <label for="mod-curs">Curs:</label>
                    <select id="mod-curs">
                        <option value="1r">1r Curs</option>
                        <option value="2n">2n Curs</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="mod-nom">Nom del Mòdul:</label>
                    <input type="text" id="mod-nom" placeholder="Ex: M03 - Programació" required>
                </div>
                <button type="submit" class="btn btn-primary">Afegir Mòdul</button>
            </form>
        </div>

        <div class="admin-card">
            <h2>🔑 2. Crear i Assignar RA</h2>
            <form id="form-ra" class="admin-form">
                <div class="form-group">
                    <label for="ra-modulo">Mòdul Destí:</label>
                    <select id="ra-modulo" required>
                        <option value="">Carregant mòduls...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ra-codi">Codi del RA:</label>
                    <input type="text" id="ra-codi" placeholder="Ex: M03_RA1" required>
                </div>
                <div class="form-group">
                    <label for="ra-nom">Nom / Descripció del RA:</label>
                    <input type="text" id="ra-nom" placeholder="Ex: Programació orientada a objectes" required>
                </div>
                <div class="form-group">
                    <label for="ra-hores">Hores Lectives:</label>
                    <input type="number" id="ra-hores" value="10" min="1" required>
                </div>
                <button type="submit" class="btn btn-success">Assignar RA a Mòdul</button>
            </form>
        </div>
    </div>

    <div class="admin-card table-card">
        <h2>⚙️ 3. Llistat i Configuració de Pesos per RA</h2>
        <div class="filter-zone">
            <label for="filtre-modulo">Filtrar per veure pesos d'un mòdul:</label>
            <select id="filtre-modulo">
                </select>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Codi RA</th>
                    <th>Cicle / Curs</th>
                    <th>Cicle / Curs</th>
                    <th>Hores Lectives</th>
                    <th>Pes sobre el Mòdul</th>
                </tr>
            </thead>
            <tbody id="taula-pesos-body">
                <tr>
                    <td colspan="4" class="text-center">Selecciona o afegeix un mòdul per carregar les seves dades.</td>
                </tr>
            </tbody>
        </table>
        
        <div class="table-footer">
            <span id="total-hores-acumulades">Total hores: 0h</span>
            <button id="btn-guardar-pesos" class="btn btn-save">💾 Desar Canvis de Pesos</button>
        </div>
    </div>
</div>

</body>
</html>