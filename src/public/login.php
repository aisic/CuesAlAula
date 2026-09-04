<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cua d'Alumnes</title>
    <link href="css/login.css" rel="stylesheet">

    <!-- 1. Primer definim la funció de resposta -->
    <script>
        async function handleCredentialResponse(response) {
            try {
                // Enviem el token rebut de Google al nostre backend de PHP
                const resposta = await fetch('api_oauth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: response.credential })
                });
                
                const resultat = await resposta.json();
                
                // Alert de diagnòstic
                alert("Resultat de la connexió: " + JSON.stringify(resultat));
                
                if (resultat.success) {
                    // Redirigim a la pantalla de l'alumne
                    window.location.href = 'alumno.php';
                } else {
                    const errorDiv = document.getElementById('error');
                    errorDiv.textContent = resultat.error || 'Error en l\'autenticació';
                    errorDiv.style.display = 'block';
                }
            } catch (e) {
                console.error("Error en el procés de login:", e);
                const errorDiv = document.getElementById('error');
                errorDiv.textContent = 'Error de connexió amb el servidor.';
                errorDiv.style.display = 'block';
            }
        }
    </script>

    <!-- 2. Carreguem la llibreria de Google DESPRÉS de definir la funció -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

<div class="login-card" data-perfil="alumno">
    <h1>Accés alumne</h1>
    <p>Identifica't amb el teu correu del centre per demanar el teu torn.</p>
    
    <div id="error" class="error-msg" style="display: none; color: red; margin-bottom: 10px;"></div>

    <div id="g_id_onload"
         data-client_id="569428212376-8bnfus0c5tal7q4d45j9c9sl8t8064oj.apps.googleusercontent.com"
         data-callback="handleCredentialResponse"
         data-auto_prompt="false">
    </div>
    <div class="g_id_signin" data-type="standard" data-size="large" data-theme="filled_blue"></div>

</div>

</body>
</html>