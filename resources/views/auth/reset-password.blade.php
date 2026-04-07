@extends('layouts.auth')

@section('title', 'Atur Ulang Password')

@section('content')
    <style>
        /* === TAMBAHAN CLASS FORCE WHITE === */
        .force-white {
            color: #ffffff !important;
        }

        .text-white-forced {
            color: rgba(255, 255, 255, 0.85) !important;
        }

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
            transition: all 0.5s ease;
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
            /* Tambahan warna teks saat light mode */
        }

        [data-coreui-theme="dark"] .glass-input {
            background: rgba(0, 0, 0, 0.2) !important;
            color: white !important;
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
        <div class="col-md-6 col-lg-4 px-4">
            <div class="card glass-pane border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/logos-primary.png') }}" alt="Logo" class="mb-3"
                            style="width: 100px; filter: drop-shadow(0 5px 15px rgba(111, 66, 193, 0.2));">
                        <h2 class="h4 fw-bold mb-1" id="welcomeText" style="color: #6f42c1;">Password Baru</h2>
                        <p class="text-white-forced small">Silahkan buat password baru yang kuat.</p>
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

                    <form method="POST" action="{{ route('password.update') }}" id="authForm">
                        @csrf
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

                        <div class="mb-3 position-relative">
                            <label class="form-label small fw-bold force-white">Password Baru</label>
                            <input type="password" name="password" id="password" class="form-control glass-input"
                                placeholder="••••••••" required autofocus>
                            <button type="button" id="togglePassword"
                                class="btn btn-link p-0 position-absolute text-white-forced"
                                style="right: 1.2rem; top: 38px; z-index: 10;">
                                <i class="bi bi-eye-slash fs-5"></i>
                            </button>
                        </div>

                        <div class="mb-4 position-relative">
                            <label class="form-label small fw-bold force-white">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control glass-input" placeholder="••••••••" required>
                            <button type="button" id="togglePasswordConfirmation"
                                class="btn btn-link p-0 position-absolute text-white-forced"
                                style="right: 1.2rem; top: 38px; z-index: 10;">
                                <i class="bi bi-eye-slash fs-5"></i>
                            </button>
                        </div>

                        <div class="d-grid mb-3">
                            <button class="btn btn-primary btn-lg rounded-4 shadow-sm fw-bold py-3" type="submit"
                                id="submitBtn"
                                style="background: linear-gradient(135deg, #6f42c1, #4b2291); border: none; color: white;">
                                <span class="spinner-border spinner-border-sm d-none" id="loader" role="status"
                                    aria-hidden="true"></span>
                                <span id="btnText">Update Password <i class="bi bi-check2-circle ms-1"></i></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Copyright Text --}}
            <div class="text-center mt-4">
                <p class="text-white opacity-50 small">
                    &copy;2026 SIMTAQU
                    <span class="d-none d-md-inline">- Sistem Informasi Tahfidz Qur'an</span>
                </p>
            </div>

        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- SCRIPT 1: LOGIKA UI (PASSWORD MATA, TEMA, LOADER)         --}}
    {{-- ========================================================= --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Fungsi Toggle Mata Password
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

            setupPasswordToggle('togglePassword', 'password');
            setupPasswordToggle('togglePasswordConfirmation', 'password_confirmation');

            // 2. Loading State pada Tombol Submit
            document.getElementById('authForm').addEventListener('submit', function() {
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                document.getElementById('loader').classList.remove('d-none');
                document.getElementById('btnText').innerText = 'Memproses...';
            });

            // 3. Logika Tema Gelap/Terang
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
@endsection

@push('script')
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
@endpush
