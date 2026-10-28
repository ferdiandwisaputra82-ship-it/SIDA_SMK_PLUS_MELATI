@extends('layouts.admin')

@section('title', 'Log Blockchain Hyperledger Fabric')
@section('subtitle', 'Ledger verifikasi kriptografis & jejak transaksi dokumen digital')

@section('content')
<div class="space-y-5">

    {{-- Success Alert --}}
    @if(session('success'))
        <div id="alert-success" style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:10px;font-size:12.5px;color:#166534;font-weight:500">
            <i data-lucide="check-circle-2" style="width:16px;height:16px;color:#22c55e;flex-shrink:0"></i>
            <span>{{ session('success') }}</span>
            <button onclick="document.getElementById('alert-success').remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#15803d">
                <i data-lucide="x" style="width:14px;height:14px"></i>
            </button>
        </div>
    @endif

    <!-- Blockchain Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @php
        $cards = [
            ['label' => 'Total Transaksi', 'val' => number_format($total), 'icon' => 'link-2', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'icon_color' => '#2563eb', 'accent' => '#2563eb'],
            ['label' => 'Status Confirmed', 'val' => number_format($confirmed), 'icon' => 'check-circle-2', 'bg' => '#f0fdf4', 'border' => '#86efac', 'icon_color' => '#16a34a', 'accent' => '#16a34a'],
            ['label' => 'Fabric Channel', 'val' => $settings['fabric_channel'] ?? 'documentchannel', 'icon' => 'server', 'bg' => '#f3e8ff', 'border' => '#d8b4fe', 'icon_color' => '#9333ea', 'accent' => '#9333ea'],
            ['label' => 'Block Height', 'val' => '#' . (4800 + $total), 'icon' => 'database', 'bg' => '#fffbeb', 'border' => '#fde68a', 'icon_color' => '#d97706', 'accent' => '#d97706']
        ];
        @endphp
        @foreach($cards as $c)
            <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;padding:18px 20px;box-shadow:0 1px 6px rgba(37,99,235,0.07);border-top:3px solid {{ $c['accent'] }}">
                <div style="width:36px;height:36px;border-radius:10px;background:{{ $c['bg'] }};border:1.5px solid {{ $c['border'] }};display:flex;align-items:center;justify-content:center;margin-bottom:10px">
                    <i data-lucide="{{ $c['icon'] }}" style="width:16px;height:16px;color:{{ $c['icon_color'] }}"></i>
                </div>
                <div style="font-size:24px;font-weight:800;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif;line-height:1;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $c['val'] }}</div>
                <div style="font-size:11.5px;font-weight:600;color:#64748b">{{ $c['label'] }}</div>
            </div>
        @endforeach
    </div>

    <!-- Search Form + Action Buttons -->
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <div style="flex:1;background:#fff;border:1.5px solid #e0e7ff;border-radius:12px;display:flex;align-items:center;gap:10px;padding:10px 16px;box-shadow:0 1px 4px rgba(37,99,235,0.06)">
            <i data-lucide="search" style="width:16px;height:16px;color:#3b82f6;flex-shrink:0"></i>
            <form action="{{ route('admin.blockchain') }}" method="GET" style="flex:1;display:flex">
                <input type="text" name="search" placeholder="Cari TX ID, hash SHA-256, nama dokumen..." value="{{ request('search') }}"
                       style="flex:1;font-size:13px;background:transparent;outline:none;border:none;color:#1e3a8a;font-family:'Inter',sans-serif">
            </form>
        </div>
        <form action="{{ route('admin.blockchain.sync') }}" method="POST">
            @csrf
            <button type="submit"
                    style="display:flex;align-items:center;gap:7px;padding:10px 18px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:12px;font-size:12.5px;font-weight:700;cursor:pointer;transition:all .18s;box-shadow:0 2px 8px rgba(37,99,235,0.25);white-space:nowrap"
                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(37,99,235,0.35)'"
                    onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(37,99,235,0.25)'"
                    title="Audit dan sinkronisasi ledger dengan node Hyperledger Fabric">
                <i data-lucide="refresh-cw" style="width:15px;height:15px"></i>
                Audit &amp; Sinkronkan Ledger
            </button>
        </form>
    </div>

    <!-- Table logs -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f8faff;border-bottom:1.5px solid #e0e7ff">
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">TX ID</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Document ID</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Tipe</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Hash (SHA-256)</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Pemilik</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Aksi</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Waktu</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Block</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s"
                            onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background=''">
                            <td style="padding:11px 18px;white-space:nowrap">
                                <code style="font-family:'Courier New',monospace;font-size:11px;font-weight:700;color:#1d4ed8;background:#eff6ff;border:1px solid #bfdbfe;padding:3px 8px;border-radius:6px" title="{{ $entry->tx_id }}">{{ substr($entry->tx_id, 0, 16) }}...</code>
                            </td>
                            <td style="padding:11px 18px;white-space:nowrap">
                                <code style="font-family:'Courier New',monospace;font-size:11px;color:#64748b">{{ substr($entry->document_id, 0, 8) }}...</code>
                            </td>
                            <td style="padding:11px 18px">
                                <span style="display:inline-block;padding:3px 9px;border-radius:6px;font-size:11px;font-weight:600;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0">{{ $entry->document_type }}</span>
                            </td>
                            <td style="padding:11px 18px;white-space:nowrap;font-family:'Courier New',monospace">
                                <code style="font-size:11px;color:#3b82f6;background:#f8fafc;padding:2px 6px;border-radius:4px;border:1px solid #e2e8f0" title="{{ $entry->hash }}">{{ substr($entry->hash, 0, 10) }}...{{ substr($entry->hash, -4) }}</code>
                            </td>
                            <td style="padding:11px 18px;font-size:12px;font-weight:600;color:#1e3a8a;white-space:nowrap">{{ $entry->owner_name }}</td>
                            <td style="padding:11px 18px;white-space:nowrap">
                                <span style="font-size:11px;font-weight:700;color:#1d4ed8;background:#dbeafe;border:1px solid #bfdbfe;padding:2px 8px;border-radius:999px">{{ $entry->action }}</span>
                            </td>
                            <td style="padding:11px 18px;font-size:11px;color:#64748b;font-family:'Courier New',monospace;white-space:nowrap">{{ $entry->getFormattedTimestamp() }}</td>
                            <td style="padding:11px 18px;font-size:11.5px;font-family:'Courier New',monospace;color:#d97706;font-weight:700;white-space:nowrap">#{{ $entry->block_height }}</td>
                            <td style="padding:11px 18px;white-space:nowrap">
                                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:600;background:#f0fdf4;color:#16a34a;border:1.5px solid #86efac">
                                    <i data-lucide="check-circle-2" style="width:12px;height:12px"></i> Confirmed
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:48px 18px">
                                <i data-lucide="link-2" style="width:36px;height:36px;color:#bfdbfe;margin:0 auto 10px;display:block"></i>
                                <span style="font-size:12.5px;color:#64748b">Belum ada transaksi blockchain terdaftar.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:14px 18px;border-top:1.5px solid #e0e7ff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:#f8faff">
            <span style="font-size:11.5px;color:#64748b;font-weight:500">Total {{ $total }} transaksi · Channel: <strong>{{ $settings['fabric_channel'] ?? 'documentchannel' }}</strong> · Chaincode: <strong>{{ $settings['chaincode_name'] ?? 'documentCC' }}</strong></span>
            <div>{{ $entries->links() }}</div>
        </div>
    </div>

</div>
@endsection
