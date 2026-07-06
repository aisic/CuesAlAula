// ==========================================
// 📊 ESTAT GLOBAL I CONTROL DE PANELS
// ==========================================
let cuaObertaActual = true;         // Estat d'obertura/tancament de la cua d'aula
let temporitzador;                  // Guardar la referència del setInterval del compte enrere
let tempsRestant = 20;              // Segons de cortesia per a l'arribada de l'alumne
let idDelTurnoActual = null;        // ID del torn actiu a la taula 'turnos'
let idCheckDelTornActual = null;    // ID del check individual que l'alumne demana avaluar
let estatTriat = null;              // Guarda de manera temporal la selecció 'apte' o 'no_apte'
let fitxerEvidencia = null;         // Variable global temporal per desar el fitxer arrossegat


// ==========================================
// ⏳ CICLE DE VIDA I DISPARADORS DE BOTONS
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    console.log("Panell de gestió centralitzada inicialitzat.");

    // 1. Commutador global de la cua d'aula (Obrir / Tancar)
    const btnLock = document.getElementById('btn-lock');
    if (btnLock) btnLock.addEventListener("click", toggleCua);

    // 2. Control de flux de la cua (Cridar següent en llista)
    const btnSiguiente = document.getElementById('btn-siguiente');
    if (btnSiguiente) btnSiguiente.addEventListener("click", cridarSiguiente);

    // 3. Confirmació de presència a l'aula
    const btnPresentat = document.getElementById('btn-presentat');
    if (btnPresentat) btnPresentat.addEventListener("click", alumneSHePresentat);

    // 4. Selectors d'estat (Guarden la decisió)
    const btnApte = document.getElementById('btn-apte');
    if (btnApte) btnApte.addEventListener("click", () => avaluaAlumne('apte'));

    const btnNoApte = document.getElementById('btn-no-apte');
    if (btnNoApte) btnNoApte.addEventListener("click", () => avaluaAlumne('no_apte'));

    // 5. Botó final d'enviament de tota l'avaluació (Unificada text + multimèdia)
    const btnDesarAval = document.getElementById('btn-desar-aval'); 
    if (btnDesarAval) btnDesarAval.addEventListener("click", finalitzarAval_CheckIndividual);
    
    inicialitzarDragAndDropGestion();

    // Engegada del Polling d'actualització dinàmica cada 4 segons
    carregarDadesPanell();
    setInterval(carregarDadesPanell, 4000);
});

// ==========================================
// 🔄 SINCRONITZACIÓ I RENDERITZACIÓ DE DADES (API)
// ==========================================

