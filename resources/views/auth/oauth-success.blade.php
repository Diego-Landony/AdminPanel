<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $error ? 'Error' : 'Autenticación exitosa' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #fff;
            color: #000;
        }
        .container {
            text-align: center;
            max-width: 400px;
            padding: 2rem;
        }
        .spinner {
            border: 2px solid #eee;
            border-top: 2px solid #000;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h1 {
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 0.875rem;
            color: #666;
        }
        .error h1 { color: #dc2626; }

        @media (prefers-color-scheme: dark) {
            body {
                background: #000;
                color: #fff;
            }
            .spinner {
                border-color: #333;
                border-top-color: #fff;
            }
            p {
                color: #999;
            }
            .error h1 { color: #ef4444; }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($error)
            <h1>Error</h1>
            <p>{{ $message ?? 'No se pudo completar la autenticación.' }}</p>
        @elseif($token)
            <div class="spinner"></div>
            <h1>Autenticación exitosa</h1>
            <p>{{ $message ?? 'Cerrando...' }}</p>
        @else
            <h1>Error</h1>
            <p>No se recibieron datos.</p>
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
