@extends('layouts.public')

@section('title', 'Beranda')

@section('content')

{{-- ═══════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════ --}}
<section class="relative pt-12 pb-20 border-b border-slate-200 dark:border-slate-800/80 overflow-hidden transition-colors duration-200">

    {{-- Real Campus Building Background Image --}}
    <div class="absolute inset-0 bg-cover bg-center z-0 filter brightness-95 dark:brightness-[0.45] contrast-[1.05] dark:contrast-[1.15] transition-all duration-500"
         style="background-image:url('{{ asset('images/gedung-smk.jpg') }}'); background-position: center 38%;"></div>

    {{-- Ambient Lighting Overlay (Adaptive for Light & Dark) --}}
    <div class="absolute inset-0 bg-gradient-to-b from-blue-100/40 via-slate-900/20 to-transparent dark:from-slate-950/80 dark:via-slate-950/85 dark:to-slate-950/95 z-[1] transition-all duration-500"></div>
    <div class="absolute bottom-0 left-0 right-0 h-16 pointer-events-none z-[2]"
         style="background: linear-gradient(180deg, transparent 0%, var(--bg-page) 100%);"></div>

    {{-- Campus Building Badge Indicator (Desktop) --}}
    <div class="hidden md:inline-flex absolute top-6 right-8 z-10 items-center gap-2 px-4 py-1.5 rounded-full bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-white/80 dark:border-slate-700/80 text-xs text-slate-800 dark:text-slate-200 font-bold shadow-lg shadow-black/5 dark:shadow-black/40 transition-colors duration-200">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <i data-lucide="building-2" class="w-3.5 h-3.5 text-blue-600 dark:text-cyan-400"></i>
        <span>Kampus SMK Plus Melati Samarinda</span>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">

            {{-- ── Left: Frosted Glass Text Card ── --}}
            <div class="md:col-span-7 space-y-6 bg-white/92 dark:bg-slate-900/85 backdrop-blur-xl border border-white/90 dark:border-slate-700/50 rounded-3xl p-7 sm:p-9 shadow-2xl shadow-slate-200/50 dark:shadow-black/70 relative overflow-hidden transition-all duration-200">
                
                {{-- Ambient Light Orb inside Card --}}
                <div class="absolute -top-16 -left-16 w-52 h-52 bg-blue-500/15 dark:bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-16 -right-16 w-52 h-52 bg-cyan-500/10 dark:bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>

                {{-- Mobile building photo card preview --}}
                <div class="block md:hidden rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm mb-3">
                    <img src="{{ asset('images/gedung-smk.jpg') }}" alt="Gedung Kampus SMK Plus Melati Samarinda" class="w-full h-48 object-cover object-center">
                    <div class="bg-white/95 dark:bg-slate-850 px-3.5 py-2.5 flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <span class="flex items-center gap-1.5 text-blue-600 dark:text-cyan-400">
                            <i data-lucide="building-2" class="w-3.5 h-3.5"></i> Gedung SMK Plus Melati
                        </span>
                        <span class="text-slate-500 dark:text-slate-400 text-[11px] font-mono">Samarinda</span>
                    </div>
                </div>

                {{-- Institutional Badge --}}
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 dark:bg-blue-950/70 border border-blue-200/80 dark:border-blue-800/80 text-xs font-bold text-blue-700 dark:text-blue-300 shadow-sm shadow-blue-500/5">
                    <span class="w-2 h-2 rounded-full bg-blue-600 dark:bg-cyan-400 animate-pulse flex-shrink-0"></span>
                    Sistem Manajemen &amp; Verifikasi Dokumen Sekolah
                </div>

                {{-- Headline --}}
                <h1 class="text-3xl sm:text-4xl md:text-[42px] font-extrabold text-slate-900 dark:text-white font-heading leading-[1.18] tracking-tight m-0">
                    Arsip Dokumen Digital <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 dark:from-blue-400 dark:via-indigo-300 dark:to-cyan-400">
                        SMK Plus Melati Samarinda
                    </span>
                </h1>

                {{-- Subtitle --}}
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed m-0 font-normal">
                    Platform resmi pengarsipan dan verifikasi keaslian ijazah, rapor, serta sertifikat sekolah secara mandiri, transparan, dan terintegrasi ledger kriptografis digital.
                </p>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-3.5 flex-wrap pt-1">
                    <a href="{{ route('verify.index') }}"
                       class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 via-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-blue-600/30 hover:shadow-blue-500/50 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 font-heading"
                       style="text-decoration:none">
                        <i data-lucide="shield-check" class="w-4.5 h-4.5"></i>
                        Verifikasi Dokumen Sekarang
                    </a>
                    <a href="#cara-kerja"
                       class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-white/90 dark:bg-slate-800/90 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-semibold text-sm border border-slate-300 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 font-heading"
                       style="text-decoration:none">
                        Panduan Alur
                        <i data-lucide="arrow-down" class="w-4 h-4"></i>
                    </a>
                </div>

                {{-- Quick Stats Row --}}
                <div class="pt-5 border-t border-slate-200/90 dark:border-slate-800/90 grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-heading">8+ Tipe</div>
                        <div class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Dokumen Didukung</div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-heading">&lt; 3 Detik</div>
                        <div class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Proses Cek Instan</div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-heading">Terbuka</div>
                        <div class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Akses Publik 24/7</div>
                    </div>
                </div>
            </div>

            {{-- ── Right: Card Preview (Digital Certificate & Cryptographic Ledger Node) ── --}}
            <div class="md:col-span-5">
                <div class="bg-white/92 dark:bg-slate-900/85 backdrop-blur-xl border border-white/90 dark:border-slate-700/50 rounded-3xl p-6 shadow-2xl shadow-slate-200/50 dark:shadow-black/70 relative overflow-hidden transition-all duration-200">
                    
                    {{-- Ambient Glow inside Preview Card --}}
                    <div class="absolute -top-12 -right-12 w-40 h-40 bg-indigo-500/15 dark:bg-cyan-500/15 rounded-full blur-2xl pointer-events-none"></div>

                    {{-- Header --}}
                    <div class="flex items-center gap-3.5 pb-4 mb-4 border-b border-slate-200 dark:border-slate-800/80">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-900 to-indigo-950 flex items-center justify-center p-1.5 shadow-md shadow-blue-900/30 border border-blue-500/20 flex-shrink-0">
                            <img src="{{ asset('images/school-logo.png') }}?v=3" alt="Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="text-sm font-extrabold text-slate-900 dark:text-white font-heading">SMK Plus Melati</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Layanan Verifikasi Ijazah &amp; Rapor</div>
                        </div>
                        <div class="ml-auto flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 rounded-full">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-[10px] text-emerald-700 dark:text-emerald-400 font-bold">Node Aktif</span>
                        </div>
                    </div>

                    {{-- Live Cryptographic Ledger Banner --}}
                    <div class="bg-slate-100/90 dark:bg-slate-950/70 border border-slate-200/80 dark:border-slate-800/80 rounded-xl px-3.5 py-2.5 mb-3 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <i data-lucide="cpu" class="w-4 h-4 text-blue-600 dark:text-cyan-400"></i>
                            <span class="font-mono text-[11px] text-slate-700 dark:text-slate-300 font-medium">SHA-256 Ledger Consensus</span>
                        </div>
                        <span class="text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/50 px-2 py-0.5 rounded-full">
                            VERIFIED
                        </span>
                    </div>

                    {{-- List of supported documents --}}
                    <div class="space-y-2.5">
                        @php
                        $docCards = [
                            ['icon' => 'graduation-cap', 'name' => 'Ijazah Kelulusan', 'desc' => 'Terverifikasi Ledger Resmi', 'color' => 'blue'],
                            ['icon' => 'bar-chart-3',    'name' => 'Rapor Akademik Siswa', 'desc' => 'Terverifikasi Ledger Resmi', 'color' => 'indigo'],
                            ['icon' => 'clipboard-list', 'name' => 'Transkrip Nilai', 'desc' => 'Terverifikasi Ledger Resmi', 'color' => 'cyan'],
                            ['icon' => 'file-text',      'name' => 'Surat Keterangan Lulus', 'desc' => 'Terverifikasi Ledger Resmi', 'color' => 'emerald'],
                        ];
                        @endphp
                        @foreach($docCards as $card)
                        <div class="flex items-center gap-3 p-2.5 sm:p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/60 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-700/60 hover:border-blue-300 dark:hover:border-blue-500/50 transition-all duration-200 group">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/70 border border-blue-200/80 dark:border-blue-800/70 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                                <i data-lucide="{{ $card['icon'] }}" class="w-4 h-4 text-blue-600 dark:text-cyan-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs sm:text-[12.5px] font-bold text-slate-800 dark:text-slate-100 font-heading truncate">{{ $card['name'] }}</div>
                                <div class="text-[10.5px] text-slate-500 dark:text-slate-400">{{ $card['desc'] }}</div>
                            </div>
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 flex-shrink-0"></i>
                        </div>
                        @endforeach
                    </div>

                    {{-- Security Footer Indicator --}}
                    <div class="mt-4 pt-3.5 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="shield" class="w-3.5 h-3.5 text-blue-600 dark:text-cyan-400"></i>
                            Integritas Kriptografis
                        </span>
                        <span class="text-blue-600 dark:text-cyan-400 font-bold font-mono text-[11px]">100% IMMUTABLE</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     STATS BANNER
