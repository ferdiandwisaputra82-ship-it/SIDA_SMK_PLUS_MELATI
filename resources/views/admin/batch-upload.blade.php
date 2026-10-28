@extends('layouts.admin')

@section('title', 'Batch ZIP Upload')
@section('subtitle', 'Upload satu file ZIP berisi banyak berkas — ekstraksi OCR & pendaftaran blockchain otomatis')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- ── Upload Card ──────────────────────────────────────────────────────────── --}}
    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden" id="upload-card">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center shadow-xs">
                    <i data-lucide="archive" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="font-heading font-bold text-slate-800 text-sm">Unggah File ZIP</h2>
                    <p class="text-[11px] text-slate-500">Ekstraksi berkas dan proses OCR otomatis</p>
                </div>
            </div>
            <span class="text-xs text-blue-700 font-mono bg-blue-50 border border-blue-200/80 px-3 py-1 rounded-full font-semibold">Format: .zip | Maks: 100 MB</span>
        </div>

        <div class="p-6">
            {{-- Drop Zone --}}
            <div id="drop-zone"
                 class="relative border-2 border-dashed border-blue-200/90 hover:border-blue-400 bg-slate-50/60 hover:bg-blue-50/30 rounded-2xl transition-all duration-200 cursor-pointer min-h-[220px] flex items-center justify-center p-6 group">

                <input type="file" id="zip-input" accept=".zip,application/zip"
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                {{-- Empty State Content --}}
                <div id="drop-content" class="flex flex-col items-center justify-center text-center gap-2.5 pointer-events-none py-4">
                    <div class="w-14 h-14 rounded-2xl bg-blue-100/80 border border-blue-200 text-blue-600 flex items-center justify-center group-hover:scale-105 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200 shadow-xs mb-1">
                        <i data-lucide="archive" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <p class="font-heading font-bold text-slate-800 text-sm md:text-base">Tarik & lepaskan file ZIP ke sini</p>
                        <p class="text-xs text-slate-500 mt-1">atau klik untuk memilih arsip berkas dari komputer Anda</p>
                    </div>
                    <div class="flex items-center justify-center gap-1.5 flex-wrap pt-2">
                        <span class="text-[11px] font-medium text-slate-400 mr-1">Mendukung berkas dalam ZIP:</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 border border-blue-200/60 text-blue-700 font-mono">.pdf</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 border border-blue-200/60 text-blue-700 font-mono">.jpg</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 border border-blue-200/60 text-blue-700 font-mono">.jpeg</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 border border-blue-200/60 text-blue-700 font-mono">.png</span>
                    </div>
                </div>

                {{-- File Selected Preview --}}
                <div id="file-selected" style="display: none;" class="flex-col items-center justify-center text-center gap-3 py-4 w-full max-w-md">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-600 flex items-center justify-center shadow-xs mx-auto mb-1">
                        <i data-lucide="check-circle-2" class="w-7 h-7"></i>
                    </div>
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-slate-800 shadow-2xs">
                            <i data-lucide="file-archive" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                            <p id="file-name-preview" class="font-heading font-bold text-slate-800 text-sm truncate max-w-[280px] sm:max-w-xs"></p>
                        </div>
                        <p id="file-size-preview" class="text-xs text-emerald-700 font-mono font-medium pt-0.5"></p>
                    </div>
                    <div class="pt-2 relative z-20">
                        <button id="clear-file-btn" type="button"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-red-600 bg-white border border-slate-200 hover:border-red-200 hover:bg-red-50 transition-all shadow-xs cursor-pointer">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            Ganti file ZIP
                        </button>
                    </div>
                </div>
            </div>

            {{-- Process Button --}}
            <button id="process-btn" type="button" disabled
                    class="mt-4 w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-bold font-heading text-sm text-slate-400 bg-slate-100 border border-slate-200 shadow-none cursor-not-allowed transition-all duration-200">
                <i data-lucide="zap" class="w-4 h-4"></i>
                Mulai Proses Batch OCR + Blockchain
            </button>
        </div>
    </div>

    {{-- ── Progress Card ─────────────────────────────────────────────── --}}
    <div id="progress-card" class="hidden bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/70">
            <div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin flex-shrink-0"></div>
            <span class="font-heading font-bold text-slate-800 text-sm">Sedang Memproses Arsip...</span>
            <span id="progress-detail" class="ml-auto text-xs text-slate-500 font-medium">Mengekstrak ZIP &amp; menjalankan OCR</span>
        </div>
        <div class="p-6 flex flex-col gap-4">
            <div class="bg-slate-100 rounded-full h-2.5 overflow-hidden">
                <div id="progress-bar" class="h-full bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full w-0 transition-all duration-700"></div>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span>File diproses satu per satu secara berurutan — mohon jangan menutup halaman ini...</span>
                <span id="progress-pct" class="font-mono font-bold text-blue-600 text-sm">0%</span>
            </div>

            <div class="grid grid-cols-3 gap-3 mt-1">
                <div class="flex flex-col items-center gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <i data-lucide="archive" class="w-5 h-5 text-blue-600"></i>
                    <span class="text-xs text-slate-700 font-medium">1. Ekstrak ZIP</span>
                </div>
                <div class="flex flex-col items-center gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <i data-lucide="scan-eye" class="w-5 h-5 text-indigo-600"></i>
                    <span class="text-xs text-slate-700 font-medium">2. AI OCR Vision</span>
                </div>
                <div class="flex flex-col items-center gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <i data-lucide="link-2" class="w-5 h-5 text-emerald-600"></i>
                    <span class="text-xs text-slate-700 font-medium">3. Catat Blockchain</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Result Card ────────────────────────────────────────────────── --}}
    <div id="result-card" class="hidden space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="summary-grid">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
                <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-800 font-heading leading-tight" id="stat-total">0</p>
                    <p class="text-xs text-slate-500 font-medium">Total File</p>
                </div>
            </div>
            <div class="bg-white border border-emerald-200 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-emerald-600 font-heading leading-tight" id="stat-success">0</p>
                    <p class="text-xs text-slate-500 font-medium">Berhasil Diproses</p>
                </div>
            </div>
            <div class="bg-white border border-rose-200 rounded-2xl p-4 flex items-center gap-3.5 shadow-xs">
                <div class="w-11 h-11 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-rose-600 font-heading leading-tight" id="stat-failed">0</p>
                    <p class="text-xs text-slate-500 font-medium">Gagal Diproses</p>
                </div>
            </div>
        </div>

        {{-- Detail Table --}}
        <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-xs">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <div class="flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-blue-600"></i>
                    <span class="font-heading font-bold text-slate-800 text-sm">Laporan Detail Per File</span>
                </div>
                <div class="flex items-center gap-2">
                    <button id="copy-json-btn" type="button"
                            class="inline-flex items-center gap-1.5 text-xs text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 px-3 py-1.5 rounded-lg font-semibold cursor-pointer shadow-xs transition-colors">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i> Salin JSON
                    </button>
                    <button id="new-batch-btn" type="button"
                            class="inline-flex items-center gap-1.5 text-xs text-white bg-blue-600 hover:bg-blue-700 px-3.5 py-1.5 rounded-lg font-bold font-heading cursor-pointer shadow-xs transition-colors">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Upload Batch Baru
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-200 text-left">
                            <th class="text-[11px] font-bold text-slate-500 uppercase tracking-wider py-3 px-4 w-10">#</th>
                            <th class="text-[11px] font-bold text-slate-500 uppercase tracking-wider py-3 px-4">Status</th>
                            <th class="text-[11px] font-bold text-slate-500 uppercase tracking-wider py-3 px-4">Nama File</th>
                            <th class="text-[11px] font-bold text-slate-500 uppercase tracking-wider py-3 px-4">Pemilik / Jenis</th>
                            <th class="text-[11px] font-bold text-slate-500 uppercase tracking-wider py-3 px-4">Transaction Hash</th>
                            <th class="text-[11px] font-bold text-slate-500 uppercase tracking-wider py-3 px-4">Blockchain</th>
                        </tr>
                    </thead>
                    <tbody id="result-tbody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <details class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden group">
            <summary class="flex items-center gap-2 px-5 py-3.5 cursor-pointer text-xs text-slate-400 hover:text-slate-200 font-semibold select-none transition-colors">
                <i data-lucide="code-2" class="w-4 h-4 text-sky-400"></i>
                Lihat Laporan JSON Lengkap
            </summary>
            <pre id="json-output" class="px-5 pb-5 pt-1 text-xs text-emerald-400 font-mono overflow-x-auto whitespace-pre-wrap leading-relaxed"></pre>
        </details>
    </div>