async function carregarDadesPanell() {
    try {
        const resposta = await fetch('api_gestion.php?accio=estat');
        const dades = await resposta.json(); 

        if (!dades.success) {
            console.error("L'API ha retornat un error de control:", dades.error);
            return;
        }

        const textCadenaClau = `${dades.nom_modul} (${dades.asignatura}) ➔ 📖 Pràctica activa: ${dades.nom_practica_activa}`;

        const titolGestion = document.getElementById('nom-asignatura');
        if (titolGestion) titolGestion.textContent = textCadenaClau;

        const titolIndex = document.getElementById('nombre-asignatura');
        if (titolIndex) titolIndex.textContent = textCadenaClau;

        if(document.getElementById('total-espera')) document.getElementById('total-espera').textContent = dades.en_espera;
        
        const alumneActiu = dades.atendiendo;
        if(document.getElementById('num-actual')) document.getElementById('num-actual').textContent = alumneActiu.turno_numero;
        if(document.getElementById('nom-actual')) document.getElementById('nom-actual').textContent = alumneActiu.nombre_alumno;
        
        idDelTurnoActual = alumneActiu.id_turno ?? null;

        const zonaAvalua = document.getElementById('zona-avalua');
        const zonaTemps = document.getElementById('zona-temps');
        
        if (alumneActiu.id_turno !== null) {
            if (zonaTemps && zonaTemps.classList.contains('hidden')) {
                if (zonaAvalua) zonaAvalua.classList.remove('hidden');
                if (document.getElementById("bloc-avaluacio-checks")) document.getElementById("bloc-avaluacio-checks").classList.remove("hidden");
            }
            omplirFitxaAvaluacioTorn(alumneActiu);
        } else {
            if (zonaAvalua) zonaAvalua.classList.add('hidden');
            if (zonaTemps && !zonaTemps.classList.contains('hidden')) {
                aturarTemporitzador();
            }
            idCheckDelTornActual = null;
        }

        cuaObertaActual = (dades.cola_abierta == 1);
        const btnLock = document.getElementById('btn-lock');
        if (btnLock) {
            if (cuaObertaActual) {
                btnLock.textContent = "🔒 Tancar Cua Alumnes";
                btnLock.style.backgroundColor = "#dc2626";
            } else {
                btnLock.textContent = "🔓 Obrir Cua Alumnes";
                btnLock.style.backgroundColor = "#16a34a";
            }
        }

        const contenidorLlista = document.getElementById('llista-alumnes');
        if (contenidorLlista) {
            contenidorLlista.innerHTML = "";
            if (dades.cua_llista.length === 0) {
                contenidorLlista.innerHTML = "<p class='empty-list-text'>No hi ha ningú esperant ara mateix.</p>";
            } else {
                dades.cua_llista.forEach((alumne, index) => {
                    const item = document.createElement('div');
                    item.className = 'list-item';
                    item.innerHTML = `
                        <span><strong>${index + 1}.</strong> ${alumne.nombre_alumno}</span>
                        <span class="badge" style="font-size:0.8rem; background-color:#f1f5f9; padding:2px 8px; border-radius:4px;">
                            ${alumne.titol_check ? alumne.titol_check : 'Torn General'} (T. ${alumne.turno_numero})
                        </span>
                    `;
                    contenidorLlista.appendChild(item);
                });
            }
        }

    } catch (error) {
        console.error("Error crític al carregar les dades de gestió:", error);
    }
}

async function toggleCua() {
    try {
        const nouEstat = !cuaObertaActual;
        await fetch('api_gestion.php?accio=toggle_cua', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ estat: nouEstat })
        });
        carregarDadesPanell();
    } catch (error) {
        console.error("Error al commutar la cua:", error);
    }
}

// ==========================================
// ⏰ LOGÍSTICA DEL COMPTE ENRERE
// ==========================================

async function cridarSiguiente() {
    try {
        const resposta = await fetch('api_gestion.php?accio=siguiente', { method: 'POST' });
        const resultat = await resposta.json();

        if (resultat.success) {
            if (resultat.quedaven_alumnes) {
                if (document.getElementById('eval-pregunta')) document.getElementById('eval-pregunta').value = '';
                // Corregit aquí: canviat 'eval-respuesta' per 'resposta-text'
                if (document.getElementById('resposta-text')) document.getElementById('resposta-text').value = '';
                estatTriat = null;
                marcarBotonsEstatVisual(null);
                iniciarTemporitzador(); 
            } else {
                alert("La cua està buida. No hi ha més alumnes per atendre!");
                aturarTemporitzador();
            }
        }
        carregarDadesPanell();
    } catch (error) {
        console.error("Error al cridar al següent alumne:", error);
    }
}

function iniciarTemporitzador() {
    aturarTemporitzador(); 
    tempsRestant = 20;
    
    const zonaTemps = document.getElementById('zona-temps');
    if (zonaTemps) zonaTemps.classList.remove('hidden');
    
    const zonaAvalua = document.getElementById('zona-avalua');
    if (zonaAvalua) zonaAvalua.classList.add('hidden'); 
    
    if(document.getElementById('comptador-enrere')) document.getElementById('comptador-enrere').textContent = tempsRestant;
    if(document.getElementById('barra-progres')) document.getElementById('barra-progres').style.width = '100%';

    temporitzador = setInterval(() => {
        tempsRestant--;
        if(document.getElementById('comptador-enrere')) document.getElementById('comptador-enrere').textContent = tempsRestant;
        if(document.getElementById('barra-progres')) document.getElementById('barra-progres').style.width = `${(tempsRestant / 20) * 100}%`;

        if (tempsRestant <= 0) {
            aturarTemporitzador();
            cridarSiguiente(); 
        }
    }, 1000);
}

function aturarTemporitzador() {
    clearInterval(temporitzador);
    const zonaTemps = document.getElementById('zona-temps');
    if (zonaTemps) zonaTemps.classList.add('hidden');
}

