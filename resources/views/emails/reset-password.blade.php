<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
            max-width: 100vw;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
        }
        
        .header {
            background-color: #ffffff;
            padding: 32px 24px 24px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }
        
        .logo::after {
            content: '';
            display: block;
            width: 60px;
            height: 2px;
            background-color: #008938;
            margin: 16px auto 0;
        }
        
        .content {
            padding: 32px 24px;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }
        
        .description {
            color: #6b7280;
            margin-bottom: 24px;
            font-size: 16px;
        }
        
        .cta-container {
            text-align: center;
            margin: 32px 0;
        }
        
        .cta-button {
            display: inline-block;
            background-color: #008938 !important;
            color: #ffffff !important;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.2s ease;
            border: none;
            cursor: pointer;
            min-width: 200px;
        }
        
        .cta-button:hover {
            background-color: #00491e;
        }
        
        .cta-button:focus {
            outline: 2px solid #01757bff;
            outline-offset: 2px;
        }
        
        .expiration-notice {
            margin: 24px 0;
        }
        
        .expiration-text {
            color: #374151;
            font-size: 14px;
            font-weight: 500;
        }
        
    
        
        .fallback-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .fallback-title {
            font-weight: 500;
            color: #374151;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .fallback-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .url-container {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 16px;
            border-radius: 6px;
            margin-top: 12px;
            max-width: 100%;
            box-sizing: border-box;
            overflow-wrap: break-word;
            word-wrap: break-word;
            white-space: normal;
            overflow-x: auto;
        }
        
        .url-text {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 12px;
            color: #6b7280;
            word-break: break-all;
            line-height: 1.4;
            max-width: 100%;
            overflow-wrap: break-word;
            white-space: normal;
            display: block;
        }
        
        .footer {
            background-color: #f9fafb;
            padding: 16px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-text {
            color: #9ca3af;
            font-size: 12px;
            line-height: 1.4;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 16px;
                max-width: 100vw;
                overflow-x: hidden;
            }
            
            .container {
                margin: 0;
                border-radius: 6px;
                width: 100%;
                max-width: 100%;
            }
            
            .header, .content, .footer {
                padding: 24px 20px;
                max-width: 100%;
                box-sizing: border-box;
            }
            
            .logo {
                font-size: 24px;
            }
            
            .greeting {
                font-size: 18px;
            }
            
            .cta-button {
                padding: 14px 24px;
                font-size: 15px;
                width: 100%;
                min-width: auto;
                max-width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
        </div>
        
        <div class="content">
            <div class="greeting">¡Hola!</div>
            
            <div class="description">
                Estás recibiendo este correo porque recibimos una solicitud de restablecimiento de contraseña para tu cuenta.
            </div>
            
            <div class="cta-container">
                <a href="{{ $url }}" class="cta-button" style="background-color: #008938; color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 6px; font-weight: 600; font-size: 16px; display: inline-block; min-width: 200px;">Restablecer Contraseña</a>
            </div>
            
            <div class="expiration-notice">
                <div class="expiration-text">
                    Este enlace de restablecimiento de contraseña expirará en 60 minutos.
                </div>
            </div>
            
            <div class="fallback-section">
               
                <div class="fallback-text">
                    Si tienes problemas para hacer clic en el botón "Restablecer Contraseña", copia y pega la URL de abajo en tu navegador web:
                </div>
                
                <div class="url-container">
                    <span class="url-text">{{ $url }}</span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-text">
                © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </div>
        </div>
    </div>
</body>
</html>