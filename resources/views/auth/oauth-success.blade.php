<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticación exitosa</title>
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
        .error {
            color: #e53e3e;
            background: #fff5f5;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #fc8181;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($token)
            <div class="spinner"></div>
            <h1>¡Autenticación exitosa!</h1>
            <p>Redirigiendo a la aplicación...</p>
        @else
            <div class="error">
                <h1>Error</h1>
                <p>No se pudo completar la autenticación. Por favor intenta nuevamente.</p>
            </div>
        @endif
    </div>

    @if($token)
    <script>
        // Datos del usuario autenticado
        const authData = {
            token: @json($token),
            customerId: @json($customerId),
            isNewCustomer: @json($isNewCustomer),
            message: @json($message)
        };

        // Guardar token en localStorage
        localStorage.setItem('auth_token', authData.token);
        localStorage.setItem('customer_id', authData.customerId);
        localStorage.setItem('is_new_customer', authData.isNewCustomer ? '1' : '0');

        // Emitir evento para Livewire (si lo estás usando)
        window.dispatchEvent(new CustomEvent('oauth-success', {
            detail: authData
        }));

        // Redirigir después de guardar los datos
        setTimeout(() => {
            // Cambiar esta URL según tu aplicación
            // Opciones:
            // 1. Redirigir al dashboard
            window.location.href = '/home';

            // 2. O cerrar esta ventana si se abrió como popup
            // window.close();

            // 3. O redirigir a una ruta personalizada
            // window.location.href = '/dashboard';
        }, 1000);
    </script>
    @endif
</body>
</html>