function alumneSHePresentat() {
    aturarTemporitzador();
    const zonaAvalua = document.getElementById('zona-avalua');
    if (zonaAvalua) zonaAvalua.classList.remove('hidden');
    
    const blocPreguntes = document.getElementById("bloc-avaluacio-checks");
    if (blocPreguntes) blocPreguntes.classList.remove('hidden');
    
    estatTriat = null;
    marcarBotonsEstatVisual(null);

    const txtEstat = document.getElementById('text-estat-torn');
    if (txtEstat) {
        txtEstat.innerHTML = "🟢 <span style='color:#16a34a; font-weight:bold;'>Alumne present a la taula</span>";
    }
}

function omplirFitxaAvaluacioTorn(dadesTorn) {
    const nouCheckId = dadesTorn.id_check_evaluacio;
    if (idCheckDelTornActual === nouCheckId) return;
    idCheckDelTornActual = nouCheckId;

    if (!idCheckDelTornActual) {
        if(document.getElementById("eval-titol-activitat")) document.getElementById("eval-titol-activitat").textContent = "⚠️ Torn d'antiga estructura";
        if(document.getElementById("eval-titol-check")) document.getElementById("eval-titol-check").textContent = "L'alumne no té cap check vàlid assignat.";
        if(document.getElementById("bloc-decisio-inicial")) document.getElementById("bloc-decisio-inicial").classList.add("hidden");
        return;
    }

    if(document.getElementById("eval-titol-activitat")) document.getElementById("eval-titol-activitat").textContent = dadesTorn.nom_activitat;
    if(document.getElementById("eval-titol-check")) document.getElementById("eval-titol-check").textContent = `Criteri a defensar: ${dadesTorn.titol_check}`;
    if(document.getElementById("bloc-decisio-inicial")) document.getElementById("bloc-decisio-inicial").classList.remove("hidden");
}

function avaluaAlumne(estat) {
    estatTriat = estat; 
    marcarBotonsEstatVisual(estat);
}

function marcarBotonsEstatVisual(estat) {
    const btnApte = document.getElementById('btn-apte');
    const btnNoApte = document.getElementById('btn-no-apte');
    if (!btnApte || !btnNoApte) return;

    if (estat === 'apte') {
        btnApte.style.border = "3px solid #10b981"; btnApte.style.opacity = "1";
        btnNoApte.style.opacity = "0.4"; btnNoApte.style.border = "none";
    } else if (estat === 'no_apte') {
        btnNoApte.style.border = "3px solid #ef4444"; btnNoApte.style.opacity = "1";
        btnApte.style.opacity = "0.4"; btnApte.style.border = "none";
    } else {
        btnApte.style.border = "none"; btnNoApte.style.border = "none";
        btnApte.style.opacity = "1"; btnNoApte.style.opacity = "1";
    }
}

// =========================================================================
// 💾 🚀 BOTÓ ÚNIC FINAL COMPLET (TEXT + MULTIMÈDIA SOTA FORMDATA)
// =========================================================================
async function finalitzarAval_CheckIndividual() {
    if (!idDelTurnoActual || !idCheckDelTornActual) { 
        alert("Dades invàlides de sessió. No es troba el check o el torn actiu."); 
        return; 
    }

    if (!estatTriat) {
        alert("Siusplau, marca primer si el resultat de la defensa ha evas estat 'Apte' o 'No Apte' abans de desar.");
        return;
    }

    const formData = new FormData();
    formData.append('id_turno', idDelTurnoActual);
    formData.append('id_check', idCheckDelTornActual);
    formData.append('resultat_prova', estatTriat);
    formData.append('pregunta', document.getElementById('eval-pregunta').value.trim());
    
    // 🌟 Corregit aquí: Apuntem correctament a 'resposta-text' que és com es diu al teu PHP
    formData.append('respuesta', document.getElementById('resposta-text').value.trim());

    if (fitxerEvidencia) {
        formData.append('resposta_fitxer', fitxerEvidencia);
    }

    try {
        aturarTemporitzador();

        const res = await fetch('api_gestion.php?accio=finalitzar_apte_individual', {
            method: 'POST',
            body: formData
        });
        
        const result = await res.json();
        if (result.success) {
            alert(`S'ha desat el check, l'evidència multimèdia i s'ha tancat el torn correctament.`);
            netejarPanellAvaluacioNatiu();
            netejarAdjuntMultimedia(); 
            carregarDadesPanell();
        } else {
            alert("Error del servidor: " + result.error);
        }
    } catch (e) { 
        console.error("Error al processar el tancament de l'avaluació unificada:", e); 
    }
}

