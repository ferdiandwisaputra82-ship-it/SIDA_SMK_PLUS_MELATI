@extends('layouts.admin')

@section('title', 'Upload Dokumen & AI OCR')
@section('subtitle', 'Pilih sumber berkas → ekstraksi AI OCR → daftarkan ke Hyperledger Fabric')

@section('content')
<style>
.upload-step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
    font-family: monospace;
    flex-shrink: 0;
}
.upload-section-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: #3b82f6;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
}
.upload-field-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.upload-field-label {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
}
.upload-input {
    width: 100%;
    padding: 10px 14px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    color: #0f172a;
    font-size: 13px;
    outline: none;
    transition: all .15s ease-in-out;
    font-family: 'Inter', sans-serif;
}
.upload-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
</style>

<div class="space-y-5" x-data="uploadOcrHandler()">

    <!-- Step indicator -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm flex items-center gap-3 sm:gap-6 overflow-x-auto">
        <div class="flex items-center gap-2.5 flex-shrink-0">
            <span class="w-7 h-7 rounded-full bg-blue-100 border border-blue-300 text-blue-700 font-bold text-xs flex items-center justify-center font-mono">1</span>
            <span class="text-xs font-bold text-blue-700 font-heading">Pilih Berkas</span>
        </div>
        <div class="flex-1 min-w-[20px] h-0.5 bg-slate-200"></div>
        <div class="flex items-center gap-2.5 flex-shrink-0">
            <span class="w-7 h-7 rounded-full font-bold text-xs flex items-center justify-center font-mono transition-colors"
                  :class="done || running ? 'bg-blue-100 border border-blue-300 text-blue-700' : 'bg-slate-100 border border-slate-300 text-slate-500'">2</span>
            <span class="text-xs font-bold font-heading transition-colors"
                  :class="done || running ? 'text-blue-700' : 'text-slate-500'">AI OCR Scan</span>
        </div>
        <div class="flex-1 min-w-[20px] h-0.5 bg-slate-200"></div>
        <div class="flex items-center gap-2.5 flex-shrink-0">
            <span class="w-7 h-7 rounded-full font-bold text-xs flex items-center justify-center font-mono transition-colors"
                  :class="done ? 'bg-emerald-100 border border-emerald-300 text-emerald-700' : 'bg-slate-100 border border-slate-300 text-slate-500'">3</span>
            <span class="text-xs font-bold font-heading transition-colors"
                  :class="done ? 'text-emerald-700' : 'text-slate-500'">Daftarkan</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- KIRI: Upload Zone -->
        <div class="lg:col-span-5 space-y-4">

            <!-- Card: Upload Zone -->
            <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-sm">

                <!-- Card Header -->
                <div class="px-5 py-4 border-b border-slate-200/90 bg-slate-50/70 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 shadow-xs">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                        </div>
                        <span class="font-heading font-bold text-slate-800 text-sm">Upload File Dokumen</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-200/70 px-2.5 py-0.5 rounded-full">Langkah 1 &amp; 2</span>
                </div>

                <div class="p-5 flex flex-col gap-5">

                    <!-- Source Toggle -->
                    <div>
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                            <i data-lucide="layers" class="w-3.5 h-3.5 text-blue-600"></i> Sumber Penyimpanan
                        </div>
                        <div class="grid grid-cols-2 p-1.5 bg-slate-100/90 rounded-xl gap-1.5 border border-slate-200">
                            <button type="button" @click="setSource('gdrive')"
                                class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-xs font-semibold border transition-all duration-150 cursor-pointer"
                                :class="uploadSource === 'gdrive'
                                    ? 'bg-white text-emerald-700 shadow-sm border-emerald-300 font-bold'
                                    : 'text-slate-600 hover:text-slate-900 border-transparent hover:bg-slate-200/50'">
                                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                    <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" opacity=".6"/>
                                    <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z" opacity=".6"/>
                                    <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" opacity=".6"/>
                                    <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z"/>
                                    <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z"/>
                                    <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z"/>
                                </svg>
                                <span>Google Drive</span>
                            </button>
                            <button type="button" @click="setSource('local')"
                                class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-xs font-semibold border transition-all duration-150 cursor-pointer"
                                :class="uploadSource === 'local'
                                    ? 'bg-white text-blue-700 shadow-sm border-blue-300 font-bold'
                                    : 'text-slate-600 hover:text-slate-900 border-transparent hover:bg-slate-200/50'">
                                <i data-lucide="hard-drive" class="w-4 h-4 flex-shrink-0"></i>
                                <span>Local Storage</span>
                            </button>
                        </div>
                    </div>

                    <!-- LOCAL MODE -->
                    <div x-show="uploadSource === 'local'" x-transition>
                        <input type="file" id="auFileInput" class="hidden" accept="image/*,application/pdf" @change="handleFileSelect($event)">

                        <!-- Unselected Dropzone -->
                        <div x-show="!fileSelected"
                             class="border-2 border-dashed rounded-2xl p-7 flex flex-col items-center justify-center text-center cursor-pointer transition-all duration-200 group relative"
                             :class="isDragging ? 'border-blue-500 bg-blue-50/80 scale-[1.01]' : 'border-blue-200/90 hover:border-blue-400 bg-slate-50/70 hover:bg-blue-50/40'"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="handleFileDrop($event)"
                             @click="triggerFileSelect()">

                            <div class="w-12 h-12 rounded-2xl bg-blue-100/90 border border-blue-200 text-blue-600 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200 shadow-xs">
                                <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                            </div>
                            <p class="font-heading font-bold text-sm text-slate-800 mb-1">Seret atau klik untuk upload</p>
                            <p class="text-xs text-slate-500 mb-3">Pilih berkas dokumen dari penyimpanan lokal Anda</p>
                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200/80 text-slate-600">PDF</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200/80 text-slate-600">JPG</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200/80 text-slate-600">PNG</span>
                                <span class="text-[11px] text-slate-400 font-medium ml-1">Maks. {{ $settings['max_file_size'] ?? '20' }}MB</span>
                            </div>
                        </div>

                        <!-- Selected File Preview Card -->
                        <div x-show="fileSelected"
                             class="border-2 border-emerald-400 bg-emerald-50/70 rounded-2xl p-4 flex items-center justify-between gap-3 shadow-sm transition-all duration-200">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 border border-emerald-200 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="file-check" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-heading font-bold text-xs sm:text-sm text-slate-900 truncate" x-text="fileName"></p>
                                    <p class="text-[11px] font-semibold text-emerald-700 flex items-center gap-1 mt-0.5">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                        <span>Siap diproses AI OCR</span>
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click.stop="triggerFileSelect()"
                                    class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-white hover:bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-200 transition-colors flex-shrink-0 cursor-pointer shadow-xs">
                                Ganti
                            </button>
                        </div>
                    </div>

                    <!-- GOOGLE DRIVE MODE -->
                    <div x-show="uploadSource === 'gdrive'" x-transition class="space-y-3">
                        <input type="file" id="gdriveFileInput" class="hidden" accept="image/*,application/pdf" @change="handleGdriveFileSelect($event)">

                        @php $gdriveReady = $gdriveReady ?? false; $gdriveError = $gdriveError ?? null; @endphp

                        @if(!$gdriveConnected || !$gdriveReady)
                        <div class="bg-amber-50/90 border border-amber-200 rounded-2xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i data-lucide="unlink" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1 text-xs">
                                    <p class="font-bold text-amber-900 mb-1">
                                        @if(!$gdriveConfigured) Google Drive Belum Dikonfigurasi
                                        @elseif(!$gdriveConnected) Google Drive Belum Terhubung
                                        @else Token Google Drive Tidak Valid
                                        @endif
                                    </p>
                                    <p class="text-amber-800/80 leading-relaxed mb-3">{{ $gdriveError ?: 'Hubungkan akun Google Drive Anda untuk memilih berkas langsung dari cloud.' }}</p>
                                    @if($gdriveConfigured)
                                    <a href="{{ route('admin.gdrive.connect') }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs shadow-xs transition-colors"
                                       style="text-decoration:none">
                                        <i data-lucide="link" class="w-3.5 h-3.5"></i> Hubungkan Drive
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($gdriveReady)
                        <div class="flex items-center justify-between px-3.5 py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-xs">
                            <span class="text-emerald-700 font-bold flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Google Drive Terhubung
                            </span>
                            <a href="{{ route('admin.gdrive.disconnect') }}" class="text-rose-600 font-medium hover:underline text-[11px]">Putuskan</a>
                        </div>

                        <button type="button" @click="openDrivePickerModal()"
                                class="w-full flex items-center gap-3 p-3.5 bg-blue-50/70 hover:bg-blue-100/70 border border-blue-200 rounded-2xl cursor-pointer transition-all duration-150 text-left group">
                            <div class="w-10 h-10 rounded-xl bg-white border border-blue-200 text-xl flex items-center justify-center flex-shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                                📁
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-heading font-bold text-xs sm:text-sm text-blue-950 truncate">Pilih dari Google Drive</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">Buka dialog Google Picker API</p>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-blue-400 group-hover:translate-x-0.5 transition-transform"></i>
                        </button>

                        <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-3.5 space-y-2">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide">
                                🔗 Tempel Link / ID Google Drive
                            </label>
                            <div class="flex gap-2">
                                <input type="text" x-model="drivePickerUrlInput" placeholder="https://drive.google.com/file/d/..."
                                       class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 outline-none focus:border-blue-500 transition-colors">
                                <button type="button" @click="applyDriveUrlInput()"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer shadow-xs">
                                    Pakai
                                </button>
                            </div>
                        </div>

                        <!-- Drive file upload / selected box -->
                        <div class="border-2 border-dashed rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all duration-150"
                             :class="gdriveFileSelected ? 'border-emerald-400 bg-emerald-50/60' : 'border-slate-300 hover:border-emerald-400 bg-slate-50/60 hover:bg-emerald-50/30'"
                             @click="triggerGdriveFileSelect()">
                            <div x-show="!gdriveFileSelected" class="flex flex-col items-center gap-1 text-center">
                                <i data-lucide="upload" class="w-5 h-5 text-emerald-600 mb-1"></i>
                                <p class="text-xs font-bold text-slate-800">Unggah Berkas Baru ke Drive</p>
                                <p class="text-[11px] text-slate-500">Format PDF, JPG, atau PNG</p>
                            </div>
                            <div x-show="gdriveFileSelected" class="flex items-center gap-2.5 text-left w-full">
                                <i data-lucide="file-check" class="w-5 h-5 text-emerald-600 flex-shrink-0"></i>
                                <span class="text-xs font-bold text-slate-900 truncate flex-1" x-text="gdriveFileName"></span>
                                <span class="text-[10px] font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Drive Ready</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- OCR Scan Button -->
                    <button type="button"
                        :disabled="(!fileSelected && uploadSource === 'local') || (!gdriveFileSelected && uploadSource === 'gdrive') || running"
                        @click="runOcr()"
                        class="w-full py-3.5 px-4 rounded-xl font-heading font-bold text-sm flex items-center justify-center gap-2 transition-all duration-200 focus:outline-none"
                        :class="((uploadSource === 'local' ? !fileSelected : !gdriveFileSelected) || running)
                            ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed'
                            : 'bg-gradient-to-r from-blue-600 via-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg shadow-blue-600/25 cursor-pointer hover:-translate-y-0.5 active:translate-y-0'">
                        <span x-show="!running && !done" class="flex items-center gap-2">
                            <i data-lucide="cpu" class="w-4 h-4"></i> Scan dengan AI OCR
                        </span>
                        <span x-show="running" class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Memproses OCR &amp; Ekstraksi Data...
                        </span>
                        <span x-show="done" class="flex items-center gap-2">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Scan Ulang Dokumen
                        </span>
                    </button>

                </div>
            </div>

            <!-- Console Log -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 font-mono text-xs text-sky-400 max-h-44 overflow-y-auto"
                 x-show="running || logs.length > 0" style="display:none">
                <template x-for="log in logs">
                    <div x-text="log" class="mb-1"></div>
                </template>
            </div>
        </div>

        <!-- KANAN: Metadata Form -->
        <div class="lg:col-span-7">
            <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-sm">

                <div class="px-5 py-4 border-b border-slate-200/90 bg-slate-50/70 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 shadow-xs">
                            <i data-lucide="file-edit" class="w-4 h-4"></i>
                        </div>
                        <span class="font-heading font-bold text-slate-800 text-sm">Metadata Dokumen</span>
                        <span x-show="done" class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 rounded-full text-[11px] text-emerald-700 font-bold flex items-center gap-1">
                            <i data-lucide="check" class="w-3 h-3"></i> OCR Selesai
                        </span>
                    </div>
                    <button type="button" @click="resetForm()" class="text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Reset</button>
                </div>

                <form action="{{ route('admin.upload.post') }}" method="POST" enctype="multipart/form-data" id="ocrConfirmForm">
                    @csrf
                    <input type="hidden" name="upload_source" x-model="uploadSource">
                    <input type="file" name="document_file" id="realFileHiddenInput" class="hidden">
                    <input type="hidden" name="hash" x-model="fileHash">
                    <input type="hidden" name="gdrive_file_id"   id="gdrive_file_id_input">
                    <input type="hidden" name="gdrive_file_name" id="gdrive_file_name_input">
                    <input type="hidden" name="gdrive_file_mime" id="gdrive_file_mime_input">

                    <div class="p-6 flex flex-col gap-4">

                        <div class="px-3.5 py-2 bg-blue-50/60 border border-blue-200/70 rounded-xl text-xs text-slate-600 flex items-center gap-2">
                            <i data-lucide="hard-drive" class="w-3.5 h-3.5 text-blue-600"></i>
                            <span>Penyimpanan Terpilih: <strong class="text-blue-700" x-text="uploadSource === 'gdrive' ? 'Google Drive Cloud' : 'Local Server Storage'"></strong></span>
                        </div>

                        <div class="upload-field-group">
                            <label class="upload-field-label">Jenis Dokumen *</label>
                            <select name="document_type" x-model="metadata.documentType" class="upload-input">
                                @foreach($docTypes as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="upload-field-group">
                            <label class="upload-field-label">Nama Pemilik Dokumen *</label>
                            <input type="text" name="owner_name" x-model="metadata.ownerName" placeholder="Nama lengkap siswa atau pemilik berkas" class="upload-input font-bold" required>
                        </div>

                        <div class="upload-field-group">
                            <label class="upload-field-label">Nomor Dokumen Resmi *</label>
                            <input type="text" name="document_number" x-model="metadata.documentNumber" placeholder="Contoh: 12345/SKL/2024 atau No. Seri Ijazah" class="upload-input font-bold font-mono" required>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="upload-field-group">
                                <label class="upload-field-label">NISN / NIM Siswa</label>
                                <input type="text" name="nisn" x-model="metadata.nisn" placeholder="Nomor induk siswa" class="upload-input font-mono">
                            </div>
                            <div class="upload-field-group">
                                <label class="upload-field-label">Tahun Dokumen Terbit</label>
                                <input type="text" name="year" x-model="metadata.year" placeholder="2026" class="upload-input font-mono">
                            </div>
                        </div>

                        <div class="upload-field-group">
                            <label class="upload-field-label">Deskripsi / Catatan Tambahan</label>
                            <textarea name="description" x-model="metadata.description" rows="2" placeholder="Catatan pengarsipan tambahan (opsional)..." class="upload-input resize-none"></textarea>
                        </div>

                        <input type="hidden" name="status" value="Menunggu Verifikasi">

                    </div>

                    <div class="px-6 py-4 border-t border-slate-200/90 flex items-center justify-between bg-slate-50/70">
                        <span class="text-xs text-slate-500" x-show="!done">Unggah &amp; selesaikan AI OCR terlebih dahulu</span>
                        <span class="text-xs text-emerald-600 font-bold flex items-center gap-1.5" x-show="done" style="display:none">
                            <i data-lucide="shield-check" class="w-4 h-4"></i> Data OCR siap didaftarkan
                        </span>
                        <button type="submit" :disabled="!done"
                            class="px-5 py-2.5 rounded-xl font-heading font-bold text-xs sm:text-sm flex items-center gap-2 transition-all duration-200"
                            :class="done
                                ? 'bg-gradient-to-r from-blue-600 via-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg shadow-blue-600/25 cursor-pointer hover:-translate-y-0.5'
                                : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed'">
                            <i data-lucide="shield-plus" class="w-4 h-4"></i>
                            Daftarkan ke Blockchain
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const GDRIVE_CLIENT_ID    = '{{ $googleClientId }}';
    const GDRIVE_API_KEY      = '{{ $googleApiKey }}';
    const GDRIVE_ACCESS_TOKEN = '{{ $gdriveAccessToken ?? "" }}';
    const GDRIVE_CONNECTED    = {{ $gdriveConnected ? 'true' : 'false' }};
    const GDRIVE_CONNECT_URL  = '{{ route("admin.gdrive.connect") }}';
    const GDRIVE_TOKEN_URL    = '{{ route("admin.gdrive.token") }}';

    function parseGoogleDriveUrl(urlOrId) {
        if (!urlOrId) return null;
        urlOrId = urlOrId.trim();
        if (/^[a-zA-Z0-9_-]{25,}$/.test(urlOrId) && !urlOrId.includes('/') && !urlOrId.includes('http')) return urlOrId;
        const match1 = urlOrId.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
        if (match1) return match1[1];
        const match2 = urlOrId.match(/[?&]id=([a-zA-Z0-9_-]+)/);
        if (match2) return match2[1];
        return null;
    }

    function loadGooglePickerScript() {
        if (!document.getElementById('google-picker-script')) {
            const script = document.createElement('script');
            script.id  = 'google-picker-script';
            script.src = 'https://apis.google.com/js/api.js';
            script.onload = () => gapi.load('picker', () => {});
            document.head.appendChild(script);
        }
    }

    async function openRealGoogleDrivePicker(onFilePicked) {
        if (!GDRIVE_CONNECTED) {
            if (confirm('Google Drive belum terhubung. Hubungkan sekarang?')) window.location.href = GDRIVE_CONNECT_URL;
            return;
        }
        if (!GDRIVE_API_KEY || GDRIVE_API_KEY === 'your_google_api_key_here') {
            alert('GOOGLE_API_KEY belum diisi di .env.');
            return;
        }
        let accessToken = GDRIVE_ACCESS_TOKEN;
        try {
            const res = await fetch(GDRIVE_TOKEN_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (res.ok) {
                const data = await res.json();
                accessToken = data.access_token || accessToken;
            }
        } catch(e) {}

        if (!accessToken) {
            if (confirm('Sesi Google Drive habis. Hubungkan ulang?')) window.location.href = GDRIVE_CONNECT_URL;
            return;
        }
        if (typeof gapi === 'undefined') {
            alert('Google API sedang dimuat... Coba klik lagi.');
            loadGooglePickerScript();
            return;
        }

        gapi.load('picker', () => {
            const picker = new google.picker.PickerBuilder()
                .addView(new google.picker.DocsView().setMode(google.picker.DocsViewMode.GRID).setMimeTypes('application/pdf,image/jpeg,image/png,image/jpg').setIncludeFolders(false))
                .setOAuthToken(accessToken)
                .setDeveloperKey(GDRIVE_API_KEY)
                .setCallback((data) => {
                    if (data.action === google.picker.Action.PICKED) {
                        const doc = data.docs[0];
                        onFilePicked({ id: doc.id, name: doc.name, mimeType: doc.mimeType, url: doc.url });
                    }
                })
                .setTitle('Pilih Dokumen dari Google Drive')
                .setLocale('id')
                .build();
            picker.setVisible(true);
        });
    }

    function uploadOcrHandler() {
        return {
            uploadSource: 'gdrive',
            drivePickerUrlInput: '',
            selectedDriveFileId: '',
            isDragging: false,
            fileSelected: false,
            fileName: '',
            fileHash: '',
            gdriveFileSelected: false,
            gdriveFileName: '',
            gdriveFileHash: '',

            openDrivePickerModal() {
                const self = this;
                openRealGoogleDrivePicker((file) => {
                    self.selectedDriveFileId  = file.id;
                    self.gdriveFileName       = file.name;
                    self.gdriveFileHash       = '0x' + Array.from(crypto.getRandomValues(new Uint8Array(32))).map(b => b.toString(16).padStart(2, '0')).join('');
                    self.fileHash             = self.gdriveFileHash;
                    self.gdriveFileSelected   = true;
                    self.done                 = false;
                    const hiddenInput = document.getElementById('gdrive_file_id_input');
                    if (hiddenInput) hiddenInput.value = file.id;
                    const hiddenName = document.getElementById('gdrive_file_name_input');
                    if (hiddenName) hiddenName.value  = file.name;
                });
            },

            applyDriveUrlInput() {
                const raw = this.drivePickerUrlInput ? this.drivePickerUrlInput.trim() : '';
                if (!raw) { alert('Masukkan Link/ID Google Drive.'); return; }
                const fileId = parseGoogleDriveUrl(raw);
                if (!fileId) { alert('Format URL/ID Google Drive tidak valid.'); return; }

                this.selectedDriveFileId = fileId;
                this.gdriveFileName = 'Google Drive File (' + fileId.substring(0, 10) + '...)';
                this.gdriveFileHash = '0x' + Array.from(crypto.getRandomValues(new Uint8Array(32))).map(b => b.toString(16).padStart(2, '0')).join('');
                this.fileHash           = this.gdriveFileHash;
                this.gdriveFileSelected = true;
                this.done               = false;

                const hiddenInput = document.getElementById('gdrive_file_id_input');
                if (hiddenInput) hiddenInput.value = fileId;
                const hiddenName = document.getElementById('gdrive_file_name_input');
                if (hiddenName) hiddenName.value  = this.gdriveFileName;
            },

            running: false,
            done: false,
            logs: [],
            metadata: {
                documentType: 'Ijazah',
                ownerName: '',
                documentNumber: '',
                nisn: '',
                year: '{{ date('Y') }}',
                description: ''
            },

            setSource(source) {
                if (this.uploadSource === source) return;
                this.uploadSource = source;
                this.fileSelected = false;
                this.gdriveFileSelected = false;
                this.fileName = '';
                this.gdriveFileName = '';
                this.fileHash = '';
                this.gdriveFileHash = '';
                this.done = false;
                this.logs = [];
                document.getElementById('auFileInput').value = '';
                document.getElementById('gdriveFileInput').value = '';
                document.getElementById('realFileHiddenInput').value = '';
            },

            triggerFileSelect() { document.getElementById('auFileInput').click(); },

            handleFileSelect(event) {
                const files = event.target.files;
                if (files && files.length > 0) this.processFile(files[0]);
            },

            handleFileDrop(event) {
                this.isDragging = false;
                const files = event.dataTransfer.files;
                if (files && files.length > 0) {
                    document.getElementById('auFileInput').files = files;
                    this.processFile(files[0]);
                }
            },

            async processFile(file) {
                this.fileName = file.name;
                this.fileSelected = true;
                this.done = false;
                this.logs = [];
                document.getElementById('realFileHiddenInput').files = document.getElementById('auFileInput').files;
                try {
                    const buf = await file.arrayBuffer();
                    const hashBuffer = await crypto.subtle.digest("SHA-256", buf);
                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                    this.fileHash = "0x" + hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
                } catch (e) { this.fileHash = 'Gagal menghitung hash client'; }
            },

            triggerGdriveFileSelect() { document.getElementById('gdriveFileInput').click(); },

            handleGdriveFileSelect(event) {
                const files = event.target.files;
                if (files && files.length > 0) this.processGdriveFile(files[0]);
            },

            async processGdriveFile(file) {
                this.gdriveFileName = file.name;
                this.gdriveFileSelected = true;
                this.done = false;
                this.logs = [];
                document.getElementById('realFileHiddenInput').files = document.getElementById('gdriveFileInput').files;
                try {
                    const buf = await file.arrayBuffer();
                    const hashBuffer = await crypto.subtle.digest("SHA-256", buf);
                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                    this.gdriveFileHash = "0x" + hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
                    this.fileHash = this.gdriveFileHash;
                } catch (e) { this.gdriveFileHash = 'Gagal menghitung hash client'; }
            },

            async runOcr() {
                const isLocal = this.uploadSource === 'local';
                const formData = new FormData();
                formData.append('document_type', this.metadata.documentType);

                if (isLocal) {
                    const fileInput = document.getElementById('auFileInput');
                    if (!fileInput.files || fileInput.files.length === 0) return;
                    formData.append('file', fileInput.files[0]);
                } else {
                    const gdriveFileInput = document.getElementById('gdriveFileInput');
                    const hiddenDriveId = document.getElementById('gdrive_file_id_input');
                    const driveFileId = hiddenDriveId ? hiddenDriveId.value : this.selectedDriveFileId;

                    if (driveFileId) {
                        formData.append('gdrive_file_id', driveFileId);
                        const hiddenName = document.getElementById('gdrive_file_name_input');
                        if (hiddenName && hiddenName.value) formData.append('gdrive_file_name', hiddenName.value);
                    } else if (gdriveFileInput && gdriveFileInput.files && gdriveFileInput.files.length > 0) {
                        formData.append('file', gdriveFileInput.files[0]);
                    } else {
                        alert('Silakan pilih file terlebih dahulu.');
                        return;
                    }
                }

                this.running = true;
                this.logs = ['[1/5] Membaca file...', '[2/5] Menjalankan OCR AI Reader...'];

                try {
                    const res = await fetch('{{ route('admin.ocr.process') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: formData
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.logs = data.logs;
                        this.metadata = data.metadata;
                        if (data.hash) {
                            this.fileHash = data.hash;
                            this.gdriveFileHash = data.hash;
                        }
                        this.done = true;
                    } else {
                        this.logs.push('❌ Gagal memproses OCR: ' + (data.message || 'Error tidak diketahui'));
                    }
                } catch (e) {
                    this.logs.push('❌ Error: ' + e.message);
                } finally {
                    this.running = false;
                }
            },

            resetForm() {
                this.fileSelected = false;
                this.gdriveFileSelected = false;
                this.fileName = '';
                this.gdriveFileName = '';
                this.fileHash = '';
                this.gdriveFileHash = '';
                this.done = false;
                this.logs = [];
                this.metadata = {
                    documentType: 'Ijazah',
                    ownerName: '',
                    documentNumber: '',
                    nisn: '',
                    year: '{{ date('Y') }}',
                    description: ''
                };
                document.getElementById('auFileInput').value = '';
                document.getElementById('realFileHiddenInput').value = '';
            }
        }
    }
    loadGooglePickerScript();
</script>
@endsection
