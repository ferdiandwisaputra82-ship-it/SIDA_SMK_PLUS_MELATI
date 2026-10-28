<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Admin Panel - SIDA</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v=3">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v=3">
    <link rel="apple-touch-icon" href="{{ asset('images/school-logo.png') }}?v=3">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
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
        /* Floating Logo Background Animation */
        @keyframes bgLogoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
            50% { transform: translateY(-16px) rotate(2deg) scale(1.02); }
        }
        .animate-bg-logo {
            animation: bgLogoFloat 8s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 via-blue-50/50 to-slate-100 text-slate-800 flex items-center justify-center font-sans antialiased selection:bg-blue-600 selection:text-white p-4 sm:p-6 relative overflow-hidden">
    
    {{-- Ambient Watermarks & Glowing Accents --}}
    <div class="animate-bg-logo fixed -top-10 -right-10 w-[460px] pointer-events-none z-0 opacity-[0.06]">
        <img src="{{ asset('images/school-logo.png') }}" alt="School Logo Watermark" class="w-full h-auto object-contain">
    </div>
    <div class="animate-bg-logo fixed -bottom-10 -left-10 w-[380px] pointer-events-none z-0 opacity-[0.04]" style="animation-delay:-4s">
        <img src="{{ asset('images/school-logo.png') }}" alt="School Logo Watermark" class="w-full h-auto object-contain">
    </div>

    {{-- Subtle Ambient Glows --}}
    <div class="fixed top-1/4 left-1/3 w-[500px] h-[500px] bg-blue-400/10 rounded-full blur-[140px] pointer-events-none z-0"></div>
    <div class="fixed bottom-1/4 right-1/3 w-[450px] h-[450px] bg-cyan-400/10 rounded-full blur-[140px] pointer-events-none z-0"></div>

    {{-- Main Container Card --}}
    <div class="relative z-10 w-full max-w-4xl bg-white border border-slate-200/90 rounded-3xl shadow-[0_20px_50px_rgba(15,23,42,0.08),0_2px_8px_rgba(15,23,42,0.04)] overflow-hidden grid grid-cols-1 lg:grid-cols-12"
         x-data="{ showPassword: false, submitting: false }">
        
        <!-- Left Brand Banner (Deep Navy Institutional Authority) -->
        <div class="lg:col-span-5 p-8 lg:p-10 bg-gradient-to-b from-[#0f2347] to-[#071328] text-white flex flex-col justify-between relative overflow-hidden border-b lg:border-b-0 lg:border-r border-blue-900/50">
            
            {{-- Top Accent Glow inside banner --}}
            <div class="absolute -top-20 -left-20 w-44 h-44 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
            
            {{-- Floating School Logo Watermark in banner --}}
            <div class="absolute -bottom-8 -right-8 w-60 h-60 pointer-events-none z-0 select-none opacity-[0.09] transform rotate-6">
                <img src="{{ asset('images/school-logo.png') }}" alt="Shield Watermark" class="w-full h-auto object-contain">
            </div>

            <div class="relative z-10 flex flex-col h-full justify-between">
                <!-- School & App Identity -->
                <a href="{{ route('verify.index') }}" class="inline-flex items-center gap-3.5 group mb-6" style="text-decoration:none">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 p-1.5 border border-white/15 backdrop-blur-md shadow-[0_4px_16px_rgba(0,0,0,0.3)] group-hover:scale-105 group-hover:border-cyan-400/50 transition-all duration-300">
                        <img src="{{ asset('images/school-logo.png') }}?v=3" alt="SIDA Logo" class="w-9 h-9 object-contain">
                    </div>
                    <div class="leading-tight">
                        <div class="text-white font-extrabold text-xl font-heading tracking-tight">
                            SIDA
                        </div>
                        <div class="text-[10px] text-blue-200/90 font-mono tracking-widest uppercase font-bold mt-0.5">SMK PLUS MELATI</div>
                    </div>
                </a>

                <!-- Centered Big School Logo & Brand Showcase -->
                <div class="my-auto py-6 flex flex-col items-center justify-center text-center">
                    <div class="relative mb-5 group">
                        <div class="absolute -inset-4 bg-gradient-to-r from-blue-500/30 to-cyan-400/30 rounded-3xl blur-2xl opacity-75 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative w-44 h-44 sm:w-52 sm:h-52 rounded-3xl bg-white/10 p-5 border border-white/20 backdrop-blur-md shadow-[0_8px_32px_rgba(0,0,0,0.4)] flex items-center justify-center">
                            <img src="{{ asset('images/school-logo.png') }}?v=3" alt="Logo SMK Plus Melati" class="w-full h-full object-contain filter drop-shadow-xl group-hover:scale-105 transition duration-300">
                        </div>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white font-heading tracking-tight mb-1">
                        SMK PLUS MELATI
                    </h2>
                    <p class="text-xs text-blue-200/80 font-medium max-w-xs leading-relaxed">
                        Sistem Manajemen Dokumen &amp; Arsip Digital Terverifikasi
                    </p>
                </div>

                <!-- Security Footer Badge -->
                <div class="pt-5 border-t border-blue-900/60 flex items-center justify-between text-[11px] text-blue-300/80">
                    <span class="flex items-center gap-1.5 font-mono">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>Tingkat Keamanan: <strong>Tinggi</strong></span>
                    </span>
                    <span class="px-2 py-0.5 rounded bg-blue-950/80 border border-blue-800/80 text-[10px] font-mono text-cyan-300">
                        256-Bit SSL
                    </span>
                </div>
            </div>
        </div>

        <!-- Right Form Area (Crisp, Clean, Highly Legible Light Aesthetics) -->
        <div class="lg:col-span-7 p-8 lg:p-12 flex flex-col justify-center bg-white">
            
            <div class="mb-7">
                <div class="flex items-center justify-between mb-2">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-heading tracking-tight">
                        Masuk ke Admin Panel
                    </h1>
                </div>
                <p class="text-slate-500 text-sm">
                    Silakan masukkan email institusi dan kata sandi akun resmi Anda.
                </p>
            </div>
            
            <form action="{{ route('admin.login.post') }}" method="POST" id="loginForm" class="space-y-4" @submit="submitting = true">
                @csrf
                
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 font-heading">
                        Email Institusi
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </div>
                        <input type="email"
                               name="email"
                               id="email"
                               required
                               autocomplete="email"
                               placeholder="nama@smkplusmelati.sch.id"
                               value="{{ old('email') }}"
                               class="w-full pl-10 pr-4 py-3.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 outline-none focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 transition-all font-sans">
                    </div>
                </div>
                
                <!-- Password Field -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider font-heading">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input :type="showPassword ? 'text' : 'password'"
                               name="password"
                               id="password"
                               required
                               autocomplete="current-password"
                               placeholder="Masukkan kata sandi..."
                               class="w-full pl-10 pr-11 py-3.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 outline-none focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 transition-all font-sans">
                        
                        <!-- Toggle Password Visibility -->
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                                title="Lihat/Sembunyikan Kata Sandi">
                            <i data-lucide="eye" class="w-4 h-4" x-show="!showPassword"></i>
                            <i data-lucide="eye-off" class="w-4 h-4" x-show="showPassword" style="display:none"></i>
                        </button>
                    </div>
                </div>

                <!-- Error Notification -->
                @if($errors->has('login'))
                    <div class="flex items-start gap-3 bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 text-rose-700">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5"></i>
                        <span class="text-xs font-semibold">{{ $errors->first('login') }}</span>
                    </div>
                @endif

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                            :disabled="submitting"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-3.5 px-6 rounded-xl text-sm font-bold shadow-lg shadow-blue-600/25 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed">
                        <template x-if="!submitting">
                            <span class="flex items-center gap-2 font-heading">
                                <i data-lucide="log-in" class="w-4 h-4"></i> Masuk ke Panel Admin
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center gap-2 font-heading">
                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Memverifikasi Kredensial...
                            </span>
                        </template>
                    </button>
                </div>
            </form>
            
            <!-- Return to Home Link -->
            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <a href="{{ route('verify.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-blue-600 font-semibold transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke Halaman Utama
                </a>
            </div>
        </div>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
