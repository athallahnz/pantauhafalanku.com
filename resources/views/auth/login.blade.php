@extends('layouts.auth')

@section('title', 'Masuk ke Sistem')

@section('content')
    <style>
        /* ================= 0. GLOBAL FORCED STYLES ================= */
        .force-white {
            color: #ffffff !important;
        }

        .text-white-forced {
            color: rgba(255, 255, 255, 0.85) !important;
        }

        /* ================= 1. BASE & AURORA ================= */
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

        /* ================= 2. CRYSTAL GLASS ================= */
        .glass-pane {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(25px) saturate(200%);
            -webkit-backdrop-filter: blur(25px) saturate(200%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.7);
            border-left: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), inset 0 0 15px rgba(255, 255, 255, 0.1);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-coreui-theme="dark"] .glass-pane {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* ================= 3. UI ELEMENTS ================= */
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
            transition: 0.3s;
        }

        .theme-switcher:hover {
            transform: scale(1.1) rotate(15deg);
            background: rgba(255, 255, 255, 0.4);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.5) !important;
            border: 1px solid rgba(111, 66, 193, 0.1) !important;
            border-radius: 16px !important;
            padding: 12px 18px !important;
            transition: 0.3s;
            color: #444 !important;
        }

        .glass-input:focus {
            background: white !important;
            box-shadow: 0 0 0 4px rgba(111, 66, 193, 0.2) !important;
            border-color: #6f42c1 !important;
        }

        [data-coreui-theme="dark"] .glass-input {
            background: rgba(0, 0, 0, 0.2) !important;
            color: white !important;
        }

        /* ================= 4. FEEDBACK STATES ================= */
        .glass-alert-success {
            background: rgba(40, 167, 69, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(40, 167, 69, 0.2);
            border-left: 4px solid #28a745;
            color: #0f5132;
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
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
            animation: shake 0.4s ease-in-out;
        }

        [data-coreui-theme="dark"] .glass-alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #d1e7dd;
        }

        [data-coreui-theme="dark"] .glass-alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #f8d7da;
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
    <div class="theme-switcher shadow-lg" id="btnThemeToggle">
        <i class="bi bi-sun-fill fs-4" id="themeIcon"></i>
    </div>

    <div class="row justify-content-center align-items-center min-vh-100 w-100 m-0" style="position: relative; z-index: 10;">
        <div class="col-md-10 col-lg-6 col-xl-5 px-4">
            <div class="card glass-pane border-0 py-3">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/logos-primary.png') }}" alt="Logo" id="mainLogo" class="mb-3"
                            style="width: 90px; filter: drop-shadow(0 5px 15px rgba(111, 66, 193, 0.2)); transition: all 0.3s ease;">
                        <h2 class="h5 fw-bold mb-2 force-white" id="welcomeText">Sistem Informasi Tahfidz Qur'an</h2>
                        <p class="text-white-forced small">Departemen Al-Qur'an - Pondok Pesantren Darut Taqwa Ponorogo</p>
                    </div>

                    {{-- NOTIFIKASI --}}
                    @if (session('success'))
                        <div class="glass-alert-success"><i
                                class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="glass-alert-danger"><i
                                class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="glass-alert-danger">
                            <ul class="mb-0 list-unstyled small fw-bold">
                                @foreach ($errors->all() as $error)
                                    <li><i class="bi bi-x-circle me-2"></i>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="loginForm">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label small fw-bold force-white">Email, Nomor, atau NIS</label>
                            <input type="text" name="login" class="form-control glass-input"
                                value="{{ old('login') }}" placeholder="Masukkan Email, Nomor, atau NIS" required
                                autofocus>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold force-white">Password</label>
                            <div class="position-relative">
                                <input id="password" type="password" name="password" class="form-control glass-input pe-5"
                                    placeholder="Masukkan Password" required>
                                <button type="button" id="togglePassword"
                                    class="btn btn-link p-0 position-absolute top-50 translate-middle-y text-white-forced"
                                    style="right: 1.2rem; z-index: 10;">
                                    <i class="bi bi-eye-slash fs-5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label small force-white" for="remember">Ingat saya</label>
                            </div>
                            <a class="small fw-bold text-decoration-none text-white"
                                href="{{ route('password.request') }}">Lupa password?</a>
                        </div>

                        <div class="d-grid mb-4">
                            <button class="btn btn-primary btn-lg rounded-4 shadow-sm fw-bold py-3" type="submit"
                                style="background: linear-gradient(135deg, #6f42c1, #4b2291); border: none; color: white;">
                                Masuk ke Akun <i class="bi bi-arrow-right-short ms-1"></i>
                            </button>
                        </div>

                        @if (Route::has('register'))
                            <div class="text-center">
                                <span class="small force-white">Belum punya akun?</span>
                                <a class="small fw-bold text-decoration-none ms-1 text-white"
                                    href="{{ route('register') }}">Daftar Sekarang</a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
            <div class="text-center mt-4">
                <p class="text-white opacity-50 small">
                    &copy;2026 SIMTAQU
                    <span class="d-none d-md-inline">- Sistem Informasi Tahfidz Qur'an</span>
                </p>
            </div>
        </div>
    </div>
@endsection

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

    // --- SETUP RENDERER ---
    const container = document.getElementById('aurora-bg');
    const renderer = new Renderer({
        alpha: true,
        premultipliedAlpha: true,
        antialias: true
    });
    const gl = renderer.gl;
    container.appendChild(gl.canvas);

    // --- THEME UTILITIES ---
    function getColors(targetTheme) {
        return targetTheme === 'dark' ?
            ['#1a0b3b', '#6f42c1', '#21094e'] :
            ['#f3e8ff', '#d8b4fe', '#f3e8ff'];
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-coreui-theme', theme);

        // Update Ikon & Logo
        const icon = document.getElementById('themeIcon');
        const logo = document.getElementById('mainLogo');

        if (icon) icon.className = theme === 'dark' ? 'bi bi-moon-stars-fill fs-4' : 'bi bi-sun-fill fs-4';
        if (logo) logo.src = theme === 'dark' ? "{{ asset('assets/logos.png') }}" :
            "{{ asset('assets/logos-primary.png') }}";

        // Update Aurora Colors
        const newStops = getColors(theme);
        program.uniforms.uColorStops.value = newStops.map(hex => {
            const c = new Color(hex);
            return [c.r, c.g, c.b];
        });
    }

    // --- INITIALIZE SHADER ---
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
                value: [
                    [0, 0, 0],
                    [0, 0, 0],
                    [0, 0, 0]
                ]
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

    // --- THEME LOGIC (System + Manual) ---
    const btnTheme = document.getElementById('btnThemeToggle');

    btnTheme.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-coreui-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', newTheme);
        applyTheme(newTheme);
    });

    // Inisialisasi awal
    const savedTheme = localStorage.getItem('theme');
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    applyTheme(savedTheme || systemTheme);

    // Listen perubahan sistem secara real-time
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });

    // --- UI INTERACTION ---
    const togglePass = document.getElementById('togglePassword');
    if (togglePass) {
        togglePass.addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = this.querySelector('i');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
</script>
