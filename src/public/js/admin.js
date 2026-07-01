// js/admin.js

let llistaModuls = [];
let llistaRasActuals = [];
let idModulSeleccionat = null;

document.addEventListener("DOMContentLoaded", () => {
    // Carregar dropdowns inicials
    carregarModuls();

    // Event listeners de formularis
    document.getElementById("form-modulo").addEventListener("submit", crearModulo);
    document.getElementById("form-ra").addEventListener("submit", crearRA);
    
    // Canvi de filtre de la taula de pesos
    document.getElementById("filtre-modulo").addEventListener("change", (e) => {
        idModulSeleccionat = e.target.value;
        carregarRasDelModulo(idModulSeleccionat);
    });

    // Guardar modificacions de hores a la taula
    document.getElementById("btn-guardar-pesos").addEventListener("click", guardarCanvisPesos);
});

async function carregarModuls() {
    const res = await fetch('api_gestio_academica.php?accio=llistar_moduls');
    const dades = await res.json();
    if(dades.success) {
        llistaModuls = dades.moduls;
        
        // Omplim els select de l'HTML
        const selectRA = document.getElementById("ra-modulo");
        const selectFiltre = document.getElementById("filtre-modulo");
        
        let opcions = '<option value="">-- Selecciona un Mòdul --</option>';
        llistaModuls.forEach(m => {
            opcions += `<option value="${m.id_modul}">[${m.cicle_formatiu} - ${m.curs}] ${m.nom_modul}</option>`;
        });
        
        selectRA.innerHTML = opcions;
        selectFiltre.innerHTML = opcions;

        // Si teníem un mòdul seleccionat el mantenim
        if (idModulSeleccionat) {
            selectFiltre.value = idModulSeleccionat;
        }
    }
}

async function carregarRasDelModulo(id_modul) {
    if(!id_modul) {
        document.getElementById("taula-pesos-body").innerHTML = `<tr><td colspan="4" class="text-center">Selecciona un mòdul per carregar les dades.</td></tr>`;
        document.getElementById("total-hores-acumulades").textContent = "Total hores: 0h";
        return;
    }

    const res = await fetch(`api_gestio_academica.php?accio=llistar_ras&id_modul=${id_modul}`);
    const dades = await res.json();
    if(dades.success) {
        llistaRasActuals = dades.ras;
        renderitzarTaulaPesos();
    }
}

function renderitzarTaulaPesos() {
    const tbody = document.getElementById("taula-pesos-body");
    tbody.innerHTML = "";

    const totalHores = llistaRasActuals.reduce((acc, curr) => acc + parseInt(curr.hores_lectives || 0), 0);
    document.getElementById("total-hores-acumulades").textContent = `Total hores del Mòdul: ${totalHores}h`;

    if(llistaRasActuals.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center">Aquest mòdul encara no té RAs assignats.</td></tr>`;
        return;
    }

    llistaRasActuals.forEach((ra, index) => {
        const hores = ra.hores_lectives;
        const pct = totalHores > 0 ? ((hores / totalHores) * 100).toFixed(1) : 0;

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td><strong>${ra.CodiModul_RA}</strong></td>
            <td><span style="color: #64748b; font-size: 0.95rem;">${ra.nom_ra}</span></td> <td>${ra.cicle_formatiu} / ${ra.curs}</td>
            <td>
                <input type="number" class="input-table-hores" value="${hores}" min="1"
                       onchange="canviHoraLocal(${index}, this.value)">h
            </td>
            <td><span class="badge-pct">${pct}% de la nota</span></td>
        `;
        tbody.appendChild(tr);
    });
}

function canviHoraLocal(index, valor) {
    llistaRasActuals[index].hores_lectives = parseInt(valor) || 0;
    renderitzarTaulaPesos(); // Recalcula instantàniament en pantalla
}

async function crearModulo(e) {
    e.preventDefault();
    const dades = {
        cicle: document.getElementById("mod-cicle").value,
        curs: document.getElementById("mod-curs").value,
        nom: document.getElementById("mod-nom").value
    };

    const res = await fetch('api_gestio_academica.php?accio=crear_modulo', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(dades)
    });
    
    const result = await res.json();
    if(result.success) {
        alert("Mòdul creat correctament!");
        document.getElementById("form-modulo").reset();
        carregarModuls(); // Refresca els dropdowns dinàmicament
    }
}

async function crearRA(e) {
    e.preventDefault();
    const id_modul = document.getElementById("ra-modulo").value;
    const dades = {
        id_modul: id_modul,
        codi_ra: document.getElementById("ra-codi").value,
        nom_ra: document.getElementById("ra-nom").value,
        hores: document.getElementById("ra-hores").value
    };

    const res = await fetch('api_gestio_academica.php?accio=crear_ra', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(dades)
    });

    const result = await res.json();
    if(result.success) {
        alert("RA Assignat correctament!");
        document.getElementById("form-ra").reset();
        idModulSeleccionat = id_modul;
        carregarModuls();
        carregarRasDelModulo(id_modul); // Actualitza la taula automàticament
    }
}

async function guardarCanvisPesos() {
    if(!idModulSeleccionat) return;

    const res = await fetch('api_gestio_academica.php?accio=guardar_pesos', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ pesos: llistaRasActuals })
    });

    const result = await res.json();
    if(result.success) {
        alert("Totes les hores de la taula s'han desat de manera permanent!");
        carregarRasDelModulo(idModulSeleccionat);
    }
}