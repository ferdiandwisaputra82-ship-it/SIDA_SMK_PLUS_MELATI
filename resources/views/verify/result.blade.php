@extends('layouts.public')

@section('title', $isValid ? 'Dokumen Valid' : (($isPending ?? false) ? 'Menunggu Validasi' : 'Dokumen Tidak Valid'))

@section('content')
@php
    $hasPreviewFile  = $hasPreviewFile ?? false;
    $previewUrl      = $previewUrl ?? null;
    $previewType     = $previewType ?? 'none';
    $previewFileName = $previewFileName ?? ($fileName ?? 'Dokumen');
    
    // Auto-fallback jika variabel belum diisi controller
    if (!$hasPreviewFile && !empty($matchedDoc['id'])) {
        $hasPreviewFile = true;
        $previewUrl = route('verify.preview', ['id' => $matchedDoc['id']]);
        $docExt = strtolower(pathinfo($matchedDoc['file_name'] ?? '', PATHINFO_EXTENSION));
        $previewType = ($docExt === 'pdf' || str_contains(strtolower($matchedDoc['file_mime'] ?? ''), 'pdf')) ? 'pdf' : 'image';
        $previewFileName = $matchedDoc['file_name'] ?? 'Dokumen Resmi';
    }
@endphp

<div style="background:linear-gradient(180deg,#f8fafc 0%,#eff6ff 60%,#f8fafc 100%);min-height:85vh;padding:40px 0 80px">
    <div class="max-w-2xl mx-auto px-4">
        
        <!-- Breadcrumb Navigation -->
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#64748b;margin-bottom:20px;font-weight:600">
            <a href="{{ route('verify.index') }}" style="color:#2563eb;text-decoration:none;display:inline-flex;align-items:center;gap:4px" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                <i data-lucide="shield-check" style="width:14px;height:14px"></i>
                Verifikasi Dokumen
            </a>
            <i data-lucide="chevron-right" style="width:14px;height:14px;color:#94a3b8"></i>
            <span style="color:#0f172a;font-weight:700">Hasil Verifikasi</span>
        </div>

        @if($isValid)
            <!-- ─── HASIL: DOKUMEN ASLI ─── -->
            <div style="background:linear-gradient(180deg,#f0fdf4 0%,#ffffff 100%);border:2px solid #86efac;border-radius:24px;padding:40px 28px;margin-bottom:24px;text-align:center;box-shadow:0 16px 40px rgba(16,185,129,0.12);position:relative;overflow:hidden">
                <div style="position:absolute;top:0;left:0;right:0;height:6px;background:linear-gradient(90deg,#10b981 0%,#34d399 50%,#059669 100%)"></div>
                
                <div style="width:84px;height:84px;background:#dcfce7;border:2.5px solid #86efac;border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;color:#16a34a;box-shadow:0 10px 24px rgba(22,163,74,0.22);animation:pulse 2s infinite">
                    <i data-lucide="shield-check" style="width:48px;height:48px"></i>
                </div>

                <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 14px;border-radius:999px;background:#16a34a;color:#ffffff;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px">
                    <i data-lucide="check-circle-2" style="width:13px;height:13px"></i>
                    STATUS KEASLIAN
                </div>

                <h1 style="font-size:26px;font-weight:900;color:#065f46;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 10px;letter-spacing:-0.5px">
                    DOKUMEN ASLI &amp; RESMI
                </h1>
                <p style="font-size:14px;color:#047857;max-width:480px;margin:0 auto 18px;line-height:1.6;font-weight:600">
                    Dokumen ini terdaftar sah di SMK Plus Melati.
                </p>
                
                <div style="display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap">
                    @if($matchedDoc)
                        @php
                            $meta = \App\Models\Document::TYPE_META[$matchedDoc['document_type']] ?? \App\Models\Document::TYPE_META['Dokumen Lainnya'];
                        @endphp
                        <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:12.5px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif">
                            <span>{{ $meta['emoji'] }}</span>
                            <span>{{ $matchedDoc['document_type'] }}</span>
                        </div>
                    @endif

                    @if(!empty($isScan))
                        <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:999px;background:#fef3c7;border:1px solid #fde68a;color:#92400e;font-size:12px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif">
                            <i data-lucide="qr-code" style="width:14px;height:14px"></i>
                            <span>Terverifikasi via Scan Barcode</span>
                        </div>
                    @endif

                    @if($hasPreviewFile && !empty($previewUrl))
                        <button type="button" onclick="openFullscreenPreview()"
                                style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:999px;background:#16a34a;border:1px solid #15803d;color:#ffffff;font-size:12.5px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;box-shadow:0 3px 10px rgba(22,163,74,0.28);transition:all .2s ease"
                                onmouseover="this.style.background='#15803d';this.style.transform='translateY(-1px)';this.style.boxShadow='0 5px 14px rgba(22,163,74,0.35)'"
                                onmouseout="this.style.background='#16a34a';this.style.transform='translateY(0)';this.style.boxShadow='0 3px 10px rgba(22,163,74,0.28)'"
                                title="Lihat Pratinjau Dokumen">
                            <i data-lucide="eye" style="width:14px;height:14px"></i>
                            <span>Pratinjau Dokumen</span>
                        </button>
                    @endif
                </div>
                
                <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px;font-size:11.5px;font-family:'JetBrains Mono',monospace;color:#15803d;background:#f0fdf4;border:1px solid #86efac;padding:6px 16px;border-radius:999px;width:fit-content;margin-left:auto;margin-right:auto">
                    <i data-lucide="clock" style="width:14px;height:14px"></i>
                    <span>Diverifikasi: {{ now()->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</span>
                </div>
            </div>

            {{-- Metadata Dokumen Card --}}
            @if($matchedDoc)
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;padding:24px;margin-bottom:20px;box-shadow:0 4px 20px rgba(15,23,42,0.04)">
                    <h2 style="font-size:15px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 16px;display:flex;align-items:center;gap:8px">
                        <i data-lucide="file-text" style="width:18px;height:18px;color:#2563eb"></i> Rincian Metadata Dokumen Resmi
                    </h2>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:12px">
                        @php
                            $rows = [
                                ['Status Keaslian', 'DOKUMEN ASLI (RESMI)'],
                                ['Jenis Dokumen', $matchedDoc['document_type']],
                                ['Nama Pemilik', $matchedDoc['owner_name']],
                                ['Nomor Dokumen', $matchedDoc['document_number']],
                                ['NIS / NISN / NIM', $matchedDoc['nisn'] ?: '—'],
                                ['Tahun Penerbitan', $matchedDoc['year']],
                                ['Status Ledger', $matchedDoc['status']],
                                ['Waktu Registrasi', \Carbon\Carbon::parse($matchedDoc['created_at'])->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') . ' WIB'],
                                ['TX ID Blockchain', substr($matchedDoc['blockchain_tx_id'], 0, 22) . '...']
                            ];
                        @endphp
                        @foreach($rows as $row)
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px">
                                <div style="font-size:10px;font-weight:700;color:{{ $row[0] === 'Status Keaslian' ? '#16a34a' : '#2563eb' }};text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;font-family:'JetBrains Mono',monospace">{{ $row[0] }}</div>
                                <div style="font-size:13.5px;font-weight:700;color:{{ $row[0] === 'Status Keaslian' ? '#16a34a' : '#0f172a' }};font-family:'Plus Jakarta Sans',sans-serif;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $row[1] }}">{{ $row[1] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Hash Comparison Box --}}
            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;padding:24px;margin-bottom:20px;box-shadow:0 4px 20px rgba(15,23,42,0.04)">
                <h2 style="font-size:15px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 16px;display:flex;align-items:center;gap:8px">
                    <i data-lucide="fingerprint" style="width:18px;height:18px;color:#2563eb"></i> Integritas Kriptografi Blockchain
                </h2>
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px">
                        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;font-family:'JetBrains Mono',monospace">Hash Dokumen Resmi (SHA-256)</div>
                        <code style="font-size:11.5px;font-family:'JetBrains Mono',monospace;color:#0f172a;word-break:break-all;user-select:all;font-weight:600">{{ $hash }}</code>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:12px 14px">
                        <i data-lucide="check-circle-2" style="width:18px;height:18px;color:#16a34a;flex-shrink:0"></i>
                        <span style="font-size:12.5px;font-weight:600;color:#15803d">Status Sah — Dokumen ini tercatat permanen di jaringan Hyperledger Fabric Blockchain dan tidak dapat dimanipulasi.</span>
                    </div>
                </div>
            </div>

            {{-- Privacy Info Alert --}}
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:14px 16px;margin-bottom:24px;display:flex;align-items:flex-start;gap:10px">
                <i data-lucide="alert-circle" style="width:18px;height:18px;color:#d97706;flex-shrink:0;margin-top:1px"></i>
                <p style="font-size:12px;color:#92400e;line-height:1.5;margin:0;font-weight:500">
                    Sistem verifikasi publik ini memvalidasi keaslian berkas secara terbuka melalui pratinjau digital sah. Hak unduh berkas arsip internal tetap dilindungi demi privasi data siswa.
                </p>
            </div>

            {{-- Action Buttons --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <a href="{{ route('verify.index') }}"
                   style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;font-weight:700;font-size:13.5px;border-radius:12px;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 4px 14px rgba(37,99,235,0.25)">
                    <i data-lucide="refresh-cw" style="width:16px;height:16px"></i> Verifikasi Dokumen Lain
                </a>
            </div>

        @elseif($isPending ?? false)
            <!-- ─── HASIL: DOKUMEN MENUNGGU VALIDASI RESMI ─── -->
            <div style="background:linear-gradient(180deg,#fffbeb 0%,#ffffff 100%);border:2px solid #fcd34d;border-radius:24px;padding:40px 28px;margin-bottom:24px;text-align:center;box-shadow:0 16px 40px rgba(245,158,11,0.12);position:relative;overflow:hidden">
                <div style="position:absolute;top:0;left:0;right:0;height:6px;background:linear-gradient(90deg,#f59e0b 0%,#fbbf24 50%,#d97706 100%)"></div>
                
                <div style="width:84px;height:84px;background:#fef3c7;border:2.5px solid #fcd34d;border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;color:#d97706;box-shadow:0 10px 24px rgba(217,119,6,0.22);animation:pulse 2s infinite">
                    <i data-lucide="clock" style="width:48px;height:48px"></i>
                </div>

                <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 14px;border-radius:999px;background:#d97706;color:#ffffff;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px">
                    <i data-lucide="alert-circle" style="width:13px;height:13px"></i>
                    STATUS PENDAFTARAN
                </div>

                <h1 style="font-size:26px;font-weight:900;color:#92400e;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 10px;letter-spacing:-0.5px">
                    MENUNGGU VALIDASI RESMI
                </h1>
                <p style="font-size:14px;color:#b45309;max-width:520px;margin:0 auto 18px;line-height:1.6;font-weight:600">
                    Dokumen ini terdaftar di buku besar sistem, namun <strong>belum disahkan atau ditandatangani resmi</strong> oleh Kepala Sekolah. Belum berlaku sebagai dokumen legal sah final.
                </p>
                
                <div style="display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap">
                    @if($matchedDoc)
                        @php
                            $meta = \App\Models\Document::TYPE_META[$matchedDoc['document_type']] ?? \App\Models\Document::TYPE_META['Dokumen Lainnya'];
                        @endphp
                        <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:999px;background:#fef3c7;border:1px solid #fde68a;color:#92400e;font-size:12.5px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif">
                            <span>{{ $meta['emoji'] }}</span>
                            <span>{{ $matchedDoc['document_type'] }}</span>
                        </div>
                    @endif

                    @if(!empty($isScan))
                        <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:999px;background:#fef3c7;border:1px solid #fde68a;color:#92400e;font-size:12px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif">
                            <i data-lucide="qr-code" style="width:14px;height:14px"></i>
                            <span>Dipindai via Scan Barcode</span>
                        </div>
                    @endif

                    @if($hasPreviewFile && !empty($previewUrl))
                        <button type="button" onclick="openFullscreenPreview()"
                                style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:999px;background:#d97706;border:1px solid #b45309;color:#ffffff;font-size:12.5px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;box-shadow:0 3px 10px rgba(217,119,6,0.28);transition:all .2s ease"
                                onmouseover="this.style.background='#b45309';this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.background='#d97706';this.style.transform='translateY(0)'"
                                title="Lihat Pratinjau Dokumen">
                            <i data-lucide="eye" style="width:14px;height:14px"></i>
                            <span>Pratinjau Dokumen</span>
                        </button>
                    @endif
                </div>
                
                <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px;font-size:11.5px;font-family:'JetBrains Mono',monospace;color:#92400e;background:#fef3c7;border:1px solid #fde68a;padding:6px 16px;border-radius:999px;width:fit-content;margin-left:auto;margin-right:auto">
                    <i data-lucide="clock" style="width:14px;height:14px"></i>
                    <span>Diverifikasi: {{ now()->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</span>
                </div>
            </div>

            {{-- Metadata Dokumen Card --}}
            @if($matchedDoc)
                <div style="background:#ffffff;border:1px solid #fcd34d;border-radius:18px;padding:24px;margin-bottom:20px;box-shadow:0 4px 20px rgba(15,23,42,0.04)">
                    <h2 style="font-size:15px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 16px;display:flex;align-items:center;gap:8px">
                        <i data-lucide="file-text" style="width:18px;height:18px;color:#d97706"></i> Rincian Metadata Dokumen (Draft Terdaftar)
                    </h2>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:12px">
                        @php
                            $pendingRows = [
                                ['Status Keaslian', 'MENUNGGU PERSETUJUAN RESMI'],
                                ['Jenis Dokumen', $matchedDoc['document_type']],
                                ['Nama Pemilik', $matchedDoc['owner_name']],
                                ['Nomor Dokumen', $matchedDoc['document_number']],
                                ['NIS / NISN / NIM', $matchedDoc['nisn'] ?: '—'],
                                ['Tahun Penerbitan', $matchedDoc['year']],
                                ['Status Ledger', 'Menunggu Verifikasi'],
                                ['Waktu Registrasi', \Carbon\Carbon::parse($matchedDoc['created_at'])->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') . ' WIB'],
                                ['TX ID Blockchain', substr($matchedDoc['blockchain_tx_id'] ?: 'Pending Minting', 0, 22) . '...']
                            ];
                        @endphp
                        @foreach($pendingRows as $row)
                            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:12px 14px">
                                <div style="font-size:10px;font-weight:700;color:{{ $row[0] === 'Status Keaslian' ? '#d97706' : '#b45309' }};text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;font-family:'JetBrains Mono',monospace">{{ $row[0] }}</div>
                                <div style="font-size:13.5px;font-weight:700;color:{{ $row[0] === 'Status Keaslian' ? '#b45309' : '#0f172a' }};font-family:'Plus Jakarta Sans',sans-serif;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $row[1] }}">{{ $row[1] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Hash Comparison Box --}}
            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;padding:24px;margin-bottom:20px;box-shadow:0 4px 20px rgba(15,23,42,0.04)">
                <h2 style="font-size:15px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 16px;display:flex;align-items:center;gap:8px">
                    <i data-lucide="fingerprint" style="width:18px;height:18px;color:#d97706"></i> Integritas Kriptografi Blockchain
                </h2>
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px">
                        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;margin-bottom:3px;font-family:'JetBrains Mono',monospace">Hash Dokumen Terdaftar (SHA-256)</div>
                        <code style="font-size:11.5px;font-family:'JetBrains Mono',monospace;color:#0f172a;word-break:break-all;user-select:all;font-weight:600">{{ $hash }}</code>
                    </div>
                </div>
            </div>

            {{-- Warning Alert --}}
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:14px 16px;margin-bottom:24px;display:flex;align-items:flex-start;gap:10px">
                <i data-lucide="alert-triangle" style="width:18px;height:18px;color:#d97706;flex-shrink:0;margin-top:1px"></i>
                <p style="font-size:12px;color:#92400e;line-height:1.5;margin:0;font-weight:500">
                    Dokumen ini belum diverifikasi final oleh Administrator/Kepala Sekolah SMK Plus Melati. Hubungi pihak sekolah jika berkas ini seharusnya sudah disahkan secara resmi.
                </p>
            </div>

            {{-- Action Buttons --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <a href="{{ route('verify.index') }}"
                   style="flex:1;min-width:200px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:linear-gradient(135deg,#d97706,#b45309);color:#ffffff;font-weight:700;font-size:13.5px;border-radius:12px;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 4px 14px rgba(217,119,6,0.25)">
                    <i data-lucide="refresh-cw" style="width:16px;height:16px"></i> Verifikasi Dokumen Lain
                </a>
                <a href="mailto:info@smkplusmelati.sch.id"
                   style="flex:1;min-width:180px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:#ffffff;color:#b45309;font-weight:700;font-size:13.5px;border-radius:12px;border:1px solid #fcd34d;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif">
                    <i data-lucide="mail" style="width:16px;height:16px"></i> Hubungi Admin Sekolah
                </a>
            </div>

        @else
            <!-- ─── HASIL: DOKUMEN PALSU / TIDAK VALID ─── -->
            <div style="background:linear-gradient(180deg,#fff1f2 0%,#ffffff 100%);border:2.5px solid #fca5a5;border-radius:24px;padding:40px 28px;margin-bottom:24px;text-align:center;box-shadow:0 16px 40px rgba(244,63,94,0.14);position:relative;overflow:hidden">
                <div style="position:absolute;top:0;left:0;right:0;height:6px;background:linear-gradient(90deg,#fb7185 0%,#f43f5e 50%,#e11d48 100%)"></div>
                
                <div style="width:84px;height:84px;background:#fee2e2;border:2.5px solid #fca5a5;border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;color:#dc2626;box-shadow:0 10px 24px rgba(220,38,38,0.2)">
                    <i data-lucide="alert-octagon" style="width:48px;height:48px"></i>
                </div>

                <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 14px;border-radius:999px;background:#dc2626;color:#ffffff;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px">
                    <i data-lucide="x-circle" style="width:13px;height:13px"></i>
                    PERINGATAN KEASLIAN
                </div>

                <h1 style="font-size:26px;font-weight:900;color:#9f1239;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 10px;letter-spacing:-0.5px">
                    DOKUMEN PALSU / TIDAK TERDAFTAR
                </h1>
                <p style="font-size:14px;color:#be123c;max-width:500px;margin:0 auto;line-height:1.6;font-weight:600">
                    Dokumen/Barcode tidak ditemukan atau telah dimodifikasi.
                </p>
            </div>

            {{-- Pratinjau Dokumen yang Diperiksa (jika ada file diunggah) --}}
            @if($hasPreviewFile && !empty($previewUrl))
                <div style="background:#ffffff;border:1px solid #fecdd3;border-radius:18px;padding:22px;margin-bottom:20px;box-shadow:0 4px 20px rgba(15,23,42,0.04)">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
                        <h2 style="font-size:15px;font-weight:800;color:#9f1239;font-family:'Plus Jakarta Sans',sans-serif;margin:0;display:flex;align-items:center;gap:8px">
                            <i data-lucide="file-warning" style="width:18px;height:18px;color:#e11d48"></i> Pratinjau Berkas yang Diperiksa
                        </h2>
                        <a href="{{ $previewUrl }}" target="_blank"
                           style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:#fff1f2;border:1px solid #fecdd3;color:#be123c;font-size:12px;font-weight:700;text-decoration:none">
                            <i data-lucide="external-link" style="width:13px;height:13px"></i> Buka File
                        </a>
                    </div>
                    <div style="background:#0f172a08;border:1px solid #fecdd3;border-radius:12px;padding:10px;text-align:center">
                        @if($previewType === 'pdf')
                            <div style="width:100%;height:450px;border-radius:8px;overflow:hidden;background:#ffffff">
                                <iframe src="{{ $previewUrl }}" style="width:100%;height:100%;border:none"></iframe>
                            </div>
                        @else
                            <img src="{{ $previewUrl }}" alt="Dokumen Tidak Sah" style="max-width:100%;max-height:450px;object-fit:contain;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06);margin:0 auto;display:block">
                        @endif
                    </div>
                </div>
            @endif

            {{-- Reasons Card --}}
            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;padding:24px;margin-bottom:20px;box-shadow:0 4px 20px rgba(15,23,42,0.04)">
                <h2 style="font-size:15px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 14px;display:flex;align-items:center;gap:8px">
                    <i data-lucide="alert-triangle" style="width:18px;height:18px;color:#e11d48"></i> Indikasi Dokumen Tidak Sah
                </h2>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fff1f2;border:1px solid #fecdd3;border-radius:12px">
                        <i data-lucide="x-circle" style="width:16px;height:16px;color:#e11d48;flex-shrink:0;margin-top:2px"></i>
                        <span style="font-size:12.5px;color:#9f1239;line-height:1.5"><strong>Modifikasi / Pemalsuan Teks:</strong> Berkas fisik atau digital kemungkinan telah diedit nilainya, namanya, nomor serinya, atau dicetak ulang tanpa wewenang pihak sekolah.</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fff1f2;border:1px solid #fecdd3;border-radius:12px">
                        <i data-lucide="x-circle" style="width:16px;height:16px;color:#e11d48;flex-shrink:0;margin-top:2px"></i>
                        <span style="font-size:12.5px;color:#9f1239;line-height:1.5"><strong>Barcode Tidak Valid / Tidak Terdaftar:</strong> Barcode yang di-scan bukan merupakan barcode resmi yang diterbitkan oleh sistem manajemen dokumen terakreditasi SMK Plus Melati.</span>
                    </div>
                </div>
            </div>

            {{-- OCR Extracted Data if available --}}
            @if(isset($ocr['metadata']))
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;padding:24px;margin-bottom:20px;box-shadow:0 4px 20px rgba(15,23,42,0.04)">
                    <h2 style="font-size:15px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 14px;display:flex;align-items:center;gap:8px">
                        <i data-lucide="scan" style="width:18px;height:18px;color:#2563eb"></i> Hasil Analisis AI OCR Berkas
                    </h2>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:10px">
                        @php
                            $extracted = [
                                ['Jenis Terdeteksi', $ocr['metadata']['documentType'] ?? '—'],
                                ['Nama Pemilik', $ocr['metadata']['ownerName'] ?? '—'],
                                ['Nomor Dokumen', $ocr['metadata']['documentNumber'] ?? '—'],
                                ['Tahun Terdeteksi', $ocr['metadata']['year'] ?? '—']
                            ];
                        @endphp
                        @foreach($extracted as $item)
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px">
                                <div style="font-size:10px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;font-family:'JetBrains Mono',monospace">{{ $item[0] }}</div>
                                <div style="font-size:13px;font-weight:700;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item[1] ?: '—' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <a href="{{ route('verify.index') }}"
                   style="flex:1;min-width:200px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;font-weight:700;font-size:13.5px;border-radius:12px;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 4px 14px rgba(37,99,235,0.25)">
                    <i data-lucide="refresh-cw" style="width:16px;height:16px"></i> Coba Verifikasi Ulang
                </a>
                <a href="mailto:info@smkplusmelati.sch.id"
                   style="flex:1;min-width:180px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:#ffffff;color:#991b1b;font-weight:700;font-size:13.5px;border-radius:12px;border:1px solid #fca5a5;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif">
                    <i data-lucide="mail" style="width:16px;height:16px"></i> Hubungi Admin Sekolah
                </a>
            </div>
        @endif

    </div>
</div>

{{-- ─── FULLSCREEN LIGHTBOX MODAL ─── --}}
@if($hasPreviewFile && !empty($previewUrl))
<div id="previewModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.92);backdrop-filter:blur(8px);z-index:99999;flex-direction:column;align-items:center;justify-content:center;padding:16px" onclick="if(event.target===this) closeFullscreenPreview()">
    <div style="position:absolute;top:16px;left:20px;right:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;z-index:100000;pointer-events:none">
        <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);padding:7px 14px;border-radius:10px;color:#ffffff;pointer-events:auto;max-width:55%;overflow:hidden">
            <i data-lucide="file-check" style="width:16px;height:16px;color:#4ade80;flex-shrink:0"></i>
            <span style="font-size:12.5px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                {{ $previewFileName }}
            </span>
            <span style="font-size:10px;font-weight:800;background:#15803d;color:#dcfce7;padding:2px 8px;border-radius:999px;text-transform:uppercase;letter-spacing:0.04em;flex-shrink:0">
                Salinan Sah
            </span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;pointer-events:auto;flex-shrink:0">
            <a href="{{ $previewUrl }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:10px;color:#ffffff;font-size:12.5px;font-weight:700;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif;transition:background .2s"
               onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <i data-lucide="external-link" style="width:14px;height:14px"></i>
                <span class="hidden sm:inline">Buka di Tab Baru</span>
            </a>
            <button type="button" onclick="closeFullscreenPreview()"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#ef4444;border:none;border-radius:10px;color:#ffffff;font-size:12.5px;font-weight:800;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;transition:background .2s"
                    onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                <i data-lucide="x" style="width:16px;height:16px"></i>
                <span>Tutup</span>
            </button>
        </div>
    </div>
    
    <div style="width:96vw;max-width:1100px;height:calc(88vh - 40px);margin-top:48px;display:flex;align-items:center;justify-content:center;position:relative">
        @if($previewType === 'pdf')
            <iframe src="{{ $previewUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH" style="width:100%;height:100%;border:none;border-radius:14px;background:#ffffff;box-shadow:0 20px 50px rgba(0,0,0,0.5)"></iframe>
        @else
            <img src="{{ $previewUrl }}" alt="{{ $previewFileName }}" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,0.5)">
        @endif
    </div>
</div>

<script>
    function openFullscreenPreview() {
        const modal = document.getElementById('previewModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            if (window.lucide) {
                window.lucide.createIcons();
            }
        }
    }

    function closeFullscreenPreview() {
        const modal = document.getElementById('previewModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeFullscreenPreview();
        }
    });
</script>
@endif

@endsection