</div>

<script>
(function () {
    const zipInput     = document.getElementById('zip-input');
    const dropZone     = document.getElementById('drop-zone');
    const dropContent  = document.getElementById('drop-content');
    const fileSelected = document.getElementById('file-selected');
    const fileNamePrev = document.getElementById('file-name-preview');
    const fileSizePrev = document.getElementById('file-size-preview');
    const clearFileBtn = document.getElementById('clear-file-btn');
    const processBtn   = document.getElementById('process-btn');
    const progressCard = document.getElementById('progress-card');
    const progressBar  = document.getElementById('progress-bar');
    const progressPct  = document.getElementById('progress-pct');
    const resultCard   = document.getElementById('result-card');
    const statTotal    = document.getElementById('stat-total');
    const statSuccess  = document.getElementById('stat-success');
    const statFailed   = document.getElementById('stat-failed');
    const resultTbody  = document.getElementById('result-tbody');
    const jsonOutput   = document.getElementById('json-output');
    const copyJsonBtn  = document.getElementById('copy-json-btn');
    const newBatchBtn  = document.getElementById('new-batch-btn');

    let selectedFile   = null;
    let lastReport     = null;

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function showFilePreview(file) {
        selectedFile = file;
        fileNamePrev.textContent = file.name;
        fileSizePrev.textContent = formatBytes(file.size);
        
        dropContent.style.display = 'none';
        fileSelected.style.display = 'flex';
        
        dropZone.classList.remove('border-blue-200/90', 'hover:border-blue-400', 'bg-slate-50/60', 'hover:bg-blue-50/30');
        dropZone.classList.add('border-emerald-400', 'bg-emerald-50/40');
        
        processBtn.disabled = false;
        processBtn.className = 'mt-4 w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-bold font-heading text-sm text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-md hover:shadow-lg shadow-blue-500/25 transition-all duration-200 cursor-pointer';
        
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function clearFile() {
        selectedFile = null;
        zipInput.value = '';
        
        fileSelected.style.display = 'none';
        dropContent.style.display = 'flex';
        
        dropZone.classList.remove('border-emerald-400', 'bg-emerald-50/40');
        dropZone.classList.add('border-blue-200/90', 'hover:border-blue-400', 'bg-slate-50/60', 'hover:bg-blue-50/30');
        
        processBtn.disabled = true;
        processBtn.className = 'mt-4 w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-bold font-heading text-sm text-slate-400 bg-slate-100 border border-slate-200 shadow-none cursor-not-allowed transition-all duration-200';
        
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    zipInput.addEventListener('change', function () {
        if (this.files && this.files[0]) showFilePreview(this.files[0]);
    });

    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!selectedFile) {
            this.classList.add('border-blue-500', 'bg-blue-50/80');
            this.classList.remove('border-blue-200/90', 'bg-slate-50/60');
        }
    });
    dropZone.addEventListener('dragleave', function () {
        if (!selectedFile) {
            this.classList.remove('border-blue-500', 'bg-blue-50/80');
            this.classList.add('border-blue-200/90', 'bg-slate-50/60');
        }
    });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        if (!selectedFile) {
            this.classList.remove('border-blue-500', 'bg-blue-50/80');
            this.classList.add('border-blue-200/90', 'bg-slate-50/60');
        }
        const files = e.dataTransfer.files;
        if (files && files[0]) {
            if (!files[0].name.toLowerCase().endsWith('.zip')) {
                alert('❌ Hanya file berformat .zip yang diterima.');
                return;
            }
            showFilePreview(files[0]);
        }
    });

    clearFileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        clearFile();
    });

    let progressTimer = null;
    function startFakeProgress() {
        let pct = 2;
        progressBar.style.width = '2%';
        progressPct.textContent = '2%';
        progressTimer = setInterval(function () {
            pct = Math.min(pct + (Math.random() * 3), 88);
            progressBar.style.width = pct + '%';
            progressPct.textContent = Math.round(pct) + '%';
        }, 800);
    }
    function completeFakeProgress() {
        clearInterval(progressTimer);
        progressBar.style.width = '100%';
        progressPct.textContent = '100%';
    }

    processBtn.addEventListener('click', async function () {
        if (!selectedFile) return;

        progressCard.classList.remove('hidden');
        resultCard.classList.add('hidden');
        processBtn.disabled = true;
        processBtn.className = 'mt-4 w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-bold font-heading text-sm text-white bg-blue-600/80 cursor-wait shadow-sm';
        processBtn.innerHTML = `<div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Memproses Batch...`;

        startFakeProgress();

        const formData = new FormData();
        formData.append('zip_file', selectedFile);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        try {
            const response = await fetch('{{ route("admin.batch-upload.post") }}', {
                method: 'POST',
                body: formData,
            });

            completeFakeProgress();
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || data.errors?.zip_file?.[0] || 'Terjadi kesalahan di server.');
            }

            renderReport(data.report);
        } catch (err) {
            completeFakeProgress();
            alert('❌ Gagal memproses batch: ' + err.message);
            processBtn.disabled = false;
            processBtn.className = 'mt-4 w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-bold font-heading text-sm text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-md hover:shadow-lg shadow-blue-500/25 transition-all duration-200 cursor-pointer';
            processBtn.innerHTML = `<i data-lucide="zap" class="w-4 h-4"></i> Mulai Proses Batch OCR + Blockchain`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        setTimeout(() => {
            progressCard.classList.add('hidden');
        }, 1000);
    });

    function renderReport(report) {
        lastReport = report;
        statTotal.textContent   = report.total;
        statSuccess.textContent = report.success;
        statFailed.textContent  = report.failed;

        resultTbody.innerHTML = '';

        report.items.forEach(function (item, idx) {
            const isSuccess = item.status === 'success';
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/80 transition-colors';

            const txShort = item.tx_id
                ? `<code class="font-mono text-xs text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200" title="${escapeHtml(item.tx_id)}">${escapeHtml(item.tx_id.substring(0, 16))}…</code>`
                : '<span class="text-slate-400">—</span>';

            const blockchainBadge = item.blockchain_status === 'Registered'
                ? `<span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Registered</span>`
                : item.blockchain_status === 'Pending'
                    ? `<span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>Pending</span>`
                    : '<span class="text-slate-400">—</span>';

            tr.innerHTML = `
                <td class="py-3 px-4 text-xs text-slate-500 font-mono">${idx + 1}</td>
                <td class="py-3 px-4">
                    ${isSuccess
                        ? `<span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Sukses</span>`
                        : `<span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Gagal</span>`
                    }
                </td>
                <td class="py-3 px-4">
                    <span class="text-slate-800 font-semibold text-xs">${escapeHtml(item.file_name)}</span>
                    ${!isSuccess && item.error ? `<p class="text-rose-600 text-[11px] mt-0.5">${escapeHtml(item.error)}</p>` : ''}
                </td>
                <td class="py-3 px-4">
                    ${isSuccess ? `
                        <span class="block text-slate-800 text-xs font-bold">${escapeHtml(item.owner_name ?? '—')}</span>
                        <span class="block text-slate-500 text-[11px]">${escapeHtml(item.document_type ?? '')}</span>
                    ` : '<span class="text-slate-400">—</span>'}
                </td>
                <td class="py-3 px-4">${isSuccess ? txShort : '<span class="text-slate-400">—</span>'}</td>
                <td class="py-3 px-4">${isSuccess ? blockchainBadge : '<span class="text-slate-400">—</span>'}</td>
            `;
            resultTbody.appendChild(tr);
        });

        jsonOutput.textContent = JSON.stringify(report, null, 2);
        resultCard.classList.remove('hidden');

        processBtn.disabled = false;
        processBtn.className = 'mt-4 w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-bold font-heading text-sm text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-md hover:shadow-lg shadow-blue-500/25 transition-all duration-200 cursor-pointer';
        processBtn.innerHTML = `<i data-lucide="zap" class="w-4 h-4"></i> Mulai Proses Batch OCR + Blockchain`;
        if (typeof lucide !== 'undefined') lucide.createIcons();

        resultCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    copyJsonBtn.addEventListener('click', function () {
        if (!lastReport) return;
        navigator.clipboard.writeText(JSON.stringify(lastReport, null, 2)).then(function () {
            copyJsonBtn.textContent = '✓ Tersalin!';
            setTimeout(() => {
                copyJsonBtn.innerHTML = `<i data-lucide="copy" class="w-3.5 h-3.5"></i> Salin JSON`;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 2000);
        });
    });

    newBatchBtn.addEventListener('click', function () {
        clearFile();
        resultCard.classList.add('hidden');
        progressCard.classList.add('hidden');
        document.getElementById('upload-card').scrollIntoView({ behavior: 'smooth' });
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
})();
</script>
@endsection

