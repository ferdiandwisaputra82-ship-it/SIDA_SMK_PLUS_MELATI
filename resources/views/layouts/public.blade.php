<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') - SIDA SMK Plus Melati</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v=3">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v=3">
    <link rel="apple-touch-icon" href="{{ asset('images/school-logo.png') }}?v=3">

    <!-- Anti-Flicker Script for Dark/Light Mode -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('sida_theme') || localStorage.getItem('vela_theme');
                var systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS Play CDN with Dark Mode Class Support -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        obsidian: {
                            950: '#060911',
                            900: '#090d16',
                            850: '#0f172a',
                            800: '#131c31',
                            700: '#1e293b',
                            600: '#334155',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        * { box-sizing: border-box; }
        
        :root {
            --bg-page: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --navbar-bg: rgba(255, 255, 255, 0.88);
            --navbar-border: #e2e8f0;
            --card-glass: rgba(255, 255, 255, 0.92);
            --card-glass-border: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 20px 45px rgba(15, 23, 42, 0.1);
            --hero-overlay-fade: linear-gradient(180deg, transparent 0%, #f8fafc 100%);
        }

        html.dark {
            --bg-page: #090d16;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --navbar-bg: rgba(9, 13, 22, 0.85);
            --navbar-border: rgba(255, 255, 255, 0.08);
            --card-glass: rgba(15, 23, 42, 0.82);
            --card-glass-border: rgba(255, 255, 255, 0.09);
            --card-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 25px rgba(37, 99, 235, 0.08);
            --hero-overlay-fade: linear-gradient(180deg, transparent 0%, #090d16 100%);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            margin: 0;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #2563eb; }

        /* Floating Logo Background Animation */
        @keyframes bgLogoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
            50% { transform: translateY(-18px) rotate(2deg) scale(1.03); }
        }
        @keyframes bgLogoPulse {
            0%, 100% { opacity: 0.06; }
            50% { opacity: 0.12; }
        }
        .animate-bg-logo {
            animation: bgLogoFloat 7s ease-in-out infinite, bgLogoPulse 5s ease-in-out infinite;
        }

        /* Robot Mascot Animations */
        @keyframes robotFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
        }
        @keyframes robotWave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-15deg); }
            75% { transform: rotate(10deg); }
        }
        .robot-container { animation: robotFloat 4s ease-in-out infinite; }
        .robot-arm-right { transform-origin: top left; animation: robotWave 2.5s ease-in-out infinite; }

        /* Glow Effects */
        .glow-blue {
            box-shadow: 0 0 25px -4px rgba(37, 99, 235, 0.4);
        }
        .glow-cyan {
            box-shadow: 0 0 25px -4px rgba(6, 182, 212, 0.4);
        }

        /* Dark Mode Overrides for Public Subpages (Verify, History, Result) */
        html.dark div[style*="background:linear-gradient(180deg,#f8fafc 0%,#eff6ff 60%,#f8fafc 100%)"] {
            background: linear-gradient(180deg, #090d16 0%, #0f172a 60%, #090d16 100%) !important;
        }
        html.dark div[style*="background:#ffffff"],
        html.dark div[style*="background: #ffffff"] {
            background-color: #0f172a !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
        }
        html.dark div[style*="background:#f8fafc"],
        html.dark div[style*="background: #f8fafc"] {
            background-color: #131c31 !important;
            border-color: #1e293b !important;
        }
        html.dark tr[style*="background:#f8fafc"],
        html.dark tr[style*="background: #f8fafc"] {
            background-color: #131c31 !important;
            border-color: #1e293b !important;
        }
        html.dark div[style*="background:linear-gradient(180deg,#f0fdf4 0%,#ffffff 100%)"] {
            background: linear-gradient(180deg, rgba(6, 78, 59, 0.4) 0%, #0f172a 100%) !important;
            border-color: rgba(16, 185, 129, 0.4) !important;
        }
        html.dark div[style*="background:linear-gradient(180deg,#fff1f2 0%,#ffffff 100%)"] {
            background: linear-gradient(180deg, rgba(136, 19, 55, 0.4) 0%, #0f172a 100%) !important;
            border-color: rgba(244, 63, 94, 0.4) !important;
        }
        html.dark div[style*="border:1px solid #e2e8f0"],
        html.dark div[style*="border-bottom:1px solid #e2e8f0"],
        html.dark div[style*="border-top:1px solid #e2e8f0"] {
            border-color: #1e293b !important;
        }
        html.dark tr[style*="border-bottom:1px solid #f1f5f9"] {
            border-color: #1e293b !important;
        }
        html.dark h1[style*="color:#0f172a"],
        html.dark h2[style*="color:#0f172a"],
        html.dark h3[style*="color:#0f172a"],
        html.dark span[style*="color:#0f172a"],
        html.dark p[style*="color:#0f172a"],
        html.dark td[style*="color:#0f172a"] {
            color: #f8fafc !important;
        }
        html.dark p[style*="color:#64748b"],
        html.dark span[style*="color:#64748b"],
        html.dark div[style*="color:#64748b"],
        html.dark td[style*="color:#64748b"] {
            color: #94a3b8 !important;
        }
        html.dark input[style*="color:#0f172a"] {
            color: #f8fafc !important;
        }

        /* Dropzone & Banners Dark Overrides */
        html.dark div[style*="border:2px dashed"] {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }
        html.dark div[style*="border:2px solid #10b981"] {
            background-color: rgba(6, 78, 59, 0.35) !important;
        }
        html.dark div[style*="background:#f0f9ff"],
        html.dark div[style*="background: #f0f9ff"] {
            background-color: rgba(12, 74, 110, 0.25) !important;
            border-color: rgba(56, 189, 248, 0.3) !important;
        }
        html.dark p[style*="color:#0369a1"] {
            color: #38bdf8 !important;
        }

        /* ═══ PUBLIC ENTRY PRELOADER / SPLASH SCREEN ═══ */
        #page-preloader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999999;
            background: radial-gradient(circle at 50% 40%, #0d2235 0%, #06111d 50%, #03070d 100%);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.4s ease, transform 0.4s ease;
            pointer-events: none;
        }

        #page-preloader.preloader-active {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }

        #page-preloader.preloader-hidden {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
            transform: scale(1.02);
        }

        @keyframes preloaderLogoFloat {
            0%, 100% {
                transform: translateY(0px) scale(1);
            }
            50% {
                transform: translateY(-7px) scale(1.015);
            }
        }

        @keyframes preloaderAuraPulse {
            0%, 100% {
                opacity: 0.35;
                transform: scale(0.95);
            }
            50% {
                opacity: 0.75;
                transform: scale(1.08);
            }
        }

        @keyframes preloaderProgressSweep {
            0% {
                left: -35%;
                width: 30%;
            }
            50% {
                left: 25%;
                width: 60%;
            }
            100% {
                left: 100%;
                width: 30%;
            }
        }

        @keyframes preloaderDotBounce {
            0%, 80%, 100% {
                transform: scale(0.6);
                opacity: 0.35;
            }
            40% {
                transform: scale(1.2);
                opacity: 1;
            }
        }

        .preloader-logo-anim {
            animation: preloaderLogoFloat 3s ease-in-out infinite;
        }

        .preloader-aura {
            animation: preloaderAuraPulse 3.5s ease-in-out infinite;
        }

        .preloader-bar-indicator {
            position: absolute;
            top: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, #018e9c, #38bdf8, transparent);
            border-radius: 9999px;
            animation: preloaderProgressSweep 1.6s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }

        .preloader-dot-1 { animation: preloaderDotBounce 1.4s infinite ease-in-out; animation-delay: 0s; }
        .preloader-dot-2 { animation: preloaderDotBounce 1.4s infinite ease-in-out; animation-delay: 0.2s; }
        .preloader-dot-3 { animation: preloaderDotBounce 1.4s infinite ease-in-out; animation-delay: 0.4s; }
    </style>
</head>
<body class="bg-[var(--bg-page)] text-slate-800 dark:text-slate-100 flex flex-col min-h-screen antialiased selection:bg-blue-600 selection:text-white relative overflow-x-hidden"
      x-data="publicLayoutState()">

    <!-- ══════════════════════════════════════════════════════════════
         PUBLIC PROCESSING PRELOADER / MODAL (FOR OCR & VERIFICATION)
    ══════════════════════════════════════════════════════════════ -->
    <div id="page-preloader" aria-label="Memuat Portal Publik" role="status">
        <!-- Ambient Glowing Aura in Background -->
        <div class="preloader-aura" style="position:absolute;width:480px;height:480px;border-radius:50%;background:radial-gradient(circle, rgba(1,142,156,0.35) 0%, rgba(56,189,248,0.12) 40%, transparent 70%);pointer-events:none;filter:blur(50px);"></div>

        <div style="position:relative;z-index:10;display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 24px;max-width:480px;">
            
            <!-- Logo Container -->
            <div class="preloader-logo-anim" style="position:relative;margin-bottom:26px;">
                <!-- Glass backdrop card with glowing teal border -->
                <div style="padding:14px 28px;border-radius:24px;background:rgba(15,23,42,0.55);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(1,142,156,0.25);box-shadow:0 20px 50px rgba(0,0,0,0.6), 0 0 35px rgba(1,142,156,0.3);">
                    <img src="{{ asset('images/school-logo.png') }}?v=3" 
                         alt="SMK Plus Melati Samarinda" 
                         style="height:64px;width:64px;object-fit:contain;display:block;filter:drop-shadow(0 6px 16px rgba(1,142,156,0.4));">
                </div>
            </div>

            <!-- Sleek Loading Bar -->
            <div style="width:210px;height:4px;background:rgba(255,255,255,0.08);border-radius:9999px;position:relative;overflow:hidden;margin-bottom:18px;border:1px solid rgba(255,255,255,0.05);box-shadow:inset 0 1px 2px rgba(0,0,0,0.4);">
                <div class="preloader-bar-indicator"></div>
            </div>

            <!-- Status Text & Subtitle -->
            <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                <div style="font-family:'Plus Jakarta Sans','Inter',sans-serif;font-size:11px;font-weight:800;letter-spacing:2.5px;color:#e2e8f0;text-transform:uppercase;display:flex;align-items:center;gap:6px;">
                    <span id="preloader-status-text">MEMUAT PORTAL PUBLIK</span>
                    <span style="display:inline-flex;gap:3px;align-items:center;">
                        <span class="preloader-dot-1" style="width:4px;height:4px;border-radius:50%;background:#38bdf8;display:inline-block;"></span>
                        <span class="preloader-dot-2" style="width:4px;height:4px;border-radius:50%;background:#38bdf8;display:inline-block;"></span>
                        <span class="preloader-dot-3" style="width:4px;height:4px;border-radius:50%;background:#38bdf8;display:inline-block;"></span>
                    </span>
                </div>
                <div id="preloader-subtext" style="font-size:11.5px;color:#64748b;font-weight:500;letter-spacing:0.3px;">
                    SMK Plus Melati Samarinda &bull; Arsip &amp; Verifikasi Digital
                </div>
            </div>

            <!-- Trust Badge Pill -->
            <div style="margin-top:28px;display:inline-flex;align-items:center;gap:7px;padding:5px 14px;border-radius:9999px;background:rgba(1,142,156,0.08);border:1px solid rgba(1,142,156,0.22);font-size:10px;color:#38bdf8;font-weight:600;letter-spacing:0.2px;">
                <span style="width:6px;height:6px;border-radius:50%;background:#10b981;box-shadow:0 0 8px #10b981;display:inline-block;"></span>
                <span>Hyperledger Fabric &bull; SHA-256 Ledger Verified</span>
            </div>

        </div>
    </div>

    <!-- Public Navigation Bar -->
    <nav class="sticky top-0 z-50 backdrop-blur-xl border-b transition-colors duration-200"
         style="background-color: var(--navbar-bg); border-color: var(--navbar-border); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <a href="{{ route('verify.index') }}" class="flex items-center gap-3.5 group" style="text-decoration:none">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-900 to-indigo-950 flex items-center justify-center p-1 shadow-md shadow-blue-900/30 border border-blue-500/20 group-hover:scale-105 transition-transform duration-200">
                        <img src="{{ asset('images/school-logo.png') }}?v=3" alt="SIDA Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="leading-tight">
                        <div class="font-heading font-extrabold text-[20px] tracking-tight text-slate-900 dark:text-white">
                            SIDA
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-bold tracking-wider uppercase mt-0.5">SMK PLUS MELATI SAMARINDA</div>
                    </div>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-7">
                    <a href="{{ route('verify.index') }}"
                       class="text-[13.5px] font-semibold transition-all duration-200 px-3 py-1.5 rounded-lg {{ Route::is('verify.index') || Route::is('verify.result') ? 'text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-950/50 shadow-sm shadow-blue-500/10 font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800/60' }}"
                       style="text-decoration:none">
                        Verifikasi Dokumen
                    </a>

                    <!-- Theme Switcher Button (Desktop) -->
                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 mx-1"></div>
                    <button @click="toggleTheme()"
                            type="button"
                            class="relative p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/70 bg-slate-100/90 dark:bg-slate-800/90 text-slate-700 dark:text-amber-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-200 shadow-sm hover:scale-105 active:scale-95 focus:outline-none flex items-center justify-center w-10 h-10 group"
                            :title="isDark ? 'Beralih ke Mode Terang (Light Mode)' : 'Beralih ke Mode Gelap (Dark Mode)'"
                            aria-label="Toggle Mode Gelap">
                        <template x-if="isDark">
                            <span class="flex items-center justify-center text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.5)]">
                                <i data-lucide="sun" class="w-4.5 h-4.5"></i>
                            </span>
                        </template>
                        <template x-if="!isDark">
                            <span class="flex items-center justify-center text-slate-700">
                                <i data-lucide="moon" class="w-4.5 h-4.5"></i>
                            </span>
                        </template>
                    </button>
                </div>

                <!-- Mobile Header Right Actions -->
                <div class="flex items-center gap-2.5 md:hidden">
                    <!-- Theme Toggle Mobile Button -->
                    <button @click="toggleTheme()"
                            type="button"
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-850 text-slate-700 dark:text-amber-400 focus:outline-none flex items-center justify-center w-9 h-9"
                            :title="isDark ? 'Mode Terang' : 'Mode Gelap'">
                        <template x-if="isDark">
                            <i data-lucide="sun" class="w-4 h-4 text-amber-400"></i>
                        </template>
                        <template x-if="!isDark">
                            <i data-lucide="moon" class="w-4 h-4 text-slate-700"></i>
                        </template>
                    </button>

                    <!-- Mobile Menu Hamburger Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-850 text-slate-800 dark:text-slate-200 cursor-pointer focus:outline-none">
                        <i data-lucide="menu" class="w-5 h-5" x-show="!mobileMenuOpen"></i>
                        <i data-lucide="x" class="w-5 h-5" style="display:none" x-show="mobileMenuOpen"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div style="display:none"
                 x-show="mobileMenuOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="md:hidden border-t border-slate-200 dark:border-slate-800/80 py-4 px-2 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-b-2xl shadow-xl space-y-2 mb-2">
                <a href="{{ route('verify.index') }}" 
                   class="block px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ Route::is('verify.index') || Route::is('verify.result') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 font-bold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                   Verifikasi Dokumen
                </a>

                <div class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 py-1 text-xs text-slate-500 dark:text-slate-400">
                    <span>Mode Tampilan:</span>
                    <button @click="toggleTheme()" class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-amber-400 py-1 px-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        <span x-text="isDark ? 'Mode Gelap' : 'Mode Terang'"></span>
                        <i data-lucide="sun" class="w-3.5 h-3.5 text-amber-400" x-show="isDark"></i>
                        <i data-lucide="moon" class="w-3.5 h-3.5 text-slate-600" x-show="!isDark"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow relative z-10">
        @yield('content')
    </main>

    <!-- Public Footer -->
    <footer class="relative z-10 border-t transition-colors duration-200"
            style="background-color: #070b14; color: #cbd5e1; border-color: rgba(255, 255, 255, 0.08)">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-12 grid grid-cols-1 sm:grid-cols-3 gap-10">

                <!-- Brand -->
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-900/90 flex items-center justify-center p-1 border border-blue-500/30 shadow-md shadow-blue-900/50">
                            <img src="{{ asset('images/school-logo.png') }}?v=3" alt="Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="font-heading font-extrabold text-white text-[15px]">SIDA</div>
                            <div class="text-[9.5px] text-blue-400 font-bold tracking-widest">SMK PLUS MELATI</div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-[290px]">
                        Sistem Informasi Manajemen &amp; Verifikasi Dokumen Resmi Sekolah Berbasis Ledger Kriptografis Digital.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <div class="text-[11px] font-bold text-white uppercase tracking-wider mb-3.5 font-heading">Navigasi Utama</div>
                    <ul class="space-y-2.5 list-none p-0 m-0">
                        <li><a href="{{ route('verify.index') }}" class="text-[13px] text-slate-400 hover:text-white transition-colors duration-200" style="text-decoration:none">Verifikasi Dokumen</a></li>
                    </ul>
                </div>

                <!-- Info -->
                <div>
                    <div class="text-[11px] font-bold text-white uppercase tracking-wider mb-3.5 font-heading">Alamat &amp; Keamanan</div>
                    <ul class="space-y-3 list-none p-0 m-0">
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="map-pin" class="w-4 h-4 text-blue-400 flex-shrink-0 mt-0.5"></i>
                            <span class="text-xs text-slate-400 leading-relaxed">SMK Plus Melati<br>Jl. H.A.M. M. Rifaddin, RT 33, Samarinda, Kalimantan Timur</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400 flex-shrink-0"></i>
                            <span class="text-xs text-slate-400">Hyperledger Fabric Ledger Security</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer bottom -->
            <div class="border-t border-slate-800/80 py-5 flex items-center justify-between flex-wrap gap-3">
                <p class="text-xs text-slate-500 m-0">&copy; {{ date('Y') }} SMK Plus Melati Samarinda &mdash; SIDA. All rights reserved.</p>
                <div class="flex items-center gap-2 text-xs text-emerald-400 font-semibold bg-emerald-950/40 border border-emerald-800/50 px-3 py-1 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sistem Digital Aktif &amp; Terproteksi</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- ═══ FRIENDLY MINI HELPER MASCOT (ROBOT SIDA) ═══ -->
    <div id="robot-mascot" x-data="robotWidget()" style="position:fixed;bottom:24px;right:24px;z-index:99;display:flex;flex-direction:column;align-items:flex-end;gap:8px;pointer-events:auto">
        <!-- Speech Bubble -->
        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-2xl p-3.5 max-w-[270px] text-slate-900 dark:text-slate-100 shadow-xl dark:shadow-2xl text-xs font-semibold leading-relaxed"
             x-show="showSpeech"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            
            <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-extrabold text-blue-600 dark:text-blue-400 uppercase font-heading">🤖 Maskot SIDA</span>
                <button @click="showSpeech = false" class="bg-transparent border-0 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
            </div>
            
            <p class="m-0 text-slate-600 dark:text-slate-300 text-xs font-normal" x-text="message"></p>

            <!-- Pointer Triangle -->
            <div class="absolute -bottom-2 right-7 w-0 h-0 border-l-[7px] border-l-transparent border-r-[7px] border-r-transparent border-t-[8px] border-t-white dark:border-t-slate-900"></div>
            <div class="absolute -bottom-[9px] right-[27px] w-0 h-0 border-l-[8px] border-l-transparent border-r-[8px] border-r-transparent border-t-[9px] border-t-slate-200 dark:border-t-slate-700 -z-10"></div>
        </div>

        <!-- Robot Graphic Button -->
        <div class="robot-container" @click="sayNextMessage()" style="cursor:pointer;position:relative" title="Klik Maskot SIDA">
            <svg width="64" height="68" viewBox="0 0 100 110" fill="none" xmlns="http://www.w3.org/2000/svg" class="drop-shadow-lg">
                <!-- Antenna -->
                <line x1="50" y1="20" x2="50" y2="9" stroke="#2563eb" stroke-width="4" stroke-linecap="round"/>
                <circle cx="50" cy="7" r="5" fill="#38bdf8"/>

                <!-- Head -->
                <rect x="25" y="20" width="50" height="36" rx="12" fill="#ffffff" stroke="#1e3a8a" stroke-width="3"/>
                <!-- Face screen -->
                <rect x="30" y="26" width="40" height="24" rx="8" fill="#0f172a"/>

                <!-- Eyes -->
                <circle cx="41" cy="38" r="4" fill="#38bdf8"/>
                <circle cx="59" cy="38" r="4" fill="#38bdf8"/>

                <!-- Smile -->
                <path d="M44 44 Q50 47 56 44" stroke="#ffffff" stroke-width="2" stroke-linecap="round" fill="none"/>

                <!-- Ears -->
                <rect x="19" y="32" width="6" height="12" rx="3" fill="#2563eb"/>
                <rect x="75" y="32" width="6" height="12" rx="3" fill="#2563eb"/>

                <!-- Neck -->
                <rect x="44" y="56" width="12" height="6" fill="#cbd5e1" rx="2"/>

                <!-- Body -->
                <rect x="22" y="62" width="56" height="42" rx="14" fill="#ffffff" stroke="#1e3a8a" stroke-width="3"/>
                <!-- Chest badge -->
                <rect x="34" y="72" width="32" height="20" rx="6" fill="#eff6ff" stroke="#bfdbfe" stroke-width="1.5"/>
                <circle cx="50" cy="82" r="4" fill="#2563eb"/>

                <!-- Left Arm -->
                <rect x="11" y="66" width="9" height="22" rx="4" fill="#2563eb"/>

                <!-- Right Arm (Waving) -->
                <g class="robot-arm-right">
                    <rect x="80" y="66" width="9" height="22" rx="4" fill="#2563eb"/>
                </g>
            </svg>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });

        function publicLayoutState() {
            return {
                mobileMenuOpen: false,
                isDark: document.documentElement.classList.contains('dark'),

                init() {
                    // Watch changes if user has system theme changes
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                        if (!localStorage.getItem('sida_theme') && !localStorage.getItem('vela_theme')) {
                            this.isDark = e.matches;
                            if (e.matches) {
                                document.documentElement.classList.add('dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                            }
                            this.refreshIcons();
                        }
                    });
                },

                toggleTheme() {
                    this.isDark = !this.isDark;
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('sida_theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('sida_theme', 'light');
                    }
                    this.refreshIcons();
                },

                refreshIcons() {
                    this.$nextTick(() => {
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    });
                }
            }
        }

        function robotWidget() {
            return {
                showSpeech: true,
                messageIndex: 0,
                messages: [
                    "Selamat Datang di SIDA SMK Plus Melati! 👋",
                    "Sistem arsip & verifikasi dokumen sekolah resmi 📄",
                    "Klik menu 'Verifikasi Dokumen' untuk memeriksa keaslian berkas secara instan!",
                    "Ada yang bisa kami bantu hari ini? 😊"
                ],
                message: "Selamat Datang di SIDA SMK Plus Melati! 👋",

                sayNextMessage() {
                    this.showSpeech = true;
                    this.messageIndex = (this.messageIndex + 1) % this.messages.length;
                    this.message = this.messages[this.messageIndex];
                }
            }
        }
    </script>

    <!-- Preloader Global Handler Script -->
    <script>
        (function() {
            var preloader = document.getElementById('page-preloader');
            if (!preloader) return;

            // Global trigger to show preloader for heavy user actions (upload OCR & QR verify)
            window.showSidaPreloader = function(statusText, subText) {
                if (!preloader) return;
                if (statusText) {
                    var el = document.getElementById('preloader-status-text');
                    if (el) el.textContent = statusText;
                }
                if (subText) {
                    var subEl = document.getElementById('preloader-subtext');
                    if (subEl) subEl.textContent = subText;
                }
                preloader.style.display = 'flex';
                preloader.classList.remove('preloader-hidden');
                preloader.classList.add('preloader-active');
            };

            window.hideSidaPreloader = function() {
                if (!preloader) return;
                preloader.classList.remove('preloader-active');
                preloader.classList.add('preloader-hidden');
                setTimeout(function() {
                    preloader.style.display = 'none';
                }, 400);
            };

            window.showPreloader = window.showSidaPreloader;
            window.hidePreloader = window.hideSidaPreloader;

            // Handle browser bfcache navigation (Back / Forward)
            window.addEventListener('pageshow', function(e) {
                if (e.persisted) {
                    preloader.style.display = 'none';
                    preloader.classList.remove('preloader-active');
                }
            });
        })();
    </script>
</body>
</html>
