// ==========================================
// 🌍 ESTAT GLOBAL I CONFIGURACIÓ DE TRADUCCIONS
// ==========================================
let jaNotificat = false; // Controla que la notificació push del torn només s'enviï una vegada
let idActivitatActual = null; // Variable global per controlar si l'activitat canvia

window.I18n = {
    translations: {},
    translate: function(key) {
        return this.translations[key] || key;
    }
};

/**
 * Carrega asíncronament les traduccions des de la carpeta lang/ basant-se en la sessió de PHP
 */
async function inicialitzarIdioma() {
    try {
        // 1. Obtenim l'estat actual de la sessió de l'alumne (inclou l'idioma elegit)
        const respostaEstat = await fetch('api_alumno.php?accio=estat');
        const dadesEstat = await respostaEstat.json();
        const idiomaSessio = dadesEstat.lang || 'ca'; 
        
        // 2. Descarreguem el diccionari JSON actiu
        const respostaLang = await fetch(`lang/${idiomaSessio}.json`);
        window.I18n.translations = await respostaLang.json();
        
    } catch (error) {
        console.error("No s'han pogut carregar les traduccions, utilitzant sistema d'emergència:", error);
        // Fallback per evitar que la interfície es quedi en blanc si falla la xarxa
        window.I18n.translations = { "minutes": "min", "your_turn_is": "Torn:" };
    }
}

// ==========================================
// ⏳ CICLE DE VIDA I DISPARADORS D'EVENTS (DOM)
// ==========================================
document.addEventListener("DOMContentLoaded", async () => {
    // 1. Inicialització de l'idioma
    await inicialitzarIdioma(); 

    // 2. Sol·licitud preventiva de permisos per a notificacions d'escriptori
    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission();
    }
    
    // 3. Gestor de l'enviament del formulari complet amb el check elegit
    const formDemanarTorn = document.getElementById("form-demanar-torn");
    if (formDemanarTorn) {
        formDemanarTorn.addEventListener("submit", enviarSollicitudTorn);
    }

    // 4. Gestor per a desapuntar-se de la cua de manera immediata
    const btnDesapuntar = document.getElementById("desapuntarse-btn");
    if (btnDesapuntar) {
        btnDesapuntar.addEventListener("click", async () => {
            await accionarCua("desapuntarse");
        });
    }

    // 5. Engegada del Polling / Sincronització en calent d'estats cada 3 segons
    await comprovarEstatCua();
    setInterval(comprovarEstatCua, 3000);
});

// ==========================================
// 📊 CONTROL DE FLUX I SCRIPTOR DE LA CUA
// ==========================================

/**
 * Revisa en bucle l'estat actual de la cua, sincronitza títols del docent i commuta pantalles
 */
