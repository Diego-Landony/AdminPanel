<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $message }} - {{ $appName }}</title>
    <link rel="icon" href="{{ $baseUrl }}/subway-icon.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            padding: 24px;
        }
        .container {
            padding: 48px 40px;
            max-width: 440px;
            width: 100%;
            text-align: center;
        }
        .logo {
            margin-bottom: 40px;
        }
        .logo img {
            height: 50px;
            width: auto;
        }
        h1 {
            color: #111;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 36px;
        }
        .btn {
            display: inline-block;
            background: #009639;
            color: white;
            padding: 14px 36px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #007a2f;
        }
        .footer {
            margin-top: 40px;
            color: #999;
            font-size: 13px;
        }
        .hint {
            margin-top: 20px;
            color: #888;
            font-size: 14px;
            display: none;
        }
        .hint.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ $baseUrl }}/subway-logo.png" alt="Subway">
        </div>
        <h1>{{ $message }}</h1>
        <p>{{ $subtitle }}</p>
        <button onclick="openApp()" class="btn">Abrir la App</button>
        <p id="hint" class="hint">Si la app no se abre, asegurate de tenerla instalada.</p>
        <div class="footer">&copy; {{ date('Y') }} Subway Guatemala</div>
    </div>
    <script>
        function openApp() {
            var deepLink = '{{ $scheme }}://verified?status={{ $status }}';
            var timeout;

            window.location.href = deepLink;

            timeout = setTimeout(function() {
                document.getElementById('hint').classList.add('show');
            }, 2000);

            window.addEventListener('blur', function() {
                clearTimeout(timeout);
            });
        }
    </script>
</body>
</html>
