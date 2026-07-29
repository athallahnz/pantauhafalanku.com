<!DOCTYPE html>
<html lang="id" data-coreui-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMTAQU APPS | Dept. Al-Qur'an Pondok Pesantren Darut Taqwa</title>
    <meta name="description"
        content="SIMTAQU APPS adalah sistem informasi pembinaan Al-Qur'an milik Departemen Al-Qur'an Pondok Pesantren Darut Taqwa Ponorogo untuk memantau tahfidz, tahsin, tilawah, evaluasi, dan laporan santri secara terintegrasi.">
    <meta name="theme-color" content="#5640a5">
    <meta property="og:type" content="website">
    <meta property="og:title" content="SIMTAQU APPS — Menjaga Proses, Menguatkan Generasi Qur'ani">
    <meta property="og:description"
        content="Produk digital resmi Departemen Al-Qur'an Pondok Pesantren Darut Taqwa Ponorogo.">

    {{-- Fonts: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- Icons & Libraries --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    {{-- Mencegah Flash Theme --}}
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-coreui-theme', savedTheme);
    </script>

    <style>
        /* ============================================================
           1. COLOR SYSTEM & VARIABLES (LIGHT & DARK)
        ============================================================ */
        :root {
            --islamic-purple-400: #917aff;
            --islamic-purple-500: #6b4eff;
            --islamic-purple-600: #5640a5;
            --islamic-purple-700: #40307a;
            --islamic-purple-900: #1b143a;
            --islamic-tosca-400: #39c1cc;
            --islamic-tosca-500: #13a3b3;
            --brand-gold-400: #f7c948;
            --brand-gold-500: #e7ad24;

            --bg-main: #f8fafc;
            --bg-section: #ffffff;
            --bg-section-alt: #f1f5f9;
            --text-heading: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border-color: rgba(0, 0, 0, 0.06);
            --nav-bg: rgba(255, 255, 255, 0.85);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --shadow-glow: 0 20px 40px -10px rgba(86, 64, 165, 0.2);
        }

        [data-coreui-theme="dark"] {
            --bg-main: #0f172a;
            --bg-section: #16202c;
            --bg-section-alt: #0f172a;
            --text-heading: #ffffff;
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --border-color: rgba(255, 255, 255, 0.08);
            --nav-bg: rgba(15, 23, 42, 0.95);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            --shadow-glow: 0 20px 40px -10px rgba(86, 64, 165, 0.4);
        }

        /* ============================================================
           2. GLOBAL STYLES & TYPOGRAPHY
        ============================================================ */
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            overflow-x: hidden;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        html {
            scroll-behavior: smooth;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: var(--text-heading);
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .bg-light,
        .bg-white {
            background-color: var(--bg-section) !important;
        }

        .bg-alt {
            background-color: var(--bg-section-alt) !important;
        }

        .text-gradient {
            background: linear-gradient(135deg, var(--islamic-purple-500), var(--islamic-tosca-400));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .section-label {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--islamic-purple-600);
            display: block;
            margin-bottom: 0.5rem;
        }

        [data-coreui-theme="dark"] .section-label {
            color: var(--islamic-tosca-400);
        }

        .theme-logo {
            position: relative;
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
        }

        .theme-logo--nav {
            width: 48px;
            height: 48px;
        }

        .theme-logo--footer {
            width: 56px;
            height: 56px;
        }

        .theme-logo--owner {
            width: 100px;
            height: 100px;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 26px;
            background: rgba(255, 255, 255, .07);
            box-shadow: 0 18px 40px -18px rgba(0, 0, 0, .55);
        }

        .theme-logo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .theme-logo .logo-dark {
            display: none;
        }

        [data-coreui-theme="dark"] .theme-logo .logo-light {
            display: none;
        }

        [data-coreui-theme="dark"] .theme-logo .logo-dark {
            display: block;
        }

        .theme-logo.theme-logo--inverse .logo-light {
            display: none;
        }

        .theme-logo.theme-logo--inverse .logo-dark {
            display: block;
        }

        .brand-copy {
            line-height: 1.05;
        }

        .brand-copy strong {
            display: block;
            font-size: 1rem;
            letter-spacing: -.02em;
        }

        .brand-copy small {
            display: block;
            margin-top: .28rem;
            color: var(--text-muted);
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .04em;
        }

        .navbar-glass:not(.scrolled) .brand-copy small {
            color: var(--text-muted);
        }

        [data-coreui-theme="dark"] .brand-copy small {
            color: rgba(255, 255, 255, .68);
        }

        /* ============================================================
        3. NAVIGATION (SOLID & SEAMLESS)
        ============================================================ */
        .navbar-glass {
            background: transparent;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding-top: 1.2rem;
            padding-bottom: 1.2rem;
        }

        .navbar-brand span,
        .nav-link {
            color: var(--text-heading) !important;
            font-weight: 700;
            transition: all 0.3s ease;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .navbar-glass.scrolled,
        .navbar-glass:has(.navbar-collapse.show) {
            background: var(--nav-bg) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding-top: 0.8rem;
            padding-bottom: 0.8rem;
            box-shadow: var(--shadow-sm);
        }

        .navbar-glass.scrolled .nav-link,
        .navbar-glass.scrolled .navbar-brand span,
        .navbar-glass:has(.navbar-collapse.show) .nav-link,
        .navbar-glass:has(.navbar-collapse.show) .navbar-brand span {
            color: var(--islamic-purple-700) !important;
            text-shadow: none !important;
        }

        [data-coreui-theme="dark"] .navbar-glass.scrolled .nav-link,
        [data-coreui-theme="dark"] .navbar-glass.scrolled .navbar-brand span,
        [data-coreui-theme="dark"] .navbar-glass:has(.navbar-collapse.show) .nav-link,
        [data-coreui-theme="dark"] .navbar-glass:has(.navbar-collapse.show) .navbar-brand span {
            color: #ffffff !important;
        }

        .nav-link:hover {
            color: var(--islamic-purple-700) !important;
            transform: translateY(-1px);
        }

        [data-coreui-theme="dark"] .nav-link:hover {
            color: var(--islamic-tosca-400) !important;
        }

        /* Apple-style Toggle Switch */
        .apple-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .apple-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .apple-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e5ea;
            transition: .4s;
            border-radius: 34px;
        }

        .apple-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        input:checked+.apple-slider {
            background-color: var(--islamic-purple-600, #34c759);
        }

        input:focus+.apple-slider {
            box-shadow: 0 0 1px var(--islamic-purple-600, #34c759);
        }

        input:checked+.apple-slider:before {
            transform: translateX(22px);
        }

        /* Style dasar untuk tombol outline purple */
        .btn-outline-purple {
            border: 2px solid var(--islamic-purple-500);
            color: var(--islamic-purple-600);
            background-color: transparent;
            transition: all 0.3s ease;
        }

        /* Efek saat kursor diarahkan (hover) */
        .btn-outline-purple:hover {
            background-color: var(--islamic-purple-600);
            transform: translateY(-1px);
            color: #ffffff !important;
        }

        /* Penyesuaian saat mode gelap (Dark Mode) aktif */
        [data-coreui-theme="dark"] .btn-outline-purple {
            border-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        [data-coreui-theme="dark"] .btn-outline-purple:hover {
            background-color: var(--islamic-tosca-400);
            border-color: var(--islamic-tosca-400);
            color: #0f172a !important;
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(255, 255, 255, 0.98);
                margin-top: 15px;
                padding: 20px;
                border-radius: 20px;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            [data-coreui-theme="dark"] .navbar-collapse {
                background: rgba(15, 23, 42, 0.98);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .nav-link {
                text-align: left !important;
                padding: 12px 0 !important;
                border-bottom: 1px solid rgba(0, 0, 0, 0.03);
                width: 100%;
                text-shadow: none !important;
            }

            [data-coreui-theme="dark"] .nav-link {
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }
        }

        @media (min-width: 992px) {
            .navbar-nav {
                gap: 0.5rem;
            }

            .navbar-nav .nav-link {
                padding-left: 1.25rem !important;
                padding-right: 1.25rem !important;
            }
        }

        /* ============================================================
           4. HERO SECTION & AURORA BACKGROUND
        ============================================================ */
        .hero-section {
            position: relative;
            padding: 180px 0 100px;
            min-height: 95vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .btn-hero-outline {
            color: var(--text-heading);
            border-color: rgba(0, 0, 0, 0.1);
        }

        [data-coreui-theme="dark"] .btn-hero-outline {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        #aurora-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            background: #ffffff;
            transition: background 0.5s ease;
        }

        [data-coreui-theme="dark"] #aurora-bg {
            background: #080514;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: clamp(2.8rem, 5.5vw, 4.5rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            color: var(--text-heading);
            transition: color 0.3s ease;
        }

        [data-coreui-theme="dark"] .hero-title {
            color: #ffffff;
            text-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            line-height: 1.8;
            max-width: 90%;
            color: var(--text-muted);
            transition: color 0.3s ease;
        }

        [data-coreui-theme="dark"] .hero-subtitle {
            color: rgba(255, 255, 255, 0.9);
        }

        .hero-badge {
            background: #ffffff;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(107, 78, 255, 0.2);
            color: var(--islamic-purple-700);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        [data-coreui-theme="dark"] .hero-badge {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        /* ============================================================
        GLOBAL CARD VARIABLES & GLASSMORPHISM
        ============================================================ */
        :root {
            --card-width: 350px;
            --card-height: 480px;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.05);
            --glass-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            --text-main: var(--text-heading);
        }

        [data-coreui-theme="dark"] {
            --glass-bg: rgba(15, 23, 42, 0.75);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.4);
            --text-main: #ffffff;
        }

        .glass-pane,
        .card-swap-container .card {
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            box-shadow: var(--glass-shadow);
            color: var(--text-main);
            padding: 2.5rem 2rem;
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        }

        /* ============================================================
        SWIPER 3D CARDS (Optimized)
        ============================================================ */
        .swiper-cards-container {
            width: 100%;
            max-width: 350px;
            height: 480px;
            margin: 0 auto;
            padding: 20px 0;
            overflow: visible !important;
        }

        .swiper-cards-container .swiper-slide {
            transform-origin: center center !important;
            will-change: transform, opacity;
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: transform 0.6s ease, opacity 0.4s ease;
        }

        .glass-pane {
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            box-shadow: var(--glass-shadow);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 2rem;
        }

        .card-swap-container {
            position: relative;
            perspective: 1200px;
            width: var(--card-width);
            height: var(--card-height);
            margin: 0 auto;
        }

        .card-swap-container .card {
            position: absolute;
            inset: 0;
            transform-style: preserve-3d;
            backface-visibility: hidden;
        }

        @media (max-width: 768px) {

            .card-swap-container,
            .swiper-cards-container {
                transform: scale(0.85);
            }
        }

        /* ============================================================
        6. FLOATING STATS SECTION
        ============================================================ */
        .stats-wrapper {
            position: relative;
            z-index: 10;
            margin-top: -80px;
            background: linear-gradient(135deg, var(--islamic-purple-600) 0%, var(--islamic-purple-900) 100%);
            border-radius: 30px;
            padding: 3.5rem 2rem;
            color: white;
            box-shadow: 0 30px 60px -12px rgba(86, 64, 165, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stats-wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 0 40px 70px -10px rgba(86, 64, 165, 0.55);
        }

        [data-coreui-theme="dark"] .stats-wrapper {
            background: linear-gradient(135deg, #4c1d95 0%, #1e1b4b 100%);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .stats-number {
            font-size: clamp(2.5rem, 4.5vw, 3.8rem);
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.5rem;
            background: linear-gradient(to bottom, #ffffff 30%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .stats-label {
            font-weight: 700;
            font-size: 1.1rem;
            color: #ffffff;
            display: block;
            margin-bottom: 0.25rem;
        }

        .stats-desc {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 400;
        }

        @media (min-width: 992px) {
            .stats-divider {
                position: relative;
            }

            .stats-divider::after {
                content: "";
                position: absolute;
                right: 0;
                top: 20%;
                height: 60%;
                width: 1px;
                background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.2), transparent);
            }
        }

        @media (max-width: 991px) {
            .stats-wrapper {
                margin-top: -50px;
                padding: 2.5rem 1.5rem;
            }
        }

        /* ============================================================
           7. FEATURES & TESTIMONIALS
        ============================================================ */
        .feature-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            height: 100%;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
            border-color: var(--islamic-purple-400);
        }

        .icon-box {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, rgba(107, 78, 255, 0.1), rgba(57, 193, 204, 0.1));
            color: var(--islamic-purple-600);
        }

        [data-coreui-theme="dark"] .icon-box {
            color: var(--islamic-tosca-400);
        }

        .feature-card:hover .icon-box {
            background: linear-gradient(135deg, var(--islamic-purple-500), var(--islamic-tosca-500));
            color: #fff;
            transform: scale(1.05) rotate(-5deg);
        }

        .testimonial-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: var(--shadow-sm);
        }

        /* ============================================================
           8. INSTITUTIONAL PRODUCT STORY
        ============================================================ */
        .institution-panel {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
            border-radius: 32px;
            background:
                radial-gradient(circle at 90% 10%, rgba(247, 201, 72, .16), transparent 28%),
                linear-gradient(135deg, rgba(107, 78, 255, .10), rgba(255, 255, 255, .02)),
                var(--card-bg);
            box-shadow: var(--shadow-md);
        }

        .institution-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .55rem .9rem;
            border: 1px solid rgba(107, 78, 255, .18);
            border-radius: 999px;
            color: var(--islamic-purple-700);
            background: rgba(107, 78, 255, .08);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .04em;
        }

        [data-coreui-theme="dark"] .institution-eyebrow {
            color: var(--brand-gold-400);
            border-color: rgba(247, 201, 72, .22);
            background: rgba(247, 201, 72, .08);
        }

        .institution-quote {
            border-left: 4px solid var(--brand-gold-400);
            padding: .25rem 0 .25rem 1.25rem;
            color: var(--text-heading);
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.7;
        }

        .institution-card {
            height: 100%;
            padding: 1.7rem;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            background: var(--card-bg);
            box-shadow: var(--shadow-sm);
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
        }

        .institution-card:hover {
            transform: translateY(-5px);
            border-color: rgba(107, 78, 255, .3);
            box-shadow: var(--shadow-md);
        }

        .institution-card .mini-icon {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            color: var(--islamic-purple-600);
            background: rgba(107, 78, 255, .1);
            font-size: 1.25rem;
        }

        .ownership-card {
            height: 100%;
            padding: 2.25rem;
            border-radius: 28px;
            color: #ffffff;
            background: linear-gradient(145deg, var(--islamic-purple-700), var(--islamic-purple-900));
            box-shadow: 0 25px 55px -20px rgba(27, 20, 58, .75);
        }

        .ownership-card h3,
        .ownership-card h4 {
            color: #ffffff;
        }

        .ownership-card p {
            color: rgba(255, 255, 255, .74);
        }

        /* ============================================================
           9. FAQ ACCORDION
        ============================================================ */
        .custom-accordion .accordion-item {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px !important;
            margin-bottom: 1.25rem;
            overflow: hidden;
        }

        .custom-accordion .accordion-button {
            color: var(--text-heading);
            font-weight: 700;
            padding: 1.5rem;
            background: transparent;
            box-shadow: none !important;
        }

        .custom-accordion .accordion-button:not(.collapsed) {
            color: var(--islamic-purple-600);
            background-color: rgba(107, 78, 255, 0.03);
        }

        [data-coreui-theme="dark"] .custom-accordion .accordion-button:not(.collapsed) {
            color: var(--islamic-tosca-400);
            background-color: rgba(57, 193, 204, 0.05);
        }

        [data-coreui-theme="dark"] .custom-accordion .accordion-body {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        [data-coreui-theme="dark"] .accordion-button::after {
            filter: invert(1);
        }

        /* ============================================================
           10. UTILITY BUTTONS & BACK TO TOP
        ============================================================ */
        .btn {
            font-weight: 700;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-login-portal {
            color: var(--islamic-purple-700);
            background-color: rgba(107, 78, 255, 0.08);
            border: 1px solid rgba(107, 78, 255, 0.15);
        }

        .btn-login-portal:hover {
            background-color: var(--islamic-purple-600);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(107, 78, 255, 0.3);
        }

        [data-coreui-theme="dark"] .btn-login-portal {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        [data-coreui-theme="dark"] .btn-login-portal:hover {
            background: var(--islamic-tosca-400);
            color: #0f172a;
        }

        .btn-back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            background: linear-gradient(135deg, var(--islamic-purple-500), var(--islamic-purple-700));
            color: white;
            border: none;
            transform: translateY(20px);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .btn-back-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        [data-coreui-theme="dark"] .btn-back-to-top {
            background: linear-gradient(135deg, var(--islamic-tosca-400), var(--islamic-tosca-500));
            color: #0f172a;
        }

        [data-coreui-theme="dark"] .text-success {
            color: #10b981 !important;
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }

        [data-coreui-theme="dark"] .btn-light {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #fff !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }


        /* ============================================================
           SYSTEM REVIEWS
        ============================================================ */
        .system-review-section {
            position: relative;
            overflow: hidden;
        }

        .system-review-section::before {
            content: '';
            position: absolute;
            top: 40px;
            left: -130px;
            width: 310px;
            height: 310px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(107, 78, 255, .13), transparent 68%);
            pointer-events: none;
        }

        .system-review-summary {
            border: 1px solid var(--border-color);
            border-radius: 24px;
            background:
                linear-gradient(135deg, rgba(107, 78, 255, .08), rgba(57, 193, 204, .07)),
                var(--card-bg);
            box-shadow: var(--shadow-md);
        }

        .system-review-average {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text-heading);
        }

        .system-review-card {
            position: relative;
            height: 100%;
            overflow: hidden;
            padding: 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            background: var(--card-bg);
            box-shadow: var(--shadow-sm);
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
        }

        .system-review-card:hover {
            transform: translateY(-6px);
            border-color: rgba(107, 78, 255, .28);
            box-shadow: var(--shadow-md);
        }

        .system-review-card::after {
            content: '\201C';
            position: absolute;
            top: -24px;
            right: 14px;
            color: rgba(107, 78, 255, .08);
            font-family: Georgia, serif;
            font-size: 8rem;
            font-weight: 800;
            line-height: 1;
            pointer-events: none;
        }

        .system-review-avatar {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            color: #fff;
            background: linear-gradient(135deg, var(--islamic-purple-500), var(--islamic-tosca-500));
            font-size: 1.05rem;
            font-weight: 800;
        }

        .system-review-stars {
            color: #f7b500;
            letter-spacing: .08rem;
        }

        .system-review-text {
            position: relative;
            z-index: 1;
            color: var(--text-main);
            font-size: .92rem;
            line-height: 1.75;
        }

        [data-coreui-theme="dark"] .system-review-card::after {
            color: rgba(169, 155, 255, .10);
        }
    </style>
</head>

<body>
    {{-- NAVBAR --}}
    <nav class="navbar navbar-expand-lg fixed-top navbar-glass">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#home">
                <span class="theme-logo theme-logo--nav">
                    <img class="logo-light" src="{{ asset('assets/logos-primary.png') }}" alt="Logo SIMTAQU APPS">
                    <img class="logo-dark" src="{{ asset('assets/logos.png') }}" alt="" aria-hidden="true">
                </span>
                <span class="brand-copy">
                    <strong>SIMTAQU APPS</strong>
                    <small>DEPT. AL-QUR'AN · DARUT TAQWA</small>
                </span>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
                <i class="bi bi-list fs-2 text-primary"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul
                    class="navbar-nav ms-lg-auto align-items-start align-items-lg-center gap-3 gap-lg-2 text-nowrap pt-3 pt-lg-0">
                    <li class="nav-item w-100 w-lg-auto"><a class="nav-link" href="#home">Beranda</a></li>
                    <li class="nav-item w-100 w-lg-auto"><a class="nav-link" href="#fitur">Fitur</a></li>
                    <li class="nav-item w-100 w-lg-auto"><a class="nav-link" href="#tentang">Tentang</a></li>
                    <li class="nav-item w-100 w-lg-auto"><a class="nav-link" href="#review">Review</a></li>
                    <li class="nav-item w-100 w-lg-auto"><a class="nav-link" href="#faq">Tanya Jawab</a></li>

                    <div
                        class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 w-100 w-lg-auto mt-2 mt-lg-0">
                        {{-- Apple Style Dark Mode Toggle --}}
                        <li class="nav-item d-flex align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-sun-fill text-warning fs-5"></i>
                                <label class="apple-switch mb-0" for="checkboxThemeToggle">
                                    <input type="checkbox" id="checkboxThemeToggle">
                                    <span class="apple-slider"></span>
                                </label>
                                <i class="bi bi-moon-stars-fill fs-6" style="color: var(--islamic-purple-600);"></i>
                            </div>
                        </li>

                        <li class="nav-item flex-grow-1 flex-lg-grow-0 d-flex gap-2 w-100 w-lg-auto">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/login') }}"
                                        class="btn text-white rounded-pill px-4 shadow-sm w-100 fw-bold"
                                        style="background: linear-gradient(135deg, var(--islamic-purple-500), var(--islamic-purple-700)); border: none;">
                                        Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                        class="btn btn-outline-purple rounded-pill px-4 w-100 fw-bold">
                                        Masuk
                                    </a>
                                @endauth
                            @endif
                        </li>
                    </div>
                </ul>
            </div>
        </div>
    </nav>

    {{-- HERO SECTION --}}
    <section id="home" class="hero-section">
        <div id="aurora-bg"></div>
        <div class="container hero-content">
            <div class="row align-items-center justify-content-between">
                <div class="col-lg-6 mb-5 mb-lg-0 animate__animated animate__fadeInLeft">
                    <div class="d-inline-block hero-badge rounded-pill px-3 py-2 mb-4">
                        <span class="fw-bold small"><i class="bi bi-patch-check-fill text-warning me-1"></i>
                            Presented by Departemen Al-Qur'an</span>
                    </div>
                    <h1 class="hero-title mb-4">
                        Menjaga Setiap Proses, Menguatkan <span class="text-warning">Generasi Qur'ani.</span>
                    </h1>
                    <p class="hero-subtitle mb-5">
                        SIMTAQU APPS menyatukan pembinaan tahfidz, tahsin, dan tilawah dalam satu sistem informasi
                        terintegrasi milik Departemen Al-Qur'an Pondok Pesantren Darut Taqwa Ponorogo.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="btn btn-warning btn-lg rounded-pill px-5 shadow-lg">
                            Masuk ke SIMTAQU <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                        <a href="#tentang" class="btn btn-lg rounded-pill px-4 btn-hero-outline shadow-sm">
                            Kenali Sistem
                        </a>
                    </div>
                </div>

                <div class="col-lg-5 animate__animated animate__fadeInRight">
                    <div class="swiper swiper-cards-container">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide glass-pane text-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-4"
                                    style="width: 88px; height: 88px;">
                                    <i class="bi bi-pie-chart-fill text-primary" style="font-size: 2.5rem;"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Pantauan 30 Juz</h4>
                                <p class="small opacity-75 mb-4 px-2" style="line-height: 1.6;">Pimpinan dan pengelola
                                    dapat membaca capaian setoran serta kelulusan ujian santri secara visual.</p>
                                <div class="progress bg-secondary bg-opacity-25 mb-3 rounded-pill"
                                    style="height: 10px;">
                                    <div class="progress-bar bg-warning rounded-pill" style="width: 85%"></div>
                                </div>
                                <div class="progress bg-secondary bg-opacity-25 rounded-pill" style="height: 10px;">
                                    <div class="progress-bar bg-info rounded-pill" style="width: 60%"></div>
                                </div>
                            </div>

                            <div class="swiper-slide glass-pane text-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-4"
                                    style="width: 88px; height: 88px;">
                                    <i class="bi bi-check2-square text-warning" style="font-size: 2.5rem;"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Setoran Terstruktur</h4>
                                <p class="small opacity-75 mb-4 px-2" style="line-height: 1.6;">Musyrif mencatat
                                    setoran, status kehadiran, dan hasil evaluasi langsung dari smartphone.</p>
                                <button class="btn btn-primary w-100 rounded-pill fw-bold text-white py-3 shadow-sm">
                                    <i class="bi bi-plus-lg me-2"></i> Input Setoran
                                </button>
                            </div>

                            <div class="swiper-slide glass-pane text-center">
                                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-4"
                                    style="width: 88px; height: 88px;">
                                    <i class="bi bi-file-earmark-excel text-success" style="font-size: 2.5rem;"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Laporan Akademik</h4>
                                <p class="small opacity-75 mb-4 px-2" style="line-height: 1.6;">Rekap santri, kelas,
                                    dan musyrif tersaji konsisten untuk evaluasi serta dokumen akademik.</p>
                                <div
                                    class="d-flex justify-content-between align-items-center border-top border-secondary border-opacity-25 pt-4 mt-auto">
                                    <span class="small fw-bold opacity-75">Status Data</span>
                                    <span class="badge bg-success rounded-pill px-3 py-2">Tersinkron</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4 text-white opacity-100 small fw-bold animate__animated animate__pulse animate__infinite"
                        style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                        <i class="bi bi-arrows-collapse d-block fs-4 mb-1"></i>
                        Geser kartu untuk melihat
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- STATS SECTION (FLOATING) --}}
    <section class="stats-section">
        <div class="container animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
            <div class="stats-wrapper">
                <div class="row g-4 text-center align-items-center">
                    <div class="col-6 col-lg-3 stats-divider">
                        <div class="stats-number"><span class="counter" data-target="30">0</span> Juz</div>
                        <div class="stats-label">Peta Capaian</div>
                        <div class="stats-desc mt-1">Progress Tahfidz Terukur</div>
                    </div>
                    <div class="col-6 col-lg-3 stats-divider">
                        <div class="stats-number"><span class="counter" data-target="3">0</span></div>
                        <div class="stats-label">Program Utama</div>
                        <div class="stats-desc mt-1">Tahfidz, Tahsin & Tilawah</div>
                    </div>
                    <div class="col-6 col-lg-3 stats-divider">
                        <div class="stats-number"><span class="counter" data-target="5">0</span></div>
                        <div class="stats-label">Level Akses</div>
                        <div class="stats-desc mt-1">Peran Pengguna</div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stats-number"><span class="counter" data-target="1">0</span></div>
                        <div class="stats-label">Sumber Data</div>
                        <div class="stats-desc mt-1">Terpusat & Terintegrasi</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FEATURES SECTION --}}
    <section id="fitur" class="py-5 my-5">
        <div class="container py-5">
            <div class="text-center mb-5 pb-3 animate__animated animate__fadeInUp">
                <span class="section-label">Ekosistem SIMTAQU</span>
                <h2 class="display-6 fw-bold mb-4">Dari Halaqah hingga Meja Pimpinan</h2>
                <p class="text-muted mx-auto fs-5" style="max-width: 720px;">Setiap fitur dirancang dari alur kerja
                    nyata Departemen Al-Qur'an agar pembinaan, evaluasi, dan pengambilan keputusan berada dalam satu
                    data yang sama.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card feature-card">
                        <div class="icon-box"><i class="bi bi-speedometer2"></i></div>
                        <h4 class="fs-5 mb-3">Dashboard Pimpinan</h4>
                        <p class="text-muted small mb-0 lh-lg">Ringkasan progres, capaian per juz, dan indikator
                            evaluasi membantu pimpinan membaca kondisi pembinaan secara cepat.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card feature-card">
                        <div class="icon-box"><i class="bi bi-journal-check"></i></div>
                        <h4 class="fs-5 mb-3">Setoran Tahfidz</h4>
                        <p class="text-muted small mb-0 lh-lg">Catat setoran harian, tahap evaluasi, ujian akhir,
                            status kelulusan, dan nilai melalui alur yang konsisten.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card feature-card">
                        <div class="icon-box"><i class="bi bi-book"></i></div>
                        <h4 class="fs-5 mb-3">Tahsin & Tilawah</h4>
                        <p class="text-muted small mb-0 lh-lg">Perkembangan kualitas bacaan dan tilawah santri
                            terdokumentasi berdampingan dengan perjalanan hafalannya.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card feature-card">
                        <div class="icon-box"><i class="bi bi-patch-check-fill"></i></div>
                        <h4 class="fs-5 mb-3">Ujian & Penilaian</h4>
                        <p class="text-muted small mb-0 lh-lg">Standar nilai dan kelulusan tersimpan rapi sehingga
                            evaluasi santri tidak lagi tersebar di banyak catatan.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card feature-card">
                        <div class="icon-box"><i class="bi bi-diagram-3-fill"></i></div>
                        <h4 class="fs-5 mb-3">Santri & Kelas</h4>
                        <p class="text-muted small mb-0 lh-lg">Kelola kelas, kelompok binaan, musyrif, migrasi
                            kenaikan kelas, status aktif, dan alumni secara terstruktur.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card feature-card">
                        <div class="icon-box"><i class="bi bi-geo-alt-fill"></i></div>
                        <h4 class="fs-5 mb-3">Absensi Musyrif</h4>
                        <p class="text-muted small mb-0 lh-lg">Kehadiran musyrif terdokumentasi dengan validasi lokasi
                            kampus untuk mendukung kedisiplinan pembinaan.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card feature-card">
                        <div class="icon-box"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
                        <h4 class="fs-5 mb-3">Laporan Akademik</h4>
                        <p class="text-muted small mb-0 lh-lg">Rekap santri, kelas, dan musyrif tersedia dalam grafik,
                            PDF, serta Excel untuk evaluasi dan dokumentasi.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card feature-card">
                        <div class="icon-box"><i class="bi bi-clock-history"></i></div>
                        <h4 class="fs-5 mb-3">Semester & Jejak Audit</h4>
                        <p class="text-muted small mb-0 lh-lg">Siklus semester dan riwayat perubahan data membantu
                            admin menjaga ketertiban proses dari waktu ke waktu.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- SYSTEM REVIEW SECTION --}}
    @if (($systemReviews ?? collect())->isNotEmpty())
        <section id="review" class="system-review-section py-5 bg-alt">
            <div class="container py-5">
                <div class="row align-items-end g-4 mb-5">
                    <div class="col-lg-8">
                        <span class="section-label">Suara dari Lapangan</span>
                        <h2 class="display-6 fw-bold mb-3">
                            Pengalaman Musyrif
                            <span class="text-gradient">Menggunakan SIMTAQU</span>
                        </h2>
                        <p class="text-muted fs-5 mb-0" style="max-width: 720px;">
                            Masukan berikut dikirim langsung dari dashboard, lalu dimoderasi sebagai bagian dari
                            perbaikan sistem yang terus berjalan.
                        </p>
                    </div>

                    <div class="col-lg-4">
                        <div class="system-review-summary p-4">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <div class="small text-muted fw-bold text-uppercase mb-1">
                                        Kepuasan Pengguna
                                    </div>
                                    <div class="system-review-average">
                                        {{ number_format($reviewStats['average'] ?? 0, 1, ',', '.') }}
                                    </div>
                                </div>

                                <div class="text-end">
                                    <div class="system-review-stars fs-5 mb-1">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <i class="bi bi-star-fill"></i>
                                        @endfor
                                    </div>
                                    <div class="small text-muted">
                                        {{ number_format($reviewStats['total'] ?? 0, 0, ',', '.') }}
                                        review terverifikasi
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    @foreach ($systemReviews as $review)
                        <div class="col-md-6 col-xl-4">
                            <article class="system-review-card">
                                <div class="system-review-stars mb-3">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <i class="bi {{ $star <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </div>

                                @if ($review->title)
                                    <h3 class="h6 fw-bold mb-2">
                                        {{ $review->title }}
                                    </h3>
                                @endif

                                <p class="system-review-text mb-4">
                                    “{{ $review->review }}”
                                </p>

                                <div class="d-flex align-items-center gap-3 mt-auto">
                                    <span class="system-review-avatar">
                                        {{ strtoupper(mb_substr($review->public_name, 0, 1)) }}
                                    </span>

                                    <div class="min-w-0">
                                        <div class="fw-bold text-truncate">
                                            {{ $review->public_name }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $review->role_label }}
                                            <span class="mx-1">•</span>
                                            Review terverifikasi
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- INSTITUTIONAL PRODUCT SECTION --}}
    <section id="tentang" class="py-5 bg-alt">
        <div class="container py-5">
            <div class="institution-panel p-4 p-lg-5">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-7">
                        <span class="institution-eyebrow mb-4">
                            <i class="bi bi-building-check"></i> DARI PESANTREN, UNTUK PESANTREN
                        </span>
                        <span class="section-label">Tentang Produk</span>
                        <h2 class="display-6 fw-bold mb-4">
                            Teknologi yang Lahir dari
                            <span class="text-gradient">Kebutuhan Pembinaan Nyata</span>
                        </h2>
                        <p class="text-muted fs-5 lh-lg mb-4">
                            SIMTAQU APPS bukan produk langganan komersial. Sistem ini adalah produk digital
                            Departemen Al-Qur'an Pondok Pesantren Darut Taqwa Ponorogo untuk menertibkan data,
                            memudahkan musyrif, dan menghadirkan evaluasi pembinaan yang lebih utuh.
                        </p>
                        <p class="institution-quote mb-4">
                            Digitalisasi bukan menggantikan sentuhan pendidikan, tetapi memastikan setiap proses
                            tahsin, tahfidz, dan tilawah dapat dijaga dengan data yang amanah.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="https://ppdaruttaqwa.com/" target="_blank" rel="noopener noreferrer"
                                class="btn rounded-pill px-4 text-white"
                                style="background-color: var(--islamic-purple-600);">
                                Profil Pondok <i class="bi bi-box-arrow-up-right ms-2"></i>
                            </a>
                            <a href="https://www.youtube.com/@GuardOfQuran" target="_blank" rel="noopener noreferrer"
                                class="btn btn-outline-purple rounded-pill px-4">
                                Kanal Dept. Al-Qur'an <i class="bi bi-youtube ms-2"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="ownership-card">
                            <div class="theme-logo theme-logo--owner theme-logo--inverse mb-4">
                                <img class="logo-light" src="{{ asset('assets/logos-primary.png') }}" alt=""
                                    aria-hidden="true">
                                <img class="logo-dark" src="{{ asset('assets/logos.png') }}"
                                    alt="Logo Departemen Al-Qur'an">
                            </div>
                            <div class="small fw-bold text-uppercase mb-2"
                                style="letter-spacing: .12em; color: var(--brand-gold-400);">Product Owner</div>
                            <h3 class="h2 mb-3">Departemen Al-Qur'an</h3>
                            <p class="mb-4 lh-lg">Pondok Pesantren Darut Taqwa · Pintu, Jenangan, Ponorogo</p>
                            <div class="border-top pt-4" style="border-color: rgba(255,255,255,.14) !important;">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <i class="bi bi-patch-check-fill text-warning fs-5"></i>
                                    <span class="small fw-semibold">Sistem institusional, bukan marketplace</span>
                                </div>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <i class="bi bi-people-fill text-warning fs-5"></i>
                                    <span class="small fw-semibold">Dibangun mengikuti alur kerja pengguna</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-arrow-repeat text-warning fs-5"></i>
                                    <span class="small fw-semibold">Dikembangkan secara berkelanjutan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <article class="institution-card">
                        <span class="mini-icon mb-4"><i class="bi bi-calendar2-check"></i></span>
                        <h3 class="h5 mb-3">Berakar Sejak 2010</h3>
                        <p class="text-muted small lh-lg mb-0">Darut Taqwa resmi berdiri pada 14 Juli 2010 dan terus
                            bertumbuh sebagai pusat pendidikan Islam modern di Ponorogo.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="institution-card">
                        <span class="mini-icon mb-4"><i class="bi bi-stars"></i></span>
                        <h3 class="h5 mb-3">Visi Generasi Qur'ani</h3>
                        <p class="text-muted small lh-lg mb-0">Sistem mendukung visi pondok dalam membentuk generasi
                            Qur'ani yang mandiri, berprestasi, dan siap memberi manfaat.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="institution-card">
                        <span class="mini-icon mb-4"><i class="bi bi-mortarboard-fill"></i></span>
                        <h3 class="h5 mb-3">Pembinaan Berkelanjutan</h3>
                        <p class="text-muted small lh-lg mb-0">Departemen Al-Qur'an mengawal pembelajaran tahfidz dan
                            tahsin pada jenjang pendidikan pesantren secara terstruktur.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    {{-- TESTIMONI SECTION --}}
    {{-- <section id="testimoni" class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5 pb-3 animate__animated animate__fadeInUp">
                <span class="section-label">Ulasan Pengguna</span>
                <h2 class="display-6 fw-bold mb-3">Dipercaya oleh Lembaga Pendidikan</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card testimonial-card">
                        <div class="d-flex mb-4">
                            <i class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="text-muted mb-5 fs-6 lh-lg">"Dulu rekap setoran santri selalu numpuk di akhir bulan
                            dan sering tercecer. Sejak pakai sistem ini, musyrif tinggal input via HP setiap habis
                            halaqah. Sangat menghemat waktu!"</p>
                        <div
                            class="d-flex align-items-center mt-auto border-top pt-4 border-opacity-10 border-secondary">
                            <img src="https://ui-avatars.com/api/?name=Ahmad+Hidayat&background=6b4eff&color=fff&rounded=true&bold=true"
                                alt="Avatar" width="48" height="48" class="me-3">
                            <div>
                                <h6 class="fw-bold mb-1">Ust. Ahmad Hidayat</h6>
                                <span class="small text-muted">Kepala TPQ Al-Huda</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card testimonial-card">
                        <div class="d-flex mb-4">
                            <i class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="text-muted mb-5 fs-6 lh-lg">"Fitur Import Excel-nya juara! Memasukkan data 400 santri
                            baru di ajaran tahun ini cuma butuh hitungan detik. Hak akses Multi-role juga bikin kerja
                            admin jauh lebih ringan."</p>
                        <div
                            class="d-flex align-items-center mt-auto border-top pt-4 border-opacity-10 border-secondary">
                            <img src="https://ui-avatars.com/api/?name=Fatimah+Az-Zahra&background=39c1cc&color=fff&rounded=true&bold=true"
                                alt="Avatar" width="48" height="48" class="me-3">
                            <div>
                                <h6 class="fw-bold mb-1">Fatimah Az-Zahra</h6>
                                <span class="small text-muted">Admin Pondok Pesantren</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card testimonial-card">
                        <div class="d-flex mb-4">
                            <i class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning me-1"></i><i
                                class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="text-muted mb-5 fs-6 lh-lg">"Sebagai pengurus yayasan, saya sekarang bisa memantau
                            tren perkembangan tahfidz seluruh cabang secara real-time dari Dashboard. Laporannya sangat
                            profesional."</p>
                        <div
                            class="d-flex align-items-center mt-auto border-top pt-4 border-opacity-10 border-secondary">
                            <img src="https://ui-avatars.com/api/?name=Dr.+H.+Ridwan&background=10b981&color=fff&rounded=true&bold=true"
                                alt="Avatar" width="48" height="48" class="me-3">
                            <div>
                                <h6 class="fw-bold mb-1">Dr. H. Ridwan</h6>
                                <span class="small text-muted">Direktur Yayasan Pendidikan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}

    {{-- FAQ SECTION --}}
    <section id="faq" class="py-5 bg-alt">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-5 mb-lg-0 animate__animated animate__fadeInLeft pe-lg-5">
                    <span class="section-label">Tanya Jawab</span>
                    <h2 class="display-6 fw-bold mb-4">Pertanyaan yang Sering Diajukan</h2>
                    <p class="text-muted mb-4 fs-5">Kenali fungsi, pengguna, dan posisi SIMTAQU APPS di lingkungan
                        Pondok Pesantren Darut Taqwa.</p>
                </div>
                <div class="col-lg-7 animate__animated animate__fadeInRight">
                    <div class="accordion custom-accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq1">
                                    Apakah SIMTAQU APPS dijual untuk umum?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show"
                                data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Tidak. SIMTAQU APPS diposisikan sebagai produk digital institusional milik
                                    Departemen Al-Qur'an Pondok Pesantren Darut Taqwa, bukan layanan langganan atau
                                    paket komersial untuk dijual kepada lembaga lain.
                                    <br>
                                    <br>
                                    Jika ingin membuat produk serupa bisa menghubungi pihak developer <a
                                        href="https://anzartstudio.id" target="_blank" rel="noopener noreferrer"
                                        class="fw-bold text-decoration-none"
                                        style="color: var(--islamic-purple-500);">
                                        AnzArt Studio.
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq2">
                                    Siapa saja yang menggunakan SIMTAQU?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Hak akses disesuaikan dengan tanggung jawab pengguna, mulai dari pengelola sistem,
                                    admin Departemen Al-Qur'an, pimpinan, musyrif, hingga santri atau wali yang
                                    memantau perkembangan melalui akun terkait.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq3">
                                    Apa saja proses pembinaan yang dikelola?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    SIMTAQU mengelola data tahfidz, tahsin, tilawah, ujian, nilai, absensi musyrif,
                                    semester, kelas, migrasi santri, analitik capaian, serta laporan dan dokumen
                                    akademik yang berkaitan.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq4">
                                    Apakah musyrif dapat mencatat setoran melalui HP?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Bisa. Antarmuka SIMTAQU responsif untuk browser smartphone sehingga musyrif dapat
                                    mencatat setoran dan evaluasi langsung setelah kegiatan pembinaan.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq5">
                                    Bagaimana memperoleh akses ke sistem?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Pengguna dapat melakukan registrasi akun, kemudian menunggu verifikasi dan
                                    penetapan hak akses oleh pengelola yang berwenang sebelum menggunakan dashboard.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- BACK TO TOP BUTTON --}}
    <button class="btn-back-to-top" id="backToTop" title="Kembali ke atas">
        <i class="bi bi-arrow-up fs-4"></i>
    </button>

    {{-- CTA & FOOTER --}}
    <footer class="pt-5" style="background-color: var(--bg-section);">
        <div class="container text-center mb-5 py-5">
            <span class="section-label">SIMTAQU APPS</span>
            <h2 class="display-6 fw-bold mb-4">Satu Data untuk Menjaga Amanah Pembinaan</h2>
            <p class="text-muted mb-5 mx-auto fs-5" style="max-width: 680px;">Masuk ke sistem untuk melanjutkan
                pencatatan, pemantauan, dan evaluasi program Al-Qur'an Pondok Pesantren Darut Taqwa.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('login') }}" class="btn px-5 py-3 rounded-pill fw-bold text-white shadow-lg"
                    style="background: linear-gradient(135deg, var(--islamic-purple-500), var(--islamic-purple-700)); border: none;">
                    Masuk ke Dashboard <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <a href="{{ route('register') }}" class="btn btn-outline-purple px-5 py-3 rounded-pill fw-bold">
                    Registrasi Akun
                </a>
            </div>
        </div>
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center py-4 border-top border-opacity-10"
            style="border-color: var(--border-color) !important;">
            <div class="mb-3 mb-md-0 d-flex align-items-center gap-3" style="color: var(--text-heading);">
                <span class="theme-logo theme-logo--footer">
                    <img class="logo-light" src="{{ asset('assets/logos-primary.png') }}" alt="Logo SIMTAQU APPS">
                    <img class="logo-dark" src="{{ asset('assets/logos.png') }}" alt="" aria-hidden="true">
                </span>
                <div>
                    <div class="fw-bold">SIMTAQU APPS</div>
                    <div class="small text-muted">Product by Dept. Al-Qur'an · Pondok Pesantren Darut Taqwa</div>
                </div>
            </div>
            <div class="small text-muted fw-medium text-center text-md-end">
                &copy; {{ date('Y') }} Pondok Pesantren Darut Taqwa Ponorogo.<br>
                Digital experience by
                <a href="https://anzartstudio.id" target="_blank" rel="noopener noreferrer"
                    class="fw-bold text-decoration-none" style="color: var(--islamic-purple-500);">
                    AnzArt Studio
                </a>
            </div>
        </div>
    </footer>

    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // --- 1 & 2. SWIPER ---
            const swiperContainer = document.querySelector('.swiper-cards-container');
            if (swiperContainer) {
                const swiper = new Swiper('.swiper-cards-container', {
                    effect: 'cards',
                    grabCursor: true,
                    loop: true,
                    speed: 800,
                    cardsEffect: {
                        slideShadows: false,
                        perSlideOffset: 12,
                        perSlideRotate: 4,
                    },
                    autoplay: {
                        delay: 3500,
                        disableOnInteraction: false
                    },
                });

                swiper.on('touchEnd', () => {
                    setTimeout(() => {
                        if (swiper.autoplay && !swiper.autoplay.running) swiper.autoplay.start();
                    }, 500);
                });

                let hasPeeked = false;
                window.addEventListener('scroll', () => {
                    const rect = swiperContainer.getBoundingClientRect();
                    if (rect.top < window.innerHeight && !hasPeeked) {
                        swiperContainer.classList.add('is-peeking');
                        setTimeout(() => swiperContainer.classList.remove('is-peeking'), 1500);
                        hasPeeked = true;
                    }
                });
            }

            // --- 3. LOGIKA SCROLL (NAVBAR & BACK TO TOP) ---
            const navbar = document.querySelector('.navbar-glass');
            const backToTopBtn = document.getElementById("backToTop");

            window.addEventListener('scroll', () => {
                const scrollPos = window.scrollY;
                if (navbar) {
                    if (scrollPos > 40) navbar.classList.add('scrolled');
                    else navbar.classList.remove('scrolled');
                }
                if (backToTopBtn) {
                    if (scrollPos > 300) backToTopBtn.classList.add('show');
                    else backToTopBtn.classList.remove('show');
                }
            });

            if (backToTopBtn) {
                backToTopBtn.addEventListener("click", () => window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                }));
            }

            // --- 4. ANIMASI COUNTER STATS ---
            const statsSection = document.querySelector('.stats-section');
            const counters = document.querySelectorAll('.counter');

            if (statsSection && counters.length > 0) {
                const counterObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            counters.forEach(counter => {
                                const target = +counter.getAttribute('data-target');
                                const duration = 2000;
                                const startTime = performance.now();

                                const update = (currentTime) => {
                                    const elapsed = currentTime - startTime;
                                    const progress = Math.min(elapsed / duration, 1);
                                    const easeOut = progress * (2 - progress);
                                    counter.innerText = Math.floor(easeOut * target);

                                    if (progress < 1) requestAnimationFrame(update);
                                    else counter.innerText = target;
                                };
                                requestAnimationFrame(update);
                            });
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.5
                });
                counterObserver.observe(statsSection);
            }
        });
    </script>

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
            fragColor = vec4(rampColor * auroraAlpha, auroraAlpha);
        }`;

        const container = document.getElementById('aurora-bg');

        if (container) {
            const renderer = new Renderer({
                alpha: true,
                premultipliedAlpha: true,
                antialias: true
            });
            const gl = renderer.gl;
            container.appendChild(gl.canvas);

            function getColors() {
                const isDark = document.documentElement.getAttribute('data-coreui-theme') === 'dark';
                return isDark ? ['#1b143a', '#40307a', '#0f172a'] : ['#ede9fe', '#c4b5fd', '#ddd6fe'];
            }

            const geometry = new Triangle(gl);
            const program = new Program(gl, {
                vertex: VERT,
                fragment: FRAG,
                uniforms: {
                    uTime: {
                        value: 0
                    },
                    uAmplitude: {
                        value: 1.4
                    },
                    uBlend: {
                        value: 0.6
                    },
                    uColorStops: {
                        value: getColors().map(hex => {
                            const c = new Color(hex);
                            return [c.r, c.g, c.b];
                        })
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
                program.uniforms.uTime.value = t * 0.0004;
                renderer.render({
                    scene: mesh
                });
            }
            requestAnimationFrame(update);

            // --- THEME TOGGLE LOGIC (DIPERBAIKI) ---
            const themeToggle = document.getElementById('checkboxThemeToggle');

            if (themeToggle) {
                // Set state awal checkbox berdasarkan localstorage
                const currentTheme = document.documentElement.getAttribute('data-coreui-theme');
                themeToggle.checked = currentTheme === 'dark';

                themeToggle.addEventListener('change', (e) => {
                    const newTheme = e.target.checked ? 'dark' : 'light';
                    document.documentElement.setAttribute('data-coreui-theme', newTheme);
                    localStorage.setItem('theme', newTheme);

                    // Update Shader Colors
                    const newStops = getColors();
                    program.uniforms.uColorStops.value = newStops.map(hex => {
                        const c = new Color(hex);
                        return [c.r, c.g, c.b];
                    });
                });
            }
        }

        // Handle Scroll Lock when Mobile Menu is Open (Dilengkapi Pengecekan)
        const navbarCollapse = document.getElementById('navbarNav');
        if (navbarCollapse) {
            navbarCollapse.addEventListener('show.bs.collapse', () => document.body.style.overflow = 'hidden');
            navbarCollapse.addEventListener('hide.bs.collapse', () => document.body.style.overflow = 'auto');
        }
    </script>
</body>

</html>
