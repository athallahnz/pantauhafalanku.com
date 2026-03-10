@extends('layouts.auth')

@section('title', 'Daftar Akun Baru')

@section('content')
    <style>
        /* REUSE LOGIN STYLES FOR CONSISTENCY */
        #aurora-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: #000;
            transition: background 0.5s ease;
        }

        .glass-pane {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(25px) saturate(200%);
            -webkit-backdrop-filter: blur(25px) saturate(200%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.7);
            border-left: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), inset 0 0 15px rgba(255, 255, 255, 0.1);
        }

        [data-coreui-theme="dark"] .glass-pane {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .theme-switcher {
            position: fixed;
            top: 25px;
            right: 25px;
            z-index: 9999;
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.5) !important;
            border: 1px solid rgba(111, 66, 193, 0.1) !important;
            border-radius: 16px !important;
            padding: 12px 18px !important;
            color: #444 !important;
        }

        [data-coreui-theme="dark"] .glass-input {
            background: rgba(0, 0, 0, 0.2) !important;
            color: white !important;
        }

        /* ROLE TILES */
        .glass-role-card {
            background: rgba(255, 255, 255, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 20px !important;
            color: #6f42c1 !important;
            transition: all 0.3s ease !important;
        }

        .btn-check:checked+.glass-role-card {
            background: rgba(111, 66, 193, 0.15) !important;
            border: 2px solid #6f42c1 !important;
            box-shadow: 0 0 15px rgba(111, 66, 193, 0.3) !important;
        }

        [data-coreui-theme="dark"] .glass-role-card {
            color: #a78bfa !important;
        }

        .glass-alert-danger {
            background: rgba(220, 53, 69, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-left: 4px solid #6f42c1;
            color: #842029;
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            animation: shake 0.4s ease;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }
    </style>

    <div id="aurora-bg"></div>
    <div class="theme-switcher shadow-lg" id="btnThemeToggle"><i class="bi bi-sun-fill fs-4" id="themeIcon"></i></div>

    <div class="row justify-content-center align-items-center min-vh-100 w-100 m-0" style="position: relative; z-index: 10;">
        <div class="col-md-10 col-lg-6 col-xl-5 px-4">
            <div class="card glass-pane border-0 py-3">
                <div class="card-body px-4 px-md-5">
                    <div class="text-center mb-4">
                        <h2 class="h4 fw-bold mb-1" id="welcomeText" style="color: #6f42c1;">Registrasi Internal</h2>
                        <p class="text-muted small">Silahkan buat akun untuk memulai akses.</p>
                    </div>

                    @if ($errors->any())
                        <div class="glass-alert-danger">
                            <ul class="mb-0 list-unstyled small fw-bold">
                                @foreach ($errors->all() as $error)
                                    <li><i class="bi bi-x-circle me-2"></i>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" id="authForm">
                        @csrf

                        {{-- ROLE TILES --}}
                        <div class="mb-4 text-center">
                            <label class="form-label small fw-bold text-muted mb-3">Daftar Sebagai:</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="role" id="roleMusyrif" value="musyrif"
                                        {{ old('role') == 'musyrif' ? 'checked' : '' }} required>
                                    <label class="btn glass-role-card w-100 p-3" for="roleMusyrif">
                                        <i class="bi bi-person-badge fs-2 d-block mb-1"></i>
                                        <span class="fw-bold">Musyrif</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="role" id="roleSantri" value="santri"
                                        {{ old('role', 'santri') == 'santri' ? 'checked' : '' }} required>
                                    <label class="btn glass-role-card w-100 p-3" for="roleSantri">
                                        <i class="bi bi-mortarboard fs-2 d-block mb-1"></i>
                                        <span class="fw-bold">Santri</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control glass-input"
                                    value="{{ old('name') }}" placeholder="Ahmad Fulan" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">E-Mail</label>
                                <input type="email" name="email" class="form-control glass-input"
                                    value="{{ old('email') }}" placeholder="email@example.com" required>
                            </div>

                            {{-- PASSWORD --}}
                            <div class="col-md-6 position-relative">
                                <label class="form-label small fw-bold text-muted">Password</label>
                                <input type="password" name="password" id="password" class="form-control glass-input"
                                    placeholder="Masukkan Password" required>
                                <button type="button" id="togglePassword"
                                    class="btn btn-link p-0 position-absolute text-muted"
                                    style="right: 1.2rem; top: 38px; z-index: 10;">
                                    <i class="bi bi-eye-slash fs-5"></i>
                                </button>
                            </div>

                            {{-- KONFIRMASI PASSWORD --}}
                            <div class="col-md-6 position-relative">
                                <label class="form-label small fw-bold text-muted">Konfirmasi</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="form-control glass-input" placeholder="Konfirmasi Password" required>
                                <button type="button" id="togglePasswordConfirmation"
                                    class="btn btn-link p-0 position-absolute text-muted"
                                    style="right: 1.2rem; top: 38px; z-index: 10;">
                                    <i class="bi bi-eye-slash fs-5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mt-4 mb-3">
                            <button class="btn btn-primary btn-lg rounded-4 shadow-sm fw-bold py-3" type="submit"
                                style="background: linear-gradient(135deg, #6f42c1, #4b2291); border: none;">
                                Daftar Sekarang <i class="bi bi-arrow-right-short ms-1"></i>
                            </button>
                        </div>

                        <div class="text-center">
                            <span class="small text-muted">Sudah punya akun?</span>
                            <a class="small fw-bold text-decoration-none ms-1" style="color: #6f42c1;"
                                href="{{ route('login') }}">Masuk di sini</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-4">
                <p class="text-white opacity-50 small">&copy; 2026 - AnzArt Studio</p>
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- SCRIPT 1: LOGIKA UI (PASSWORD MATA & TEMA)                --}}
    {{-- ========================================================= --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fungsi untuk toggle mata password
            function setupPasswordToggle(btnId, inputId) {
                const btn = document.getElementById(btnId);
                const input = document.getElementById(inputId);
                if (btn && input) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const isPassword = input.type === 'password';
                        input.type = isPassword ? 'text' : 'password';
                        const icon = this.querySelector('i');
                        icon.className = isPassword ? 'bi bi-eye fs-5' : 'bi bi-eye-slash fs-5';
                    });
                }
            }

            // Jalankan untuk kedua input
            setupPasswordToggle('togglePassword', 'password');
            setupPasswordToggle('togglePasswordConfirmation', 'password_confirmation');

            // Logika Tema Gelap/Terang
            const btnTheme = document.getElementById('btnThemeToggle');
            if (btnTheme) {
                btnTheme.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-coreui-theme') ||
                        'light';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                    document.documentElement.setAttribute('data-coreui-theme', newTheme);
                    localStorage.setItem('theme', newTheme);

                    document.getElementById('themeIcon').className = newTheme === 'dark' ?
                        'bi bi-moon-stars-fill fs-4' : 'bi bi-sun-fill fs-4';

                    const welcomeText = document.getElementById('welcomeText');
                    if (welcomeText) welcomeText.style.color = newTheme === 'dark' ? '#fff' : '#6f42c1';

                    // Beri tahu script animasi Aurora kalau tema berubah
                    window.dispatchEvent(new CustomEvent('themeChanged', {
                        detail: {
                            isDark: newTheme === 'dark'
                        }
                    }));
                });
            }
        });
    </script>

    {{-- ========================================================= --}}
    {{-- SCRIPT 2: ANIMASI AURORA (WEBGL)                            --}}
    {{-- ========================================================= --}}
    <script type="module">
        import {
            Renderer,
            Program,
            Mesh,
            Color,
            Triangle
        } from 'https://esm.sh/ogl';

        const VERT = `#version 300 es
        in vec2 position; void main() { gl_Position = vec4(position, 0.0, 1.0); }`;

        const FRAG = `#version 300 es
        precision highp float;
        uniform float uTime; uniform float uAmplitude; uniform vec3 uColorStops[3]; uniform vec2 uResolution; uniform float uBlend;
        out vec4 fragColor;
        vec3 permute(vec3 x) { return mod(((x * 34.0) + 1.0) * x, 289.0); }
        float snoise(vec2 v){
            const vec4 C = vec4(0.211324865405187, 0.366025403784439, -0.577350269189626, 0.024390243902439);
            vec2 i = floor(v + dot(v, C.yy));
            vec2 x0 = v - i + dot(i, C.xx);
            vec2 i1 = (x0.x > x0.y) ? vec2(1.0, 0.0) : vec2(0.0, 1.0);
            vec4 x12 = x0.xyxy + C.xxzz; x12.xy -= i1; i = mod(i, 289.0);
            vec3 p = permute(permute(i.y + vec3(0.0, i1.y, 1.0)) + i.x + vec3(0.0, i1.x, 1.0));
            vec3 m = max(0.5 - vec3(dot(x0, x0), dot(x12.xy, x12.xy), dot(x12.zw, x12.zw)), 0.0);
            m = m * m; m = m * m;
            vec3 x = 2.0 * fract(p * C.www) - 1.0; vec3 h = abs(x) - 0.5;
            vec3 ox = floor(x + 0.5); vec3 a0 = x - ox;
            m *= 1.79284291400159 - 0.85373472095314 * (a0*a0 + h*h);
            vec3 g; g.x = a0.x * x0.x + h.x * x0.y; g.yz = a0.yz * x12.xz + h.yz * x12.yw;
            return 130.0 * dot(m, g);
        }
        void main() {
            vec2 uv = gl_FragCoord.xy / uResolution;
            int index = (uv.x < 0.5) ? 0 : 1;
            float lerpFactor = (uv.x - (index == 0 ? 0.0 : 0.5)) / 0.5;
            vec3 rampColor = mix(uColorStops[index], uColorStops[index+1], lerpFactor);
            float height = snoise(vec2(uv.x * 2.0 + uTime * 0.1, uTime * 0.25)) * 0.5 * uAmplitude;
            height = exp(height); height = (uv.y * 2.0 - height + 0.2);
            float intensity = 0.6 * height;
            float auroraAlpha = smoothstep(0.2 - uBlend * 0.5, 0.2 + uBlend * 0.5, intensity);
            fragColor = vec4(intensity * rampColor * auroraAlpha, auroraAlpha);
        }`;

        const container = document.getElementById('aurora-bg');
        const renderer = new Renderer({
            alpha: true,
            premultipliedAlpha: true,
            antialias: true
        });
        const gl = renderer.gl;
        container.appendChild(gl.canvas);

        function getColors(isDark) {
            return isDark ? ['#1a0b3b', '#6f42c1', '#21094e'] : ['#f3e8ff', '#d8b4fe', '#f3e8ff'];
        }

        const isDarkInitial = document.documentElement.getAttribute('data-coreui-theme') === 'dark';
        let colors = getColors(isDarkInitial);
        let colorStopsArray = colors.map(hex => {
            const c = new Color(hex);
            return [c.r, c.g, c.b];
        });

        const geometry = new Triangle(gl);
        const program = new Program(gl, {
            vertex: VERT,
            fragment: FRAG,
            uniforms: {
                uTime: {
                    value: 0
                },
                uAmplitude: {
                    value: 1.2
                },
                uBlend: {
                    value: 0.5
                },
                uColorStops: {
                    value: colorStopsArray
                },
                uResolution: {
                    value: [container.offsetWidth, container.offsetHeight]
                }
            }
        });

        const mesh = new Mesh(gl, {
            geometry,
            program
        });

        function resize() {
            renderer.setSize(container.offsetWidth, container.offsetHeight);
            program.uniforms.uResolution.value = [container.offsetWidth, container.offsetHeight];
        }
        window.addEventListener('resize', resize);
        resize();

        function update(t) {
            requestAnimationFrame(update);
            program.uniforms.uTime.value = t * 0.0005;
            renderer.render({
                scene: mesh
            });
        }
        requestAnimationFrame(update);

        // Menangkap event perubahan tema dari UI Script
        window.addEventListener('themeChanged', (e) => {
            const newColors = getColors(e.detail.isDark);
            program.uniforms.uColorStops.value = newColors.map(hex => {
                const c = new Color(hex);
                return [c.r, c.g, c.b];
            });
        });
    </script>
@endsection