function netejarPanellAvaluacioNatiu() {
    idCheckDelTornActual = null; idDelTurnoActual = null; estatTriat = null;
    if (document.getElementById('eval-pregunta')) document.getElementById('eval-pregunta').value = '';
    // Corregit aquí: canviat 'eval-respuesta' per 'resposta-text'
    if (document.getElementById('resposta-text')) document.getElementById('resposta-text').value = '';
    marcarBotonsEstatVisual(null);
    const txtEstat = document.getElementById('text-estat-torn');
    if (txtEstat) txtEstat.innerHTML = "";
    if(document.getElementById("bloc-avaluacio-checks")) document.getElementById("bloc-avaluacio-checks").classList.add("hidden");
    if(document.getElementById("zona-avalua")) document.getElementById("zona-avalua").classList.add("hidden"); 
}

// ==========================================
// 📥 GESTIÓ INTERACTIVA DRAG & DROP
// ==========================================
function inicialitzarDragAndDropGestion() {
    const dropZone = document.getElementById('drop-zone-gestion');
    const fileInput = document.getElementById('file-input-gestion');
    const btnEliminar = document.getElementById('btn-eliminar-media');

    if (!dropZone) return;

    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', (e) => processarFitxerEvidencia(e.target.files[0]));

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.backgroundColor = '#dbeafe'; dropZone.style.borderColor = '#1d4ed8';
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.style.backgroundColor = '#f0f7ff'; dropZone.style.borderColor = '#3b82f6';
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.style.backgroundColor = '#f0f7ff'; dropZone.style.borderColor = '#3b82f6';
        if (e.dataTransfer.files.length > 0) {
            processarFitxerEvidencia(e.dataTransfer.files[0]);
        }
    });

    if(btnEliminar) {
        btnEliminar.addEventListener('click', (e) => {
            e.stopPropagation();
            netejarAdjuntMultimedia();
        });
    }
}

function processarFitxerEvidencia(file) {
    if (!file) return;

    const formatsPermesos = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png', 'video/mpeg', 'video/mp4', 'video/quicktime'];
    if (!formatsPermesos.includes(file.type)) {
        alert("Format no vàlid. Només s'accepten imatges (JPG/GIF) o vídeos (MPEG/MP4).");
        return;
    }

    fitxerEvidencia = file;

    const promptDiv = document.getElementById('drop-zone-prompt');
    const previewContainer = document.getElementById('preview-media-container');
    const previewBox = document.getElementById('media-preview-box');
    const infoText = document.getElementById('preview-media-info');

    if(promptDiv) promptDiv.style.display = 'none';
    if(previewContainer) previewContainer.style.display = 'block';
    
    const midaMB = (file.size / (1024 * 1024)).toFixed(2);
    if(infoText) infoText.textContent = `📂 ${file.name} (${midaMB} MB)`;

    const objectURL = URL.createObjectURL(file);
    if (file.type.startsWith('image/') && previewBox) {
        previewBox.innerHTML = `<img src="${objectURL}" style="max-width: 100%; max-height: 200px; border-radius: 4px; object-fit: contain;">`;
    } else if (file.type.startsWith('video/') && previewBox) {
        previewBox.innerHTML = `<video src="${objectURL}" controls style="max-width: 100%; max-height: 200px; border-radius: 4px; background: #000;"></video>`;
    }
}

function netejarAdjuntMultimedia() {
    fitxerEvidencia = null;
    const fileInput = document.getElementById('file-input-gestion');
    if (fileInput) fileInput.value = "";
    
    if(document.getElementById('drop-zone-prompt')) document.getElementById('drop-zone-prompt').style.display = 'block';
    if(document.getElementById('preview-media-container')) document.getElementById('preview-media-container').style.display = 'none';
    if(document.getElementById('media-preview-box')) document.getElementById('media-preview-box').innerHTML = '';
}