@extends('layouts.admin')

@section('title', 'Manajemen Dokumen')
@section('subtitle', 'Kelola dan verifikasi semua dokumen sekolah')

@section('content')
<div class="space-y-5" x-data="documentManager()">
    <!-- Top Actions / Filters -->
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <!-- Search & Filters -->
        <form action="{{ route('admin.documents') }}" method="GET" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1;min-width:0">
            <!-- Search -->
            <div style="flex:1;min-width:200px;max-width:380px;background:#fff;border:1.5px solid #e0e7ff;border-radius:12px;display:flex;align-items:center;gap:10px;padding:9px 14px;box-shadow:0 1px 4px rgba(37,99,235,0.06)">
                <i data-lucide="search" style="width:16px;height:16px;color:#3b82f6;flex-shrink:0"></i>
                <input type="text" name="search" placeholder="Cari nama, nomor, NISN..." value="{{ request('search') }}"
                       style="flex:1;font-size:13px;background:transparent;outline:none;border:none;color:#1e3a8a;font-family:'Inter',sans-serif">
            </div>

            <!-- Type -->
            <select name="type" onchange="this.form.submit()" style="background:#fff;border:1.5px solid #e0e7ff;border-radius:12px;padding:9px 14px;font-size:12.5px;color:#1e3a8a;outline:none;cursor:pointer">
                <option value="">Semua Tipe</option>
                @foreach($docTypes as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $typeMeta[$t]['emoji'] }} {{ $t }}</option>
                @endforeach
            </select>

            <!-- Status -->
            <select name="status" onchange="this.form.submit()" style="background:#fff;border:1.5px solid #e0e7ff;border-radius:12px;padding:9px 14px;font-size:12.5px;color:#1e3a8a;outline:none;cursor:pointer">
                <option value="">Semua Status</option>
                <option value="Terverifikasi" {{ request('status') === 'Terverifikasi' ? 'selected' : '' }}>Terverifikasi</option>
                <option value="Menunggu Verifikasi" {{ request('status') === 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                <option value="Ditolak" {{ request('status') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </form>

        <!-- Action Buttons -->
        @if(\App\Config\PermissionConfig::can('documents.upload'))
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
            <button @click="openAddModal()"
                    style="display:flex;align-items:center;gap:6px;padding:9px 18px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:12px;font-size:12.5px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.28);font-family:'Plus Jakarta Sans',sans-serif">
                <i data-lucide="plus" style="width:16px;height:16px"></i>
                <span>Tambah Dokumen</span>
            </button>

            @if(\App\Config\PermissionConfig::can('upload.page'))
            <a href="{{ route('admin.upload') }}"
               style="display:flex;align-items:center;gap:6px;padding:9px 16px;background:#eff6ff;color:#2563eb;border:1.5px solid #bfdbfe;border-radius:12px;font-size:12.5px;font-weight:700;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif">
                <i data-lucide="upload" style="width:16px;height:16px"></i>
                <span>Upload Baru</span>
            </a>
            @endif
        </div>
        @endif
    </div>

    <!-- Main Documents Table -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(37,99,235,0.07)">

        <!-- Scroll Controls -->
        <div id="tableScrollControls" style="display:none;align-items:center;justify-content:space-between;padding:12px 18px 6px;border-bottom:1px solid #f1f5f9">
            <span style="font-size:11px;color:#64748b;display:flex;align-items:center;gap:6px">
                <i data-lucide="move-horizontal" style="width:14px;height:14px;color:#3b82f6"></i>
                <span>Geser untuk melihat lebih banyak kolom</span>
            </span>
            <div style="display:flex;align-items:center;gap:8px">
                <div style="display:flex;align-items:center;gap:6px;margin-right:8px">
                    <div style="width:80px;height:4px;background:#e2e8f0;border-radius:999px;overflow:hidden">
                        <div id="scrollProgressBar" style="height:100%;background:#2563eb;border-radius:999px;width:0%"></div>
                    </div>
                    <span id="scrollProgressText" style="font-size:10px;color:#64748b;font-family:'Courier New',monospace">0%</span>
                </div>
                <button id="btnScrollLeft" onclick="tableScrollBy(-1)" style="padding:6px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;cursor:pointer">
                    <i data-lucide="chevron-left" style="width:14px;height:14px"></i>
                </button>
                <button id="btnScrollRight" onclick="tableScrollBy(1)" style="padding:6px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;cursor:pointer">
                    <i data-lucide="chevron-right" style="width:14px;height:14px"></i>
                </button>
            </div>
        </div>

        <!-- Table Wrapper -->
        <div style="position:relative">
        <div class="overflow-x-auto table-scroll-container" id="docTableScroll">
            <table style="width:100%;border-collapse:collapse;min-width:820px">
                <thead>
                    <tr style="background:#f8faff;border-bottom:1.5px solid #e0e7ff">
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 16px;text-align:left;min-width:140px">Nomor Dokumen</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 16px;text-align:left;min-width:140px">Nama Pemilik</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 16px;text-align:left;min-width:120px">Tipe Dokumen</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 16px;text-align:left;min-width:60px">Tahun</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 16px;text-align:left;min-width:150px">Status</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 16px;text-align:left;min-width:100px">Hash</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 16px;text-align:left;min-width:120px">Waktu Upload</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        @php
                            $meta = $doc->getTypeMeta();
                        @endphp
                        <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;cursor:pointer"
                            onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''"
                            @click="openViewModal({{ json_encode($doc) }})"
                            title="Klik untuk membuka pratinjau &amp; kelola dokumen">
                            <td style="padding:11px 16px">
                                <code style="font-family:'Courier New',monospace;font-size:11.5px;font-weight:700;color:#1d4ed8;background:#eff6ff;border:1.5px solid #bfdbfe;padding:2px 8px;border-radius:6px;transition:all .15s"
                                      onmouseover="this.style.background='#dbeafe';this.style.borderColor='#93c5fd'"
                                      onmouseout="this.style.background='#eff6ff';this.style.borderColor='#bfdbfe'">{{ $doc->document_number }}</code>
                            </td>
                            <td style="padding:11px 16px;font-size:12.5px;font-weight:700;color:#1e3a8a;white-space:nowrap">
                                <span style="transition:color .15s" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#1e3a8a'">{{ $doc->owner_name }}</span>
                            </td>
                            <td style="padding:11px 16px">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:7px;font-size:11.5px;font-weight:500;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0">
                                    <span>{{ $meta['emoji'] }}</span>
                                    <span>{{ $doc->document_type }}</span>
                                </span>
                            </td>
                            <td style="padding:11px 16px;font-size:12px;color:#64748b;font-family:'Courier New',monospace">{{ $doc->year }}</td>
                            <td style="padding:11px 16px;white-space:nowrap">
                                @if($doc->status === 'Terverifikasi')
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:#f0fdf4;color:#16a34a;border:1.5px solid #86efac">
                                        <i data-lucide="check-circle-2" style="width:12px;height:12px"></i> Terverifikasi
                                    </span>
                                @elseif($doc->status === 'Menunggu Verifikasi')
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:#fffbeb;color:#d97706;border:1.5px solid #fde68a">
                                        <i data-lucide="clock" style="width:12px;height:12px"></i> Menunggu
                                    </span>
                                @else
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:#fff1f2;color:#dc2626;border:1.5px solid #fca5a5">
                                        <i data-lucide="x-circle" style="width:12px;height:12px"></i> Ditolak
                                    </span>
                                @endif
                            </td>
                            <td style="padding:11px 16px">
                                <code style="font-family:'Courier New',monospace;font-size:11px;color:#3b82f6" title="{{ $doc->hash }}">{{ substr($doc->hash, 0, 8) }}...{{ substr($doc->hash, -4) }}</code>
                            </td>
                            <td style="padding:11px 16px;font-size:11.5px;color:#64748b;font-family:'Courier New',monospace;white-space:nowrap">{{ $doc->getFormattedUploadedAt() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:48px 16px">
                                <i data-lucide="folder-open" style="width:36px;height:36px;color:#bfdbfe;margin:0 auto 10px;display:block"></i>
                                <span style="font-size:12.5px;color:#64748b">Tidak ada dokumen ditemukan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        <div style="padding:14px 18px;border-top:1.5px solid #e0e7ff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:#f8faff">
            <span style="font-size:11.5px;color:#64748b">Menampilkan {{ $documents->count() }} dari {{ $totalCount }} dokumen</span>
            <div>{{ $documents->links() }}</div>
        </div>
    </div>

    <!-- ─── MODAL: TAMBAH DOKUMEN ─── -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" 
         x-show="addModalOpen" x-transition style="display: none;">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="addModalOpen = false"></div>
        <div style="position:relative;background:#fff;border:1.5px solid #e0e7ff;border-radius:18px;width:100%;max-width:620px;overflow:hidden;box-shadow:0 20px 40px rgba(37,99,235,0.15)">
            <div style="padding:16px 20px;border-bottom:1.5px solid #e0e7ff;display:flex;justify-content:space-between;align-items:center;background:#f8faff">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i data-lucide="cpu" style="width:16px;height:16px;color:#2563eb"></i>
                    </div>
                    <div>
                        <h3 style="font-size:14px;font-weight:800;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif;margin:0">Tambah Dokumen Baru</h3>
                        <p style="font-size:11px;color:#64748b;margin:2px 0 0">Daftarkan hash file dokumen ke Hyperledger Fabric</p>
                    </div>
                </div>
                <button type="button" @click="addModalOpen = false" style="background:none;border:none;cursor:pointer;color:#94a3b8"><i data-lucide="x" style="width:16px;height:16px"></i></button>
            </div>

            <form action="{{ route('admin.documents.store') }}" method="POST" id="addDocForm">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;max-height:70vh;overflow-y:auto">
                    <!-- Kiri: Client-side file hasher -->
                    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;border-right:1px solid #e0e7ff">
                        <div style="font-size:11px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:6px">
                            <i data-lucide="hash" style="width:14px;height:14px"></i> Hash Kriptografis
                        </div>
                        
                        <input type="file" id="modalFileInput" style="display:none" accept="image/*,application/pdf" @change="hashModalFile($event)">
                        
                        <div @click="triggerModalFile()"
                             style="border:2px dashed #bfdbfe;background:#f8faff;border-radius:14px;padding:20px;text-align:center;cursor:pointer;transition:all .18s">
                            <div x-show="modalFileName" style="display:flex;flex-direction:column;gap:4px">
                                <div style="width:36px;height:36px;background:#eff6ff;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 4px">
                                    <i data-lucide="file-text" style="width:18px;height:18px;color:#2563eb"></i>
                                </div>
                                <p style="font-size:12px;font-weight:700;color:#1e3a8a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin:0" x-text="modalFileName"></p>
                                <p style="font-size:10px;color:#3b82f6;margin:0">Klik untuk ganti file</p>
                            </div>
                            <div x-show="!modalFileName" style="display:flex;flex-direction:column;gap:6px">
                                <div style="width:36px;height:36px;background:#eff6ff;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto">
                                    <i data-lucide="upload" style="width:18px;height:18px;color:#64748b"></i>
                                </div>
                                <p style="font-size:12px;color:#475569;margin:0">Pilih file untuk di-hash</p>
                            </div>
                        </div>

                        <div style="background:#f8faff;padding:12px;border-radius:10px;border:1px solid #bfdbfe" x-show="modalHash">
                            <label style="font-size:10px;font-weight:700;color:#2563eb;text-transform:uppercase;display:block;margin-bottom:4px">Hash SHA-256 Terhitung</label>
                            <code style="font-size:10.5px;font-family:'Courier New',monospace;color:#16a34a;word-break:break-all;line-height:1.4" x-text="modalHash"></code>
                        </div>
                        <input type="hidden" name="hash" :value="modalHash">
                        <input type="hidden" name="file_name" :value="modalFileName">
                        <input type="hidden" name="file_mime" :value="modalFileMime">
                    </div>

                    <!-- Kanan: Form fields -->
                    <div style="padding:20px;display:flex;flex-direction:column;gap:12px">
                        <div style="font-size:11px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:6px">
                            <i data-lucide="edit" style="width:14px;height:14px"></i> Metadata Dokumen
                        </div>
                        
                        <div>
                            <label style="font-size:10.5px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Jenis Dokumen *</label>
                            <select name="document_type" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none">
                                @foreach($docTypes as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label style="font-size:10.5px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Nama Pemilik *</label>
                            <input type="text" name="owner_name" placeholder="Contoh: Ahmad Fauzi" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none" required>
                        </div>

                        <div>
                            <label style="font-size:10.5px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Nomor Dokumen *</label>
                            <input type="text" name="document_number" placeholder="Contoh: 100/SK/SMK/2026" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none" required>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            <div>
                                <label style="font-size:10.5px;font-weight:700;color:#475569;display:block;margin-bottom:4px">NISN / NIM</label>
                                <input type="text" name="nisn" placeholder="0058912345" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none">
                            </div>
                            <div>
                                <label style="font-size:10.5px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Tahun</label>
                                <input type="text" name="year" value="{{ date('Y') }}" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none">
                            </div>
                        </div>

                        <div>
                            <label style="font-size:10.5px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Deskripsi</label>
                            <textarea name="description" placeholder="Deskripsi dokumen..." rows="2" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none;resize:none"></textarea>
                        </div>

                        <input type="hidden" name="status" value="Menunggu Verifikasi">
                    </div>
                </div>

                <div style="padding:14px 20px;border-top:1.5px solid #e0e7ff;display:flex;justify-content:flex-end;gap:10px;background:#f8faff">
                    <button type="button" @click="addModalOpen = false" style="padding:8px 16px;border:1.5px solid #cbd5e1;background:#fff;border-radius:9px;font-size:12px;font-weight:600;color:#475569;cursor:pointer">Batal</button>
                    <button type="submit" style="padding:8px 20px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif">Daftarkan ke Blockchain</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL: EDIT DOKUMEN ─── -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" 
         x-show="editModalOpen" x-transition style="display: none;">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="editModalOpen = false"></div>
        <div style="position:relative;background:#fff;border:1.5px solid #e0e7ff;border-radius:18px;width:100%;max-width:480px;overflow:hidden;box-shadow:0 20px 40px rgba(37,99,235,0.15)">
            <div style="padding:16px 20px;border-bottom:1.5px solid #e0e7ff;display:flex;justify-content:space-between;align-items:center;background:#f8faff">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i data-lucide="edit-3" style="width:16px;height:16px;color:#d97706"></i>
                    </div>
                    <div>
                        <h3 style="font-size:14px;font-weight:800;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif;margin:0">Edit Metadata Dokumen</h3>
                        <p style="font-size:11px;color:#d97706;margin:2px 0 0;font-family:'Courier New',monospace" x-text="editForm.document_number"></p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" style="background:none;border:none;cursor:pointer;color:#94a3b8"><i data-lucide="x" style="width:16px;height:16px"></i></button>
            </div>

            <form :action="editActionUrl" method="POST">
                @csrf
                @method('PUT')
                <div style="padding:20px;display:flex;flex-direction:column;gap:12px;max-height:70vh;overflow-y:auto">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Nama Pemilik *</label>
                        <input type="text" name="owner_name" x-model="editForm.owner_name" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none" required>
                    </div>

                    <div>
                        <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Jenis Dokumen *</label>
                        <select name="document_type" x-model="editForm.document_type" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none">
                            @foreach($docTypes as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div>
                            <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">NISN / NIM</label>
                            <input type="text" name="nisn" x-model="editForm.nisn" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Tahun</label>
                            <input type="text" name="year" x-model="editForm.year" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none">
                        </div>
                    </div>

                    <div>
                        <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Deskripsi</label>
                        <textarea name="description" x-model="editForm.description" rows="2" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none;resize:none"></textarea>
                    </div>

                    <div>
                        <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Status Dokumen</label>
                        <select name="status" x-model="editForm.status" style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;color:#1e3a8a;outline:none">
                            <option value="Terverifikasi">Terverifikasi</option>
                            <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                            <option value="Ditolak">Ditolak</option>
                        </select>
                    </div>
                </div>

                <div style="padding:14px 20px;border-top:1.5px solid #e0e7ff;display:flex;justify-content:flex-end;gap:10px;background:#f8faff">
                    <button type="button" @click="editModalOpen = false" style="padding:8px 16px;border:1.5px solid #cbd5e1;background:#fff;border-radius:9px;font-size:12px;font-weight:600;color:#475569;cursor:pointer">Batal</button>
                    <button type="submit" style="padding:8px 20px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL: PRATINJAU & DETAIL DOKUMEN ─── -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5" 
         x-show="viewModalOpen" x-transition style="display: none;" @click="viewModalOpen = false">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div style="position:relative;background:#fff;border:1.5px solid #cbd5e1;border-radius:20px;width:100%;max-width:1140px;height:90vh;max-height:860px;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 25px 60px -15px rgba(15,23,42,0.35)" @click.stop>
            
            <!-- Modal Header -->
            <div style="padding:14px 22px;border-bottom:1.5px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;background:#f8fafc;flex-shrink:0">
                <div style="display:flex;align-items:center;gap:12px;min-width:0">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#ffffff;box-shadow:0 4px 12px rgba(37,99,235,0.25);flex-shrink:0">
                        <i data-lucide="file-text" style="width:20px;height:20px"></i>
                    </div>
                    <div style="min-width:0">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <h3 style="font-size:15px;font-weight:800;color:#0f172a;font-family:'Plus Jakarta Sans',sans-serif;margin:0" x-text="viewDoc.owner_name || 'Detail Dokumen'"></h3>
                            <span style="font-size:11px;padding:2px 8px;border-radius:6px;background:#eff6ff;color:#1d4ed8;font-weight:700;border:1px solid #bfdbfe" x-text="viewDoc.document_type"></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:2px">
                            <code style="font-size:11px;color:#2563eb;font-family:'Courier New',monospace;font-weight:700" x-text="viewDoc.document_number"></code>
                            <span style="color:#94a3b8;font-size:11px">•</span>
                            <span style="font-size:11.5px;color:#64748b" x-text="'Tahun ' + (viewDoc.year || '-')"></span>
                        </div>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
                    <!-- Status Badge -->
                    <template x-if="viewDoc.status === 'Terverifikasi'">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:#f0fdf4;color:#16a34a;border:1.5px solid #86efac">
                            <i data-lucide="check-circle-2" style="width:13px;height:13px"></i>
                            <span>Terverifikasi</span>
                        </span>
                    </template>
                    <template x-if="viewDoc.status === 'Menunggu Verifikasi'">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:#fffbeb;color:#d97706;border:1.5px solid #fde68a">
                            <i data-lucide="clock" style="width:13px;height:13px"></i>
                            <span>Menunggu Verifikasi</span>
                        </span>
                    </template>
                    <template x-if="viewDoc.status === 'Ditolak'">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:999px;font-size:11.5px;font-weight:700;background:#fff1f2;color:#dc2626;border:1.5px solid #fca5a5">
                            <i data-lucide="x-circle" style="width:13px;height:13px"></i>
                            <span>Ditolak</span>
                        </span>
                    </template>

                    <!-- Close Button -->
                    <button type="button" @click="viewModalOpen = false" 
                            style="width:32px;height:32px;border-radius:9px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;cursor:pointer;transition:all .15s"
                            onmouseover="this.style.background='#e2e8f0';this.style.color='#0f172a'"
                            onmouseout="this.style.background='#f1f5f9';this.style.color='#64748b'">
                        <i data-lucide="x" style="width:16px;height:16px"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Content (2-Column Grid) -->
            <div class="view-modal-grid" style="flex:1;min-height:0;overflow-y:auto;padding:18px 22px;background:#f8fafc;display:grid;grid-template-columns:minmax(0, 1.35fr) minmax(320px, 1fr);gap:20px">
                
                <!-- Left Column: Document Preview -->
                <div style="display:flex;flex-direction:column;gap:10px;min-height:480px">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                        <div style="display:flex;align-items:center;gap:6px">
                            <i data-lucide="file-check-2" style="width:16px;height:16px;color:#2563eb"></i>
                            <span style="font-size:12.5px;font-weight:800;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif">Tampilan Berkas Dokumen</span>
                            <span style="font-size:11px;color:#64748b;background:#e2e8f0;padding:2px 8px;border-radius:6px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="viewDoc.file_name || 'Berkas Dokumen'"></span>
                        </div>
                        <template x-if="viewDoc.file_path">
                            <a :href="'/admin/documents/' + viewDoc.id + '/file'" target="_blank"
                               style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;color:#2563eb;text-decoration:none;padding:4px 10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:7px">
                                <i data-lucide="external-link" style="width:12px;height:12px"></i>
                                <span>Buka di Tab Baru</span>
                            </a>
                        </template>
                    </div>

                    <!-- Viewer Container -->
                    <div style="flex:1;min-height:460px;background:#ffffff;border:1.5px solid #cbd5e1;border-radius:14px;overflow:hidden;position:relative;display:flex;align-items:center;justify-content:center;box-shadow:inset 0 2px 4px rgba(0,0,0,0.02)">
                        <!-- PDF Viewer -->
                        <template x-if="viewDoc.file_path && isPdfDoc(viewDoc)">
                            <iframe :src="'/admin/documents/' + viewDoc.id + '/file#toolbar=1&navpanes=0&scrollbar=1&view=FitH'"
                                    style="width:100%;height:100%;min-height:460px;border:none;background:#ffffff"
                                    title="Pratinjau PDF"></iframe>
                        </template>

                        <!-- Image Viewer -->
                        <template x-if="viewDoc.file_path && isImageDoc(viewDoc)">
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;background:#f1f5f9;min-height:460px">
                                <img :src="'/admin/documents/' + viewDoc.id + '/file'"
                                     :alt="viewDoc.document_number"
                                     style="max-width:100%;max-height:460px;object-fit:contain;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.12)">
                            </div>
                        </template>

                        <!-- Unsupported File Fallback -->
                        <template x-if="viewDoc.file_path && !isPdfDoc(viewDoc) && !isImageDoc(viewDoc)">
                            <div style="text-align:center;padding:40px 20px">
                                <div style="width:56px;height:56px;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                                    <i data-lucide="file" style="width:28px;height:28px;color:#2563eb"></i>
                                </div>
                                <h4 style="font-size:14px;font-weight:800;color:#1e3a8a;margin:0 0 6px" x-text="viewDoc.file_name"></h4>
                                <p style="font-size:12px;color:#64748b;margin:0 0 16px">Format file ini dapat diunduh langsung untuk dibuka di komputer.</p>
                                <a :href="'/admin/documents/' + viewDoc.id + '/download'"
                                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#2563eb;color:#ffffff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none">
                                    <i data-lucide="download" style="width:14px;height:14px"></i> Unduh File
                                </a>
                            </div>
                        </template>

                        <!-- No File Attached -->
                        <template x-if="!viewDoc.file_path">
                            <div style="text-align:center;padding:40px 20px">
                                <div style="width:56px;height:56px;background:#fff1f2;border:1.5px solid #fecdd3;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                                    <i data-lucide="file-question" style="width:28px;height:28px;color:#e11d48"></i>
                                </div>
                                <h4 style="font-size:14px;font-weight:800;color:#9f1239;margin:0 0 6px">Berkas Fisik Belum Tersedia</h4>
                                <p style="font-size:12px;color:#64748b;margin:0">Dokumen ini belum memiliki lampiran berkas digital (PDF/Gambar).</p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Right Column: Metadata & Blockchain Details -->
                <div style="display:flex;flex-direction:column;gap:14px">
                    
                    <!-- Status Notification Banner -->
                    <div>
                        <template x-if="viewDoc.status === 'Terverifikasi'">
                            <div style="display:flex;align-items:flex-start;gap:10px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:12px 14px;color:#166534">
                                <i data-lucide="check-circle-2" style="width:18px;height:18px;color:#16a34a;flex-shrink:0;margin-top:1px"></i>
                                <div style="font-size:12px;line-height:1.4">
                                    <strong style="display:block;font-size:12.5px;color:#15803d;margin-bottom:2px">Dokumen Sah &amp; Disetujui</strong>
                                    Dokumen ini telah disetujui. Anda dapat melakukan pembubuhan barcode keaslian atau mencetak surat keabsahan.
                                </div>
                            </div>
                        </template>
                        <template x-if="viewDoc.status === 'Menunggu Verifikasi'">
                            <div style="display:flex;align-items:flex-start;gap:10px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;padding:12px 14px;color:#92400e">
                                <i data-lucide="clock" style="width:18px;height:18px;color:#d97706;flex-shrink:0;margin-top:1px"></i>
                                <div style="font-size:12px;line-height:1.4">
                                    <strong style="display:block;font-size:12.5px;color:#b45309;margin-bottom:2px">Menunggu Verifikasi</strong>
                                    Dokumen baru diunggah dan membutuhkan persetujuan Super Admin sebelum dapat dibubuhi barcode keaslian.
                                </div>
                            </div>
                        </template>
                        <template x-if="viewDoc.status === 'Ditolak'">
                            <div style="display:flex;align-items:flex-start;gap:10px;background:#fff1f2;border:1.5px solid #fca5a5;border-radius:12px;padding:12px 14px;color:#991b1b">
                                <i data-lucide="x-circle" style="width:18px;height:18px;color:#dc2626;flex-shrink:0;margin-top:1px"></i>
                                <div style="font-size:12px;line-height:1.4">
                                    <strong style="display:block;font-size:12.5px;color:#b91c1c;margin-bottom:2px">Dokumen Ditolak</strong>
                                    Dokumen tidak lolos verifikasi dan pembubuhan barcode keaslian dinonaktifkan.
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Metadata Card -->
                    <div style="background:#ffffff;border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
                        <div style="font-size:12px;font-weight:800;color:#1e3a8a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;display:flex;align-items:center;gap:6px">
                            <i data-lucide="info" style="width:14px;height:14px;color:#2563eb"></i> Data Rincian Dokumen
                        </div>
                        <div style="display:flex;flex-direction:column;gap:8px;font-size:12px">
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f1f5f9">
                                <span style="color:#64748b">Nama Pemilik</span>
                                <span style="font-weight:700;color:#0f172a" x-text="viewDoc.owner_name"></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f1f5f9">
                                <span style="color:#64748b">Nomor Dokumen</span>
                                <code style="font-weight:700;color:#1d4ed8;font-family:'Courier New',monospace" x-text="viewDoc.document_number"></code>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f1f5f9">
                                <span style="color:#64748b">Jenis &amp; Tahun</span>
                                <span style="font-weight:600;color:#0f172a" x-text="viewDoc.document_type + ' (' + viewDoc.year + ')'"></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f1f5f9">
                                <span style="color:#64748b">NISN / NIM</span>
                                <span style="font-weight:600;color:#0f172a;font-family:'Courier New',monospace" x-text="viewDoc.nisn || '—'"></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f1f5f9">
                                <span style="color:#64748b">Diunggah Oleh</span>
                                <span style="font-weight:600;color:#0f172a" x-text="viewDoc.uploaded_by || '—'"></span>
                            </div>
                            <div style="padding:8px 0 0" x-show="viewDoc.description">
                                <span style="color:#64748b;display:block;font-size:11px;margin-bottom:3px">Keterangan:</span>
                                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:11.5px;color:#334155" x-text="viewDoc.description"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Action Buttons) -->
            <div style="padding:12px 22px;border-top:1.5px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;background:#f8fafc;flex-shrink:0">
                
                <!-- Left Action Group: Edit, Unduh, Cetak -->
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    @if(\App\Config\PermissionConfig::can('documents.edit'))
                        <button type="button" @click="openEditModal(viewDoc)"
                                style="padding:7px 14px;background:#fffbeb;border:1.5px solid #fde68a;color:#b45309;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s"
                                onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='#fffbeb'">
                            <i data-lucide="edit-3" style="width:14px;height:14px"></i>
                            <span>Edit Dokumen</span>
                        </button>
                    @endif

                    @if(\App\Config\PermissionConfig::can('documents.download'))
                        <a :href="'/admin/documents/' + viewDoc.id + '/download'" x-show="viewDoc.file_path"
                           style="padding:7px 14px;background:#eff6ff;border:1.5px solid #bfdbfe;color:#1d4ed8;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .15s"
                           onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                            <i data-lucide="download" style="width:14px;height:14px"></i>
                            <span>Unduh Berkas</span>
                        </a>
                    @endif

                    @if(\App\Config\PermissionConfig::can('documents.print'))
                        <a :href="'/admin/documents/' + viewDoc.id + '/print'" target="_blank"
                           style="padding:7px 14px;background:#eff6ff;border:1.5px solid #bfdbfe;color:#1d4ed8;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .15s"
                           onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                            <i data-lucide="printer" style="width:14px;height:14px"></i>
                            <span>Cetak Dokumen</span>
                        </a>
                    @endif

                    @if(\App\Config\PermissionConfig::can('documents.delete'))
                        <form :action="'/admin/documents/' + viewDoc.id" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini secara permanen?')" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    style="padding:7px 14px;background:#fff1f2;border:1.5px solid #fca5a5;color:#dc2626;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s"
                                    onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fff1f2'">
                                <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                                <span>Hapus</span>
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Right Action Group: Stamp, Approve, Reject, Tutup -->
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    @if(\App\Config\PermissionConfig::can('documents.stamp'))
                        <!-- Fitur Bubuhi kalau sudah disetujui -->
                        <a :href="'/admin/documents/' + viewDoc.id + '/stamp'"
                           x-show="viewDoc.status === 'Terverifikasi' && viewDoc.file_path"
                           style="padding:7px 16px;background:linear-gradient(135deg,#4f46e5,#4338ca);color:#ffffff;border:none;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(79,70,229,0.25);transition:all .15s">
                            <i data-lucide="stamp" style="width:14px;height:14px"></i>
                            <span>Atur &amp; Bubuhi Barcode</span>
                        </a>
                    @endif

                    @if(\App\Config\PermissionConfig::can('documents.approve'))
                        <!-- Fitur Setujui -->
                        <form :action="'/admin/documents/' + viewDoc.id + '/approve'" method="POST" x-show="viewDoc.status !== 'Terverifikasi'" style="display:inline">
                            @csrf
                            <button type="submit"
                                    style="padding:7px 15px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 3px 10px rgba(22,163,74,0.25)">
                                <i data-lucide="check-circle-2" style="width:14px;height:14px"></i>
                                <span>Setujui</span>
                            </button>
                        </form>

                        <!-- Fitur Tolak -->
                        <form :action="'/admin/documents/' + viewDoc.id + '/reject'" method="POST" x-show="viewDoc.status !== 'Ditolak'" style="display:inline">
                            @csrf
                            <button type="submit"
                                    style="padding:7px 15px;background:#ffffff;color:#dc2626;border:1.5px solid #fca5a5;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px">
                                <i data-lucide="x-circle" style="width:14px;height:14px"></i>
                                <span>Tolak</span>
                            </button>
                        </form>
                    @endif

                    <button type="button" @click="viewModalOpen = false" 
                            style="padding:7px 16px;background:#e2e8f0;color:#334155;border:none;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;transition:background .15s"
                            onmouseover="this.style.background='#cbd5e1'" onmouseout="this.style.background='#e2e8f0'">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* Custom scrollbar untuk tabel dokumen */
#docTableScroll::-webkit-scrollbar { height: 5px; }
#docTableScroll::-webkit-scrollbar-track { background: #eef2ff; }
#docTableScroll::-webkit-scrollbar-thumb { background: #bfdbfe; border-radius: 99px; }
#docTableScroll::-webkit-scrollbar-thumb:hover { background: #60a5fa; }
.table-scroll-container { cursor: grab; }
.table-scroll-container.is-dragging { cursor: grabbing; user-select: none; }

@media (max-width: 900px) {
    .view-modal-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
    function tableScrollBy(dir) {
        const el = document.getElementById('docTableScroll');
        if (!el) return;
        const amount = Math.min(el.clientWidth * 0.6, 350);
        el.scrollBy({ left: dir * amount, behavior: 'smooth' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container  = document.getElementById('docTableScroll');
        const controls   = document.getElementById('tableScrollControls');
        const progressBar= document.getElementById('scrollProgressBar');
        const progressTxt= document.getElementById('scrollProgressText');

        function updateScroll() {
            if (!container) return;
            const { scrollLeft, scrollWidth, clientWidth } = container;
            const maxScroll = scrollWidth - clientWidth;
            const pct = maxScroll > 0 ? Math.round((scrollLeft / maxScroll) * 100) : 0;

            if (scrollWidth > clientWidth + 4) {
                if (controls) controls.style.display = 'flex';
            }
            if (progressBar) progressBar.style.width = pct + '%';
            if (progressTxt) progressTxt.textContent = pct + '%';
        }

        if (container) {
            container.addEventListener('scroll', updateScroll, { passive: true });
            window.addEventListener('resize', updateScroll);
            updateScroll();
            setTimeout(updateScroll, 100);
            setTimeout(updateScroll, 500);

            let isDragging = false, startX = 0, scrollStart = 0;
            container.addEventListener('mousedown', (e) => {
                if (e.target.closest('button, a, input, select, form')) return;
                isDragging = true;
                startX = e.pageX - container.offsetLeft;
                scrollStart = container.scrollLeft;
                container.style.cursor = 'grabbing';
            });
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                e.preventDefault();
                const x = e.pageX - container.offsetLeft;
                container.scrollLeft = scrollStart - (x - startX) * 1.5;
            });
            document.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    container.style.cursor = 'grab';
                }
            });
        }
    });

    function documentManager() {
        return {
            addModalOpen: false,
            editModalOpen: false,
            viewModalOpen: false,
            
            modalFileName: '',
            modalFileMime: '',
            modalHash: '',
            
            editActionUrl: '',
            editForm: {
                document_type: '',
                owner_name: '',
                document_number: '',
                nisn: '',
                year: '',
                description: '',
                status: ''
            },
            
            viewDoc: {},

            isPdfDoc(doc) {
                if (!doc || !doc.file_path) return false;
                const mime = (doc.file_mime || '').toLowerCase();
                const name = (doc.file_name || doc.file_path || '').toLowerCase();
                return mime.includes('pdf') || name.endsWith('.pdf');
            },

            isImageDoc(doc) {
                if (!doc || !doc.file_path) return false;
                const mime = (doc.file_mime || '').toLowerCase();
                const name = (doc.file_name || doc.file_path || '').toLowerCase();
                return mime.startsWith('image/') || /\.(png|jpe?g|webp|gif|svg)$/i.test(name);
            },

            openAddModal() {
                this.modalFileName = '';
                this.modalFileMime = '';
                this.modalHash = '';
                this.addModalOpen = true;
            },

            triggerModalFile() {
                document.getElementById('modalFileInput').click();
            },

            async hashModalFile(event) {
                const file = event.target.files[0];
                if (file) {
                    this.modalFileName = file.name;
                    this.modalFileMime = file.type;
                    
                    try {
                        const buf = await file.arrayBuffer();
                        const hashBuffer = await crypto.subtle.digest("SHA-256", buf);
                        const hashArray = Array.from(new Uint8Array(hashBuffer));
                        this.modalHash = "0x" + hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
                    } catch (e) {
                        this.modalHash = 'Gagal menghitung hash client';
                    }
                }
            },

            openEditModal(doc) {
                this.viewModalOpen = false;
                this.editActionUrl = '/admin/documents/' + doc.id;
                this.editForm = {
                    document_type: doc.document_type || '',
                    owner_name: doc.owner_name || '',
                    document_number: doc.document_number || '',
                    nisn: doc.nisn || '',
                    year: doc.year || '',
                    description: doc.description || '',
                    status: doc.status || ''
                };
                this.editModalOpen = true;
                this.$nextTick(() => {
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                });
            },

            openViewModal(doc) {
                this.viewDoc = doc;
                this.viewModalOpen = true;
                this.$nextTick(() => {
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                });
                setTimeout(() => {
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                }, 50);
            }
        }
    }
</script>
@endsection
