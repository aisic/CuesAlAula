// js/login-google.js

async function handleCredentialResponse(response) {
    try {
       // 1. Detectem automàticament quin perfil té la pàgina actual
       // const loginCard = document.querySelector('.login-card');
       // const perfilUsuari = loginCard ? loginCard.getAttribute('data-perfil') : 'alumno';

        // 2. Enviem el token i el perfil a l'API
        const resposta = await fetch('api_oauth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: response.credential })
        });
        const resultat = await resposta.json();
        
        if (resultat.success) {
            // 3. Redirigim a la pàgina que ens digui l'API de manera dinàmica
            window.location.href = resultat.redireccio;
        } else {
            alert(resultat.error || "Error en la connexió amb Google");
        }
    } catch (e) {
        console.error("Error durant l'autenticació:", e);
    }
}

// 🟢 Motiu de seguretat/compatibilitat: 
// Exposem la funció globalment perquè el script de Google la pugui cridar des de l'iframe
window.handleCredentialResponse = handleCredentialResponse;