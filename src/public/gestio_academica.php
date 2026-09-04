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
    </header>

    <div class="admin-card" style="margin-bottom: 25px; border-left: 5px solid #2563eb; background: #f8fafc;">
        <h2 style="color: #1e3a8a; margin-top: 0;">🎯 0. Establir Unitat Didàctica i Pràctica d'Avui (Sessió Activa)</h2>
        <p style="font-size: 0.9rem; color: #475569; margin-top: -5px;">El que seleccionis aquí canviarà instantàniament el títol a la cua pública, el teu panell i filtrarà els checks al mòbil dels alumnes.</p>
        
        <form id="form-sessio-activa" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; margin-top: 15px;">
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label for="select-sessio-modulo" style="font-weight: bold; color: #334155;">1. Selecciona Mòdul:</label>
                <select id="select-sessio-modulo" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;" required>
                    <option value="">Selecciona mòdul...</option>
                </select>
            </div>

            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label for="select-sessio-ra" style="font-weight: bold; color: #334155;">2. Selecciona RA:</label>
                <select id="select-sessio-ra" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;" disabled required>
                    <option value="">Abans tria un mòdul...</option>
                </select>
            </div>

            <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
                <label for="select-sessio-practica" style="font-weight: bold; color: #334155;">3. Pràctica/Activitat Activa:</label>
                <select id="select-sessio-practica" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;" disabled required>
                    <option value="">Abans tria un RA...</option>
                </select>
            </div>

            <button type="submit" id="btn-activar-sessio" class="btn btn-primary" style="background-color: #2563eb; padding: 12px 20px; height: 42px; font-weight: bold;">
                🚀 Activar a tota l'Aula
            </button>
        </form>
    </div>

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
                    <input type="text" id="mod-nom" placeholder="Ex: C037 - Seguretat en Sistemes, xarxes i serveis" required>
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
            <select id="filtre-modulo"></select>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Codi RA</th>
                    <th>Nom del RA</th>
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