@extends('layouts.admin')

@section('title', 'Review Hasil OCR')
@section('subtitle', 'Verifikasi data ekstraksi sebelum dicatat ke Hyperledger Fabric')

@section('content')
<div class="space-y-5" x-data="ocrQueueHandler()">
    @if(count($ocrQueue) > 0)
        <div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:14px;padding:16px 18px;display:flex;align-items:flex-start;gap:12px">
            <i data-lucide="alert-circle" style="width:18px;height:18px;color:#d97706;flex-shrink:0;margin-top:2px"></i>
            <div style="font-size:12px;color:#92400e;line-height:1.5">
                <strong style="color:#78350f;font-family:'Plus Jakarta Sans',sans-serif">{{ count($ocrQueue) }} dokumen</strong> memerlukan review manual. Data OCR hasil ekstraksi otomatis perlu diverifikasi sebelum dicatat secara permanen ke ledger Hyperledger Fabric.
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Left: Queue list -->
            <div class="lg:col-span-1 space-y-3">
                <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
                    <div style="padding:14px 18px;border-bottom:1.5px solid #e0e7ff;background:#f8faff">
                        <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;color:#1e3a8a;font-size:12px;text-transform:uppercase;letter-spacing:.08em;margin:0">Antrian Review</h3>
                    </div>
                    <div style="display:flex;flex-direction:column">
                        @foreach($ocrQueue as $idx => $item)
                            <div @click="selectItem({{ $idx }}, {{ json_encode($item) }})"
                                 :style="activeIdx === {{ $idx }} ? 'background:#eff6ff;border-left:4px solid #2563eb' : ''"
                                 style="padding:14px 18px;border-bottom:1px solid #f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:background .15s"
                                 onmouseover="if(activeIdx !== {{ $idx }}) this.style.background='#f8faff'"
                                 onmouseout="if(activeIdx !== {{ $idx }}) this.style.background='transparent'">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:36px;height:36px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px">
                                        📁
                                    </div>
                                    <div>
                                        <div style="font-size:13px;font-weight:700;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px">{{ $item['file_name'] }}</div>
                                        <div style="font-size:11px;color:#d97706;font-weight:600;margin-top:2px">{{ $item['detected_type'] }} · {{ $item['confidence'] }}%</div>
                                    </div>
                                </div>
                                <i data-lucide="chevron-right" style="width:16px;height:16px;color:#94a3b8"></i>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right: Item Detail & Form Edit -->
            <div class="lg:col-span-2">
                <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(37,99,235,0.07)" x-show="activeItem">
                    <form :action="'/admin/ocr-review/' + activeItem.id + '/approve'" method="POST">
                        @csrf
                        <div style="padding:18px 20px;border-bottom:1.5px solid #e0e7ff;background:#f8faff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                            <div>
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                                    <h3 style="font-size:15px;font-weight:800;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif;margin:0" x-text="activeItem.file_name"></h3>
                                    <span style="padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:#fffbeb;color:#d97706;border:1.5px solid #fde68a" x-text="activeItem.detected_type"></span>
                                </div>
                                <p style="font-size:11.5px;color:#64748b;margin:4px 0 0">Diunggah: <span x-text="activeItem.uploaded_at"></span> · Tingkat Akurasi: <strong style="color:#d97706" x-text="activeItem.confidence + '%'"></strong></p>
                            </div>
                            <div style="display:flex;gap:8px">
                                <button type="button" @click="rejectItem(activeItem.id)"
                                        style="padding:8px 16px;border:1.5px solid #cbd5e1;background:#fff;border-radius:9px;font-size:12px;font-weight:600;color:#475569;cursor:pointer">Tolak</button>
                                <button type="submit"
                                        style="padding:8px 20px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif">Setujui &amp; Simpan</button>
                            </div>
                        </div>
                        
                        <div style="padding:20px;display:grid;grid-template-cols:1fr;gap:14px">
                            <template x-for="field in activeItem.fields">
                                <div style="padding:14px;border-radius:12px;border:1.5px solid" :style="field.ok ? 'background:#f8faff;border-color:#e0e7ff' : 'background:#fffbeb;border-color:#fde68a'">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                                        <span style="font-size:11px;font-weight:700;color:#1e3a8a;text-transform:uppercase;letter-spacing:.05em" x-text="field.field"></span>
                                        <div style="display:flex;align-items:center;gap:6px">
                                            <div style="width:48px;height:5px;border-radius:999px;background:#e2e8f0;overflow:hidden">
                                                <div style="height:100%;border-radius:999px" :style="'width:' + field.confidence + '%;background:' + (field.ok ? '#22c55e' : '#f59e0b')"></div>
                                            </div>
                                            <span style="font-size:10.5px;font-weight:700" :style="field.ok ? 'color:#16a34a' : 'color:#d97706'" x-text="field.confidence + '%'"></span>
                                        </div>
                                    </div>
                                    <input :name="getFieldInputName(field.field)" :value="field.value"
                                           style="width:100%;padding:8px 10px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:13px;color:#1e3a8a;font-weight:600;outline:none"
                                           :style="field.ok ? '' : 'border-color:#f59e0b'">
                                    <p style="font-size:10.5px;color:#d97706;margin:5px 0 0;font-weight:500" x-show="!field.ok">⚠️ Kepercayaan rendah — mohon periksa kembali</p>
                                </div>
                            </template>
                        </div>
                    </form>
                    
                    <form :action="'/admin/ocr-review/' + activeItem.id + '/reject'" method="POST" id="rejectForm" style="display:none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>

    @else
        <!-- Empty queue view -->
        <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;padding:48px 24px;text-align:center;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
            <div style="width:56px;height:56px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                <i data-lucide="check-circle-2" style="width:28px;height:28px;color:#16a34a"></i>
            </div>
            <h3 style="font-size:18px;font-weight:800;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 6px">Semua Dokumen Terverifikasi!</h3>
            <p style="font-size:12.5px;color:#64748b;margin:0 0 20px">Tidak ada dokumen dalam antrian review OCR saat ini.</p>
            <a href="{{ route('admin.upload') }}"
               style="display:inline-block;padding:10px 24px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border-radius:12px;font-size:12.5px;font-weight:700;text-decoration:none;font-family:'Plus Jakarta Sans',sans-serif">
               Upload Dokumen Baru
            </a>
        </div>
    @endif
</div>

<script>
    function ocrQueueHandler() {
        const queue = @json($ocrQueue);
        return {
            activeIdx: 0,
            activeItem: queue.length > 0 ? queue[0] : null,

            selectItem(idx, item) {
                this.activeIdx = idx;
                this.activeItem = item;
            },

            getFieldInputName(label) {
                if (label === 'Nama Pemilik') return 'owner_name';
                if (label === 'Nomor Dokumen') return 'document_number';
                if (label === 'NIS/NISN') return 'nisn';
                if (label === 'Tahun') return 'year';
                return 'description';
            },

            rejectItem(id) {
                if (confirm('Tolak dokumen ini dari antrian?')) {
                    const form = document.getElementById('rejectForm');
                    form.action = '/admin/ocr-review/' + id + '/reject';
                    form.submit();
                }
            }
        }
    }
</script>
@endsection
