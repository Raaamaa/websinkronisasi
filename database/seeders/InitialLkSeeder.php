<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LembarKerja;
use App\Models\LkImportProfile;
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
                'code' => 'ZI',
                'name' => 'LKE Zona Integritas',
                'category' => 'internal',
                'period' => 2026,
                'evaluation_interval' => '12 Bulan',
                'description' => 'Lembar Kerja Evaluasi Zona Integritas menuju WBK/WBBM',
                'profile' => [
                    'name' => 'Profil Standar LKE ZI',
                    'sheet_name' => 'LKE',
                    'header_row' => 1,
                    'parsing_strategy' => 'numbered_list_single_cell',
                    'column_mapping' => [
                        'code_col' => 'B',
                        'title_col' => 'C',
                        'evidence_items_col' => 'D',
                        'evidence_link_col' => 'E',
                        'pic_col' => 'F',
                    ],
                    'target_unit_column' => null,
                ],
            ],
            [
                'code' => 'PEKPPP',
                'name' => 'Pemantauan & Evaluasi Kinerja Penyelenggara Pelayanan Publik',
                'category' => 'internal',
                'period' => 2026,
                'evaluation_interval' => '6 Bulan',
                'description' => 'Instrumen Evaluasi Penyelenggaraan Pelayanan Publik Kementerian PANRB',
                'profile' => [
                    'name' => 'Profil Standar PEKPPP',
                    'sheet_name' => 'Penilaian',
                    'header_row' => 2,
                    'parsing_strategy' => 'checklist_sheet',
                    'column_mapping' => [
                        'code_col' => 'A',
                        'title_col' => 'B',
                        'evidence_items_col' => 'C',
                        'evidence_link_col' => 'D',
                        'checklist_sheet_name' => 'Checklist',
                    ],
                    'target_unit_column' => null,
                ],
            ],
            [
                'code' => 'KIP',
                'name' => 'Evaluasi Keterbukaan Informasi Publik (SAQ/PPID)',
                'category' => 'internal',
                'period' => 2026,
                'evaluation_interval' => '3 Bulan',
                'description' => 'Self Assessment Questionnaire Keterbukaan Informasi Publik Komisi Informasi Pusat',
                'profile' => [
                    'name' => 'Profil Standar KIP',
                    'sheet_name' => 'SAQ',
                    'header_row' => 1,
                    'parsing_strategy' => 'single_item_per_row',
                    'column_mapping' => [
                        'code_col' => 'B',
                        'title_col' => 'C',
                        'evidence_items_col' => 'D',
                        'evidence_link_col' => 'E',
                        'target_unit_col' => 'F',
                    ],
                    'target_unit_column' => 'F',
                ],
            ],
            [
                'code' => 'SAKIP',
                'name' => 'Sistem Akuntabilitas Kinerja Instansi Pemerintah',
                'category' => 'internal',
                'period' => 2026,
                'evaluation_interval' => '12 Bulan',
                'description' => 'Evaluasi Akuntabilitas Kinerja Instansi Pemerintah',
                'profile' => [
                    'name' => 'Profil Standar SAKIP',
                    'sheet_name' => 'Sheet1',
                    'header_row' => 1,
                    'parsing_strategy' => 'single_item_per_row',
                    'column_mapping' => [
                        'code_col' => 'A',
                        'title_col' => 'B',
                        'evidence_items_col' => 'C',
                        'evidence_link_col' => 'D',
                    ],
                    'target_unit_column' => null,
                ],
            ],
            [
                'code' => 'DATA_SEKUNDER',
                'name' => 'Pengumpulan Data Sekunder',
                'category' => 'eksternal',
                'period' => 2026,
                'evaluation_interval' => '1 Bulan',
                'description' => 'Format Pengumpulan Dokumen Bukti Data Sekunder Kantor',
                'profile' => [
                    'name' => 'Profil Standar Data Sekunder',
                    'sheet_name' => 'Sheet1',
                    'header_row' => 1,
                    'parsing_strategy' => 'single_item_per_row',
                    'column_mapping' => [
                        'code_col' => 'A',
                        'title_col' => 'B',
                        'evidence_items_col' => 'C',
                        'evidence_link_col' => 'D',
                    ],
                    'target_unit_column' => null,
                ],
            ],
            [
                'code' => 'DATA_SEKTORAL',
                'name' => 'Pengumpulan Data Statistik Sektoral',
                'category' => 'eksternal',
                'period' => 2026,
                'evaluation_interval' => '1 Bulan',
                'description' => 'Format Pengumpulan Bukti Penyelenggaraan Statistik Sektoral',
                'profile' => [
                    'name' => 'Profil Standar Data Sektoral',
                    'sheet_name' => 'Sheet1',
                    'header_row' => 1,
                    'parsing_strategy' => 'single_item_per_row',
                    'column_mapping' => [
                        'code_col' => 'A',
                        'title_col' => 'B',
                        'evidence_items_col' => 'C',
                        'evidence_link_col' => 'D',
                    ],
                    'target_unit_column' => null,
                ],
            ],
        ];

        foreach ($lks as $item) {
            // 1. Simpan atau perbarui profil impor
            $profile = LkImportProfile::updateOrCreate(
                [
                    'lk_code' => $item['code'],
                    'name' => $item['profile']['name'],
                ],
                [
                    'sheet_name' => $item['profile']['sheet_name'],
                    'header_row' => $item['profile']['header_row'],
                    'parsing_strategy' => $item['profile']['parsing_strategy'],
                    'column_mapping' => $item['profile']['column_mapping'],
                    'target_unit_column' => $item['profile']['target_unit_column'],
                ]
            );

            // 2. Simpan atau perbarui Lembar Kerja
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
                    'default_profile_id' => $profile->id,
                    'is_active' => true,
                ]
            );

            // Pastikan seluruh baris LK aktif dengan kode ini memiliki default_profile_id yang valid
            LembarKerja::where('code', $item['code'])
                ->whereNull('default_profile_id')
                ->update(['default_profile_id' => $profile->id]);
        }
    }
}
