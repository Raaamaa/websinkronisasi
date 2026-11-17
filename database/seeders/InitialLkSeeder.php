<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LembarKerja;
use Illuminate\Database\Seeder;

class InitialLkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lks = [
            [
                'code' => 'KIP',
                'name' => 'Evaluasi Keterbukaan Informasi Publik (SAQ/PPID)',
                'category' => 'internal',
                'period' => 2026,
                'evaluation_interval' => '3 Bulan',
                'description' => 'Self Assessment Questionnaire Keterbukaan Informasi Publik Komisi Informasi Pusat',
            ],
            [
                'code' => 'PEKPPP',
                'name' => 'Pemantauan & Evaluasi Kinerja Penyelenggara Pelayanan Publik',
                'category' => 'internal',
                'period' => 2026,
                'evaluation_interval' => '6 Bulan',
                'description' => 'Instrumen Evaluasi Penyelenggaraan Pelayanan Publik Kementerian PANRB',
            ],
            [
                'code' => 'ZI',
                'name' => 'LKE Zona Integritas',
                'category' => 'internal',
                'period' => 2026,
                'evaluation_interval' => '12 Bulan',
                'description' => 'Lembar Kerja Evaluasi Zona Integritas menuju WBK/WBBM',
            ],
            [
                'code' => 'SAKIP',
                'name' => 'Sistem Akuntabilitas Kinerja Instansi Pemerintah',
                'category' => 'internal',
                'period' => 2026,
                'evaluation_interval' => '12 Bulan',
                'description' => 'Evaluasi Akuntabilitas Kinerja Instansi Pemerintah',
            ],
            [
                'code' => 'DATA_SEKTORAL',
                'name' => 'Pengumpulan Data Statistik Sektoral',
                'category' => 'eksternal',
                'period' => 2026,
                'evaluation_interval' => '1 Bulan',
                'description' => 'Format Pengumpulan Bukti Penyelenggaraan Statistik Sektoral',
            ],
            [
                'code' => 'DATA_SEKUNDER',
                'name' => 'Pengumpulan Data Sekunder',
                'category' => 'eksternal',
                'period' => 2026,
                'evaluation_interval' => '1 Bulan',
                'description' => 'Format Pengumpulan Dokumen Bukti Data Sekunder Kantor',
            ],
        ];

        foreach ($lks as $item) {
            LembarKerja::updateOrCreate(
                [
                    'code' => $item['code'],
                    'period' => $item['period'],
                    'evaluation_interval' => $item['evaluation_interval'],
                ],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'default_profile_id' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
