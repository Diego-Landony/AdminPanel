<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Descarga la app oficial de Subway Guatemala. Ordena tu sub favorito, acumula puntos y disfruta promociones exclusivas.">
    <meta name="theme-color" content="#008938">
    <meta name="google-site-verification" content="-5qwOCjotY61KpprRLAAlZDAtOewsVtlOTZJa-23o4c" />
    <meta name="application-name" content="Subway Guatemala">
    <meta property="og:site_name" content="Subway Guatemala">
    <meta property="og:title" content="Subway Guatemala">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://appmobile.subwaycardgt.com/">
    <meta property="og:description" content="Descarga la app oficial de Subway Guatemala. Ordena tu sub favorito, acumula puntos y disfruta promociones exclusivas.">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>Subway Guatemala</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --subway-green: #008938;
            --subway-green-dark: #00491E;
            --subway-yellow: #F2B700;
            --subway-yellow-bright: #FCE300;
            --subway-tomato: #FF5C39;
            --subway-peppers: #97D700;
            --text-dark: #1a1a1a;
            --text-gray: #666;
            --bg-light: #f8f9fa;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: #fff;
        }

        /* Header */
        .header {
            background: #fff;
            padding: 0.75rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .header.hidden {
            transform: translateY(-100%);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo {
            height: 36px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--subway-green) 0%, var(--subway-green-dark) 100%);
            padding: 7rem 2rem 4rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 200%;
            background: rgba(255,255,255,0.03);
            transform: rotate(-15deg);
            pointer-events: none;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text h1 {
            font-size: 3.25rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
            margin-bottom: 0.5rem;
        }

        .hero-text p {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
            max-width: 480px;
        }

        .store-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .store-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: #000;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .store-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .store-btn svg,
        .store-btn img {
            width: 28px;
            height: 28px;
        }

        .store-btn-text {
            text-align: left;
        }

        .store-btn-text small {
            display: block;
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .store-btn-text strong {
            font-size: 1.1rem;
        }

        /* Phone Screenshots */
        .hero-visual {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
        }

        .phone-frame {
            width: 240px;
            background: #1a1a1a;
            border-radius: 36px;
            padding: 10px;
            box-shadow: 0 40px 80px rgba(0,0,0,0.4);
            transition: transform 0.3s ease;
        }

        .phone-frame:hover {
            transform: translateY(-10px);
        }

        .phone-frame.secondary {
            width: 210px;
            transform: translateY(40px);
        }

        .phone-frame.secondary:hover {
            transform: translateY(30px);
        }

        .phone-frame img {
            width: 100%;
            border-radius: 28px;
            display: block;
        }

        /* Features Section */
        .features {
            padding: 3.5rem 2rem;
            background: var(--subway-green-dark);
        }

        .features-content {
            max-width: 1100px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
        }

        .feature-card {
            padding: 2rem 2.25rem;
            position: relative;
        }

        .feature-card:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255,255,255,0.15);
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            background: var(--subway-yellow);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .feature-icon svg {
            width: 22px;
            height: 22px;
            fill: #fff;
        }

        .feature-card h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.4rem;
        }

        .feature-card p {
            color: rgba(255,255,255,0.75);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* CTA Section */
        .cta {
            padding: 6rem 2rem;
            background: var(--subway-green);
            text-align: center;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-icon {
            width: 100px;
            height: 100px;
            border-radius: 24px;
            margin-bottom: 2rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            background: #fff;
            padding: 12px;
            object-fit: contain;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
        }

        .cta .store-buttons {
            justify-content: center;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            padding: 3rem 2rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            height: 30px;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--subway-yellow);
        }

        .footer-copy {
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .hero-text p {
                margin: 0 auto 2rem;
            }

            .store-buttons {
                justify-content: center;
            }

            .hero-visual {
                order: -1;
            }

            .phone-frame {
                width: 180px;
            }

            .phone-frame.secondary {
                width: 160px;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .feature-card:not(:last-child)::after {
                right: 10%;
                left: 10%;
                top: auto;
                bottom: 0;
                height: 1px;
                width: 80%;
            }
        }

        @media (max-width: 600px) {
            .header {
                padding: 0.6rem 1rem;
            }

            .logo {
                height: 28px;
            }

            .hero {
                padding: 5.5rem 1rem 3rem;
                min-height: auto;
            }

            .hero-text h1 {
                font-size: 2rem;
            }

            .hero-text p {
                font-size: 1rem;
            }

            .hero-visual {
                gap: 0.75rem;
            }

            .phone-frame {
                width: 150px;
                border-radius: 24px;
                padding: 6px;
            }

            .phone-frame img {
                border-radius: 20px;
            }

            .phone-frame.secondary {
                width: 130px;
                transform: translateY(20px);
            }

            .phone-frame.secondary:hover {
                transform: translateY(14px);
            }

            .store-btn {
                padding: 0.6rem 1rem;
            }

            .cta h2 {
                font-size: 1.75rem;
            }

            .cta-icon {
                width: 80px;
                height: 80px;
                border-radius: 20px;
            }

            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="navbar">
        <div class="header-content">
            <img src="{{ asset('subway-logo.png') }}" alt="Subway Guatemala" class="logo">
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Subway Guatemala</h1>
                <h2 style="font-size:2rem;font-weight:700;color:#fff;line-height:1.1;margin-bottom:1.5rem;">Tu Sub favorito, <span style="color:var(--subway-yellow);">a un toque</span></h2>
                <p>Ordena desde tu celular, acumula puntos con cada compra y disfruta de promociones exclusivas solo para usuarios de la app.</p>
                <div class="store-buttons">
                    <a href="https://apps.apple.com/gt/app/subway-guatemala/id1264179919" target="_blank" rel="noopener" class="store-btn">
                        <img src="{{ asset('logotipo-de-apple.png') }}" alt="App Store">
                        <span class="store-btn-text">
                            <small>Disponible en</small>
                            <strong>App Store</strong>
                        </span>
                    </a>
                    <a href="https://play.google.com/store/apps/details?id=com.subwaycard&hl=es_GT" target="_blank" rel="noopener" class="store-btn">
                        <img src="{{ asset('google-play_icon.png') }}" alt="Google Play">
                        <span class="store-btn-text">
                            <small>Disponible en</small>
                            <strong>Google Play</strong>
                        </span>
                    </a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="phone-frame">
                    <img src="{{ asset('home-app.png') }}" alt="Subway Guatemala App - Inicio">
                </div>
                <div class="phone-frame secondary">
                    <img src="{{ asset('login-app.png') }}" alt="Subway Guatemala App - Inicio de sesión">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="features-content">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm-3 18h-4v-1h4v1zm3-3H7V5h10v12z"/></svg>
                    </div>
                    <h3>Pedidos desde tu celular</h3>
                    <p>Personaliza tu sub, elige tu restaurante y recógelo listo.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-.52.07-1.06.07-1.58.02A7.94 7.94 0 0 1 4 12c0-4.42 3.58-8 8-8s8 3.58 8 8c0 3.73-2.56 6.87-6 7.73v-1.28c.95-.37 1.78-1 2.38-1.82L15 15.24a3.96 3.96 0 0 1-6 0l-1.38 1.39A5.93 5.93 0 0 0 10 18.5v1.43zM12 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                    </div>
                    <h3>Puntos con cada compra</h3>
                    <p>Acumula puntos automáticamente y canjéalos por productos gratis.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21.41 11.58l-9-9C12.04 2.21 11.53 2 11 2H4a2 2 0 0 0-2 2v7c0 .53.21 1.04.59 1.41l9 9c.36.36.86.59 1.41.59.55 0 1.05-.23 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                    </div>
                    <h3>Ofertas solo para ti</h3>
                    <p>Promociones y descuentos exclusivos disponibles dentro de la app.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="descargar">
        <div class="cta-content">
            <img src="{{ asset('iconsubway.png') }}" alt="Subway Guatemala App" class="cta-icon">
            <h2>Descarga la app ahora</h2>
            <p>Únete a miles de usuarios que ya disfrutan los beneficios de ordenar con Subway Guatemala</p>
            <div class="store-buttons">
                <a href="https://apps.apple.com/gt/app/subway-guatemala/id1264179919" target="_blank" rel="noopener" class="store-btn">
                    <img src="{{ asset('logotipo-de-apple.png') }}" alt="App Store">
                    <span class="store-btn-text">
                        <small>Disponible en</small>
                        <strong>App Store</strong>
                    </span>
                </a>
                <a href="https://play.google.com/store/apps/details?id=com.subwaycard&hl=es_GT" target="_blank" rel="noopener" class="store-btn">
                    <img src="{{ asset('google-play_icon.png') }}" alt="Google Play">
                    <span class="store-btn-text">
                        <small>Disponible en</small>
                        <strong>Google Play</strong>
                    </span>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <img src="{{ asset('subway-logo.png') }}" alt="Subway Guatemala" class="footer-logo">
            <nav class="footer-links">
                <a href="{{ route('legal.terms') }}">Términos y Condiciones</a>
                <a href="{{ route('legal.privacy') }}">Política de Privacidad</a>
                <a href="mailto:servicioalcliente@subwayguatemala.com">Contacto</a>
            </nav>
            <p class="footer-copy">&copy; {{ date('Y') }} Subway Guatemala. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script>
        (function() {
            var lastScroll = 0;
            var navbar = document.getElementById('navbar');
            window.addEventListener('scroll', function() {
                var currentScroll = window.pageYOffset;
                if (currentScroll > lastScroll && currentScroll > 80) {
                    navbar.classList.add('hidden');
                } else {
                    navbar.classList.remove('hidden');
                }
                lastScroll = currentScroll;
            });
        })();
    </script>
</body>
</html>
