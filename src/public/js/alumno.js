// js/alumno.js
let jaNotificat = false;

// Demanar permís per a les notificacions només entrar
if (Notification.permission === "default") {
    Notification.requestPermission();
}

async function comprovarEstatCua() {
    try {
        const resposta = await fetch('api_alumno.php?accio=estat');
        const dades = await resposta.json();
        
        const contenidorEstat = document.getElementById('estat-cua-contenidor');
        const textEstat = document.getElementById('estat-cua-text');
        const botoApuntar = document.getElementById('apuntarse-btn'); 

        // 1. CONTROL DE VISIBILITAT DE PANTALLES
        if (dades.en_cua) {
            document.getElementById('seccio-apuntarse').classList.add('hidden');
            document.getElementById('seccio-espera').classList.remove('hidden');
            
            document.getElementById('el-meu-torn').textContent = dades.el_meu_torn;
            document.getElementById('alumnes-davant').textContent = dades.alumnes_davant;
            document.getElementById('temps-estimat').textContent = dades.temps_estimat + " min";

            if (dades.estat_actual === 'atendiendo') {
                document.getElementById('text-estat-torn').innerHTML = "<span style='color:#15803d; font-weight:bold;'>¡ÉS EL TEU TORN! Passa al lloc del professor</span>";
                llencarNotificacio();
            } else {
                document.getElementById('text-estat-torn').textContent = "El teu número de torn és:";
                jaNotificat = false; 
            }
        } else {
            document.getElementById('seccio-apuntarse').classList.remove('hidden');
            document.getElementById('seccio-espera').classList.add('hidden');
            jaNotificat = false;
        }

        // 2. CONTROL UNIFICAT DE L'ESTAT DE LA CUA (Sense duplicats)
        if (contenidorEstat && textEstat) {
            if (dades.cola_abierta == 1) {
                textEstat.textContent = "🟢 LA CUA ESTÀ OBERTA (Pots demanar torn)";
                contenidorEstat.style.backgroundColor = "#e6f4ea";
                contenidorEstat.style.color = "#137333";

                if (botoApuntar) {
                    botoApuntar.removeAttribute('disabled');
                    botoApuntar.disabled = false;
                    botoApuntar.textContent = "Apuntar-me a la Cua"; 
                    botoApuntar.style.opacity = "1";
                    botoApuntar.style.cursor = "pointer";
                    botoApuntar.style.pointerEvents = "auto";
                }
            } else {
                textEstat.textContent = "🔴 LA CUA ESTÀ TANCADA PEL PROFESSOR";
                contenidorEstat.style.backgroundColor = "#fce8e6";
                contenidorEstat.style.color = "#c5221f";

                if (botoApuntar) {
                    botoApuntar.setAttribute('disabled', 'true');
                    botoApuntar.disabled = true;
                    botoApuntar.textContent = "🔒 Cua tancada temporalment";
                    botoApuntar.style.opacity = "0.5";
                    botoApuntar.style.cursor = "not-allowed";
                    botoApuntar.style.pointerEvents = "none";
                }
            }
        }

    } catch (error) {
        console.error("Error en la connexió al comprovar estat:", error);
    }
}

// 🟢 FUNCIÓ SECURE: Gestiona l'acció controlant els errors de JSON retornats pel PHP
async function accionarCua(accio) {
    try {
        const resposta = await fetch(`api_alumno.php?accio=${accio}`, { method: 'POST' });
        
        // Llegim el text de resposta directament per avaluar si està buit o malformat
        const textResposta = await resposta.text();
        
        let dades;
        try {
            dades = JSON.parse(textResposta);
        } catch (e) {
            console.error("El servidor ha retornat un format no JSON:", textResposta);
            alert("Error crític del servidor: La resposta no és un JSON vàlid.");
            return;
        }

        if (dades && dades.success) {
            // Si la base de dades s'ha guardat correctament, actualitzem la vista de seguida
            await comprovarEstatCua(); 
        } else {
            alert("Atenció: " + (dades.error || "No s'ha pogut processar la petició."));
        }
    } catch (error) {
        console.error("Error de xarxa en processar acció:", error);
    }
}

document.addEventListener("DOMContentLoaded", () => { 
    const btnApuntar = document.getElementById("apuntarse-btn");
    if (btnApuntar) {
        btnApuntar.addEventListener("click", async () => {
            if ("Notification" in window && Notification.permission === "default") {
                await Notification.requestPermission();
            }
            await accionarCua("apuntarse");
        });
    }

    const btnDesapuntar = document.getElementById("desapuntarse-btn");
    if (btnDesapuntar) {
        btnDesapuntar.addEventListener("click", async () => {
            await accionarCua("desapuntarse");
        });
    }
});

function llencarNotificacio() {
    if (!jaNotificat && Notification.permission === "granted") {
        new Notification("¡És el teu torn!", {
            body: "El professor et crida per a la revisió.",
            icon: "https://cdn-icons-png.flaticon.com/512/179/179133.png"
        });
        jaNotificat = true;
    }
}

// Inicialització del Polling actiu
comprovarEstatCua();
setInterval(comprovarEstatCua, 3000);