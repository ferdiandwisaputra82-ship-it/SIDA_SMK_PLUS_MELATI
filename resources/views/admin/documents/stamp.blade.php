@extends('layouts.admin')

@section('title', 'Atur Letak Barcode - ' . ($document->owner_name ?: $document->document_number))

@section('content')
<div class="max-w-6xl mx-auto space-y-5 pb-12">
    <!-- Top Header Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.documents') }}" 
               class="w-9 h-9 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors"
               title="Kembali ke Daftar Dokumen">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $document->document_type }}
                    </span>
                    @if($document->isGdriveFile())
                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                            <i data-lucide="cloud" class="w-3 h-3"></i> Google Drive
                        </span>
                    @else
                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-md bg-slate-100 text-slate-600 border border-slate-200">
                            Lokal
                        </span>
                    @endif
                </div>
                <h2 class="text-base md:text-lg font-bold text-slate-900 mt-1 flex items-center gap-2">
                    <i data-lucide="stamp" class="w-5 h-5 text-indigo-600"></i>
                    Atur Posisi Barcode: {{ $document->owner_name ?: $document->document_number }}
                </h2>
            </div>
        </div>

        <!-- Action Button Form -->
        <form id="stampForm" action="{{ route('admin.documents.stamp-barcode', $document->id) }}" method="POST" class="flex items-center gap-2">
            @csrf
            <input type="hidden" name="x_ratio" id="form_x_ratio" value="0.635">
            <input type="hidden" name="y_ratio" id="form_y_ratio" value="0.740">
            <input type="hidden" name="page_num" id="form_page_num" value="1">

            <a href="{{ route('admin.documents') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Batal
            </a>
            <button type="submit" id="submitBtn"
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white text-xs font-bold shadow-md shadow-indigo-500/25 flex items-center gap-2 transition-all cursor-pointer">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span>Selesai & Bubuhi Barcode</span>
            </button>
        </form>
    </div>

    <!-- Instruction and Presets Bar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Control Sidebar (Left) -->
        <div class="lg:col-span-4 space-y-4">
            <!-- Instruction Box -->
            <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-4 text-xs text-indigo-900 leading-relaxed">
                <div class="font-bold flex items-center gap-1.5 text-indigo-800 mb-1.5">
                    <i data-lucide="info" class="w-4 h-4 text-indigo-600"></i>
                    Petunjuk Pengaturan Posisi
                </div>
                <p class="text-indigo-800/90">
                    Geser (<strong>drag</strong>) kotak barcode langsung di atas dokumen, atau <strong>klik</strong> pada titik mana saja di dokumen (misal: tepat di kolom tanda tangan pejabat).
                </p>
            </div>

            <!-- Position Controls & Presets -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="sliders" class="w-4 h-4 text-slate-500"></i>
                    Preset Posisi Barcode
                </h3>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="setPreset(0.635, 0.740)"
                            class="p-2.5 rounded-xl border border-indigo-200 bg-indigo-50/50 hover:bg-indigo-100/60 text-indigo-800 font-semibold flex flex-col items-start gap-1 transition-colors text-left cursor-pointer">
                        <span class="font-bold flex items-center gap-1"><i data-lucide="check" class="w-3 h-3 text-indigo-600"></i> Kanan Bawah</span>
                        <span class="text-[10px] text-slate-500 font-normal">Standar Kolom TTD Surat/Ijazah</span>
                    </button>
                    <button type="button" onclick="setPreset(0.500, 0.800)"
                            class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold flex flex-col items-start gap-1 transition-colors text-left cursor-pointer">
                        <span class="font-bold">Tengah Bawah</span>
                        <span class="text-[10px] text-slate-500 font-normal">Sertifikat / Piagam</span>
                    </button>
                    <button type="button" onclick="setPreset(0.250, 0.740)"
                            class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold flex flex-col items-start gap-1 transition-colors text-left cursor-pointer">
                        <span class="font-bold">Kiri Bawah</span>
                        <span class="text-[10px] text-slate-500 font-normal">TTD Pihak Pertama</span>
                    </button>
                    <button type="button" onclick="setPreset(0.800, 0.150)"
                            class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold flex flex-col items-start gap-1 transition-colors text-left cursor-pointer">
                        <span class="font-bold">Kanan Atas</span>
                        <span class="text-[10px] text-slate-500 font-normal">Header / Barcode Arsip</span>
                    </button>
                </div>

                <!-- Live Coordinate Indicators -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-mono">
                    <span class="text-slate-500">Koordinat Saat Ini:</span>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md font-bold">X: <span id="label_x">63.5%</span></span>
                        <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md font-bold">Y: <span id="label_y">74.0%</span></span>
                    </div>
                </div>

                <!-- QR Size Scale Slider -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Ukuran Barcode</span>
                        <span id="label_size" class="text-slate-500 font-mono text-[11px]">14% Dokumen</span>
                    </div>
                    <input type="range" id="sizeSlider" min="9" max="22" value="14" step="1"
                           class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                </div>

                @if($isPdf)
                <!-- PDF Page Selector -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Halaman Target (PDF)</span>
                        <span id="label_pdf_page" class="text-slate-500 font-mono text-[11px]">Halaman 1</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="prevPageBtn" class="px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold hover:bg-slate-50 text-slate-600 cursor-pointer disabled:opacity-40">
                            &larr; Prev
                        </button>
                        <select id="pdfPageSelect" class="flex-1 px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-semibold outline-none">
                            <option value="1">Halaman 1</option>
                        </select>
                        <button type="button" id="nextPageBtn" class="px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold hover:bg-slate-50 text-slate-600 cursor-pointer disabled:opacity-40">
                            Next &rarr;
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <!-- Document Details Summary -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 text-xs space-y-2 shadow-xs">
                <div class="font-bold text-slate-800">Detail Berkas</div>
                <div class="flex justify-between py-1 border-b border-slate-100 text-slate-600">
                    <span>Nama Berkas:</span>
                    <span class="font-semibold text-slate-800 truncate max-w-[170px]" title="{{ $document->file_name }}">{{ $document->file_name }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100 text-slate-600">
                    <span>Nomor Dokumen:</span>
                    <span class="font-semibold text-slate-800">{{ $document->document_number }}</span>
                </div>
                <div class="flex justify-between py-1 text-slate-600">
                    <span>Pemilik:</span>
                    <span class="font-semibold text-slate-800">{{ $document->owner_name }}</span>
                </div>
            </div>
        </div>

        <!-- Interactive Document Canvas Viewport (Right) -->
        <div class="lg:col-span-8 flex flex-col items-center">
            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="py-16 text-center text-slate-400 space-y-3">
                <div class="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="text-xs font-medium">Memuat pratinjau dokumen...</p>
            </div>

            <!-- Interactive Stage Wrapper -->
            <div id="stageWrapper" class="relative inline-block select-none shadow-xl border border-slate-300 rounded-lg bg-white hidden">
                <!-- Underlay Document: Image or PDF Canvas -->
                @if($isPdf)
                    <canvas id="pdfCanvas" class="block w-full h-auto cursor-crosshair rounded-lg"></canvas>
                @else
                    <img id="docImage" src="{{ route('admin.documents.file', ['document' => $document->id, 'original' => 1]) }}?v={{ time() }}" 
                         alt="Document Preview" 
                         class="block max-w-full h-auto cursor-crosshair rounded-lg">
                @endif

                <!-- Draggable Barcode Box Overlay (Transparan Tanpa Background) -->
                <div id="qrBox" 
                     class="absolute z-20 cursor-grab active:cursor-grabbing border-2 border-dashed border-indigo-600 rounded-lg bg-indigo-50/15 p-0.5 flex flex-col items-center justify-center transition-shadow shadow-md hover:shadow-xl shadow-indigo-950/20"
                     style="width: 70px; height: 70px; transform: translate(-50%, -50%); left: 63.5%; top: 74%;">
                    <!-- QR Code binary lokal base64 transparan (tanpa background putih) -->
                    <img src="data:image/png;base64,{{ App\Services\QrCodeService::generateBase64($verifyUrl, 180) }}"
                         alt="QR" class="w-full h-full object-contain pointer-events-none rounded-xs">
                    <!-- Badge Handle Label -->
                    <div class="absolute -bottom-5 px-1.5 py-0.5 bg-indigo-700 text-[9px] font-bold text-white rounded-md whitespace-nowrap shadow-xs pointer-events-none flex items-center gap-1">
                        BARCODE RESMI BERLOGO
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($isPdf)
<!-- PDF.js via Cloudflare CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stageWrapper   = document.getElementById('stageWrapper');
    const qrBox          = document.getElementById('qrBox');
    const loadingInd     = document.getElementById('loadingIndicator');
    const formXRatio     = document.getElementById('form_x_ratio');
    const formYRatio     = document.getElementById('form_y_ratio');
    const formPageNum    = document.getElementById('form_page_num');
    const labelX         = document.getElementById('label_x');
    const labelY         = document.getElementById('label_y');
    const sizeSlider     = document.getElementById('sizeSlider');
    const labelSize      = document.getElementById('label_size');

    // Default coordinates: Kolom Kanan Bawah (Surat Resmi / Ijazah)
    let currentXRatio = 0.635;
    let currentYRatio = 0.740;
    let currentSizePct = 14; // % of stage width

    function updateBoxPosition(xRatio, yRatio) {
        // Clamp between 0.05 and 0.95 to keep box visible
        xRatio = Math.max(0.04, Math.min(0.96, xRatio));
        yRatio = Math.max(0.04, Math.min(0.96, yRatio));

        currentXRatio = xRatio;
        currentYRatio = yRatio;

        qrBox.style.left = (xRatio * 100).toFixed(2) + '%';
        qrBox.style.top  = (yRatio * 100).toFixed(2) + '%';

        formXRatio.value = xRatio.toFixed(3);
        formYRatio.value = yRatio.toFixed(3);

        labelX.textContent = (xRatio * 100).toFixed(1) + '%';
        labelY.textContent = (yRatio * 100).toFixed(1) + '%';
    }

    function updateBoxSize(sizePct) {
        currentSizePct = sizePct;
        const stageWidth = stageWrapper.offsetWidth;
        const pixelSize = Math.max(45, Math.round(stageWidth * (sizePct / 100)));
        qrBox.style.width  = pixelSize + 'px';
        qrBox.style.height = pixelSize + 'px';
        labelSize.textContent = sizePct + '% Dokumen';
    }

    window.setPreset = function(x, y) {
        updateBoxPosition(x, y);
    };

    sizeSlider.addEventListener('input', function() {
        updateBoxSize(parseInt(this.value, 10));
    });

    // ── Drag & Drop Handling ────────────────────────────────────────────────
    let isDragging = false;
    let hasMoved   = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let initialBoxLeft = 0;
    let initialBoxTop = 0;

    qrBox.addEventListener('mousedown', function(e) {
        e.preventDefault();
        isDragging = true;
        hasMoved   = false;
        dragStartX = e.clientX;
        dragStartY = e.clientY;

        const rect = stageWrapper.getBoundingClientRect();
        initialBoxLeft = (currentXRatio * rect.width);
        initialBoxTop  = (currentYRatio * rect.height);

        document.body.style.userSelect = 'none';
    });

    // Touch support for mobile/tablets
    qrBox.addEventListener('touchstart', function(e) {
        if (e.touches.length === 1) {
            isDragging = true;
            hasMoved   = false;
            dragStartX = e.touches[0].clientX;
            dragStartY = e.touches[0].clientY;
            const rect = stageWrapper.getBoundingClientRect();
            initialBoxLeft = (currentXRatio * rect.width);
            initialBoxTop  = (currentYRatio * rect.height);
        }
    }, { passive: true });

    window.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        if (Math.abs(e.clientX - dragStartX) > 3 || Math.abs(e.clientY - dragStartY) > 3) {
            hasMoved = true;
        }

        const rect = stageWrapper.getBoundingClientRect();
        const deltaX = e.clientX - dragStartX;
        const deltaY = e.clientY - dragStartY;

        const newLeftPx = initialBoxLeft + deltaX;
        const newTopPx  = initialBoxTop + deltaY;

        const newXRatio = newLeftPx / rect.width;
        const newYRatio = newTopPx / rect.height;

        updateBoxPosition(newXRatio, newYRatio);
    });

    window.addEventListener('touchmove', function(e) {
        if (!isDragging || e.touches.length !== 1) return;
        if (Math.abs(e.touches[0].clientX - dragStartX) > 3 || Math.abs(e.touches[0].clientY - dragStartY) > 3) {
            hasMoved = true;
        }

        const rect = stageWrapper.getBoundingClientRect();
        const deltaX = e.touches[0].clientX - dragStartX;
        const deltaY = e.touches[0].clientY - dragStartY;

        const newLeftPx = initialBoxLeft + deltaX;
        const newTopPx  = initialBoxTop + deltaY;

        const newXRatio = newLeftPx / rect.width;
        const newYRatio = newTopPx / rect.height;

        updateBoxPosition(newXRatio, newYRatio);
    }, { passive: true });

    window.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            document.body.style.userSelect = '';
            setTimeout(function() {
                hasMoved = false;
            }, 50);
        }
    });

    window.addEventListener('touchend', function() {
        isDragging = false;
        setTimeout(function() {
            hasMoved = false;
        }, 50);
    });

    // ── Click on stage to instantly reposition QR Box ──────────────────────
    stageWrapper.addEventListener('click', function(e) {
        // If clicking on qrBox itself or just finished dragging, ignore
        if (hasMoved) return;
        if (e.target === qrBox || qrBox.contains(e.target)) return;

        const rect = stageWrapper.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickY = e.clientY - rect.top;

        const xRatio = clickX / rect.width;
        const yRatio = clickY / rect.height;

        updateBoxPosition(xRatio, yRatio);
    });

    // ── Document Loading Logic (PDF vs Image) ──────────────────────────────
    @if($isPdf)
        // PDF Loading via PDF.js
        const pdfUrl = "{{ route('admin.documents.file', ['document' => $document->id, 'original' => 1]) }}?v={{ time() }}";
        const pdfCanvas = document.getElementById('pdfCanvas');
        const ctx = pdfCanvas.getContext('2d');
        const pdfPageSelect = document.getElementById('pdfPageSelect');
        const labelPdfPage = document.getElementById('label_pdf_page');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');

        let pdfDoc = null;
        let currentPageNum = 1;

        function renderPdfPage(num) {
            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({ scale: 1.0 });
                // Target width for comfortable reading & dragging (max ~750px)
                const targetWidth = Math.min(window.innerWidth - 80, 750);
                const scale = targetWidth / viewport.width;
                const scaledViewport = page.getViewport({ scale: scale });

                pdfCanvas.width  = scaledViewport.width;
                pdfCanvas.height = scaledViewport.height;

                const renderContext = {
                    canvasContext: ctx,
                    viewport: scaledViewport
                };

                page.render(renderContext).promise.then(function() {
                    loadingInd.classList.add('hidden');
                    stageWrapper.classList.remove('hidden');
                    updateBoxSize(currentSizePct);
                    updateBoxPosition(currentXRatio, currentYRatio);
                });

                currentPageNum = num;
                formPageNum.value = num;
                pdfPageSelect.value = num;
                labelPdfPage.textContent = 'Halaman ' + num + ' dari ' + pdfDoc.numPages;

                prevPageBtn.disabled = (num <= 1);
                nextPageBtn.disabled = (num >= pdfDoc.numPages);
            });
        }

        pdfjsLib.getDocument(pdfUrl).promise.then(function(doc) {
            pdfDoc = doc;

            // Populate page select dropdown
            pdfPageSelect.innerHTML = '';
            for (let i = 1; i <= pdfDoc.numPages; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = 'Halaman ' + i + (i === pdfDoc.numPages ? ' (Halaman TTD)' : '');
                pdfPageSelect.appendChild(opt);
            }

            // Default to the last page (where signatures are placed in Indonesian official letters)
            renderPdfPage(pdfDoc.numPages);
        }).catch(function(err) {
            loadingInd.innerHTML = '<div class="text-rose-600 font-semibold p-4">Gagal memuat pratinjau PDF: ' + err.message + '</div>';
        });

        pdfPageSelect.addEventListener('change', function() {
            renderPdfPage(parseInt(this.value, 10));
        });
        prevPageBtn.addEventListener('click', function() {
            if (currentPageNum > 1) renderPdfPage(currentPageNum - 1);
        });
        nextPageBtn.addEventListener('click', function() {
            if (currentPageNum < pdfDoc.numPages) renderPdfPage(currentPageNum + 1);
        });

    @else
        // Image Loading
        const docImage = document.getElementById('docImage');
        docImage.onload = function() {
            loadingInd.classList.add('hidden');
            stageWrapper.classList.remove('hidden');
            updateBoxSize(currentSizePct);
            updateBoxPosition(currentXRatio, currentYRatio);
        };
        docImage.onerror = function() {
            loadingInd.innerHTML = '<div class="text-rose-600 font-semibold p-4">Gagal memuat berkas gambar dokumen.</div>';
        };
        // If image already cached
        if (docImage.complete && docImage.naturalHeight !== 0) {
            docImage.onload();
        }
    @endif

    // Initial positioning
    updateBoxPosition(currentXRatio, currentYRatio);

    // Form submit loading state
    const stampForm = document.getElementById('stampForm');
    const submitBtn = document.getElementById('submitBtn');
    if (stampForm && submitBtn) {
        let isSubmitted = false;
        stampForm.addEventListener('submit', function(e) {
            if (isSubmitted) {
                e.preventDefault();
                return;
            }
            isSubmitted = true;
            submitBtn.innerHTML = '<div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div><span>Menyimpan Barcode...</span>';
            submitBtn.classList.add('opacity-80', 'cursor-wait');
        });
    }
});
</script>
@endsection