═══════════════════════════════════════════ --}}
<section class="bg-white dark:bg-slate-900/90 border-b border-slate-200 dark:border-slate-800/80 py-8 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-8">
            @php
            $metrics = [
                ['num' => '8+', 'lbl' => 'Tipe Dokumen Resmi', 'icon' => 'files'],
                ['num' => '100%', 'lbl' => 'Keabsahan Terjamin', 'icon' => 'shield-check'],
                ['num' => '< 3 Detik', 'lbl' => 'Waktu Pemrosesan', 'icon' => 'zap'],
                ['num' => 'Samarinda', 'lbl' => 'Kalimantan Timur', 'icon' => 'map-pin'],
            ];
            @endphp
            @foreach($metrics as $m)
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200/80 dark:border-blue-800/60 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <i data-lucide="{{ $m['icon'] }}" class="w-5 h-5 text-blue-600 dark:text-cyan-400"></i>
                </div>
                <div>
                    <div class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white font-heading leading-tight">
                        {{ $m['num'] }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">{{ $m['lbl'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     DOCUMENT KATALOG
═══════════════════════════════════════════ --}}
<section class="py-16 sm:py-20 bg-slate-50/80 dark:bg-[#070b14] transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="max-w-xl mb-10">
            <div class="text-xs font-bold text-blue-600 dark:text-cyan-400 uppercase tracking-widest font-heading mb-2 flex items-center gap-1.5">
                <i data-lucide="layers" class="w-4 h-4"></i>
                Katalog Dokumen
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white font-heading tracking-tight mb-2">
                Jenis Dokumen Resmi yang Dikelola
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed m-0">
                Setiap dokumen yang diterbitkan oleh sekolah tersimpan dalam basis data terverifikasi dan siap divalidasi keabsahannya.
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
            @foreach(\App\Models\Document::TYPE_META as $type => $meta)
            <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 hover:border-blue-400 dark:hover:border-blue-500/60 rounded-2xl p-5 shadow-sm hover:shadow-xl hover:shadow-blue-500/10 hover:-translate-y-1.5 transition-all duration-300 group relative overflow-hidden">
                {{-- Card Top Accent Line --}}
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-600 to-cyan-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl mb-3 group-hover:scale-110 transition-transform duration-300 shadow-inner">
                    {{ $meta['emoji'] }}
                </div>
                <div class="text-[13.5px] font-bold text-slate-900 dark:text-slate-100 font-heading mb-1.5 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ $type }}
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400"></i>
                    <span>Terdaftar Resmi</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════════ --}}
