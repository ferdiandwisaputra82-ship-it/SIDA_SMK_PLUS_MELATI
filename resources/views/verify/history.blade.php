@extends('layouts.public')

@section('title', 'Riwayat Verifikasi')

@section('content')
<div style="background:linear-gradient(180deg,#f8fafc 0%,#eff6ff 60%,#f8fafc 100%);min-height:82vh;padding:40px 0 80px" x-data="{ search: '' }">
    <div class="max-w-5xl mx-auto px-4">
        
        <!-- Breadcrumb Navigation -->
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#64748b;margin-bottom:20px;font-weight:600">
            <a href="{{ route('verify.index') }}" style="color:#2563eb;text-decoration:none;display:inline-flex;align-items:center;gap:4px" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                <i data-lucide="shield-check" style="width:14px;height:14px"></i>
                Verifikasi Dokumen
            </a>
            <i data-lucide="chevron-right" style="width:14px;height:14px;color:#94a3b8"></i>
            <span style="color:#0f172a;font-weight:700">Riwayat Verifikasi</span>
        </div>

        <!-- Page Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:14px">
            <div>
                <h1 style="font-size:24px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0;display:flex;align-items:center;gap:10px">
                    <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;border:1.5px solid #bfdbfe;display:flex;align-items:center;justify-content:center;color:#2563eb">
                        <i data-lucide="history" style="width:20px;height:20px"></i>
                    </div>
                    Riwayat Verifikasi Dokumen
                </h1>
                <p style="font-size:13px;color:#64748b;margin:4px 0 0">
                    Daftar riwayat dokumen yang telah Anda periksa keabsahannya dalam sesi peramban ini.
                </p>
            </div>
            <a href="{{ route('verify.index') }}"
               style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;padding:11px 22px;border-radius:12px;font-size:13.5px;font-weight:700;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 4px 14px rgba(37,99,235,0.25);transition:all .18s"
               onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i data-lucide="plus" style="width:16px;height:16px"></i> Verifikasi Dokumen Baru
            </a>
        </div>

        {{-- Table Container Card --}}
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 8px 30px rgba(15,23,42,0.05)">
            
            <!-- Search bar -->
            <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:12px;background:#f8fafc">
                <i data-lucide="search" style="width:18px;height:18px;color:#94a3b8;flex-shrink:0"></i>
                <input type="text" placeholder="Cari berdasarkan nama file atau ID verifikasi..." x-model="search"
                       style="flex:1;font-size:13.5px;background:transparent;outline:none;border:none;color:#0f172a;font-family:'Inter',sans-serif">
                <span x-show="search.length > 0" @click="search = ''" style="cursor:pointer;color:#94a3b8;font-size:12px" title="Hapus pencarian">✕</span>
            </div>

            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                            <th style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;padding:12px 20px;font-family:'JetBrains Mono',monospace">ID Verifikasi</th>
                            <th style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;padding:12px 20px;font-family:'Plus Jakarta Sans',sans-serif">Nama File</th>
                            <th style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;padding:12px 20px;font-family:'Plus Jakarta Sans',sans-serif">Waktu Pemeriksaan</th>
                            <th style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;padding:12px 20px;font-family:'Plus Jakarta Sans',sans-serif">Tipe Dokumen</th>
                            <th style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;padding:12px 20px;font-family:'Plus Jakarta Sans',sans-serif">Hasil Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $row)
                            <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s"
                                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''"
                                x-show="search === '' || '{{ strtolower($row['file_name']) }}'.includes(search.toLowerCase()) || '{{ strtolower($row['id']) }}'.includes(search.toLowerCase())">
                                <td style="padding:14px 20px;white-space:nowrap">
                                    <code style="font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:700;color:#1d4ed8;background:#eff6ff;border:1px solid #bfdbfe;padding:3px 8px;border-radius:6px">{{ $row['id'] }}</code>
                                </td>
                                <td style="padding:14px 20px">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <div style="width:32px;height:32px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#2563eb">
                                            <i data-lucide="file-text" style="width:16px;height:16px"></i>
                                        </div>
                                        <span style="font-size:13px;font-weight:700;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px" title="{{ $row['file_name'] }}">{{ $row['file_name'] }}</span>
                                    </div>
                                </td>
                                <td style="padding:14px 20px;font-size:12px;color:#64748b;font-family:'JetBrains Mono',monospace;white-space:nowrap">{{ $row['timestamp'] }}</td>
                                <td style="padding:14px 20px;font-size:12.5px;color:#475569;font-weight:600">{{ $row['doc_type'] ?: '—' }}</td>
                                <td style="padding:14px 20px;white-space:nowrap">
                                    @if($row['result'] === 'Valid')
                                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:#f0fdf4;color:#16a34a;border:1px solid #86efac">
                                            <i data-lucide="check-circle-2" style="width:13px;height:13px"></i> Valid
                                        </span>
                                    @else
                                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:#fff1f2;color:#dc2626;border:1px solid #fca5a5">
                                            <i data-lucide="x-circle" style="width:13px;height:13px"></i> Tidak Valid
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:50px 20px">
                                    <div style="width:54px;height:54px;border-radius:16px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                                        <i data-lucide="folder-search" style="width:28px;height:28px"></i>
                                    </div>
                                    <div style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:4px;font-family:'Plus Jakarta Sans',sans-serif">Belum Ada Riwayat Verifikasi</div>
                                    <p style="font-size:12.5px;color:#64748b;margin:0 0 16px">Unggah berkas dokumen untuk memeriksa status keaslian dan melihat riwayatnya di sini.</p>
                                    <a href="{{ route('verify.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:10px;background:#2563eb;color:#ffffff;font-size:12.5px;font-weight:700;text-decoration:none">
                                        <i data-lucide="upload" style="width:14px;height:14px"></i> Verifikasi Sekarang
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
