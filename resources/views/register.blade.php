<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OpenShelf</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2C3E50;
            --primary-light: #4C9F8A;
            --secondary: #3A7B6B;
            --bg-dark: #0F172A;
            --surface: #1E293B;
            --surface-hover: #334155;
            --border-color: #334155;
            --text-main: #F8F9FA;
            --text-muted: #94a3b8;
            --error: #C65D5D;
            --success: #2E8B57;
            --warning: #D97706;
            --focus-ring: rgba(76, 159, 138, 0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', system-ui, sans-serif; }
        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .ambient-bg {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: -1; overflow: hidden;
            background: radial-gradient(circle at 10% 20%, rgba(44, 62, 80, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(76, 159, 138, 0.12) 0%, transparent 40%),
                        radial-gradient(circle at 50% 50%, rgba(76, 159, 138, 0.05) 0%, transparent 60%),
                        #0f172a;
        }

        .ambient-bg::after {
            content: '';
            position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
            opacity: 0.03; pointer-events: none;
        }

        .registration-container { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2.5rem 1.5rem; width: 100%; }
        .registration-card {
            background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 28px; width: 100%; max-width: 850px;
            padding: 3.5rem 2.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
            animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1); position: relative; z-index: 10;
        }

        @keyframes cardEntrance { from { opacity: 0; transform: translateY(40px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .registration-header { text-align: center; margin-bottom: 2rem; }
        .brand-logo { width: 64px; height: 64px; border-radius: 16px; margin-bottom: 1rem; display: inline-block; box-shadow: 0 4px 20px rgba(44, 62, 80, 0.3); }
        .registration-header h1 { font-size: 1.75rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; letter-spacing: -0.02em; }
        .registration-header p { color: var(--text-muted); font-size: 0.95rem; }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; font-size: 0.95rem; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        .alert-error { background: rgba(198, 93, 93, 0.14); border: 1px solid rgba(198, 93, 93, 0.2); color: #f8b4b4; }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; margin-bottom: 1.5rem; }
        .form-group { position: relative; }
        .full-width { grid-column: span 1; }
        .input-group { position: relative; display: flex; align-items: center; }
        .input-group > i:first-child:not(.toggle-password, .check-icon) { position: absolute; left: 1.25rem; color: var(--text-muted); font-size: 1rem; transition: color 0.3s ease; pointer-events: none; }
        .input-group input, .input-group select { width: 100%; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 12px; padding: 0.875rem 1.25rem 0.875rem 3rem; color: #fff; font-size: 1rem; transition: all 0.2s ease; }
        .input-group input:focus, .input-group select:focus { outline: none; border-color: var(--primary-light); background: var(--bg-dark); box-shadow: 0 0 0 3px var(--focus-ring); }
        .input-group input:focus ~ i:first-child:not(.toggle-password, .check-icon) { color: var(--primary-light); }
        .input-group input[type="password"], .input-group input[name="password_confirmation"] { padding-right: 3.25rem; }
        .toggle-password { position: absolute; right: 1rem; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem; padding: 0.25rem; display: flex; align-items: center; justify-content: center; transition: color 0.2s ease, transform 0.15s ease; z-index: 2; line-height: 1; }
        .toggle-password:hover { color: var(--primary-light); transform: scale(1.15); }
        .toggle-password:focus { outline: none; color: var(--primary-light); }
        .error-message { color: var(--error); font-size: 0.85rem; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.4rem; }
        .password-strength-container { margin-top: 0.75rem; }
        .password-strength-bar { height: 6px; background: rgba(15, 23, 42, 0.6); border-radius: 10px; overflow: hidden; margin-bottom: 0.75rem; }
        .strength-fill { height: 100%; width: 0; transition: all 0.4s ease; }
        .strength-weak { background: var(--error); width: 33%; }
        .strength-medium { background: var(--warning); width: 66%; }
        .strength-strong { background: var(--success); width: 100%; }
        .password-requirements { display: grid; grid-template-columns: 1fr; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted); }
        .requirement { display: flex; align-items: center; gap: 0.4rem; transition: all 0.2s ease; }
        .requirement.met { color: var(--success); }
        .terms-group { margin: 1.5rem 0; display: flex; align-items: center; gap: 0.5rem; color: var(--text-muted); font-size: 0.9rem; }
        .terms-group input { display: none; }
        .custom-checkbox { width: 20px; height: 20px; border: 1.5px solid var(--border-color); border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; background: rgba(15, 23, 42, 0.5); }
        .terms-group input:checked + .custom-checkbox { background: var(--primary-light); border-color: var(--primary-light); }
        .custom-checkbox i { color: #fff; font-size: 0.7rem; opacity: 0; transform: scale(0.5); transition: all 0.2s ease; }
        .terms-group input:checked + .custom-checkbox i { opacity: 1; transform: scale(1); }
        .terms-group a { color: var(--primary-light); text-decoration: none; transition: color 0.2s; }
        .terms-group a:hover { color: var(--primary); }
        .btn-register { width: 100%; background: var(--primary); color: #fff; border: none; border-radius: 12px; padding: 1rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 0.75rem; }
        .btn-register:hover:not(:disabled) { background: var(--primary-light); transform: translateY(-1px); }
        .btn-register:active:not(:disabled) { transform: translateY(1px); }
        .login-link { text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.95rem; }
        .login-link a { color: var(--primary-light); text-decoration: none; font-weight: 600; margin-left: 0.25rem; transition: color 0.2s ease; }
        .login-link a:hover { color: var(--primary); text-decoration: underline; }

        @media (min-width: 640px) {
            .registration-card { padding: 3rem; }
            .registration-header h1 { font-size: 2.25rem; }
            .form-grid { grid-template-columns: repeat(2, 1fr); }
            .full-width { grid-column: span 2; }
            .password-requirements { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="ambient-bg"></div>
    <div class="registration-container">
        <div class="registration-card">
            <div class="registration-header">
                <img src="{{ asset('images/logo-icon.svg') }}" alt="OpenShelf" class="brand-logo">
                <h1>Join OpenShelf</h1>
                <p>Create your account and start sharing</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-circle-check fa-2x"></i>
                    <div>
                        <strong>Registration Successful!</strong>
                        <p>{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <i class="fas fa-circle-exclamation fa-2x"></i>
                    <div>
                        <strong>Registration Failed!</strong>
                        <ul style="margin: 0.25rem 0 0 1rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" id="registrationForm">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" id="name" placeholder="Full Name" value="{{ old('name') }}" required>
                        </div>
                        @error('name')
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" placeholder="Email Address" value="{{ old('email') }}" required>
                        </div>
                        @error('email')
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-building-columns"></i>
                            <input type="text" name="department" id="department" placeholder="Department" value="{{ old('department') }}" required>
                        </div>
                        @error('department')
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-calendar-days"></i>
                            <input type="text" name="session" id="session" placeholder="Session (YYYY-YY)" value="{{ old('session') }}" required>
                        </div>
                        @error('session')
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" id="phone" placeholder="Phone Number" value="{{ old('phone') }}" required>
                        </div>
                        @error('phone')
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-hotel"></i>
                            <select name="hall" id="hall" required>
                                <option value="" disabled {{ old('hall') ? '' : 'selected' }}>Select Hall</option>
                                @php($halls = [
                                    '1' => 'Amar Ekushey Hall',
                                    '2' => 'Dr. Muhammad Shahidullah Hall',
                                    '3' => 'Fazlul Huq Muslim Hall',
                                    '4' => 'Salimullah Muslim Hall',
                                    '5' => 'Shahid Sergeant Zahurul Haq Hall',
                                    '6' => 'Haji Muhammad Mohsin Hall',
                                    '7' => 'Sir A.F. Rahman Hall',
                                    '8' => 'Masterda Surja Sen Hall',
                                    '9' => 'Kobi Jashimuddin Hall',
                                    '10' => 'Muktijoddha Ziaur Rahman Hall',
                                    '11' => 'Shaheed Sharif Osman Hadi Hall',
                                    '12' => 'Bijoy Ekattor Hall',
                                    '13' => 'Jagannath Hall',
                                    '14' => 'Ruqayyah Hall',
                                    '15' => 'Shamsun Nahar Hall',
                                    '16' => 'Bangladesh-Kuwait Maitree Hall',
                                    '17' => 'Begum Fazilatunnesa Mujib Hall',
                                    '18' => 'Kobi Sufiya Kamal Hall',
                                ])
                                @foreach ($halls as $value => $label)
                                    <option value="{{ $value }}" {{ old('hall') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('hall')
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-door-open"></i>
                            <input type="text" name="roomNumber" id="roomNumber" placeholder="Room/Hostel Info" value="{{ old('roomNumber') }}" required>
                        </div>
                        @error('roomNumber')
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group full-width">
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" placeholder="Secure Password" required>
                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility" tabindex="-1">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                        <div class="password-strength-container">
                            <div class="password-strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                            <div class="password-requirements">
                                <div class="requirement" id="req-length"><i class="fas fa-circle-dot"></i> 8+ Characters</div>
                                <div class="requirement" id="req-upper"><i class="fas fa-circle-dot"></i> Uppercase</div>
                                <div class="requirement" id="req-lower"><i class="fas fa-circle-dot"></i> Lowercase</div>
                                <div class="requirement" id="req-number"><i class="fas fa-circle-dot"></i> Number</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <div class="input-group">
                            <i class="fas fa-shield"></i>
                            <input type="password" name="password_confirmation" id="confirm_password" placeholder="Confirm Password" required>
                            <button type="button" class="toggle-password" id="toggleConfirmPassword" aria-label="Toggle confirm password visibility" tabindex="-1">
                                <i class="fas fa-eye" id="toggleConfirmPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="terms-group">
                    <input type="checkbox" name="terms" id="terms" {{ old('terms') ? 'checked' : '' }}>
                    <label for="terms" class="custom-checkbox"><i class="fas fa-check"></i></label>
                    <label for="terms">I accept the <a href="{{ route('terms') }}">Terms</a> and <a href="{{ route('privacy') }}">Privacy Policy</a></label>
                </div>

                <button type="submit" class="btn-register" id="submitBtn">
                    <span class="btn-text">Create Account</span>
                    <i class="fas fa-user-plus"></i>
                </button>
            </form>

            <div class="login-link">
                Already part of the community? <a href="/login/">Sign in here</a>
            </div>
        </div>
    </div>

    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = password;
            const icon = document.getElementById('togglePasswordIcon');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
            const input = confirmPassword;
            const icon = document.getElementById('toggleConfirmPasswordIcon');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        });

        function checkPasswordStrength() {
            const value = password.value;
            const hasLength = value.length >= 8;
            const hasUpper = /[A-Z]/.test(value);
            const hasLower = /[a-z]/.test(value);
            const hasNumber = /\d/.test(value);

            updateReq('req-length', hasLength);
            updateReq('req-upper', hasUpper);
            updateReq('req-lower', hasLower);
            updateReq('req-number', hasNumber);

            const met = [hasLength, hasUpper, hasLower, hasNumber].filter(Boolean).length;
            const fill = document.getElementById('strengthFill');
            fill.className = 'strength-fill';
            if (value.length > 0) {
                if (met <= 2) fill.classList.add('strength-weak');
                else if (met === 3) fill.classList.add('strength-medium');
                else fill.classList.add('strength-strong');
            } else {
                fill.style.width = '0';
            }
        }

        function updateReq(id, isMet) {
            const el = document.getElementById(id);
            const icon = el.querySelector('i');
            if (isMet) {
                el.classList.add('met');
                icon.className = 'fas fa-circle-check';
            } else {
                el.classList.remove('met');
                icon.className = 'fas fa-circle-dot';
            }
        }

        password.addEventListener('input', checkPasswordStrength);

        function checkPasswordMatch() {
            if (confirmPassword.value.length > 0) {
                confirmPassword.style.borderColor = password.value === confirmPassword.value ? '#2dce89' : '#f5365c';
            } else {
                confirmPassword.style.borderColor = '#e9ecef';
            }
        }

        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);

        document.getElementById('registrationForm').addEventListener('submit', function (e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });

        document.getElementById('session').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 4) {
                value = value.substr(0, 4) + '-' + value.substr(4, 2);
            }
            e.target.value = value;
        });

        document.getElementById('phone').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substr(0, 11);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
