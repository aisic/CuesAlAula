<?php
require_once 'seguridad_profesor.php';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Configuració de Pesos i RAs</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; padding: 40px; color: #1e293b; }
        .container { max-width: 600px; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); margin: 0 auto; }
        h1 { margin-top: 0; font-size: 1.5rem; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .ra-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .ra-name { font-weight: 600; color: #334155; }
        .input-hores { width: 80px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-size: 1rem; }
        .badge-pct { background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
        .btn-save { background: #2563eb; color: white; border: none; width: 100%; padding: 14px; border-radius: 8px; font-size: 1rem; font-weight: bold; cursor: pointer; margin-top: 20px; transition: background 0.2s; }
        .btn-save:hover { background: #1d4ed8; }
        .total-info { margin-top: 15px; font-size: 0.9rem; text-align: right; color: #64748b; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h1>⚙️ Configuració de Pesos (Hores Lectives per RA)</h1>
    <div id="llista-pesos">Carregant configuració...</div>
    <div class="total-info" id="total-hores">Total Hores: 0h</div>
    <button class="btn-save" onclick="guardarConfiguracion()">💾 Guardar Configuració</button>
</div>

<script>
let rasData = [];

document.addEventListener("DOMContentLoaded", carregarPesos);

async function carregarPesos() {
    const res = await fetch('api_config_notes.php?accio=llegir');
    const dades = await res.json();
    if(dades.success) {
        rasData = dades.ras;
        renderitzarInterficie();
    }
}

function renderitzarInterficie() {
    const contenidor = document.getElementById('llista-pesos');
    const totalHoresHtml = document.getElementById('total-hores');
    contenidor.innerHTML = '';
    
    // Càlcul del total d'hores primer per saber el percentatge exacte
    const totalHores = rasData.reduce((acc, curr) => acc + parseInt(curr.hores_lectives || 0), 0);
    totalHoresHtml.textContent = `Total Hores del Mòdul: ${totalHores}h`;

    rasData.forEach((ra, index) => {
        const hores = ra.hores_lectives;
        const pct = totalHores > 0 ? ((hores / totalHores) * 100).toFixed(1) : 0;

        const row = document.createElement('div');
        row.className = 'ra-row';
        row.innerHTML = `
            <span class="ra-name">${ra.CodiModul_RA}</span>
            <div>
                <input type="number" class="input-hores" value="${hores}" min="1" 
                       onchange="actualitzarHoraLocal(${index}, this.value)">
                <span style="margin-left:5px; color:#94a3b8; font-size:0.9rem;">h</span>
            </div>
            <span class="badge-pct">${pct}% del mòdul</span>
        `;
        contenidor.appendChild(row);
    });
}

function actualitzarHoraLocal(index, valor) {
    rasData[index].hores_lectives = parseInt(valor) || 0;
    renderitzarInterficie(); // Torna a calcular percentatges immediatament en temps real
}

async function guardarConfiguracion() {
    const res = await fetch('api_config_notes.php?accio=guardar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ pesos: rasData })
    });
    const dades = await res.json();
    if(dades.success) {
        alert("Pesos dels RAs guardats correctament!");
    } else {
        alert("Error: " + dades.error);
    }
}
</script>
</body>
</html>