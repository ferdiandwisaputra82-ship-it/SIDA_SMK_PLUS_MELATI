<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Document;
use App\Models\BlockchainEntry;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Admin Users
        $users = [
            [
                'name' => 'Dr. Hendra Wijaya',
                'email' => 'hendra@smkplusmelati.sch.id',
                'password' => bcrypt('securepassword'),
                'role' => 'Super Admin',
                'status' => 'Aktif',
                'last_login' => 'Hari ini, 08:30 WIB'
            ],
            [
                'name' => 'Sri Mulyani, M.Pd',
                'email' => 'sri@smkplusmelati.sch.id',
                'password' => bcrypt('securepassword'),
                'role' => 'Operator',
                'status' => 'Aktif',
                'last_login' => 'Kemarin, 14:15 WIB'
            ],
            [
                'name' => 'Drs. M. Taufik',
                'email' => 'taufik@smkplusmelati.sch.id',
                'password' => bcrypt('securepassword'),
                'role' => 'Guru',
                'status' => 'Aktif',
                'last_login' => '3 hari yang lalu'
            ]
        ];

        foreach ($users as $u) {
            AdminUser::create($u);
        }

        // 2. Seed System Settings
        $defaults = SystemSetting::defaults();
        foreach ($defaults as $k => $v) {
            SystemSetting::create(['key' => $k, 'value' => $v]);
        }

        // 3. Seed Documents & Blockchain Entries
        $docs = [
            [
                'id' => '00000000-0000-0000-0000-000000000001',
                'document_type' => 'Ijazah',
                'owner_name' => 'Ahmad Fauzi Ramadhan',
                'document_number' => 'M-SMK/06-3/0012345',
                'nisn' => '0058912345',
                'year' => '2025',
                'description' => 'Ijazah Kelulusan SMK Plus Melati Jurusan RPL',
                'hash' => '0x3f78a2e1c94b5d6e7f8a901b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a901b2c',
                'file_path' => '',
                'file_name' => 'ijazah_ahmad_fauzi.pdf',
                'file_mime' => 'application/pdf',
                'uploaded_by' => 'hendra@smkplusmelati.sch.id',
                'blockchain_tx_id' => 'tx-doc0012345-abc12345',
                'status' => 'Terverifikasi'
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000002',
                'document_type' => 'Rapor',
                'owner_name' => 'Rani Widya Lestari',
                'document_number' => 'RAPOR-SEM5-2025',
                'nisn' => '0067123456',
                'year' => '2025',
                'description' => 'Rapor Semester 5 Tahun Ajaran 2024/2025',
                'hash' => '0x8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b',
                'file_path' => '',
                'file_name' => 'rapor_semester_5_rani.pdf',
                'file_mime' => 'application/pdf',
                'uploaded_by' => 'sri@smkplusmelati.sch.id',
                'blockchain_tx_id' => 'tx-doc8a9b0c-xyz98765',
                'status' => 'Terverifikasi'
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000003',
                'document_type' => 'Surat Tugas',
                'owner_name' => 'Budi Santoso, S.Kom',
                'document_number' => '421.5/ST-089/SMK/2026',
                'nisn' => '19880214',
                'year' => '2026',
                'description' => 'Surat Tugas Pelatihan Pengembangan IoT SMK',
                'hash' => '0xe5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6',
                'file_path' => '',
                'file_name' => 'surat_tugas_iot_budi.pdf',
                'file_mime' => 'application/pdf',
                'uploaded_by' => 'hendra@smkplusmelati.sch.id',
                'blockchain_tx_id' => 'tx-doc4215-st089',
                'status' => 'Terverifikasi'
            ]
        ];

        foreach ($docs as $d) {
            Document::create($d);

            BlockchainEntry::create([
                'tx_id' => $d['blockchain_tx_id'],
                'document_id' => $d['id'],
                'document_type' => $d['document_type'],
                'hash' => $d['hash'],
                'owner_name' => $d['owner_name'],
                'action' => 'REGISTER',
                'block_height' => 4812 + BlockchainEntry::count(),
                'channel' => 'documentchannel',
                'status' => 'Confirmed'
            ]);
        }

        // 4. Seed Activity Logs
        $activities = [
            [
                'action' => 'Sistem dimulai',
                'user_email' => 'system',
                'target' => 'SIDA v3.0',
                'type' => 'system',
                'ip' => '—'
            ],
            [
                'action' => 'Registrasi ijazah Ahmad Fauzi',
                'user_email' => 'hendra@smkplusmelati.sch.id',
                'target' => 'DOC-2025-001',
                'type' => 'upload',
                'ip' => '192.168.1.101'
            ]
        ];

        foreach ($activities as $a) {
            ActivityLog::create($a);
        }
    }
}
