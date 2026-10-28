<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Dokumen — {{ $document->file_name ?: $document->document_number }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v=3">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v=3">
    <link rel="apple-touch-icon" href="{{ asset('images/school-logo.png') }}?v=3">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --border: rgba(148,163,184,0.15);
            --cyan: #22d3ee;
            --emerald: #34d399;
            --text: #e2e8f0;
            --muted: #94a3b8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Top Action Bar ── */
        #action-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            background: rgba(15,23,42,0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .bar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .bar-brand .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--emerald);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .bar-brand .label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--cyan);
            font-weight: 600;
        }

        .bar-brand .filename {
            font-size: 12px;
            color: var(--muted);
            font-family: 'JetBrains Mono', monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }

        .sep { width: 1px; height: 24px; background: var(--border); }

        .btn-print {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
        }
        .btn-print:hover { opacity: .9; transform: scale(1.02); }
        .btn-print:active { transform: scale(.98); }

        .btn-close {
            padding: 8px 16px;
            background: var(--surface);
            color: var(--muted);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }
        .btn-close:hover { background: #334155; }

        /* ── Meta Info Strip ── */
        #meta-strip {
            margin-top: 64px;
            padding: 14px 24px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .meta-item .lbl {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .meta-item .val {
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-verified   { background: rgba(52,211,153,.15); color: #34d399; border: 1px solid rgba(52,211,153,.3); }
        .status-pending    { background: rgba(251,191,36,.15);  color: #fbbf24; border: 1px solid rgba(251,191,36,.3); }
        .status-rejected   { background: rgba(248,113,113,.15); color: #f87171; border: 1px solid rgba(248,113,113,.3); }

        /* ── Viewer Area ── */
        #viewer {
            flex: 1;
            display: flex;
            align-items: stretch;
            justify-content: center;
            padding: 24px;
            min-height: calc(100vh - 130px);
        }

        /* PDF iframe */
        #pdf-frame {
            width: 100%;
            max-width: 960px;
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 0 60px rgba(0,0,0,.5);
            min-height: calc(100vh - 180px);
        }

        /* Image viewer */
        #img-viewer {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            width: 100%;
        }

        #doc-image {
            max-width: 960px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 0 60px rgba(0,0,0,.5);
            background: #fff;
        }

        /* No file */
        #no-file {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            color: var(--muted);
            text-align: center;
        }

        #no-file svg { opacity: .4; }
        #no-file h3 { font-size: 18px; font-weight: 700; color: var(--text); }
        #no-file p  { font-size: 13px; line-height: 1.6; }

        /* ── PRINT MEDIA ── */
        @media print {
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            #action-bar,
            #meta-strip {
                display: none !important;
            }

            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            #viewer {
                padding: 0 !important;
                min-height: auto !important;
            }

            #pdf-frame {
                width: 100vw !important;
                max-width: 100% !important;
                min-height: 100vh !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            #doc-image {
                max-width: 100% !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- ── Top Action Bar ── -->
    <div id="action-bar">
        <div class="bar-brand">
            <span class="dot"></span>
            <span class="label">SIDA · Cetak Dokumen</span>
            <span class="sep"></span>
            <span class="filename">{{ $document->file_name ?: 'Dokumen #' . $document->id }}</span>
        </div>

        @if($document->file_path && $hasFile)
        <button class="btn-print" onclick="doPrint()">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak Dokumen
        </button>
        @endif

        <button class="btn-close" onclick="window.close()">✕ Tutup</button>
    </div>

    <!-- ── Meta Info Strip ── -->
    <div id="meta-strip">
        <div class="meta-item">
            <span class="lbl">Pemilik</span>
            <span class="val">{{ $document->owner_name }}</span>
        </div>
        <div class="sep"></div>
        <div class="meta-item">
            <span class="lbl">No. Dokumen</span>
            <span class="val" style="font-family:'JetBrains Mono',monospace">{{ $document->document_number }}</span>
        </div>
        <div class="sep"></div>
        <div class="meta-item">
            <span class="lbl">Jenis</span>
            <span class="val">{{ $meta['emoji'] }} {{ $document->document_type }}</span>
        </div>
        <div class="sep"></div>
        <div class="meta-item">
            <span class="lbl">Status</span>
            @php
                $statusClass = match($document->status) {
                    'Terverifikasi'       => 'status-verified',
                    'Menunggu Verifikasi' => 'status-pending',
                    default               => 'status-rejected',
                };
            @endphp
            <span class="status-badge {{ $statusClass }}">
                @if($document->status === 'Terverifikasi') ✓
                @elseif($document->status === 'Menunggu Verifikasi') ⏳
                @else ✕ @endif
                {{ $document->status }}
            </span>
        </div>
        <div class="sep"></div>
        <div class="meta-item">
            <span class="lbl">Tahun</span>
            <span class="val">{{ $document->year }}</span>
        </div>
        <div class="sep"></div>
        <div class="meta-item" style="margin-left:auto">
            <a href="{{ route('verify.hash', $document->blockchain_tx_id ?: $document->hash) }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:rgba(37,99,235,0.2);border:1px solid #3b82f6;border-radius:8px;color:#93c5fd;text-decoration:none;font-size:11.5px;font-weight:700">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                Pindai / Cek Keaslian Barcode
            </a>
        </div>
    </div>

    <!-- ── Viewer ── -->
    <div id="viewer">
        @if(!$document->file_path || !$hasFile)
            {{-- No file available --}}
            <div id="no-file">
                <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3>File Tidak Tersedia</h3>
                <p>Dokumen ini tidak memiliki file yang dapat ditampilkan.<br>
                   Silakan upload ulang file melalui menu Edit Dokumen.</p>
            </div>

        @elseif($isPdf)
            {{-- PDF Viewer --}}
            <iframe
                id="pdf-frame"
                src="{{ route('admin.documents.file', $document) }}#toolbar=1&view=FitH"
                title="PDF Viewer — {{ $document->file_name }}">
            </iframe>

        @else
            {{-- Image Viewer --}}
            <div id="img-viewer">
                <img
                    id="doc-image"
                    src="{{ route('admin.documents.file', $document) }}"
                    alt="{{ $document->file_name }}"
                    onload="imageLoaded()"
                    onerror="imageError()">
            </div>
        @endif
    </div>

    <script>
        @if($isPdf)
        // For PDF: print via iframe after it loads
        function doPrint() {
            const frame = document.getElementById('pdf-frame');
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch(e) {
                // Cross-origin fallback: open in new tab
                window.open('{{ route('admin.documents.file', $document) }}', '_blank');
            }
        }

        // Auto-print after PDF loads
        document.getElementById('pdf-frame').addEventListener('load', function() {
            setTimeout(doPrint, 500);
        });

        @else
        // For image: print the page (image fills the print area via CSS)
        function doPrint() {
            window.print();
        }

        function imageLoaded() {
            setTimeout(doPrint, 600);
        }

        function imageError() {
            const v = document.getElementById('img-viewer');
            v.innerHTML = `
                <div id="no-file">
                    <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3>Gagal Memuat Gambar</h3>
                    <p>File tidak dapat ditampilkan. Coba download ulang.</p>
                </div>`;
        }
        @endif
    </script>

</body>
</html>
