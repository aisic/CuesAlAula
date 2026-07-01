// js/activitats.js

let idRaSeleccionat = null;

document.addEventListener("DOMContentLoaded", () => {
    carregarModulsInicials();

    // Event quan es canvia el mòdul del formulari de creació
    document.getElementById("select-modulo").addEventListener("change", (e) => {
        vincularRAsAlDropdown(e.target.value, "select-ra");
    });

    // Event quan es canvia el mòdul del filtre de la taula
    document.getElementById("select-modulo").addEventListener("change", (e) => {
        vincularRAsAlDropdown(e.target.value, "filtre-ra");
    });

    // Event de canvi de RA al filtre de llistat
    document.getElementById("filtre-ra").addEventListener("change", (e) => {
        idRaSeleccionat = e.target.value;
        carregarActivitatsDelRA(idRaSeleccionat);
    });

    document.getElementById("form-activitat").addEventListener("submit", crearActivitat);
});

async function carregarModulsInicials() {
    const res = await fetch('api_activitats.php?accio=llistar_moduls');
    const dades = await res.json();
    if(dades.success) {
        const selectMod = document.getElementById("select-modulo");
        let html = '<option value="">-- Selecciona un Mòdul --</option>';
        dades.moduls.forEach(m => {
            html += `<option value="${m.id_modul}">[${m.cicle_formatiu}] ${m.nom_modul}</option>`;
        });
        selectMod.innerHTML = html;
    }
}

async function vincularRAsAlDropdown(id_modul, elementId) {
    const selectTarget = document.getElementById(elementId);
    if(!id_modul) {
        selectTarget.innerHTML = '<option value="">Primer tria un mòdul...</option>';
        selectTarget.disabled = true;
        return;
    }

    const res = await fetch(`api_activitats.php?accio=llistar_ras&id_modul=${id_modul}`);
    const dades = await res.json();
    if(dades.success) {
        let html = '<option value="">-- Selecciona el RA --</option>';
        dades.ras.forEach(r => {
            html += `<option value="${r.id}">${r.CodiModul_RA} - ${r.nom_ra}</option>`;
        });
        selectTarget.innerHTML = html;
        selectTarget.disabled = false;
    }
}

async function carregarActivitatsDelRA(id_ra) {
    const tbody = document.getElementById("taula-activitats-body");
    if(!id_ra) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center">Selecciona un RA per veure les seves activitats estructurades.</td></tr>`;
        return;
    }

    const res = await fetch(`api_activitats.php?accio=llistar_activitats&id_ra=${id_ra}`);
    const dades = await res.json();
    if(dades.success) {
        tbody.innerHTML = "";
        const totalActivitats = dades.activitats.length;

        if(totalActivitats === 0) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center">Aquest RA no té cap activitat. Totes tindran un pes d'1/1 al començar.</td></tr>`;
            return;
        }

        // Lògica de divisió proporcional sol·licitada (1/N)
        const pesPercentatge = (100 / totalActivitats).toFixed(1);

        dades.activitats.forEach(act => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td><strong>${act.nom_activitat}</strong></td>
                <td><span class="badge-proporcio">Fracció: 1/${totalActivitats} (${pesPercentatge}%)</span></td>
                <td><button class="btn btn-danger" onclick="eliminarActivitat(${act.id_activitat_conceptual})">🗑️ Eliminar</button></td>
            `;
            tbody.appendChild(tr);
        });
    }
}

async function crearActivitat(e) {
    e.preventDefault();
    const id_ra = document.getElementById("select-ra").value;
    const dades = {
        id_ra: id_ra,
        nom_activitat: document.getElementById("input-nom-activitat").value
    };

    const res = await fetch('api_activitats.php?accio=crear_activitat', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(dades)
    });

    const result = await res.json();
    if(result.success) {
        alert("Activitat creada i assignada correctament!");
        document.getElementById("input-nom-activitat").value = "";
        
        // Sincronitzem el filtre i forcem refresc de la taula de pesos
        document.getElementById("filtre-ra").value = id_ra;
        idRaSeleccionat = id_ra;
        carregarActivitatsDelRA(id_ra);
    }
}

async function eliminarActivitat(id_act) {
    if(!confirm("Estàs segur que vols eliminar aquesta activitat? Això reajustarà els pesos de les restants.")) return;

    const res = await fetch(`api_activitats.php?accio=eliminar_activitat&id=${id_act}`, { method: 'POST' });
    const result = await res.json();
    if(result.success) {
        carregarActivitatsDelRA(idRaSeleccionat);
    }
}