<section id="cara-kerja" class="py-16 sm:py-20 bg-white dark:bg-slate-900/50 border-y border-slate-200 dark:border-slate-800/80 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="text-center max-w-lg mx-auto mb-12">
            <div class="text-xs font-bold text-blue-600 dark:text-cyan-400 uppercase tracking-widest font-heading mb-2 inline-flex items-center gap-1.5">
                <i data-lucide="git-commit" class="w-4 h-4"></i>
                Alur Verifikasi
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white font-heading tracking-tight mb-2">
                Tahapan Pemeriksaan Dokumen
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 m-0">Proses pemeriksaan yang cepat, aman, dan transparan bagi publik maupun instansi.</p>
        </div>

        @php
        $steps = [
            [
                'num'   => '1',
                'title' => 'Pilih & Upload File',
                'desc'  => 'Unggah berkas ijazah, rapor, atau sertifikat dalam format PDF, JPG, atau PNG.',
                'icon'  => 'upload-cloud'
            ],
            [
                'num'   => '2',
                'title' => 'Pemeriksaan Hash Digital',
                'desc'  => 'Sistem secara otomatis menghitung nilai hash SHA-256 kriptografis dari berkas yang diunggah.',
                'icon'  => 'fingerprint'
            ],
            [
                'num'   => '3',
                'title' => 'Pencocokan Ledger',
                'desc'  => 'Nilai hash dicocokkan langsung dengan basis data pendaftaran resmi milik sekolah.',
                'icon'  => 'database'
            ],
            [
                'num'   => '4',
                'title' => 'Hasil Keabsahan Instant',
                'desc'  => 'Status keabsahan dan rincian metadata dokumen langsung ditampilkan secara transparan.',
                'icon'  => 'badge-check'
            ],
        ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($steps as $step)
            <div class="bg-slate-50/90 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800/80 hover:border-blue-400 dark:hover:border-blue-500/60 rounded-2xl p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-600 text-white font-extrabold text-sm flex items-center justify-center font-heading shadow-md shadow-blue-600/30 group-hover:scale-105 transition-transform duration-200">
                        {{ $step['num'] }}
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 flex items-center justify-center text-blue-600 dark:text-cyan-400">
                        <i data-lucide="{{ $step['icon'] }}" class="w-4 h-4"></i>
                    </div>
                </div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white font-heading mb-2">{{ $step['title'] }}</h3>
                <p class="text-xs sm:text-[13px] text-slate-600 dark:text-slate-400 leading-relaxed m-0">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     CTA BANNER
═══════════════════════════════════════════ --}}
<section class="py-14 sm:py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-br from-blue-900 via-indigo-950 to-slate-950 border border-blue-500/30 rounded-3xl p-9 sm:p-12 text-center shadow-2xl shadow-blue-950/50 relative overflow-hidden text-white">
            
            {{-- Decorative Ambient Glow Orbs --}}
            <div class="absolute -top-20 -left-20 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-cyan-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-xs font-semibold text-blue-200 mb-4 backdrop-blur-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping"></span>
                Layanan Mandiri Publik 24/7
            </div>

            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold font-heading tracking-tight mb-3 text-white">
                Ingin Memeriksa Keaslian Dokumen Sekolah?
            </h2>

            <p class="text-xs sm:text-sm text-blue-200/90 max-w-lg mx-auto mb-7 leading-relaxed font-normal">
                Gunakan layanan verifikasi mandiri online untuk memastikan dokumen yang Anda miliki terdaftar secara sah dan terlindungi di SMK Plus Melati Samarinda.
            </p>

            <div class="flex items-center justify-center gap-3.5 flex-wrap">
                <a href="{{ route('verify.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-blue-950 hover:bg-blue-50 font-bold text-sm shadow-xl shadow-white/10 hover:scale-105 active:scale-95 transition-all duration-200 font-heading"
                   style="text-decoration:none">
                    <i data-lucide="shield-check" class="w-4.5 h-4.5 text-blue-600"></i>
                    Verifikasi Dokumen Sekarang
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
