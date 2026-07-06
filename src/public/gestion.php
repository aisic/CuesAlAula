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
            <a href="estadisticas.php" class="btn btn-stats">📊 Estadístiques</a>
            <a href="gestio_academica.php" class="btn btn-gestio">⚙️ Configuració Acadèmica</a>
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
            <p id="text-estat-torn" style="text-align: center; font-size: 0.9rem; margin-top: -5px;"></p>
            
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

            <div id="zona-avalua" class="hidden" style="background: white; padding: 25px; border-radius: 12px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">📋 Avaluació del Criteri Sol·licitat</h3>
                
                <div id="info-check-solicitat" style="background: #eff6ff; border-left: 4px solid #2563eb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: bold; text-transform: uppercase;">Activitat i Criteri a defensar:</p>
                    <h4 id="eval-titol-activitat" style="margin: 5px 0 2px 0; color: #0f172a; font-size: 1.1rem;">-</h4>
                    <p id="eval-titol-check" style="margin: 0; color: #334155; font-size: 0.95rem; font-style: italic;">-</p>
                </div>

                <div id="bloc-decisio-inicial" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button id="btn-no-apte" style="flex: 1; background-color: #dc2626; color: white; padding: 12px; font-weight: bold; border-radius: 6px; border: none; cursor: pointer; font-size: 1rem; transition: all 0.2s;">❌ Marcar No Apte</button>
                    <button id="btn-apte" style="flex: 1; background-color: #16a34a; color: white; padding: 12px; font-weight: bold; border-radius: 6px; border: none; cursor: pointer; font-size: 1rem; transition: all 0.2s;">✅ Marcar Apte</button>
                </div>

                <div id="bloc-avaluacio-checks" class="hidden">
                    <div style="margin-bottom: 15px;">
                        <label for="eval-pregunta" style="display:block; font-weight:bold; font-size:0.85rem; margin-bottom:5px; color:#475569;">Pregunta realitzada en aquest check:</label>
                        <textarea id="eval-pregunta" placeholder="Què li has preguntat a l'alumne sobre aquest criteri?" style="width:100%; border-radius:6px; border:1px solid #cbd5e1; height:65px; padding:8px; box-sizing:border-box;"></textarea>
                    </div>
                    <div class="avaluacio-multimedia-container" style="margin-top: 20px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                    🎓 Evidència de la Defensa de l'Alumne
                </h3>
                
                <div style="margin-bottom: 15px;">
                    <label for="resposta-text" style="display: block; font-weight: 600; margin-bottom: 5px; color: #475569; font-size: 0.9rem;">Observacions o text de la resposta:</label>
                    <textarea id="resposta-text" placeholder="Escriu aquí el resum de la defensa, codi comentat o respostes de l'alumne..." style="width: 100%; height: 100px; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-family: inherit; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #475569; font-size: 0.9rem;">Adjuntar Captura o Vídeo de l'acció:</label>
                    
                    <div id="drop-zone-gestion" style="border: 2px dashed #3b82f6; background-color: #f0f7ff; border-radius: 8px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.2s ease;">
                        <div id="drop-zone-prompt">
                            <span style="font-size: 2rem; display: block; margin-bottom: 8px;">📸 / 🎥</span>
                            <span style="color: #1d4ed8; font-weight: 600;">Arrossega aquí una captura (JPG/GIF) o un vídeo (MPEG)</span>
                            <span style="display: block; font-size: 0.8rem; color: #64748b; margin-top: 4px;">O fes clic per explorar el teu ordinador</span>
                        </div>
                        <input type="file" id="file-input-gestion" accept="image/jpeg,image/png,image/gif,video/mpeg,video/mp4" style="display: none;">
                        
                        <div id="preview-media-container" style="margin-top: 15px; display: none; background: white; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <div id="media-preview-box" style="max-width: 100%; max-height: 250px; overflow: hidden; display: flex; justify-content: center; align-items: center; margin-bottom: 10px;">
                                </div>
                            <p id="preview-media-info" style="font-size: 0.85rem; color: #475569; margin: 0 0 8px 0; font-weight: 500;"></p>
                            <button type="button" id="btn-eliminar-media" style="background: #ef4444; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600;">🗑️ Treure fitxer</button>
                        </div>
                    </div>
                </div>
            </div>

                    <button id="btn-desar-aval" class="btn" style="background-color: #2563eb; color: white; width: 100%; padding: 14px; font-weight: bold; border-radius: 6px; border: none; cursor: pointer; font-size: 1rem;">💾 Desar Check i Tancar Torn</button>
                </div>
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