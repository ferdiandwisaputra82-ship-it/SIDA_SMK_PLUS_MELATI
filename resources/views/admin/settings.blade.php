@extends('layouts.admin')

@section('title', 'Pengaturan Sistem')
@section('subtitle', 'Konfigurasi profil sekolah, koneksi Hyperledger Fabric, dan mesin OCR')

@section('content')
<div style="max-width:760px">
    <form action="{{ route('admin.settings.update') }}" method="POST" style="display:flex;flex-direction:column;gap:20px">
        @csrf
        
        <!-- Profile Sekolah -->
        <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;padding:22px;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
            <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;color:#1e3a8a;font-size:14px;display:flex;align-items:center;gap:10px;margin:0 0 18px">
                <div style="width:36px;height:36px;border-radius:10px;background:#f0fdf4;border:1.5px solid #86efac;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i data-lucide="shield" style="width:16px;height:16px;color:#16a34a"></i>
                </div>
                Profil Sekolah / Institusi
            </h3>
            
            <div style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Nama Sekolah / Institusi</label>
                    <input type="text" name="sekolah_nama" value="{{ $settings['sekolah_nama'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;font-weight:600;outline:none">
                </div>
                
                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">NPSN Sekolah</label>
                    <input type="text" name="sekolah_npsn" value="{{ $settings['sekolah_npsn'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;font-family:'Courier New',monospace;outline:none">
                </div>

                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Alamat Sekolah</label>
                    <input type="text" name="sekolah_alamat" value="{{ $settings['sekolah_alamat'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;outline:none">
                </div>

                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Email Resmi</label>
                    <input type="email" name="sekolah_email" value="{{ $settings['sekolah_email'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;font-family:'Courier New',monospace;outline:none">
                </div>
            </div>
        </div>

        <!-- Fabric configs -->
        <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;padding:22px;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
            <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;color:#1e3a8a;font-size:14px;display:flex;align-items:center;gap:10px;margin:0 0 18px">
                <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;border:1.5px solid #bfdbfe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i data-lucide="link-2" style="width:16px;height:16px;color:#2563eb"></i>
                </div>
                Konfigurasi Hyperledger Fabric
            </h3>
            
            <div style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Fabric Channel ID</label>
                    <input type="text" name="fabric_channel" value="{{ $settings['fabric_channel'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1d4ed8;font-family:'Courier New',monospace;outline:none">
                </div>

                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Chaincode Name</label>
                    <input type="text" name="chaincode_name" value="{{ $settings['chaincode_name'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1d4ed8;font-family:'Courier New',monospace;outline:none">
                </div>

                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Peer Endpoints (GRPCS)</label>
                    <input type="text" name="peer_endpoint" value="{{ $settings['peer_endpoint'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1d4ed8;font-family:'Courier New',monospace;outline:none">
                </div>

                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Local MSP ID</label>
                    <input type="text" name="msp_id" value="{{ $settings['msp_id'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1d4ed8;font-family:'Courier New',monospace;outline:none">
                </div>
            </div>
        </div>

        <!-- OCR Configs -->
        <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;padding:22px;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
            <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;color:#1e3a8a;font-size:14px;display:flex;align-items:center;gap:10px;margin:0 0 18px">
                <div style="width:36px;height:36px;border-radius:10px;background:#f3e8ff;border:1.5px solid #d8b4fe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i data-lucide="cpu" style="width:16px;height:16px;color:#9333ea"></i>
                </div>
                Konfigurasi OCR Engine
            </h3>
            
            <div style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Engine</label>
                    <input type="text" name="ocr_engine" value="{{ $settings['ocr_engine'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;font-family:'Courier New',monospace;outline:none">
                </div>

                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Language Model</label>
                    <input type="text" name="ocr_lang" value="{{ $settings['ocr_lang'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;font-family:'Courier New',monospace;outline:none">
                </div>

                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Min. Confidence Threshold (%)</label>
                    <input type="text" name="ocr_threshold" value="{{ $settings['ocr_threshold'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;font-family:'Courier New',monospace;outline:none">
                </div>

                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Max File Size (MB)</label>
                    <input type="text" name="max_file_size" value="{{ $settings['max_file_size'] ?? '' }}"
                           style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;font-family:'Courier New',monospace;outline:none">
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div style="display:flex;gap:12px">
            <button type="submit"
                    style="padding:12px 28px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.28);font-family:'Plus Jakarta Sans',sans-serif"
                    onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
