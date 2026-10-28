@extends('layouts.admin')

@section('title', 'Dashboard Utama')
@section('subtitle', 'Ringkasan aktivitas dokumen dan sinkronisasi sistem digital')

@section('content')
<div x-data="dashboardManager()" x-init="initDashboard()" class="space-y-5 pb-6">

    <!-- ═══ TOAST NOTIFICATION ═══ -->
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         style="display:none;"
         class="fixed top-20 right-4 sm:right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-xs font-semibold"
         :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'">
        <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0" x-show="toast.type === 'success'"></i>
        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0" x-show="toast.type === 'error'"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- ═══ TOP BAR: LIVE SYNC & QUICK ACTIONS ═══ -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs transition-all">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Left: Live Sync & Status Indicator -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Status Pill -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-xs font-bold shadow-2xs">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span>Sistem Digital Online</span>
                </div>

                <!-- Last Synced Pill -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-50 border border-slate-200 text-slate-600 text-xs font-medium">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Sinkron: <strong x-text="lastSynced" class="text-slate-800 font-semibold">{{ now()->timezone('Asia/Jakarta')->format('H:i') }} WITA</strong></span>
                </div>

                <!-- Auto-sync Switch -->
                <label class="hidden sm:inline-flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" x-model="autoSync" @change="toggleAutoSync()" class="sr-only">
                    <div class="w-8 h-4.5 bg-slate-200 rounded-full transition-colors relative" :class="autoSync ? '!bg-blue-600' : ''">
                        <div class="w-3.5 h-3.5 bg-white rounded-full transition-transform absolute top-0.5 left-0.5 shadow-xs" :class="autoSync ? 'translate-x-3.5' : ''"></div>
                    </div>
                    <span>Auto-Sync (30s)</span>
                </label>
            </div>

            <!-- Right: Interactive Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">

                <!-- Sync Now Button -->
                <button type="button" @click="syncData()" :disabled="isSyncing"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-all active:scale-95 disabled:opacity-60">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-500" :class="isSyncing ? 'animate-spin text-blue-600' : ''"></i>
                    <span x-text="isSyncing ? 'Menyinkronkan...' : 'Sinkronkan Data'">Sinkronkan Data</span>
                </button>

                <!-- Quick Upload Shortcut -->
                @if(\App\Config\PermissionConfig::can('upload.page'))
                <a href="{{ route('admin.upload') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm shadow-emerald-500/20 transition-all active:scale-95">
                    <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Upload Baru</span>
                </a>
                @endif
            </div>

        </div>
    </div>

    <!-- ═══ OCR REVIEW ALERT (IF QUEUE > 0) ═══ -->
    @if(count($ocrQueue) > 0)
    <div x-data="{ dismissed: false }" x-show="!dismissed"
         class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/90 rounded-2xl p-4 sm:p-4.5 shadow-xs transition-all">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center shrink-0 text-amber-700 shadow-2xs">
                    <i data-lucide="cpu" class="w-5 h-5 animate-pulse"></i>
                </div>
                <div>
                    <div class="font-heading font-extrabold text-amber-900 text-xs sm:text-sm">
                        {{ count($ocrQueue) }} Dokumen Memerlukan Review OCR AI
                    </div>
                    <div class="text-[11px] sm:text-xs text-amber-700 mt-0.5">
                        Dokumen hasil ekstraksi cerdas menunggu verifikasi admin sebelum tercatat di sistem.
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.ocr-review') }}"
                   class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg shadow-xs transition">
                    Review Sekarang →
                </a>
                <button type="button" @click="dismissed = true" class="p-1.5 text-amber-600 hover:bg-amber-100 rounded-lg transition" title="Tutup">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- ═══ TOP STATISTICS CARDS (INTERACTIVE FILTERING) ═══ -->
    <div>
        <div class="flex items-center justify-between mb-2.5">
            <h2 class="font-heading font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-blue-600"></i>
                Statistik Dokumen
            </h2>
            <div x-show="activeStatusFilter !== 'all'" style="display:none;" class="flex items-center gap-1.5 text-xs text-blue-600 font-semibold">
                <span>Filter Aktif: <strong x-text="activeStatusFilter"></strong></span>
                <button type="button" @click="filterByStatus('all')" class="underline hover:text-blue-800 text-[11px]">(Reset)</button>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            
            <!-- Card 1: Total Dokumen -->
            <div @click="filterByStatus('all')"
                 class="bg-white border rounded-2xl p-4 sm:p-5 shadow-xs cursor-pointer transition-all transform hover:-translate-y-1 relative overflow-hidden group select-none"
                 :class="activeStatusFilter === 'all' ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-md' : 'border-slate-200 hover:border-blue-300'">
                <div class="absolute top-0 left-0 right-0 h-1 bg-blue-600"></div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 font-heading">Total Dokumen</span>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-blue-50 border border-blue-200/80 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="folder-open" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-heading tracking-tight leading-none mb-1.5" x-text="formatNumber(stats.totalDocs)">
                    {{ number_format($totalDocs) }}
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 font-medium pt-1 border-t border-slate-100">
                    <span>Semua jenis berkas</span>
                    <span class="text-blue-600 font-bold group-hover:underline text-[10px]">Tampilkan Semua</span>
                </div>
            </div>

            <!-- Card 2: Terverifikasi -->
            <div @click="filterByStatus('Terverifikasi')"
                 class="bg-white border rounded-2xl p-4 sm:p-5 shadow-xs cursor-pointer transition-all transform hover:-translate-y-1 relative overflow-hidden group select-none"
                 :class="activeStatusFilter === 'Terverifikasi' ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md' : 'border-slate-200 hover:border-emerald-300'">
                <div class="absolute top-0 left-0 right-0 h-1 bg-emerald-600"></div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 font-heading">Terverifikasi</span>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-700 font-heading tracking-tight leading-none mb-1.5" x-text="formatNumber(stats.verified)">
                    {{ number_format($verified) }}
                </div>
                <div class="flex items-center justify-between text-[11px] text-emerald-600 font-medium pt-1 border-t border-slate-100">
                    <span x-text="getVerifiedPercentage() + '% sah'">{{ $totalDocs > 0 ? round(($verified / $totalDocs) * 100) : 0 }}% sah</span>
                    <span class="font-bold group-hover:underline text-[10px]">Filter →</span>
                </div>
            </div>

            <!-- Card 3: Menunggu Verifikasi -->
            <div @click="filterByStatus('Menunggu Verifikasi')"
                 class="bg-white border rounded-2xl p-4 sm:p-5 shadow-xs cursor-pointer transition-all transform hover:-translate-y-1 relative overflow-hidden group select-none"
                 :class="activeStatusFilter === 'Menunggu Verifikasi' ? 'border-amber-500 ring-2 ring-amber-500/20 shadow-md' : 'border-slate-200 hover:border-amber-300'">
                <div class="absolute top-0 left-0 right-0 h-1 bg-amber-500"></div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 font-heading">Menunggu Review</span>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-amber-700 font-heading tracking-tight leading-none mb-1.5" x-text="formatNumber(stats.pending)">
                    {{ number_format($pending) }}
                </div>
                <div class="flex items-center justify-between text-[11px] text-amber-600 font-medium pt-1 border-t border-slate-100">
                    <span>Perlu peninjauan</span>
                    <span class="font-bold group-hover:underline text-[10px]">Filter →</span>
                </div>
            </div>

            <!-- Card 4: Ditolak / Bermasalah -->
            <div @click="filterByStatus('Ditolak')"
                 class="bg-white border rounded-2xl p-4 sm:p-5 shadow-xs cursor-pointer transition-all transform hover:-translate-y-1 relative overflow-hidden group select-none"
                 :class="activeStatusFilter === 'Ditolak' ? 'border-rose-500 ring-2 ring-rose-500/20 shadow-md' : 'border-slate-200 hover:border-rose-300'">
                <div class="absolute top-0 left-0 right-0 h-1 bg-rose-600"></div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] sm:text-xs font-bold text-slate-500 font-heading">Ditolak</span>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-rose-50 border border-rose-200/80 flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-rose-700 font-heading tracking-tight leading-none mb-1.5" x-text="formatNumber(stats.rejected)">
                    {{ number_format($rejected) }}
                </div>
                <div class="flex items-center justify-between text-[11px] text-rose-600 font-medium pt-1 border-t border-slate-100">
                    <span>Dokumen ditolak</span>
                    <span class="font-bold group-hover:underline text-[10px]">Filter →</span>
                </div>
            </div>

        </div>
    </div>


    <!-- ═══ CHARTS SECTION (RESPONSIVE) ═══ -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-5">

        <!-- Weekly Trends Line Chart -->
        <div class="xl:col-span-2 bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-heading font-extrabold text-slate-900 text-sm sm:text-base flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-4 h-4 text-blue-600"></i>
                        Tren Upload &amp; Verifikasi Mingguan
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Perbandingan dokumen masuk dan dokumen yang disetujui 7 hari terakhir</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg">
                        7 Hari Terakhir
                    </span>
                    <button type="button" @click="refreshChart()" title="Perbarui Grafik" class="p-1 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-slate-100 transition">
                        <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
            <!-- Responsive Canvas Height: 180px on HP, 230px on desktop -->
            <div class="h-48 sm:h-56 relative w-full">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

        <!-- Right Side: Type Breakdown & Status Progress -->
        <div class="flex flex-col gap-4">
            
            <!-- Type Distribution Chart -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-4.5 shadow-xs flex-1">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-heading font-extrabold text-slate-900 text-xs sm:text-sm flex items-center gap-1.5">
                        <i data-lucide="pie-chart" class="w-3.5 h-3.5 text-blue-600"></i>
                        Dokumen per Tipe
                    </h3>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Distribusi Berkas</span>
                </div>
                <div class="h-28 sm:h-32 relative w-full">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            <!-- Status Progress Bars (Clickable to Filter) -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-4.5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-heading font-extrabold text-slate-900 text-xs sm:text-sm flex items-center gap-1.5">
                        <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 text-blue-600"></i>
                        Status Berkas
                    </h3>
                    <span class="text-[10px] text-slate-400">Klik baris untuk filter</span>
                </div>
                
                <div class="space-y-2.5">
                    <!-- Terverifikasi -->
                    <div @click="filterByStatus('Terverifikasi')" class="cursor-pointer group p-1 -m-1 rounded-lg hover:bg-slate-50 transition">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Terverifikasi
                            </span>
                            <span class="font-extrabold text-slate-900" x-text="formatNumber(stats.verified)">{{ number_format($verified) }}</span>
                        </div>
                        <div class="h-2 bg-emerald-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-600 rounded-full transition-all duration-500"
                                 :style="'width: ' + getVerifiedPercentage() + '%'"></div>
                        </div>
                    </div>

                    <!-- Menunggu -->
                    <div @click="filterByStatus('Menunggu Verifikasi')" class="cursor-pointer group p-1 -m-1 rounded-lg hover:bg-slate-50 transition">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Menunggu
                            </span>
                            <span class="font-extrabold text-slate-900" x-text="formatNumber(stats.pending)">{{ number_format($pending) }}</span>
                        </div>
                        <div class="h-2 bg-amber-100 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500 rounded-full transition-all duration-500"
                                 :style="'width: ' + getPendingPercentage() + '%'"></div>
                        </div>
                    </div>

                    <!-- Ditolak -->
                    <div @click="filterByStatus('Ditolak')" class="cursor-pointer group p-1 -m-1 rounded-lg hover:bg-slate-50 transition">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Ditolak
                            </span>
                            <span class="font-extrabold text-slate-900" x-text="formatNumber(stats.rejected)">{{ number_format($rejected) }}</span>
                        </div>
                        <div class="h-2 bg-rose-100 rounded-full overflow-hidden">
                            <div class="h-full bg-rose-600 rounded-full transition-all duration-500"
                                 :style="'width: ' + getRejectedPercentage() + '%'"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ═══ INTERACTIVE SECTION: RECENT DOCUMENTS & ACTIVITY LOGS (TABS) ═══ -->
    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
        
        <!-- Tabs & Search Header -->
        <div class="p-3.5 sm:p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
            
            <!-- Left Tabs -->
            <div class="flex items-center gap-2">
                <button type="button" @click="activeTab = 'docs'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-2"
                        :class="activeTab === 'docs' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                    <span>Dokumen Terbaru</span>
                    <span class="px-1.5 py-0.2 rounded-md text-[10px]" :class="activeTab === 'docs' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'" x-text="filteredDocs.length"></span>
                </button>

                <button type="button" @click="activeTab = 'activity'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-2"
                        :class="activeTab === 'activity' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'">
                    <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                    <span>Aktivitas Sistem</span>
                    <span class="px-1.5 py-0.2 rounded-md text-[10px]" :class="activeTab === 'activity' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">{{ count($recentActivity) }}</span>
                </button>
            </div>

            <!-- Right Controls: Live Search & Filter Bar (Only for Docs Tab) -->
            <div x-show="activeTab === 'docs'" class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <!-- Search Input -->
                <div class="relative w-full sm:w-56">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari nama, nomor..."
                           class="w-full pl-8 pr-7 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-medium">
                    <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>

                <!-- Status Filter Dropdown / Pill in Header -->
                <select x-model="activeStatusFilter" class="text-xs bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="all">Semua Status</option>
                    <option value="Terverifikasi">Terverifikasi</option>
                    <option value="Menunggu Verifikasi">Menunggu Review</option>
                    <option value="Ditolak">Ditolak</option>
                </select>

                <!-- Link to all docs -->
                <a href="{{ route('admin.documents') }}" class="hidden sm:inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 whitespace-nowrap pl-1">
                    Semua →
                </a>
            </div>

        </div>

        <!-- ═══ TAB CONTENT 1: RECENT DOCUMENTS ═══ -->
        <div x-show="activeTab === 'docs'">
            
            <!-- DESKTOP / TABLET VIEW (TABLE) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[10.5px] uppercase font-bold text-slate-500 tracking-wider font-heading">
                            <th class="py-3 px-4">Nomor Dokumen</th>
                            <th class="py-3 px-4">Nama Pemilik</th>
                            <th class="py-3 px-4">Tipe Berkas</th>
                            <th class="py-3 px-4">Tahun</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <template x-for="doc in filteredDocs" :key="doc.id">
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-blue-700">
                                    <div class="flex items-center gap-1.5">
                                        <span class="bg-blue-50 text-blue-700 border border-blue-200/80 px-2 py-0.5 rounded-md text-[11px]" x-text="doc.document_number"></span>
                                        <button type="button" @click="copyText(doc.document_number)" title="Salin nomor" class="text-slate-400 hover:text-blue-600 transition">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-800 font-heading whitespace-nowrap" x-text="doc.owner_name"></td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        <span x-text="doc.type_emoji"></span>
                                        <span x-text="doc.document_type"></span>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-500" x-text="doc.year"></td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <template x-if="doc.status === 'Terverifikasi'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Terverifikasi
                                        </span>
                                    </template>
                                    <template x-if="doc.status === 'Menunggu Verifikasi'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <svg class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/></svg> Menunggu
                                        </span>
                                    </template>
                                    <template x-if="doc.status !== 'Terverifikasi' && doc.status !== 'Menunggu Verifikasi'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <svg class="w-3 h-3 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 9l-6 6m0-6l6 6"/></svg> Ditolak
                                        </span>
                                    </template>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" @click="openDocDetail(doc)"
                                                title="Detail Dokumen"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition border border-transparent hover:border-blue-200">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <a :href="doc.download_url" title="Unduh Berkas"
                                           class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition border border-transparent hover:border-emerald-200">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                        <a :href="doc.print_url" target="_blank" title="Cetak Berkas"
                                           class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition border border-transparent hover:border-indigo-200">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredDocs.length === 0">
                            <td colspan="6" class="text-center py-10 px-4 text-slate-500 text-xs">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                    <i data-lucide="file-x" class="w-6 h-6"></i>
                                </div>
                                <div>Tidak ada dokumen yang sesuai dengan pencarian atau filter.</div>
                                <button type="button" @click="resetFilters()" class="mt-2 text-xs font-bold text-blue-600 hover:underline">
                                    Reset Semua Filter
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SMARTPHONE VIEW (TOUCH-FRIENDLY CARD LIST FOR HP) -->
            <div class="block md:hidden divide-y divide-slate-100 p-2">
                <template x-for="doc in filteredDocs" :key="doc.id">
                    <div class="p-3 bg-white rounded-xl border border-slate-200/80 mb-2 shadow-2xs transition hover:border-blue-300">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-semibold bg-slate-100 text-slate-700">
                                <span x-text="doc.type_emoji"></span>
                                <span x-text="doc.document_type"></span>
                            </span>
                            
                            <!-- Status Badge -->
                            <template x-if="doc.status === 'Terverifikasi'">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Terverifikasi
                                </span>
                            </template>
                            <template x-if="doc.status === 'Menunggu Verifikasi'">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <svg class="w-2.5 h-2.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/></svg> Menunggu
                                </span>
                            </template>
                            <template x-if="doc.status !== 'Terverifikasi' && doc.status !== 'Menunggu Verifikasi'">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <svg class="w-2.5 h-2.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 9l-6 6m0-6l6 6"/></svg> Ditolak
                                </span>
                            </template>
                        </div>

                        <!-- Owner & Doc Number -->
                        <div class="mb-2">
                            <div class="font-heading font-extrabold text-sm text-slate-900 leading-tight" x-text="doc.owner_name"></div>
                            <div class="flex items-center gap-2 mt-1 text-[11px] font-mono text-slate-500">
                                <span class="bg-blue-50 text-blue-700 border border-blue-200 px-1.5 py-0.5 rounded font-bold" x-text="doc.document_number"></span>
                                <span class="text-slate-400">•</span>
                                <span x-text="'Thn ' + doc.year"></span>
                            </div>
                        </div>

                        <!-- Mobile Thumb Buttons -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button type="button" @click="openDocDetail(doc)"
                                    class="flex-1 py-1.5 px-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold rounded-lg text-center transition flex items-center justify-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Detail</span>
                            </button>
                            <a :href="doc.download_url"
                               class="flex-1 py-1.5 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold rounded-lg text-center transition flex items-center justify-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Unduh</span>
                            </a>
                            <a :href="doc.print_url" target="_blank"
                               class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition" title="Cetak">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </a>
                        </div>
                    </div>
                </template>

                <div x-show="filteredDocs.length === 0" class="text-center py-8 text-slate-500 text-xs">
                    <div>Tidak ada dokumen yang sesuai.</div>
                    <button type="button" @click="resetFilters()" class="mt-2 text-xs font-bold text-blue-600">
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Footer summary -->
            <div class="p-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <span>Menampilkan <strong x-text="filteredDocs.length" class="text-slate-800"></strong> dari {{ $totalDocs }} dokumen</span>
                <a href="{{ route('admin.documents') }}" class="font-bold text-blue-600 hover:underline">
                    Buka Manajemen Dokumen Lengkap →
                </a>
            </div>

        </div>

        <!-- ═══ TAB CONTENT 2: ACTIVITY LOGS ═══ -->
        <div x-show="activeTab === 'activity'" style="display:none;" class="p-4 sm:p-5">
            <div class="space-y-3">
                @forelse($recentActivity as $act)
                    @php $typeConf = \App\Models\ActivityLog::TYPE_CONFIG[$act->type] ?? null; @endphp
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50/70 border border-slate-200/70 hover:bg-slate-50 transition">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0 border {{ $typeConf['color'] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                            {{ $typeConf['emoji'] ?? '📝' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="font-heading font-bold text-xs text-slate-900 truncate">
                                    {{ $act->action }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    {{ $act->getFormattedTimestamp() }}
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-600 mt-0.5 truncate">
                                Target: <code class="font-mono text-slate-800 bg-white px-1.5 py-0.5 rounded border border-slate-200">{{ $act->target ?: '—' }}</code>
                                @if($act->user_email)
                                    <span class="text-slate-400 ml-1.5">• Oleh: {{ $act->user_email }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-500 text-xs">
                        Belum ada catatan aktivitas terbaru.
                    </div>
                @endforelse
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 text-right">
                <a href="{{ route('admin.activity') }}" class="text-xs font-bold text-blue-600 hover:underline">
                    Lihat Seluruh Riwayat Aktivitas & Audit Trail →
                </a>
            </div>
        </div>

    </div>

    <!-- ═══ SCHOOL PROFILE & SYSTEM STATUS FOOTER CARD ═══ -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0 text-blue-600">
                <i data-lucide="school" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="font-heading font-extrabold text-slate-900 text-sm">SMK PLUS MELATI SAMARINDA</div>
                <div class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                    Jalan H.A.M. M. Rifaddin, RT 33, Kelurahan Harapan Baru, Kecamatan Loa Janan Ilir, Samarinda, Kalimantan Timur.
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <div class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Node Blockchain Terhubung</span>
            </div>
            <a href="{{ route('admin.blockchain') }}" class="text-xs font-bold text-blue-600 hover:underline">
                Audit Trail →
            </a>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- ═══ MODAL 2: DETAIL CEPAT DOKUMEN (QUICK PREVIEW MODAL) ═══ -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div x-show="docModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display:none;"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <template x-if="selectedDoc">
            <div @click.away="docModalOpen = false"
                 class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-200 text-slate-900">
                
                <!-- Modal Header -->
                <div class="bg-slate-900 text-white p-5 relative">
                    <button type="button" @click="docModalOpen = false"
                            class="absolute top-4 right-4 text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xl" x-text="selectedDoc.type_emoji"></span>
                        <span class="text-xs uppercase font-bold text-blue-300 tracking-wider" x-text="selectedDoc.document_type"></span>
                    </div>
                    <h3 class="font-heading font-extrabold text-lg text-white" x-text="selectedDoc.owner_name"></h3>
                    <div class="text-xs font-mono text-slate-300 mt-1" x-text="selectedDoc.document_number"></div>
                </div>

                <!-- Modal Body -->
                <div class="p-5 space-y-4 text-xs">
                    
                    <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Tahun Dokumen</span>
                            <span class="font-bold text-slate-800 text-sm font-mono" x-text="selectedDoc.year"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Status</span>
                            <span class="font-bold text-sm"
                                  :class="selectedDoc.status === 'Terverifikasi' ? 'text-emerald-600' : (selectedDoc.status === 'Menunggu Verifikasi' ? 'text-amber-600' : 'text-rose-600')"
                                  x-text="selectedDoc.status"></span>
                        </div>
                    </div>

                    <div class="text-slate-600 text-xs">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase mb-0.5">Waktu Diunggah</span>
                        <span x-text="selectedDoc.created_at_formatted"></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                        <div class="grid grid-cols-2 gap-2">
                            <a :href="selectedDoc.download_url"
                               class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center flex items-center justify-center gap-1.5 transition">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Unduh File</span>
                            </a>
                            <a :href="selectedDoc.print_url" target="_blank"
                               class="py-2.5 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-center flex items-center justify-center gap-1.5 transition">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                                <span>Cetak Berkas</span>
                            </a>
                        </div>
                        
                        <template x-if="selectedDoc.verify_url">
                            <a :href="selectedDoc.verify_url" target="_blank"
                               class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-center flex items-center justify-center gap-1.5 transition">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-600"></i>
                                <span>Buka Verifikasi Publik</span>
                            </a>
                        </template>
                    </div>

                </div>

            </div>
        </template>
    </div>

</div>

<!-- ═══ SCRIPTS: CHART.JS & ALPINE DASHBOARD MANAGER ═══ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function dashboardManager() {
        return {
            // Stats state
            stats: {
                totalDocs: {{ (int)$totalDocs }},
                verified:  {{ (int)$verified }},
                pending:   {{ (int)$pending }},
                rejected:  {{ (int)$rejected }},
                totalTx:   {{ (int)$totalTx }},
            },
            
            // Tab state
            activeTab: 'docs', // 'docs' or 'activity'
            activeStatusFilter: 'all',
            searchQuery: '',

            // Live sync state
            isSyncing: false,
            autoSync: false,
            autoSyncInterval: null,
            lastSynced: '{{ now()->timezone('Asia/Jakarta')->format('H:i') }} WITA',

            // Quick detail modal state
            docModalOpen: false,
            selectedDoc: null,

            // Toast state
            toast: {
                show: false,
                message: '',
                type: 'success'
            },

            // Docs array from server
            docs: @json($recentDocsData ?? []),

            // Weekly data for charts
            weeklyData: @json($weeklyData),
            typeStats: @json($typeStats),

            // Charts instances
            weeklyChart: null,
            typeChart: null,

            // Filtered documents getter
            get filteredDocs() {
                return this.docs.filter(doc => {
                    // Filter by status
                    const matchesStatus = this.activeStatusFilter === 'all' || doc.status === this.activeStatusFilter;
                    
                    // Filter by search query
                    const query = this.searchQuery.trim().toLowerCase();
                    const matchesSearch = !query || 
                        (doc.owner_name && doc.owner_name.toLowerCase().includes(query)) ||
                        (doc.document_number && doc.document_number.toLowerCase().includes(query)) ||
                        (doc.document_type && doc.document_type.toLowerCase().includes(query));

                    return matchesStatus && matchesSearch;
                });
            },

            initDashboard() {
                this.$nextTick(() => {
                    this.initWeeklyChart();
                    this.initTypeChart();
                    lucide.createIcons();
                });

                this.$watch('activeTab', () => this.$nextTick(() => lucide.createIcons()));
                this.$watch('activeStatusFilter', () => this.$nextTick(() => lucide.createIcons()));
                this.$watch('searchQuery', () => this.$nextTick(() => lucide.createIcons()));
                this.$watch('docs', () => this.$nextTick(() => lucide.createIcons()));
            },

            filterByStatus(status) {
                if (this.activeStatusFilter === status && status !== 'all') {
                    this.activeStatusFilter = 'all';
                } else {
                    this.activeStatusFilter = status;
                }
                this.$nextTick(() => lucide.createIcons());
            },

            resetFilters() {
                this.activeStatusFilter = 'all';
                this.searchQuery = '';
                this.$nextTick(() => lucide.createIcons());
            },

            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num || 0);
            },

            getVerifiedPercentage() {
                return this.stats.totalDocs > 0 ? Math.round((this.stats.verified / this.stats.totalDocs) * 100) : 0;
            },

            getPendingPercentage() {
                return this.stats.totalDocs > 0 ? Math.round((this.stats.pending / this.stats.totalDocs) * 100) : 0;
            },

            getRejectedPercentage() {
                return this.stats.totalDocs > 0 ? Math.round((this.stats.rejected / this.stats.totalDocs) * 100) : 0;
            },

            openDocDetail(doc) {
                this.selectedDoc = doc;
                this.docModalOpen = true;
                this.$nextTick(() => lucide.createIcons());
            },

            copyText(text) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast('Nomor dokumen berhasil disalin: ' + text);
                });
            },

            showToast(message, type = 'success') {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.show = true;
                setTimeout(() => {
                    this.toast.show = false;
                }, 3500);
            },

            toggleAutoSync() {
                if (this.autoSync) {
                    this.showToast('Auto-sync aktif (setiap 30 detik)');
                    this.autoSyncInterval = setInterval(() => {
                        this.syncData(true);
                    }, 30000);
                } else {
                    if (this.autoSyncInterval) clearInterval(this.autoSyncInterval);
                    this.showToast('Auto-sync dinonaktifkan');
                }
            },

            async syncData(silent = false) {
                if (this.isSyncing) return;
                this.isSyncing = true;

                try {
                    const response = await fetch('{{ route('admin.dashboard') }}?ajax=1', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            this.stats = data.stats;
                            this.docs = data.recentDocs;
                            this.weeklyData = data.weeklyData;
                            this.typeStats = data.typeStats;
                            this.lastSynced = data.syncedAt || 'Baru saja';
                            
                            this.updateCharts();
                            if (!silent) {
                                this.showToast('Data dashboard berhasil disinkronkan!');
                            }
                        }
                    }
                } catch (e) {
                    console.error('Sync failed:', e);
                    if (!silent) {
                        this.showToast('Gagal menyinkronkan data!', 'error');
                    }
                } finally {
                    this.isSyncing = false;
                    this.$nextTick(() => lucide.createIcons());
                }
            },

            initWeeklyChart() {
                const canvas = document.getElementById('weeklyChart');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                const gradTotal = ctx.createLinearGradient(0, 0, 0, 180);
                gradTotal.addColorStop(0, 'rgba(37, 99, 235, 0.28)');
                gradTotal.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

                const gradOk = ctx.createLinearGradient(0, 0, 0, 180);
                gradOk.addColorStop(0, 'rgba(16, 185, 129, 0.28)');
                gradOk.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

                const days   = this.weeklyData.map(d => d.day);
                const totals = this.weeklyData.map(d => d.total);
                const oks    = this.weeklyData.map(d => d.ok);

                this.weeklyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: days,
                        datasets: [
                            {
                                label: 'Total Upload',
                                data: totals,
                                borderColor: '#2563eb',
                                backgroundColor: gradTotal,
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.5,
                                pointBackgroundColor: '#2563eb',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Terverifikasi',
                                data: oks,
                                borderColor: '#10b981',
                                backgroundColor: gradOk,
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.5,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    color: '#475569',
                                    font: { family: 'Plus Jakarta Sans', size: 11, weight: '700' }
                                }
                            },
                            tooltip: {
                                padding: 10,
                                cornerRadius: 8,
                                bodyFont: { family: 'Inter', size: 11 }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { color: '#64748b', font: { family: 'Inter', size: 10.5, weight: '600' } }
                            },
                            y: {
                                border: { dash: [4, 4], display: false },
                                grid: { color: '#f1f5f9' },
                                ticks: { color: '#64748b', font: { family: 'Inter', size: 10.5 }, precision: 0 }
                            }
                        }
                    }
                });
            },

            initTypeChart() {
                const canvas = document.getElementById('typeChart');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                const types  = Object.keys(this.typeStats);
                const counts = Object.values(this.typeStats);

                this.typeChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: types,
                        datasets: [{
                            label: 'Jumlah Dokumen',
                            data: counts,
                            backgroundColor: '#3b82f6',
                            borderRadius: 6,
                            hoverBackgroundColor: '#1d4ed8'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                padding: 8,
                                cornerRadius: 6,
                                bodyFont: { family: 'Inter', size: 11 }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    color: '#64748b',
                                    font: { family: 'Inter', size: 9, weight: '600' },
                                    maxRotation: 25,
                                    minRotation: 25,
                                    callback: function(value, index) {
                                        const label = types[index] || '';
                                        return label.length > 7 ? label.substring(0, 7) + '…' : label;
                                    }
                                }
                            },
                            y: {
                                display: false
                            }
                        }
                    }
                });
            },

            updateCharts() {
                if (this.weeklyChart) {
                    this.weeklyChart.data.labels = this.weeklyData.map(d => d.day);
                    this.weeklyChart.data.datasets[0].data = this.weeklyData.map(d => d.total);
                    this.weeklyChart.data.datasets[1].data = this.weeklyData.map(d => d.ok);
                    this.weeklyChart.update();
                }
                if (this.typeChart) {
                    this.typeChart.data.labels = Object.keys(this.typeStats);
                    this.typeChart.data.datasets[0].data = Object.values(this.typeStats);
                    this.typeChart.update();
                }
            },

            refreshChart() {
                this.updateCharts();
                this.showToast('Grafik diperbarui');
            }
        }
    }
</script>
@endsection
