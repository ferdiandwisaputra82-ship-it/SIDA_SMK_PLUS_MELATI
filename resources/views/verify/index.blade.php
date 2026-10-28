@extends('layouts.public')

@section('title', 'Verifikasi Dokumen')

@section('content')
<div style="position:relative;background:linear-gradient(180deg,#f8fafc 0%,#eff6ff 60%,#f8fafc 100%);min-height:82vh;padding:40px 0 80px;overflow:hidden">

    {{-- Subtle Floating Logo Background Watermark --}}
    <div class="animate-bg-logo" style="position:absolute;top:12%;right:6%;width:340px;pointer-events:none;z-index:0;opacity:0.07">
        <img src="{{ asset('images/school-logo.png') }}?v=3" alt="School Logo Watermark" style="width:100%;height:auto;object-fit:contain">
    </div>
    <div class="animate-bg-logo" style="position:absolute;bottom:10%;left:3%;width:220px;pointer-events:none;z-index:0;opacity:0.05;animation-delay:-3.5s">
        <img src="{{ asset('images/school-logo.png') }}?v=3" alt="School Logo Watermark" style="width:100%;height:auto;object-fit:contain">
    </div>

    <div class="max-w-2xl mx-auto px-4 relative z-10" x-data="verifyForm()">
        
        <!-- Header Navigation Bar -->
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:12.5px;color:#64748b;margin-bottom:20px;font-weight:600">
            <div style="display:flex;align-items:center;gap:6px">
                <span style="color:#0f172a;font-weight:700;display:inline-flex;align-items:center;gap:5px">
                    <i data-lucide="shield-check" style="width:15px;height:15px;color:#2563eb"></i>
                    Portal Verifikasi Dokumen
                </span>
            </div>
        </div>

        {{-- Main Verification Card --}}
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 12px 36px rgba(15,23,42,0.06), 0 2px 6px rgba(15,23,42,0.03);position:relative;overflow:hidden">
            
            {{-- Top Accent Gradient Line --}}
            <div style="height:4px;background:linear-gradient(90deg,#2563eb 0%,#38bdf8 50%,#10b981 100%)"></div>

            <div style="padding:36px 32px 40px 32px">
                
                {{-- Header Section --}}
                <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:20px">
                    <div style="width:48px;height:48px;border-radius:14px;background:#eff6ff;border:1.5px solid #bfdbfe;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#2563eb;box-shadow:0 4px 12px rgba(37,99,235,0.12)">
                        <i data-lucide="shield-check" style="width:26px;height:26px"></i>
                    </div>
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                            <h1 style="font-size:21px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans','Inter',sans-serif;margin:0;line-height:1.25;letter-spacing:-0.3px">
                                Verifikasi Keaslian Dokumen
                            </h1>
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;background:#f0fdf4;color:#16a34a;border:1px solid #86efac">
                                <span style="width:6px;height:6px;border-radius:50%;background:#16a34a" class="animate-pulse"></span>
                                Ledger Online
                            </span>
                        </div>
                        <p style="font-size:13px;color:#64748b;margin:0;line-height:1.6;font-family:'Inter',sans-serif;font-weight:400">
                            Cek keaslian berkas ijazah, rapor, atau sertifikat secara instan melalui jaringan Hyperledger Fabric Blockchain.
                        </p>
                    </div>
                </div>

                {{-- Supported Types Banner --}}
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:12px 16px;margin-bottom:22px;display:flex;align-items:center;gap:12px">
                    <div style="width:28px;height:28px;border-radius:8px;background:#e0f2fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#0284c7">
                        <i data-lucide="info" style="width:16px;height:16px"></i>
                    </div>
                    <p style="font-size:12.5px;color:#0369a1;line-height:1.55;margin:0;font-weight:500;font-family:'Inter',sans-serif">
                        Mendukung seluruh berkas resmi: <strong style="font-weight:700">Ijazah, Rapor, Transkrip Nilai, Sertifikat, dan SKL</strong>.
                    </p>
                </div>

                {{-- Mode Tabs: Upload Berkas vs Scan Barcode Kamera --}}
                <div class="flex items-center gap-1.5 p-1 mb-5 bg-slate-100 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700/60">
                    <button type="button" 
                            @click="setMode('upload')"
                            class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-semibold tracking-tight transition-all duration-150 cursor-pointer select-none"
                            :class="activeMode === 'upload' 
                                ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 font-bold shadow-sm' 
                                : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 font-medium'">
                        <i data-lucide="upload-cloud" class="w-3.5 h-3.5 shrink-0"></i>
                        <span>Unggah Berkas</span>
                    </button>
                    <button type="button" 
                            @click="setMode('scanner')"
                            class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-semibold tracking-tight transition-all duration-150 cursor-pointer select-none"
                            :class="activeMode === 'scanner' 
                                ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 font-bold shadow-sm' 
                                : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 font-medium'">
                        <i data-lucide="qr-code" class="w-3.5 h-3.5 shrink-0"></i>
                        <span>Scan Barcode Kamera</span>
                    </button>
                </div>

                {{-- TAB 1: UPLOAD BERKAS --}}
                <div x-show="activeMode === 'upload'">
                    <form action="{{ route('verify.process') }}" method="POST" enctype="multipart/form-data" id="mainVerifyForm">
                        @csrf
                        
                        <input type="file" name="document" id="documentInput" style="display:none" accept=".pdf,.png,.jpg,.jpeg" @change="handleFileSelect($event)">
                        
                        {{-- Interactive Dropzone --}}
                        <div class="w-full flex flex-col items-center justify-center text-center cursor-pointer transition-all duration-200 rounded-2xl p-6"
                             :class="fileSelected 
                                 ? 'border-2 border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/20' 
                                 : (isDragging 
                                     ? 'border-2 border-dashed border-blue-600 bg-blue-50 dark:bg-blue-950/30 scale-[1.005]' 
                                     : 'border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 hover:border-slate-400 dark:hover:border-slate-600')"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="handleFileDrop($event)"
                             @click="triggerFileSelect()">
                             
                            <!-- State 1: File Not Selected -->
                            <div class="flex flex-col items-center justify-center text-center w-full mx-auto"
                                 style="text-align:center;margin:0 auto;width:100%"
                                 x-show="!fileSelected">
                                <div class="mx-auto flex items-center justify-center"
                                     style="width:44px;height:44px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;color:#2563eb;box-shadow:0 4px 12px rgba(37,99,235,0.08)">
                                    <i data-lucide="upload-cloud" style="width:22px;height:22px"></i>
                                </div>
                                <p style="font-size:14px;font-weight:700;color:#0f172a;font-family:'Plus Jakarta Sans','Inter',sans-serif;margin:0 auto 6px;text-align:center;letter-spacing:-0.1px">
                                    Klik atau Seret Berkas ke Sini
                                </p>
                                <div style="display:flex;align-items:center;justify-content:center;gap:5px;flex-wrap:wrap;margin-top:2px">
                                    <span style="font-size:10.5px;font-weight:700;color:#475569;background:#e2e8f0;padding:2px 7px;border-radius:5px;font-family:'Inter',sans-serif;letter-spacing:0.3px">PDF</span>
                                    <span style="font-size:10.5px;font-weight:700;color:#475569;background:#e2e8f0;padding:2px 7px;border-radius:5px;font-family:'Inter',sans-serif;letter-spacing:0.3px">JPG</span>
                                    <span style="font-size:10.5px;font-weight:700;color:#475569;background:#e2e8f0;padding:2px 7px;border-radius:5px;font-family:'Inter',sans-serif;letter-spacing:0.3px">PNG</span>
                                    <span style="font-size:11px;color:#94a3b8;font-family:'Inter',sans-serif">·</span>
                                    <span style="font-size:11.5px;color:#64748b;font-family:'Inter',sans-serif">Maks. {{ $settings['max_file_size'] ?? '20' }}MB</span>
                                </div>
                            </div>

                            <!-- State 2: File Selected -->
                            <div class="flex flex-col items-center justify-center text-center w-full mx-auto"
                                 style="text-align:center;margin:0 auto;width:100%"
                                 x-show="fileSelected"
                                 x-cloak>
                                <div class="mx-auto flex items-center justify-center"
                                     style="width:44px;height:44px;background:#dcfce7;border:1px solid #86efac;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;color:#16a34a;box-shadow:0 4px 12px rgba(22,163,74,0.12)">
                                    <i data-lucide="file-check" style="width:22px;height:22px"></i>
                                </div>
                                <p class="text-center font-extrabold" style="font-size:13.5px;font-weight:700;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0 auto 2px;max-width:90%;word-break:break-all;text-align:center" x-text="fileName"></p>
                                <p class="text-center font-bold" style="font-size:11.5px;color:#16a34a;font-weight:700;margin:0 auto 10px;text-align:center" x-text="fileSize"></p>
                                <button type="button" @click.stop="resetFile()" class="mx-auto" style="font-size:11px;color:#e11d48;background:#ffe4e6;border:1px solid #fecdd3;padding:4px 12px;border-radius:6px;cursor:pointer;font-weight:700;display:inline-flex;align-items:center;gap:5px;margin:0 auto">
                                    <i data-lucide="refresh-cw" style="width:11px;height:11px"></i> Ganti Berkas
                                </button>
                            </div>
                        </div>

                        <!-- Hash Client View -->
                        <div style="margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;display:none" x-show="fileSelected">
                            <div style="font-size:11px;font-weight:700;color:#0284c7;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;font-family:'JetBrains Mono',monospace;display:flex;align-items:center;gap:6px">
                                <i data-lucide="fingerprint" style="width:14px;height:14px;color:#0284c7"></i>
                                SHA-256 Client Hash
                            </div>
                            <code style="font-size:12px;font-family:'JetBrains Mono',monospace;color:#1e293b;word-break:break-all;font-weight:600" x-text="fileHash || 'Menghitung hash SHA-256...'"></code>
                        </div>

                        <!-- Submit Button -->
                        <div style="margin-top:24px">
                            <button type="button"
                                    :disabled="!fileSelected || processing"
                                    @click="submitForm()"
                                    style="font-family:'Inter',sans-serif;font-size:13.5px;letter-spacing:0.1px"
                                    class="w-full py-4 px-6 rounded-xl font-bold tracking-wide flex items-center justify-center gap-2 transition-all duration-200"
                                    :class="fileSelected && !processing
                                        ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-600/30 cursor-pointer hover:from-blue-700 hover:to-blue-800'
                                        : 'bg-slate-100 text-slate-500 border border-slate-300 cursor-not-allowed'">
                                
                                <template x-if="!processing && fileSelected">
                                    <span style="display:flex;align-items:center;gap:8px">
                                        <i data-lucide="shield-check" style="width:18px;height:18px"></i>
                                        Verifikasi Dokumen Sekarang
                                    </span>
                                </template>

                                <template x-if="!processing && !fileSelected">
                                    <span style="display:flex;align-items:center;gap:8px">
                                        <i data-lucide="upload" style="width:16px;height:16px"></i>
                                        Pilih Dokumen Terlebih Dahulu
                                    </span>
                                </template>

                                <template x-if="processing">
                                    <span style="display:flex;align-items:center;gap:8px">
                                        <i data-lucide="loader-2" style="width:18px;height:18px" class="animate-spin"></i>
                                        Memproses AI OCR &amp; Blockchain...
                                    </span>
                                </template>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TAB 2: LIVE CAMERA QR SCANNER --}}
                <div x-show="activeMode === 'scanner'" x-cloak>
                    <div style="background:#0f172a;border-radius:16px;padding:20px;text-align:center;color:#ffffff;position:relative;overflow:hidden">
                        <div id="qr-reader" style="width:100%;max-width:440px;margin:0 auto;border-radius:12px;overflow:hidden"></div>
                        <div style="margin-top:16px;display:flex;flex-direction:column;align-items:center;gap:8px">
                            <button type="button" @click="toggleCamera()"
                                    style="padding:10px 24px;border-radius:10px;background:#2563eb;color:#ffffff;border:none;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(37,99,235,0.3)">
                                <i data-lucide="camera" style="width:16px;height:16px"></i>
                                <span x-text="cameraRunning ? 'Matikan Kamera' : 'Nyalakan Kamera Scanner'"></span>
                            </button>
                            <p style="font-size:12px;color:#94a3b8;margin:0;max-width:360px">
                                Arahkan kamera ke Barcode / QR Code yang tercetak pada dokumen fisik untuk cek keaslian otomatis.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Verification Tips & Info Section --}}
        <div style="margin-top:24px;display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px">
            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.03)">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                    <div style="width:30px;height:30px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0">
                        <i data-lucide="lock" style="width:16px;height:16px"></i>
                    </div>
                    <h3 style="font-size:13px;font-weight:700;color:#0f172a;font-family:'Plus Jakarta Sans','Inter',sans-serif;margin:0;letter-spacing:-0.1px">Privasi &amp; Enkripsi SHA-256</h3>
                </div>
                <p style="font-size:12px;color:#64748b;line-height:1.65;margin:0;font-family:'Inter',sans-serif">
                    Kalkulasi nilai hash digital dihitung langsung di peramban Anda. Hanya identitas sidik jari digital (hash) dan metadata resmi yang diverifikasi terhadap jaringan ledger.
                </p>
            </div>

            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.03)">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                    <div style="width:30px;height:30px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#16a34a;flex-shrink:0">
                        <i data-lucide="qr-code" style="width:16px;height:16px"></i>
                    </div>
                    <h3 style="font-size:13px;font-weight:700;color:#0f172a;font-family:'Plus Jakarta Sans','Inter',sans-serif;margin:0;letter-spacing:-0.1px">Barcode Verifikasi Resmi</h3>
                </div>
                <p style="font-size:12px;color:#64748b;line-height:1.65;margin:0;font-family:'Inter',sans-serif">
                    Setiap berkas resmi SMK Plus Melati dilengkapi Barcode QR Kriptografi di sudut bawah dokumen yang dapat dipindai langsung dengan kamera smartphone apa pun.
                </p>
            </div>
        </div>

    </div>
