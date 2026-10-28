@extends('layouts.admin')

@section('title', 'Riwayat Aktivitas Sistem')

@section('content')
<div class="space-y-5">

    <!-- Search Form -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:12px;display:flex;align-items:center;gap:10px;padding:10px 16px;box-shadow:0 1px 4px rgba(37,99,235,0.06)">
        <i data-lucide="search" style="width:16px;height:16px;color:#3b82f6;flex-shrink:0"></i>
        <form action="{{ route('admin.activity') }}" method="GET" style="flex:1;display:flex">
            <input type="text" name="search" placeholder="Cari aktivitas, user, target..."
                   value="{{ request('search') }}"
                   style="flex:1;font-size:13px;background:transparent;outline:none;border:none;color:#1e3a8a;font-family:'Inter',sans-serif"
                   onfocus="this.closest('div').style.borderColor='#3b82f6'"
                   onblur="this.closest('div').style.borderColor='#e0e7ff'">
        </form>
    </div>

    <!-- Activity Table -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f8faff;border-bottom:1.5px solid #e0e7ff">
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Aktivitas</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">User</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Target</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Tipe</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Waktu</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $act)
                        @php $cfg = $typeConfig[$act->type] ?? $typeConfig['system']; @endphp
                        <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s"
                            onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background=''">
                            <td style="padding:11px 18px;font-size:12.5px;font-weight:600;color:#1e3a8a">{{ $act->action }}</td>
                            <td style="padding:11px 18px;font-size:11.5px;color:#475569;font-family:'Courier New',monospace">{{ $act->user_email }}</td>
                            <td style="padding:11px 18px;font-size:12px;color:#334155;font-weight:500">{{ $act->target }}</td>
                            <td style="padding:11px 18px">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:7px;font-size:11px;font-weight:600;background:#eff6ff;color:#1d4ed8;border:1.5px solid #bfdbfe">
                                    <span>{{ $cfg['emoji'] }}</span>
                                    <span>{{ $act->type }}</span>
                                </span>
                            </td>
                            <td style="padding:11px 18px;font-size:11px;color:#64748b;font-family:'Courier New',monospace;white-space:nowrap">{{ $act->getFormattedTimestamp() }}</td>
                            <td style="padding:11px 18px">
                                <code style="font-size:11px;font-family:'Courier New',monospace;color:#2563eb;background:#eff6ff;border:1px solid #bfdbfe;padding:2px 8px;border-radius:6px">{{ $act->ip }}</code>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:48px 18px">
                                <i data-lucide="activity" style="width:36px;height:36px;color:#bfdbfe;margin:0 auto 10px;display:block"></i>
                                <span style="font-size:12.5px;color:#64748b">Belum ada aktivitas tercatat.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:14px 18px;border-top:1.5px solid #e0e7ff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:#f8faff">
            <span style="font-size:11.5px;color:#64748b;font-weight:500">Menampilkan {{ $activities->count() }} aktivitas terakhir</span>
            <div>{{ $activities->links() }}</div>
        </div>
    </div>
</div>
@endsection
