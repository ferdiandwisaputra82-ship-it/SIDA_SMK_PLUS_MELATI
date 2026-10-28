@extends('layouts.admin')

@section('title', 'Database Console')
@section('subtitle', 'Eksekusi query dan pemantauan statistik database aktif')

@section('content')
<div class="space-y-5" x-data="dbConsoleHandler()">
    <!-- Top Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        @php
        $cards = [
            ['label' => 'Status Database', 'val' => '🟢 Terhubung', 'icon' => 'activity', 'bg' => '#f0fdf4', 'border' => '#86efac', 'icon_color' => '#16a34a'],
            ['label' => 'DB Engine', 'val' => strtoupper(config('database.default')), 'icon' => 'server', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'icon_color' => '#2563eb'],
            ['label' => 'Total Dokumen', 'val' => number_format($totalDocs), 'icon' => 'folder-open', 'bg' => '#f3e8ff', 'border' => '#d8b4fe', 'icon_color' => '#9333ea'],
            ['label' => 'Total TX Blockchain', 'val' => number_format($totalTx), 'icon' => 'link-2', 'bg' => '#fffbeb', 'border' => '#fde68a', 'icon_color' => '#d97706']
        ];
        @endphp
        @foreach($cards as $c)
            <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;padding:16px 18px;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:38px;height:38px;border-radius:10px;background:{{ $c['bg'] }};border:1.5px solid {{ $c['border'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i data-lucide="{{ $c['icon'] }}" style="width:16px;height:16px;color:{{ $c['icon_color'] }}"></i>
                    </div>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif">{{ $c['label'] }}</div>
                        <div style="font-size:13px;color:#475569;margin-top:2px;font-weight:600">{{ $c['val'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- SQL Query Editor -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;padding:20px;box-shadow:0 1px 6px rgba(37,99,235,0.07);display:flex;flex-direction:column;gap:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;color:#1e3a8a;font-size:14px;margin:0">SQL Query Editor</h3>
                <p style="font-size:11.5px;color:#64748b;margin:2px 0 0">Jalankan SQL SELECT pada database aktif (SQLite/MySQL)</p>
            </div>
            
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" @click="setQuery('SELECT * FROM documents LIMIT 5;')"
                        style="padding:5px 12px;font-size:11px;background:#eff6ff;color:#1d4ed8;border:1.5px solid #bfdbfe;border-radius:8px;font-weight:600;cursor:pointer">Semua Dokumen</button>
                <button type="button" @click="setQuery('SELECT document_type, COUNT(*) as jumlah FROM documents GROUP BY document_type;')"
                        style="padding:5px 12px;font-size:11px;background:#eff6ff;color:#1d4ed8;border:1.5px solid #bfdbfe;border-radius:8px;font-weight:600;cursor:pointer">Per Tipe</button>
                <button type="button" @click="setQuery('SELECT * FROM blockchain_entries LIMIT 5;')"
                        style="padding:5px 12px;font-size:11px;background:#eff6ff;color:#1d4ed8;border:1.5px solid #bfdbfe;border-radius:8px;font-weight:600;cursor:pointer">Blockchain Log</button>
            </div>
        </div>

        <textarea x-model="sqlQuery"
                  style="width:100%;height:110px;padding:12px;background:#0f172a;color:#38bdf8;font-family:'Courier New',monospace;font-size:12px;border:1.5px solid #1e293b;border-radius:10px;outline:none;line-height:1.5"
                  placeholder="SELECT * FROM documents LIMIT 5;"></textarea>
        
        <!-- Error Alert -->
        <div style="background:#fff1f2;border:1.5px solid #fca5a5;border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:8px" x-show="error" style="display:none">
            <i data-lucide="alert-circle" style="width:16px;height:16px;color:#dc2626;flex-shrink:0"></i>
            <span style="font-size:12px;color:#991b1b;font-family:'Courier New',monospace" x-text="error"></span>
        </div>

        <!-- Buttons -->
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="button" @click="runQuery()" :disabled="running"
                    style="padding:9px 20px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;display:flex;align-items:center;gap:6px">
                <i data-lucide="cpu" style="width:14px;height:14px" x-show="!running"></i>
                <svg x-show="running" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" style="display:none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Jalankan Query
            </button>
            <button type="button" @click="clear()" style="padding:9px 16px;border:1.5px solid #cbd5e1;background:#fff;border-radius:10px;font-size:12px;font-weight:600;color:#475569;cursor:pointer">Clear</button>
        </div>
    </div>

    <!-- Query Results -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(37,99,235,0.07)" x-show="results" style="display:none">
        <div style="padding:12px 18px;border-bottom:1.5px solid #e0e7ff;background:#f8faff;font-family:'Plus Jakarta Sans',sans-serif">
            <span style="font-size:12px;font-weight:700;color:#1e3a8a" x-text="'Hasil Query (' + (results ? results.rows.length : 0) + ' baris)'"></span>
        </div>
        <div style="overflow-x:auto;max-height:300px;overflow-y:auto">
            <table style="width:100%;border-collapse:collapse;font-family:'Courier New',monospace;font-size:11.5px">
                <thead>
                    <tr style="background:#f8faff;border-bottom:1.5px solid #e0e7ff">
                        <template x-for="col in (results ? results.columns : [])">
                            <th style="padding:10px 14px;color:#64748b;font-weight:700;text-align:left" x-text="col"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in (results ? results.rows : [])">
                        <tr style="border-bottom:1px solid #f1f5f9">
                            <template x-for="val in row">
                                <td style="padding:10px 14px;color:#1e3a8a" x-text="val === null ? 'NULL' : String(val)"></td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DB Logs Console -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
        <div style="padding:12px 18px;border-bottom:1.5px solid #e0e7ff;background:#f8faff">
            <span style="font-size:12px;font-weight:700;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif">Log SQL Engine</span>
        </div>
        <div style="background:#0f172a;padding:14px;font-family:'Courier New',monospace;font-size:11px;color:#38bdf8;max-height:160px;overflow-y:auto">
            <template x-for="log in logs">
                <div x-text="log" style="margin-bottom:3px"></div>
            </template>
        </div>
    </div>
</div>

<script>
    function dbConsoleHandler() {
        return {
            sqlQuery: 'SELECT * FROM documents LIMIT 5;',
            error: '',
            running: false,
            results: null,
            logs: [
                '[' + new Date().toLocaleTimeString() + '] SQLite/MySQL Web Driver Active.',
                '[' + new Date().toLocaleTimeString() + '] Database engine is ready for read-only queries.'
            ],

            setQuery(query) {
                this.sqlQuery = query;
            },

            clear() {
                this.sqlQuery = '';
                this.results = null;
                this.error = '';
            },

            async runQuery() {
                if (!this.sqlQuery.trim()) return;

                this.running = true;
                this.error = '';
                this.results = null;
                
                const time = new Date().toLocaleTimeString();
                this.logs.push('[' + time + '] Executing query: ' + this.sqlQuery);

                try {
                    const res = await fetch('{{ route('admin.database.query') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ query: this.sqlQuery })
                    });

                    const data = await res.json();
                    if (data.error) {
                        this.error = data.error;
                        this.logs.push('[' + new Date().toLocaleTimeString() + '] ❌ Error: ' + data.error);
                    } else if (data.success) {
                        this.results = data;
                        this.logs.push('[' + new Date().toLocaleTimeString() + '] ✓ Query successful. Count: ' + data.count);
                    }
                } catch (e) {
                    this.error = e.message;
                    this.logs.push('[' + new Date().toLocaleTimeString() + '] ❌ Error: ' + e.message);
                } finally {
                    this.running = false;
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });
                }
            }
        }
    }
</script>
@endsection