</div>

{{-- Include HTML5 QR Code Scanner Library --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    function verifyForm() {
        return {
            activeMode: 'upload',
            isDragging: false,
            fileSelected: false,
            fileName: '',
            fileSize: '',
            fileHash: '',
            processing: false,
            cameraRunning: false,
            html5QrScanner: null,

            setMode(mode) {
                this.activeMode = mode;
                if (mode !== 'scanner' && this.cameraRunning) {
                    this.stopCamera();
                }
                if (mode === 'scanner' && !this.cameraRunning) {
                    this.$nextTick(() => {
                        this.startCamera();
                    });
                }
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },

            triggerFileSelect() {
                document.getElementById('documentInput').click();
            },

            async handleFileSelect(event) {
                const files = event.target.files;
                if (files && files.length > 0) {
                    await this.processFile(files[0]);
                }
            },

            async handleFileDrop(event) {
                this.isDragging = false;
                const files = event.dataTransfer.files;
                if (files && files.length > 0) {
                    document.getElementById('documentInput').files = files;
                    await this.processFile(files[0]);
                }
            },

            async processFile(file) {
                this.fileName = file.name;
                this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB · Siap diverifikasi';
                this.fileSelected = true;
                this.fileHash = '';

                try {
                    const buf = await file.arrayBuffer();
                    const hashBuffer = await crypto.subtle.digest("SHA-256", buf);
                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                    this.fileHash = "0x" + hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
                } catch (e) {
                    this.fileHash = 'Gagal menghitung hash client';
                }

                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },

            resetFile() {
                document.getElementById('documentInput').value = '';
                this.fileSelected = false;
                this.fileName = '';
                this.fileSize = '';
                this.fileHash = '';

                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },

            submitForm() {
                if (this.fileSelected) {
                    this.processing = true;
                    if (typeof window.showSidaPreloader === 'function') {
                        window.showSidaPreloader('MEMVERIFIKASI DOKUMEN...', 'Memeriksa Keaslian Berkas & Ledger Blockchain...');
                    }
                    document.getElementById('mainVerifyForm').submit();
                }
            },


            toggleCamera() {
                if (this.cameraRunning) {
                    this.stopCamera();
                } else {
                    this.startCamera();
                }
            },

            startCamera() {
                const readerElement = document.getElementById('qr-reader');
                if (!readerElement) return;

                if (!this.html5QrScanner) {
                    this.html5QrScanner = new Html5Qrcode("qr-reader");
                }

                const config = { fps: 10, qrbox: { width: 250, height: 250 } };

                this.html5QrScanner.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        this.stopCamera();
                        if (typeof window.showSidaPreloader === 'function') {
                            window.showSidaPreloader('MEMVERIFIKASI QR CODE...', 'Membuka Hasil Verifikasi Blockchain...');
                        }
                        if (decodedText.startsWith('http://') || decodedText.startsWith('https://')) {
                            window.location.href = decodedText;
                        } else {
                            window.location.href = "{{ url('/verify') }}/" + encodeURIComponent(decodedText);
                        }
                    },
                    (errorMessage) => {
                        // frame scan ignore
                    }
                ).then(() => {
                    this.cameraRunning = true;
                }).catch((err) => {
                    console.error("Gagal memulai kamera: ", err);
                    alert("Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan di browser Anda.");
                });
            },

            stopCamera() {
                if (this.html5QrScanner && this.cameraRunning) {
                    this.html5QrScanner.stop().then(() => {
                        this.cameraRunning = false;
                    }).catch(err => {
                        console.error("Error stop camera", err);
                    });
                }
            }
        }
    }
</script>
@endsection
