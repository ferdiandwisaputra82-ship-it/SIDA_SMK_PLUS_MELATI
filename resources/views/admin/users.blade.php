@extends('layouts.admin')

@section('title', 'Manajemen Pengguna & Hak Akses')
@section('subtitle', 'Kelola akun pengguna, peran, dan wewenang di sistem SIDA')

@section('content')
<div class="space-y-5" x-data="userManagerHandler()" x-init="init()">

    {{-- Validation errors --}}
    @if($errors->any())
    <div style="background:#fff1f2;border:1.5px solid #fca5a5;color:#991b1b;border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:10px">
        <i data-lucide="alert-circle" style="width:18px;height:18px;color:#dc2626;flex-shrink:0;margin-top:2px"></i>
        <div>
            <div style="font-size:13px;font-weight:700;margin-bottom:4px;font-family:'Plus Jakarta Sans',sans-serif">Gagal menyimpan pengguna:</div>
            <ul style="font-size:12px;margin:0;padding-left:16px;line-height:1.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Info Role Card -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 6px rgba(37,99,235,0.06)">
        <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;border:1.5px solid #bfdbfe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i data-lucide="shield-check" style="width:16px;height:16px;color:#2563eb"></i>
        </div>
        <div style="font-size:12px;color:#475569;line-height:1.5">
            <strong style="color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif">Peran dalam Sistem:</strong>
            <span style="color:#2563eb;font-weight:600">Super Admin</span> (akses penuh) ·
            <span style="color:#1d4ed8;font-weight:600">Admin</span> (kelola dokumen) ·
            <span style="color:#0369a1;font-weight:600">Operator/TU</span> (upload dokumen) ·
            <span style="color:#0f766e;font-weight:600">Guru</span> (upload dokumen)
        </div>
    </div>

    <!-- Actions & Filter -->
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div style="flex:1;max-width:420px;background:#fff;border:1.5px solid #e0e7ff;border-radius:12px;display:flex;align-items:center;gap:10px;padding:10px 16px;box-shadow:0 1px 4px rgba(37,99,235,0.06)">
            <i data-lucide="search" style="width:16px;height:16px;color:#3b82f6;flex-shrink:0"></i>
            <form action="{{ route('admin.users') }}" method="GET" style="flex:1;display:flex">
                <input type="text" name="search" placeholder="Cari nama atau email..." value="{{ request('search') }}"
                       style="flex:1;font-size:13px;background:transparent;outline:none;border:none;color:#1e3a8a;font-family:'Inter',sans-serif">
            </form>
        </div>
        
        <button @click="openAddModal()"
                style="display:flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:12px;font-size:12.5px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.28);font-family:'Plus Jakarta Sans',sans-serif;transition:all .18s"
                onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
            <i data-lucide="plus" style="width:16px;height:16px"></i> Tambah Pengguna
        </button>
    </div>

    <!-- Users Table -->
    <div style="background:#fff;border:1.5px solid #e0e7ff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(37,99,235,0.07)">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f8faff;border-bottom:1.5px solid #e0e7ff">
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Pengguna</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Email</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Peran &amp; Akses</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Status</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Login Terakhir</th>
                        <th style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:12px 18px;text-align:left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        @php
                            $roleStyleMap = [
                                'Super Admin' => 'background:#f3e8ff;color:#7e22ce;border:1px solid #d8b4fe',
                                'Admin'       => 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe',
                                'Operator'    => 'background:#e0f2fe;color:#0369a1;border:1px solid #7dd3fc',
                                'Guru'        => 'background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4',
                            ];
                            $roleBadgeStyle = $roleStyleMap[$u->role] ?? 'background:#f1f5f9;color:#334155;border:1px solid #cbd5e1';
                        @endphp

                        <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s"
                            onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background=''">
                            <td style="padding:12px 18px;white-space:nowrap">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#2563eb,#1d4ed8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700;flex-shrink:0;font-family:'Plus Jakarta Sans',sans-serif">
                                        {{ $u->getInitials() }}
                                    </div>
                                    <span style="font-size:13px;font-weight:700;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif">{{ $u->name }}</span>
                                </div>
                            </td>
                            <td style="padding:12px 18px;font-size:12px;color:#475569;font-family:'Courier New',monospace">{{ $u->email }}</td>
                            <td style="padding:12px 18px;white-space:nowrap">
                                <span style="padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;{{ $roleBadgeStyle }}">{{ $u->role }}</span>
                            </td>
                            <td style="padding:12px 18px;white-space:nowrap">
                                @if($u->status === 'Aktif')
                                    <span style="padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:#f0fdf4;color:#16a34a;border:1.5px solid #86efac">Aktif</span>
                                @else
                                    <span style="padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:#fff1f2;color:#dc2626;border:1.5px solid #fca5a5">Nonaktif</span>
                                @endif
                            </td>
                            <td style="padding:12px 18px;font-size:11.5px;color:#64748b;font-family:'Courier New',monospace;white-space:nowrap">{{ $u->last_login }}</td>
                            <td style="padding:12px 18px;white-space:nowrap">
                                <div style="display:flex;align-items:center;gap:6px">
                                    <button @click="openEditModal({{ json_encode($u) }})"
                                            style="padding:6px;border-radius:8px;border:1px solid #bfdbfe;background:#eff6ff;color:#2563eb;cursor:pointer;display:flex;align-items:center;justify-content:center"
                                            title="Edit">
                                        <i data-lucide="edit" style="width:14px;height:14px"></i>
                                    </button>
                                    
                                    <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Hapus pengguna ini?')" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                style="padding:6px;border-radius:8px;border:1px solid #fca5a5;background:#fff1f2;color:#dc2626;cursor:pointer;display:flex;align-items:center;justify-content:center"
                                                title="Hapus">
                                            <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- ─── MODAL: ADD/EDIT USER ─── -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" 
         x-show="modalOpen" x-transition style="display: none;">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="modalOpen = false"></div>
        <div style="position:relative;background:#fff;border:1.5px solid #e0e7ff;border-radius:18px;width:100%;max-width:440px;overflow:hidden;box-shadow:0 20px 40px rgba(37,99,235,0.15)">
            <div style="padding:16px 20px;border-bottom:1.5px solid #e0e7ff;display:flex;justify-content:space-between;align-items:center;background:#f8faff">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i data-lucide="user-plus" style="width:16px;height:16px;color:#2563eb"></i>
                    </div>
                    <div>
                        <h3 style="font-size:14px;font-weight:800;color:#1e3a8a;font-family:'Plus Jakarta Sans',sans-serif;margin:0" x-text="editMode ? 'Edit Pengguna' : 'Tambah Pengguna Baru'"></h3>
                        <p style="font-size:11px;color:#64748b;margin:2px 0 0">Kelola akun dan perizinan akses sistem</p>
                    </div>
                </div>
                <button type="button" @click="modalOpen = false" style="background:none;border:none;cursor:pointer;color:#94a3b8"><i data-lucide="x" style="width:16px;height:16px"></i></button>
            </div>

            <form :action="actionUrl" method="POST">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                
                <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Nama Lengkap *</label>
                        <input type="text" name="name" x-model="form.name" placeholder="Contoh: Sri Mulyani, M.Pd"
                               style="width:100%;padding:9px 12px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;outline:none font-family:'Inter',sans-serif" required>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Email Institusi *</label>
                        <input type="email" name="email" x-model="form.email" placeholder="email@smkplusmelati.sch.id"
                               style="width:100%;padding:9px 12px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;outline:none font-family:'Inter',sans-serif" required>
                    </div>

                    <div x-show="!editMode" x-transition>
                        <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Password *</label>
                        <div style="position:relative">
                            <input :type="showPass ? 'text' : 'password'" name="password" x-model="form.password"
                                   placeholder="Min. 8 karakter"
                                   style="width:100%;padding:9px 36px 9px 12px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;outline:none"
                                   :required="!editMode">
                            <button type="button" @click="showPass = !showPass"
                                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b">
                                <i :data-lucide="showPass ? 'eye-off' : 'eye'" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Peran / Hak Akses</label>
                            <select name="role" x-model="form.role" style="width:100%;padding:9px 12px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;outline:none">
                                @foreach($roles as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:5px">Status</label>
                            <select name="status" x-model="form.status" style="width:100%;padding:9px 12px;background:#fff;border:1.5px solid #cbd5e1;border-radius:9px;font-size:13px;color:#1e3a8a;outline:none">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div style="padding:14px 20px;border-top:1.5px solid #e0e7ff;display:flex;justify-content:flex-end;gap:10px;background:#f8faff">
                    <button type="button" @click="modalOpen = false" style="padding:8px 16px;border:1.5px solid #cbd5e1;background:#fff;border-radius:9px;font-size:12px;font-weight:600;color:#475569;cursor:pointer">Batal</button>
                    <button type="submit" style="padding:8px 20px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif" x-text="editMode ? 'Simpan Perubahan' : 'Tambah Pengguna'"></button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function userManagerHandler() {
        return {
            modalOpen: false,
            editMode: false,
            actionUrl: '',
            showPass: false,
            hasErrors: {{ $errors->any() ? 'true' : 'false' }},
            form: {
                name: '{{ old('name') }}',
                email: '{{ old('email') }}',
                password: '',
                role: '{{ old('role', 'Guru') }}',
                status: '{{ old('status', 'Aktif') }}'
            },

            init() {
                if (this.hasErrors) {
                    this.editMode = false;
                    this.actionUrl = '{{ route('admin.users.store') }}';
                    this.modalOpen = true;
                    this.$nextTick(() => lucide.createIcons());
                }
            },

            openAddModal() {
                this.editMode = false;
                this.showPass = false;
                this.actionUrl = '{{ route('admin.users.store') }}';
                this.form = {
                    name: '',
                    email: '',
                    password: '',
                    role: 'Guru',
                    status: 'Aktif'
                };
                this.modalOpen = true;
                this.$nextTick(() => lucide.createIcons());
            },

            openEditModal(user) {
                this.editMode = true;
                this.showPass = false;
                this.actionUrl = '/admin/users/' + user.id;
                this.form = {
                    name: user.name,
                    email: user.email,
                    role: user.role,
                    status: user.status
                };
                this.modalOpen = true;
                this.$nextTick(() => lucide.createIcons());
            }
        }
    }
</script>
@endsection
