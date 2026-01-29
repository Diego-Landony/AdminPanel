<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#009743">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .header {
            background: #009743;
            padding: 1rem 2rem;
            text-align: center;
        }

        .header img {
            max-height: 50px;
        }

        .header h1 {
            color: #fff;
            font-size: 1.25rem;
            margin-top: 0.5rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .content {
            background: #fff;
        }

        .content h1, .content h2, .content h3 {
            color: #009743;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .content h1 { font-size: 1.75rem; }
        .content h2 { font-size: 1.5rem; }
        .content h3 { font-size: 1.25rem; }

        .content p {
            margin-bottom: 1rem;
            text-align: justify;
        }

        .content ul, .content ol {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }

        .content li {
            margin-bottom: 0.5rem;
        }

        .content a {
            color: #009743;
        }

        .meta {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            font-size: 0.875rem;
            color: #666;
        }

        .footer {
            background: #f5f5f5;
            padding: 1.5rem 2rem;
            text-align: center;
            font-size: 0.875rem;
            color: #666;
            margin-top: 2rem;
        }

        .footer a {
            color: #009743;
            text-decoration: none;
        }

        .not-found {
            text-align: center;
            padding: 4rem 2rem;
        }

        .not-found h2 {
            color: #666;
            margin-bottom: 1rem;
        }

        @media (max-width: 600px) {
            .container {
                padding: 1rem;
            }

            .header {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="{{ route('landing') }}">
            <img src="{{ asset('subway-icon.png') }}" alt="{{ config('app.name') }}" style="max-height:45px;">
        </a>
        <h1>{{ $title }}</h1>
    </div>

    <div class="container">
        @if($document)
            <div class="content">
                {!! $document->content_html !!}
            </div>

            <div class="meta">
                <p><strong>Versión:</strong> {{ $document->version }}</p>
                <p><strong>Última actualización:</strong> {{ $document->published_at->format('d/m/Y') }}</p>
            </div>
        @else
            <div class="not-found">
                <h2>Documento no disponible</h2>
                <p>Este documento aún no ha sido publicado. Por favor, intenta más tarde.</p>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        <p style="margin-top: 0.5rem;">
            @if($type === 'terms')
                <a href="{{ route('legal.privacy') }}">Política de Privacidad</a>
            @else
                <a href="{{ route('legal.terms') }}">Términos y Condiciones</a>
            @endif
            &nbsp;|&nbsp;
            <a href="{{ route('landing') }}">Inicio</a>
            &nbsp;|&nbsp;
            <a href="mailto:servicioalcliente@subwayguatemala.com">Contacto</a>
        </p>
    </div>
</body>
</html>
