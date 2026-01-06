<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($success) && $success ? 'Contraseña Restablecida' : 'Restablecer Contraseña' }} - {{ config('app.mobile_name', 'Subway App') }}</title>
    <link rel="icon" href="{{ config('app.url') }}/subway-icon.png" type="image/png">
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
        h1.error { color: #dc2626; }
        h1.success { color: #009639; }
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
            width: 100%;
        }
        .btn:hover {
            background: #007a2f;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
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

        /* Form Styles */
        .form-container {
            text-align: left;
            margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        .input-wrapper {
            position: relative;
        }
        input[type="password"],
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 14px 48px 14px 16px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: border-color 0.2s;
            background: #fff;
        }
        input:focus {
            outline: none;
            border-color: #009639;
        }
        input:disabled {
            background: #f5f5f5;
            color: #888;
            padding: 14px 16px;
        }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #888;
            padding: 4px;
        }
        .toggle-password:hover {
            color: #333;
        }
        .toggle-password svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        /* Validation Requirements */
        .requirements {
            margin-top: 12px;
            font-size: 13px;
        }
        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: #888;
            transition: color 0.2s;
        }
        .requirement.valid {
            color: #009639;
        }
        .requirement.invalid {
            color: #dc2626;
        }
        .requirement svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: left;
        }
        .error-box ul {
            margin: 4px 0 0 16px;
            padding: 0;
        }

        .match-status {
            font-size: 13px;
            margin-top: 8px;
        }
        .match-status.match {
            color: #009639;
        }
        .match-status.no-match {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ config('app.url') }}/subway-logo.png" alt="Subway">
        </div>

        @if(isset($success) && $success)
            {{-- Success State --}}
            <h1 class="success">Contraseña Restablecida</h1>
            <p>{{ $message }}</p>
            <button onclick="openApp()" class="btn">Abrir la App</button>
            <p id="hint" class="hint">Si la app no se abre, asegúrate de tenerla instalada.</p>
        @elseif($token && $email)
            {{-- Reset Form --}}
            <h1>Nueva Contraseña</h1>
            <p>Ingresa tu nueva contraseña para continuar.</p>

            @if($error)
                <div class="error-box">{{ $error }}</div>
            @endif

            @if($errors->any())
                <div class="error-box">
                    <ul>
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('customer.password.update') }}" id="resetForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="form-container">
                    <div class="form-group">
                        <label for="email_display">Correo electrónico</label>
                        <input type="email" id="email_display" value="{{ $email }}" disabled>
                    </div>

                    <div class="form-group">
                        <label for="password">Nueva contraseña</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" required autocomplete="new-password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-off-icon" style="display:none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <div class="requirements">
                            <div class="requirement" id="req-length">
                                <svg class="check" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Mínimo 8 caracteres</span>
                            </div>
                            <div class="requirement" id="req-letter">
                                <svg class="check" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Al menos 1 letra</span>
                            </div>
                            <div class="requirement" id="req-number">
                                <svg class="check" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Al menos 1 número</span>
                            </div>
                            <div class="requirement" id="req-symbol">
                                <svg class="check" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Al menos 1 símbolo (!@#$%...)</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirmar contraseña</label>
                        <div class="input-wrapper">
                            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password_confirmation', this)">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-off-icon" style="display:none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <div id="match-status" class="match-status"></div>
                    </div>
                </div>

                <button type="submit" class="btn" id="submitBtn" disabled>Restablecer Contraseña</button>
            </form>
        @else
            {{-- Error State --}}
            <h1 class="error">Enlace Inválido</h1>
            <p>{{ $error ?? 'El enlace ha expirado o es inválido. Solicita uno nuevo desde la app.' }}</p>
        @endif

        <div class="footer">© {{ date('Y') }} Subway Guatemala</div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            var input = document.getElementById(inputId);
            var eyeIcon = btn.querySelector('.eye-icon');
            var eyeOffIcon = btn.querySelector('.eye-off-icon');

            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                input.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }

        function openApp() {
            var deepLink = '{{ config('app.mobile_scheme', 'subwayapp') }}://login';
            var timeout;

            window.location.href = deepLink;

            timeout = setTimeout(function() {
                document.getElementById('hint').classList.add('show');
            }, 2000);

            window.addEventListener('blur', function() {
                clearTimeout(timeout);
            });
        }

        // Password validation
        var passwordInput = document.getElementById('password');
        var confirmInput = document.getElementById('password_confirmation');
        var submitBtn = document.getElementById('submitBtn');
        var matchStatus = document.getElementById('match-status');

        if (passwordInput) {
            var requirements = {
                length: { el: document.getElementById('req-length'), test: function(p) { return p.length >= 8; } },
                letter: { el: document.getElementById('req-letter'), test: function(p) { return /[a-zA-Z]/.test(p); } },
                number: { el: document.getElementById('req-number'), test: function(p) { return /[0-9]/.test(p); } },
                symbol: { el: document.getElementById('req-symbol'), test: function(p) { return /[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\/`~;']/.test(p); } }
            };

            function validatePassword() {
                var password = passwordInput.value;
                var allValid = true;

                for (var key in requirements) {
                    var req = requirements[key];
                    var isValid = req.test(password);

                    req.el.classList.remove('valid', 'invalid');
                    if (password.length > 0) {
                        req.el.classList.add(isValid ? 'valid' : 'invalid');
                    }

                    if (!isValid) allValid = false;
                }

                checkMatch();
                return allValid;
            }

            function checkMatch() {
                var password = passwordInput.value;
                var confirm = confirmInput.value;

                if (confirm.length === 0) {
                    matchStatus.textContent = '';
                    matchStatus.className = 'match-status';
                } else if (password === confirm) {
                    matchStatus.textContent = '✓ Las contraseñas coinciden';
                    matchStatus.className = 'match-status match';
                } else {
                    matchStatus.textContent = '✗ Las contraseñas no coinciden';
                    matchStatus.className = 'match-status no-match';
                }

                updateSubmitButton();
            }

            function updateSubmitButton() {
                var password = passwordInput.value;
                var confirm = confirmInput.value;
                var allReqsValid = true;

                for (var key in requirements) {
                    if (!requirements[key].test(password)) {
                        allReqsValid = false;
                        break;
                    }
                }

                var passwordsMatch = password === confirm && confirm.length > 0;
                submitBtn.disabled = !(allReqsValid && passwordsMatch);
            }

            passwordInput.addEventListener('input', validatePassword);
            confirmInput.addEventListener('input', checkMatch);
        }
    </script>
</body>
</html>
