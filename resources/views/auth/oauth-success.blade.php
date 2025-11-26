<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $error ? 'Error de autenticación' : 'Autenticación exitosa' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            color: #333;
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
        }
        p {
            color: #666;
            margin: 0;
        }
        .success {
            color: #38a169;
        }
        .error {
            color: #e53e3e;
            background: #fff5f5;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #fc8181;
        }
        .error h1 {
            color: #e53e3e;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($error)
            {{-- Caso de error --}}
            <div class="error">
                <h1>Error de autenticación</h1>
                <p>{{ $message ?? 'No se pudo completar la autenticación. Por favor intenta nuevamente.' }}</p>
            </div>
        @elseif($token)
            {{-- Caso de éxito --}}
            <div class="success">
                <div class="spinner"></div>
                <h1>¡Autenticación exitosa!</h1>
                <p>{{ $message ?? 'Cerrando ventana...' }}</p>
            </div>
        @else
            {{-- Caso sin datos --}}
            <div class="error">
                <h1>Error</h1>
                <p>No se recibieron datos de autenticación.</p>
            </div>
        @endif
    </div>

    <script>
        @if($error)
            // CASO DE ERROR: Comunicar error al frontend
            const errorData = {
                error: @json($error),
                message: @json($message ?? 'Error de autenticación')
            };

            // Intentar comunicarse con la ventana padre (si fue abierta como popup)
            if (window.opener) {
                try {
                    window.opener.postMessage({
                        type: 'oauth-error',
                        data: errorData
                    }, '*');

                    // Cerrar esta ventana después de comunicar el error
                    setTimeout(() => {
                        window.close();
                    }, 3000);
                } catch (e) {
                    console.error('No se pudo comunicar con la ventana padre:', e);
                }
            }

            // También emitir evento local por si la página está en iframe
            window.dispatchEvent(new CustomEvent('oauth-error', {
                detail: errorData
            }));

        @elseif($token)
            // CASO DE ÉXITO: Comunicar datos al frontend
            const authData = {
                token: @json($token),
                customerId: @json($customerId),
                isNewCustomer: @json($isNewCustomer),
                message: @json($message)
            };

            // Guardar token en localStorage (por si el frontend lo necesita)
            try {
                localStorage.setItem('auth_token', authData.token);
                localStorage.setItem('customer_id', authData.customerId);
                localStorage.setItem('is_new_customer', authData.isNewCustomer ? '1' : '0');
            } catch (e) {
                console.warn('No se pudo guardar en localStorage:', e);
            }

            // Intentar comunicarse con la ventana padre (si fue abierta como popup)
            if (window.opener) {
                try {
                    window.opener.postMessage({
                        type: 'oauth-success',
                        data: authData
                    }, '*');

                    // Cerrar esta ventana después de comunicar los datos
                    setTimeout(() => {
                        window.close();
                    }, 1000);
                } catch (e) {
                    console.error('No se pudo comunicar con la ventana padre:', e);
                }
            } else {
                // Si no hay window.opener, intentar cerrar de todos modos
                setTimeout(() => {
                    window.close();
                }, 1000);
            }

            // También emitir evento local por si la página está en iframe
            window.dispatchEvent(new CustomEvent('oauth-success', {
                detail: authData
            }));
        @endif
    </script>
</body>
</html>