async function comprovarEstatCua() {
    try {
        // --- 🅰️ SINCRO 1: Llegim dades acadèmiques del mòdul i la pràctica de l'aula (api_gestion) ---
        const resGestion = await fetch('api_gestion.php?accio=estat');
        const dadesGestion = await resGestion.json();
        
        if (dadesGestion.success) {
            // Pintem la informació triada pel professor a la capçalera
            const titolH1 = document.getElementById('nombre-asignatura');
            if (titolH1) {
                titolH1.innerHTML = `${dadesGestion.nom_modul} (${dadesGestion.asignatura})<br><small style="font-size: 1rem; color: #475569; font-weight: normal;">📖 Pràctica d'avui: ${dadesGestion.nom_practica_activa}</small>`;
            }

            // Si el professor ha canviat la pràctica activa, buidem i recarreguem els seus checks
            if (dadesGestion.id_activitat_activa && dadesGestion.id_activitat_activa !== idActivitatActual) {
                idActivitatActual = dadesGestion.id_activitat_activa;
                await carregarChecksDeLaPractica(idActivitatActual);
            }
        }

        // --- 🅱️ SINCRO 2: Llegim l'estat personal del torn de l'alumne (api_alumno) ---
        const respostaAlumno = await fetch('api_alumno.php?accio=estat');
        const dadesAlumno = await respostaAlumno.json();
        
        const contenidorEstat = document.getElementById('estat-cua-contenidor');
        const textEstat = document.getElementById('estat-cua-text');
        const botoApuntar = document.getElementById('apuntarse-btn'); 
        const selectCheck = document.getElementById('alum-check');

        // Commutació dinàmica de visualitzacions d'espera / entrada de cua
        if (dadesAlumno.en_cua) {
            const seccioApuntar = document.getElementById('seccio-apuntarse');
            const seccioEspera = document.getElementById('seccio-espera');
            if (seccioApuntar) seccioApuntar.classList.add('hidden');
            if (seccioEspera) seccioEspera.classList.remove('hidden');
            
            const elMeuTorn = document.getElementById('el-meu-torn');
            const alumnesDavant = document.getElementById('alumnes-davant');
            const tempsEstimat = document.getElementById('temps-estimat');
            const textEstatTorn = document.getElementById('text-estat-torn');

            if (elMeuTorn) elMeuTorn.textContent = dadesAlumno.el_meu_torn;
            if (alumnesDavant) alumnesDavant.textContent = dadesAlumno.alumnes_davant;
            if (tempsEstimat) tempsEstimat.textContent = dadesAlumno.temps_estimat + " " + window.I18n.translate('minutes');

            if (dadesAlumno.estat_actual === 'atendiendo') {
                if (textEstatTorn) textEstatTorn.innerHTML = `<span style='color:#15803d; font-weight:bold;'>${window.I18n.translate('its_your_turn')}</span>`;
                llencarNotificacio();
            } else {
                if (textEstatTorn) textEstatTorn.textContent = window.I18n.translate('your_turn_is');
                jaNotificat = false; 
            }
        } else {
            const seccioApuntar = document.getElementById('seccio-apuntarse');
            const seccioEspera = document.getElementById('seccio-espera');
            if (seccioApuntar) seccioApuntar.classList.remove('hidden');
            if (seccioEspera) seccioEspera.classList.add('hidden');
            jaNotificat = false;
        }

        // Control de l'estat global de cua oberta o tancada per part del docent
        if (contenidorEstat && textEstat) {
            if (dadesAlumno.cola_abierta == 1) {
                textEstat.textContent = window.I18n.translate('queue_is_open');
                contenidorEstat.style.backgroundColor = "#e6f4ea";
                contenidorEstat.style.color = "#137333";
                selectCheck.disabled = false;

                if (botoApuntar) {
                    botoApuntar.removeAttribute('disabled');
                    botoApuntar.disabled = false;
                    botoApuntar.textContent = window.I18n.translate('btn_join'); 
                    botoApuntar.style.opacity = "1";
                    botoApuntar.style.cursor = "pointer";
                    botoApuntar.style.pointerEvents = "auto";
                }
            } else {
                textEstat.textContent = window.I18n.translate('queue_is_closed');
                contenidorEstat.style.backgroundColor = "#fce8e6";
                contenidorEstat.style.color = "#c5221f";
                selectCheck.disabled = true;
                
                if (botoApuntar) {
                    botoApuntar.setAttribute('disabled', 'true');
                    botoApuntar.disabled = true;
                    botoApuntar.textContent = window.I18n.translate('queue_closed_temporarily');
                    botoApuntar.style.opacity = "0.5";
                    botoApuntar.style.cursor = "not-allowed";
                    botoApuntar.style.pointerEvents = "none";
                }
            }
        }

    } catch (error) {
        console.error("Error en la connexió global del pol·ling de l'alumne:", error);
    }
}

/**
 * Funció helper per llistar directament els criteris/checks disponibles de la pràctica triada
 */
