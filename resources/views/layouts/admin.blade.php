<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - SIDA Admin Panel</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v=3">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v=3">
    <link rel="apple-touch-icon" href="{{ asset('images/school-logo.png') }}?v=3">


    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            margin: 0;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #2563eb; }

        /* ═══ SIDEBAR ═══ */
        #admin-sidebar {
            width: 250px;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(180deg, #0b1329 0%, #080d1a 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.07);
            box-shadow: 4px 0 24px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 50;
            overflow: hidden;
        }

        #main-content {
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
        }

        /* Desktop Mode (>= 1024px) */
        @media (min-width: 1024px) {
            #admin-sidebar {
                transform: translateX(0) !important;
            }
            #admin-sidebar.collapsed { width: 72px; }
            #admin-sidebar.collapsed .sidebar-label,
            #admin-sidebar.collapsed .nav-text,
            #admin-sidebar.collapsed .section-label,
            #admin-sidebar.collapsed .school-info,
            #admin-sidebar.collapsed .active-indicator,
            #admin-sidebar.collapsed .nav-badge { display: none !important; }
            #admin-sidebar.collapsed .nav-item { justify-content: center; padding-left: 0; padding-right: 0; }
            #admin-sidebar.collapsed .nav-icon-box { margin: 0; }

            #main-content {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
            #main-content.sidebar-collapsed {
                margin-left: 72px;
                width: calc(100% - 72px);
            }
        }

        /* Mobile & Tablet Mode (< 1024px) */
        @media (max-width: 1023px) {
            #admin-sidebar {
                width: 280px;
                transform: translateX(-100%);
                z-index: 60;
                box-shadow: 4px 0 30px rgba(0,0,0,0.5);
            }
            #admin-sidebar.mobile-open {
                transform: translateX(0) !important;
            }
            #main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        /* Mobile Bottom Nav Bar */
        .mobile-bottom-bar {
            padding-bottom: env(safe-area-inset-bottom, 8px);
        }

        /* Brand Header */
        .sidebar-brand {
            background: linear-gradient(135deg, #1e3a8a 0%, #172554 100%);
            padding: 18px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }
        .sidebar-brand::after {
            content: '';
            position: absolute;
            bottom: 0; left: 16px; right: 16px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.3), transparent);
        }
        .brand-logo-wrap {
            width: 38px; height: 38px;
            border-radius: 12px;
            background: rgba(255,255,255,0.14);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.22);
            box-shadow: 0 4px 12px rgba(0,0,0,0.18);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; padding: 4px;
        }
        .brand-logo-wrap img { width: 100%; height: 100%; object-fit: contain; }
        .brand-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; color: #ffffff; font-size: 15px; line-height: 1.1; letter-spacing: -0.3px; }
        .brand-title span { color: #60a5fa; }
        .brand-sub { font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em; color: #93c5fd; font-weight: 700; margin-top: 3px; opacity: 0.9; }

        /* Nav section label */
        .section-label {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #475569;
            padding: 6px 12px 6px 8px;
            margin-bottom: 4px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.05);
        }

        /* Nav item base - Kotak Fitur Modern Melengkung */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 8px 12px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: #94a3b8;
            cursor: pointer;
            border: 1px solid transparent;
            background: transparent;
            width: 100%;
            text-align: left;
            position: relative;
            margin-bottom: 4px;
        }

        /* Nav Icon Box */
        .nav-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #94a3b8;
            transition: all 0.22s ease;
        }
        .nav-icon-box i { width: 16px; height: 16px; }

        /* Hover State */
        .nav-item:not(.nav-active):hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            transform: translateX(3px);
        }
        .nav-item:not(.nav-active):hover .nav-icon-box {
            background: rgba(37, 99, 235, 0.18);
            border-color: rgba(37, 99, 235, 0.35);
            color: #60a5fa;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }

        /* Active Item - Modern Vibrant Gradient + Inner Glow + Melengkung */
        .nav-active {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 60%, #1e40af 100%) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            border-radius: 14px !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
            box-shadow: 0 6px 20px -3px rgba(37, 99, 235, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
        }
        .nav-active .nav-icon-box {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.35) !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
        }

        /* Active Indicator Pill on Right */
        .active-indicator {
            width: 4px;
            height: 14px;
            border-radius: 99px;
            background: #ffffff;
            margin-left: auto;
            flex-shrink: 0;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
        }

        /* Nav scroll area */
        .nav-scroll { flex: 1; padding: 14px 10px; overflow-y: auto; }
        .nav-group + .nav-group { margin-top: 16px; }

        /* ═══ HEADER ═══ */
        #main-header {
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 14px;
            position: sticky;
            top: 0;
            z-index: 30;
            flex-shrink: 0;
        }

        .toggle-btn {
            padding: 7px; border-radius: 8px;
            background: #f8fafc; border: 1px solid #cbd5e1; color: #475569;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.15s ease; flex-shrink: 0;
        }
        .toggle-btn:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }

        .header-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; color: #0f172a; font-size: 14.5px; }
        .header-subtitle { font-size: 11px; color: #64748b; margin-top: 1px; }

        .header-sep { width: 1px; height: 26px; background: #e2e8f0; }
        .clock-date { font-size: 12px; font-weight: 600; color: #334155; line-height: 1.3; text-align: right; }
        .clock-time { font-size: 10.5px; color: #2563eb; line-height: 1.3; text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; }

        .notif-btn {
            position: relative; padding: 7px; border-radius: 8px;
            background: #f8fafc; border: 1px solid #cbd5e1; color: #475569;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: all 0.15s ease;
        }
        .notif-btn:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .notif-dot { position: absolute; top: 5px; right: 5px; width: 7px; height: 7px; background: #ef4444; border-radius: 50%; }

        .user-chip {
            display: flex; align-items: center; gap: 8px;
            padding: 4px 10px 4px 4px; border-radius: 10px;
            cursor: pointer; transition: background 0.15s ease;
            background: #f8fafc; border: 1px solid #cbd5e1;
        }
        .user-chip:hover { background: #eff6ff; border-color: #bfdbfe; }
        .user-avatar {
            width: 30px; height: 30px; border-radius: 8px;
            background: #1e3a8a; color: #ffffff;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; flex-shrink: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .user-name { font-size: 12px; font-weight: 700; color: #0f172a; line-height: 1.2; font-family: 'Plus Jakarta Sans', sans-serif; }
        .user-role { font-size: 10px; color: #64748b; line-height: 1.2; }

        /* ═══ SIDEBAR FOOTER ═══ */
        .sidebar-footer {
            padding: 14px 12px;
            background: #070b14;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            flex-shrink: 0;
        }
        .logout-btn {
            border-radius: 12px;
            padding: 8px 12px;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            transition: all 0.2s ease;
        }
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.18);
            border-color: rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            transform: translateX(3px);
        }
        .logout-btn .nav-icon-box {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.22);
            color: #f87171;
        }
        .logout-btn:hover .nav-icon-box {
            background: rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }

        .school-info-card {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 14px;
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.65), rgba(15, 23, 42, 0.85));
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            margin-top: 10px;
        }
        .school-logo {
            width: 30px; height: 30px; border-radius: 10px;
            background: rgba(30, 58, 138, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 3px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .school-logo img { width: 100%; height: 100%; object-fit: contain; }
        .school-name { font-size: 10.5px; font-weight: 800; color: #ffffff; line-height: 1.2; font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.2px; }
        .school-tagline { font-size: 9px; color: #94a3b8; line-height: 1.2; margin-top: 1px; }
    </style>
</head>
<body x-data="adminLayout()">

    <!-- Mobile Backdrop -->
    <div x-show="isMobile && mobileSidebarOpen"
         @click="mobileSidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display:none;"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden">
    </div>

    <!-- ═══ SIDEBAR ═══ -->
    <aside id="admin-sidebar" :class="{ 'collapsed': !isMobile && !sidebarOpen, 'mobile-open': isMobile && mobileSidebarOpen }">

        <!-- Brand Header -->
        <div class="sidebar-brand">
            <div class="brand-logo-wrap">
                <img src="{{ asset('images/school-logo.png') }}?v=3" alt="Logo">
            </div>
            <div class="sidebar-label" style="overflow:hidden">
                <div class="brand-title">SIDA</div>
                <div class="brand-sub">Sistem Dokumen &amp; Arsip</div>
            </div>
            <button type="button" @click="mobileSidebarOpen = false" class="lg:hidden ml-auto p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition" title="Tutup Menu">
                <i data-lucide="x" style="width:18px;height:18px"></i>
            </button>
        </div>

        <!-- Navigation -->
        <div class="nav-scroll">
            @php
            $mainItems = [
                ['label' => 'Dashboard Utama',     'icon' => 'layout-dashboard', 'route' => 'admin.dashboard',    'permission' => 'dashboard.view'],
                ['label' => 'Manajemen Dokumen',  'icon' => 'folder-open',      'route' => 'admin.documents',    'permission' => 'documents.view'],
                ['label' => 'Upload Dokumen Baru', 'icon' => 'upload',           'route' => 'admin.upload',       'permission' => 'upload.page'],
                ['label' => 'Batch ZIP Upload',   'icon' => 'archive',          'route' => 'admin.batch-upload', 'permission' => 'batch_upload.page'],
            ];
            $analisisItems = [
                ['label' => 'Review Hasil OCR',   'icon' => 'cpu',              'route' => 'admin.ocr-review',   'permission' => 'ocr_review.page'],
                ['label' => 'Log Blockchain',     'icon' => 'link-2',           'route' => 'admin.blockchain',   'permission' => 'blockchain.page'],
                ['label' => 'Riwayat Aktivitas',  'icon' => 'activity',         'route' => 'admin.activity',     'permission' => 'activity.view'],
            ];
            $sistemItems = [
                ['label' => 'Kelola Pengguna',    'icon' => 'users',            'route' => 'admin.users',        'permission' => 'users.manage'],
                ['label' => 'Pengaturan Sistem',  'icon' => 'settings',         'route' => 'admin.settings',     'permission' => 'settings.manage'],
            ];

            $mainFiltered     = array_filter($mainItems,     fn($i) => \App\Config\PermissionConfig::can($i['permission']));
            $analisisFiltered = array_filter($analisisItems, fn($i) => \App\Config\PermissionConfig::can($i['permission']));
            $sistemFiltered   = array_filter($sistemItems,   fn($i) => \App\Config\PermissionConfig::can($i['permission']));

            $ocrQueueCount = count(session('ocr_queue', []));
            @endphp

            {{-- UTAMA --}}
            @if(count($mainFiltered) > 0)
            <div class="nav-group">
                <div class="section-label">Utama</div>
                @foreach($mainFiltered as $item)
                    @php $isActive = Route::is($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                       @click="if(isMobile) mobileSidebarOpen = false"
                       class="nav-item {{ $isActive ? 'nav-active' : '' }}">
                        <div class="nav-icon-box">
                            <i data-lucide="{{ $item['icon'] }}"></i>
                        </div>
                        <span class="nav-text" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item['label'] }}</span>
                        @if($isActive)
                            <span class="active-indicator"></span>
                        @endif
                    </a>
                @endforeach
            </div>
            @endif

            {{-- ANALISIS --}}
            @if(count($analisisFiltered) > 0)
            <div class="nav-group">
                <div class="section-label">Analisis &amp; Audit</div>
                @foreach($analisisFiltered as $item)
                    @php $isActive = Route::is($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                       @click="if(isMobile) mobileSidebarOpen = false"
                       class="nav-item {{ $isActive ? 'nav-active' : '' }}">
                        <div class="nav-icon-box">
                            <i data-lucide="{{ $item['icon'] }}"></i>
                        </div>
                        <span class="nav-text" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item['label'] }}</span>
                        @if($item['route'] === 'admin.ocr-review' && $ocrQueueCount > 0)
                            <span class="nav-badge" style="margin-left:auto;background:#d97706;color:#fff;font-size:9px;font-weight:700;padding:2px 7px;border-radius:999px;box-shadow:0 2px 6px rgba(217,119,6,0.3)">{{ $ocrQueueCount }}</span>
                        @elseif($isActive)
                            <span class="active-indicator"></span>
                        @endif
                    </a>
                @endforeach
            </div>
            @endif

            {{-- SISTEM --}}
            @if(count($sistemFiltered) > 0)
            <div class="nav-group">
                <div class="section-label">Pengaturan</div>
                @foreach($sistemFiltered as $item)
                    @php $isActive = Route::is($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                       @click="if(isMobile) mobileSidebarOpen = false"
                       class="nav-item {{ $isActive ? 'nav-active' : '' }}">
                        <div class="nav-icon-box">
                            <i data-lucide="{{ $item['icon'] }}"></i>
                        </div>
                        <span class="nav-text" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item['label'] }}</span>
                        @if($isActive)
                            <span class="active-indicator"></span>
                        @endif
                    </a>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" title="Keluar" class="nav-item logout-btn">
                    <div class="nav-icon-box">
                        <i data-lucide="log-out"></i>
                    </div>
                    <span class="nav-text" style="font-weight:700">Keluar</span>
                </button>
            </form>
            <div class="school-info school-info-card">
                <div class="school-logo">
                    <img src="{{ asset('images/school-logo.png') }}?v=3" alt="Logo SMK">
                </div>
                <div style="overflow:hidden">
                    <div class="school-name">SMK PLUS MELATI</div>
                    <div class="school-tagline">Samarinda, Kaltim</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ═══ MAIN CONTENT ═══ -->
    <div id="main-content" :class="(!isMobile && !sidebarOpen) ? 'sidebar-collapsed' : ''">

        <!-- Header Bar -->
        <header id="main-header">
            <button @click="toggleSidebar()" title="Toggle Menu" class="toggle-btn">
                <i data-lucide="menu" style="width:16px;height:16px"></i>
            </button>

            <div style="min-width:0" class="truncate">
                <div class="header-title truncate">@yield('title')</div>
                @hasSection('subtitle')
                    <div class="header-subtitle truncate hidden sm:block">@yield('subtitle')</div>
                @endif
            </div>

            <!-- Right controls -->
            <div style="margin-left:auto;display:flex;align-items:center;gap:10px">


                <!-- Date / Time -->
                <div class="hidden lg:block">
                    <div class="clock-date" id="headerDate"></div>
                    <div class="clock-time" id="headerTime"></div>
                </div>

                <div class="header-sep hidden lg:block"></div>

                <!-- Notification Bell -->
                @if(\App\Config\PermissionConfig::can('ocr_review.page'))
                <a href="{{ route('admin.ocr-review') }}" title="Notifikasi Review OCR" class="notif-btn">
                    <i data-lucide="bell" style="width:16px;height:16px"></i>
                    @if($ocrQueueCount > 0)
                        <span class="notif-dot"></span>
                    @endif
                </a>
                @endif

                <div class="header-sep"></div>

                <!-- User Profile -->
                @php
                    $adminName = session('admin_name', 'Admin');
                    $adminRole = session('admin_role', 'Administrator');
                    $nameParts = explode(' ', $adminName);
                    $initials  = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                @endphp
                <div class="user-chip">
                    <div class="user-avatar"><span>{{ $initials }}</span></div>
                    <div class="hidden sm:block">
                        <div class="user-name">{{ $adminName }}</div>
                        <div class="user-role">{{ $adminRole ?: 'Administrator' }}</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-3.5 sm:p-5 lg:p-6 pb-24 md:pb-8">
            @if(session('success'))
                <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;font-size:12.5px;font-weight:600;background:#f0fdf4;border:1px solid #86efac;color:#166534"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)">
                    <i data-lucide="check-circle-2" style="width:16px;height:16px;color:#22c55e;flex-shrink:0"></i>
                    <span style="flex:1">{{ session('success') }}</span>
                    <button @click="show = false" style="background:none;border:none;cursor:pointer;color:#15803d"><i data-lucide="x" style="width:14px;height:14px"></i></button>
                </div>
            @endif

            @if(session('error'))
                <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;font-size:12.5px;font-weight:600;background:#fff1f2;border:1px solid #fca5a5;color:#991b1b"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)">
                    <i data-lucide="alert-circle" style="width:16px;height:16px;color:#dc2626;flex-shrink:0"></i>
                    <span style="flex:1">{{ session('error') }}</span>
                    <button @click="show = false" style="background:none;border:none;cursor:pointer;color:#991b1b"><i data-lucide="x" style="width:14px;height:14px"></i></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Mobile Bottom Navigation Bar (< 768px) -->
    <nav class="mobile-bottom-bar md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/80 px-2 py-1.5 flex items-center justify-around shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
        <a href="{{ route('admin.dashboard') }}"
           class="flex flex-col items-center py-1 px-2.5 text-[11px] font-semibold transition {{ Route::is('admin.dashboard') ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <i data-lucide="layout-dashboard" style="width:20px;height:20px;margin-bottom:2px"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('admin.documents') }}"
           class="flex flex-col items-center py-1 px-2.5 text-[11px] font-semibold transition {{ Route::is('admin.documents*') ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <i data-lucide="folder-open" style="width:20px;height:20px;margin-bottom:2px"></i>
            <span>Dokumen</span>
        </a>
        @if(\App\Config\PermissionConfig::can('upload.page'))
        <a href="{{ route('admin.upload') }}" class="flex flex-col items-center -mt-5 group" title="Upload Dokumen Baru">
            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-blue-700 to-blue-500 text-white flex items-center justify-center shadow-lg shadow-blue-500/35 transition transform group-active:scale-95 group-hover:scale-105">
                <i data-lucide="upload" style="width:22px;height:22px"></i>
            </div>
            <span class="text-[10px] font-bold text-blue-700 mt-0.5">Upload</span>
        </a>
        @endif
        <button type="button" @click="mobileSidebarOpen = true"
                class="flex flex-col items-center py-1 px-2.5 text-[11px] font-semibold text-slate-500 hover:text-slate-800 transition">
            <i data-lucide="menu" style="width:20px;height:20px;margin-bottom:2px"></i>
            <span>Menu</span>
        </button>
    </nav>

    <!-- Initialize Lucide Icons & Layout Scripts -->
    <script>
        lucide.createIcons();

        function adminLayout() {
            return {
                isMobile: window.innerWidth < 1024,
                sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
                mobileSidebarOpen: false,
                init() {
                    const handleResize = () => {
                        this.isMobile = window.innerWidth < 1024;
                        if (!this.isMobile) {
                            this.mobileSidebarOpen = false;
                        }
                    };
                    window.addEventListener('resize', handleResize);
                },
                toggleSidebar() {
                    if (this.isMobile) {
                        this.mobileSidebarOpen = !this.mobileSidebarOpen;
                    } else {
                        this.sidebarOpen = !this.sidebarOpen;
                        localStorage.setItem('sidebarOpen', this.sidebarOpen);
                    }
                }
            }
        }

        // Live clock
        function updateClock() {
            const now = new Date();
            const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            const dateEl = document.getElementById('headerDate');
            const timeEl = document.getElementById('headerTime');
            if (dateEl) dateEl.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
            if (timeEl) {
                const hh = String(now.getHours()).padStart(2,'0');
                const mm = String(now.getMinutes()).padStart(2,'0');
                timeEl.textContent = hh + ':' + mm + ' WITA';
            }
        }
        updateClock();
        setInterval(updateClock, 30000);
    </script>
</body>
</html>