/*async function carregarChecksDeLaPractica(idActivitat) {
    const selectCheck = document.getElementById('alum-check');
    if (!selectCheck) return;

    try {
        const res = await fetch(`api_gestio_academica.php?accio=llistar_checks&id_activitat=${idActivitat}`);
        const dades = await res.json();
        
        selectCheck.innerHTML = '<option value="">-- Selecciona el criteri que defensaràs --</option>';
        
        if (dades.success && dades.checks) {
            dades.checks.forEach(c => {
                selectCheck.innerHTML += `<option value="${c.id_check}">${c.titol_check}</option>`;
            });
            selectCheck.disabled = false;
        } else {
            selectCheck.innerHTML = '<option value="">No hi ha checks associats a aquesta pràctica</option>';
            selectCheck.disabled = true;
        }
    } catch(e) {
        console.error("Error carregant criteris:", e);
    }
}
*/

/**
 * Funció helper per llistar directament els criteris/checks disponibles de la pràctica triada
 */
async function carregarChecksDeLaPractica(idActivitat) {
    const selectCheck = document.getElementById('alum-check');
    if (!selectCheck) return;

    try {
        // 🌟 Redirigim la consulta cap al nou endpoint d'api_gestion.php
        const res = await fetch(`api_gestion.php?accio=llistar_checks_alumne&id_activitat=${idActivitat}`);
        const dades = await res.json();
        
        selectCheck.innerHTML = '<option value="">-- Selecciona el criteri que defensaràs --</option>';
        
        if (dades.success && dades.checks && dades.checks.length > 0) {
            dades.checks.forEach(c => {
                selectCheck.innerHTML += `<option value="${c.id_check}">${c.titol_check}</option>`;
            });
            selectCheck.disabled = false; // Desbloquegem el selector 🎉
        } else {
            selectCheck.innerHTML = '<option value="">No hi ha checks associats a aquesta pràctica</option>';
            selectCheck.disabled = true;
        }
    } catch(e) {
        console.error("Error carregant criteris:", e);
        selectCheck.innerHTML = '<option value="">Error en carregar els criteris de l\'aula</option>';
        selectCheck.disabled = true;
    }
}
/**
 * Executa transaccions POST contra l'API de l'alumne (demanar o deixar torn)
 */
async function accionarCua(accio, cosDades = null) {
    try {
        const opcionsFetch = { method: 'POST' };
        
        if (cosDades) {
            opcionsFetch.headers = { 'Content-Type': 'application/json' };
            opcionsFetch.body = JSON.stringify(cosDades);
        }

        const resposta = await fetch(`api_alumno.php?accio=${accio}`, opcionsFetch);
        const textResposta = await resposta.text();
        
        let dades;
        try {
            dades = JSON.parse(textResposta);
        } catch (e) {
            console.error("El servidor ha retornat un format no JSON:", textResposta);
            alert(window.I18n.translate('invalid_json_error'));
            return;
        }

        if (dades && dades.success) {
            await comprovarEstatCua(); 
        } else {
            alert(window.I18n.translate('warning_prefix') + (dades.error || "Error indeterminat."));
        }
    } catch (error) {
        console.error("Error de xarxa en processar acció:", error);
    }
}

/**
 * Intercepta el formulari de sol·licitud i envia l'alumne a la cua amb el seu check triat
 */
async function enviarSollicitudTorn(e) {
    e.preventDefault();
    
    const selectCheck = document.getElementById("alum-check");
    if (!selectCheck) return;

    const idCheck = selectCheck.value;
    if (!idCheck) { 
        alert("Siusplau, tria el criteri concret que vols avaluar."); 
        return; 
    }

    // Canalitzem la petició amb el cos correcte cap al backend
    await accionarCua("demanar_turno", { id_check_evaluacio: idCheck });
}

// ==========================================
// 🔔 SISTEMA DE NOTIFICACIONS PUSH
// ==========================================

/**
 * Dispara una alerta emergent en l'escriptori de l'usuari si l'app es troba en segon pla
 */
function llencarNotificacio() {
    if (!jaNotificat && Notification.permission === "granted") {
        new Notification(window.I18n.translate('its_your_turn'), {
            body: window.I18n.translate('notification_body'),
            icon: "https://cdn-icons-png.flaticon.com/512/179/179133.png"
        });
        jaNotificat = true; // Evitem spam bloquejant el llançador fins al següent torn
    }
}