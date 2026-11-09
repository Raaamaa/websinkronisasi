<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use Illuminate\Database\Seeder;
use ZipArchive;

class DemoIndicatorAndSampleDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Indikator Resmi LK Periode Seluruh Interval (12 Bulan, 6 Bulan, 3 Bulan)
        $lkData = [
            'ZI' => [
                'name' => 'LKE Zona Integritas',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'indicators' => [
                    [
                        'code' => 'ZI-1.1',
                        'title' => 'Pembentukan Tim Kerja Pembangunan Zona Integritas Menuju WBK/WBBM',
                        'evidence_link' => 'https://drive.google.com/drive/folders/1BpsZI2026-WBK-WBBM-Tegal-TimKerja',
                        'pic' => 'Subbagian Umum',
                        'evidence_items' => [
                            'SK Tim Kerja Pembangunan ZI Menuju WBK/WBBM Tahun 2026',
                            'Surat Keputusan Pembentukan Tim Zona Integritas',
                        ],
                    ],
                    [
                        'code' => 'ZI-1.2',
                        'title' => 'Rencana Kerja dan Rencana Aksi Pembangunan Zona Integritas',
                        'evidence_link' => 'https://drive.google.com/drive/folders/1BpsZI2026-WBK-WBBM-Tegal-RencanaAksi',
                        'pic' => 'Subbagian Umum',
                        'evidence_items' => [
                            'Dokumen Rencana Aksi Pembangunan Zona Integritas Tahun 2026',
                        ],
                    ],
                ],
            ],
            'PEKPPP' => [
                'name' => 'Pemantauan & Evaluasi Kinerja Penyelenggara Pelayanan Publik',
                'category' => 'internal',
                'evaluation_interval' => '6 Bulan',
                'indicators' => [
                    [
                        'code' => 'PEKPPP-01',
                        'title' => 'Penetapan Standar Pelayanan Statistik Terpadu (PST) dan Maklumat Pelayanan',
                        'evidence_link' => 'https://drive.google.com/drive/folders/1BpsPEKPPP2026-StandarPelayananPublik',
                        'pic' => 'Tim Pelayanan Statistik',
                        'evidence_items' => [
                            'SK Standar Pelayanan Statistik Terpadu PST BPS',
                            'Maklumat Pelayanan Publik BPS Kabupaten Tegal',
                        ],
                    ],
                ],
            ],
            'SAKIP' => [
                'name' => 'Sistem Akuntabilitas Kinerja Instansi Pemerintah',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'indicators' => [
                    [
                        'code' => 'SAKIP-01',
                        'title' => 'Laporan Kinerja Instansi Pemerintah (LKjIP) dan Perjanjian Kinerja',
                        'evidence_link' => 'https://drive.google.com/drive/folders/1BpsSAKIP2026-LKjIP-PerjanjianKinerja',
                        'pic' => 'Tim IPDS & Evaluasi',
                        'evidence_items' => [
                            'Laporan Kinerja Instansi Pemerintah (LKjIP) Tahun 2026',
                            'Perjanjian Kinerja Tahunan Kepala Kantor BPS',
                        ],
                    ],
                ],
            ],
            'KIP' => [
                'name' => 'Evaluasi Keterbukaan Informasi Publik (SAQ/PPID)',
                'category' => 'internal',
                'evaluation_interval' => '3 Bulan',
                'indicators' => [
                    [
                        'code' => 'KIP-01',
                        'title' => 'Penetapan Struktur PPID dan Penetapan Daftar Informasi Publik (DIP)',
                        'evidence_link' => 'https://drive.google.com/drive/folders/1BpsKIP2026-PPID-DaftarInformasiPublik',
                        'pic' => 'Pejabat Pengelola Informasi',
                        'evidence_items' => [
                            'SK PPID dan Daftar Informasi Publik (DIP) Tahun 2026',
                            'Daftar Informasi Publik SAQ Keterbukaan Informasi',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($lkData as $code => $info) {
            /** @var LembarKerja $lk */
            $lk = LembarKerja::updateOrCreate(
                ['code' => $code, 'period' => 2026],
                [
                    'name' => $info['name'],
                    'category' => $info['category'],
                    'is_active' => true,
                    'evaluation_interval' => $info['evaluation_interval'] ?? '12 Bulan',
                    'description' => "Instrumen evaluasi {$info['name']} untuk periode ".($info['evaluation_interval'] ?? '12 Bulan').'.',
                ]
            );

            foreach ($info['indicators'] as $index => $indData) {
                /** @var LkIndicator $indicator */
                $indicator = LkIndicator::updateOrCreate(
                    ['lk_id' => $lk->id, 'code' => $indData['code']],
                    [
                        'title' => $indData['title'],
                        'order_index' => $index + 1,
                        'evidence_link' => $indData['evidence_link'],
                        'pic' => $indData['pic'],
                    ]
                );

                foreach ($indData['evidence_items'] as $itemIdx => $itemName) {
                    LkEvidenceItem::updateOrCreate(
                        ['indicator_id' => $indicator->id, 'raw_template_name' => $itemName],
                        [
                            'resolved_name' => $itemName,
                            'order_index' => $itemIdx + 1,
                        ]
                    );
                }
            }
        }

        // 2. Buat Berkas Fisik Contoh di direktori public/sample-documents/
        $sampleDir = public_path('sample-documents');
        if (! is_dir($sampleDir)) {
            mkdir($sampleDir, 0755, true);
        }

        // File 1: SK Tim Kerja Pembangunan ZI
        $pdf1 = $this->createMultilinePdf([
            'BADAN PUSAT STATISTIK KABUPATEN TEGAL',
            'KEPUTUSAN KEPALA BPS NOMOR B-105/3328/KP.010/2026',
            'TENTANG PEMBENTUKAN TIM KERJA PEMBANGUNAN ZONA INTEGRITAS (ZI-1.1)',
            'MENUJU WILAYAH BEBAS DARI KORUPSI (WBK)',
            'DAN WILAYAH BIROKRASI BERSIH DAN MELAYANI (WBBM)',
            'BPS KABUPATEN TEGAL TAHUN 2026',
        ]);
        file_put_contents($sampleDir.'/SK_Tim_Pembangunan_Zona_Integritas_2026.pdf', $pdf1);

        // File 2: Standar Pelayanan Publik PEKPPP
        $pdf2 = $this->createMultilinePdf([
            'BADAN PUSAT STATISTIK KABUPATEN TEGAL',
            'SURAT KEPUTUSAN KEPALA BPS NOMOR B-201/3328/VS.020/2026',
            'TENTANG PENETAPAN STANDAR PELAYANAN STATISTIK TERPADU (PST)',
            'DAN MAKLUMAT PELAYANAN PUBLIK',
            'PEMANTAUAN DAN EVALUASI KINERJA PENYELENGGARA PELAYANAN PUBLIK (PEKPPP-01)',
            'BPS KABUPATEN TEGAL TAHUN 2026',
        ]);
        file_put_contents($sampleDir.'/Standar_Pelayanan_Publik_BPS_2026.pdf', $pdf2);

        // File 3: Laporan Kinerja LKjIP SAKIP
        $pdf3 = $this->createMultilinePdf([
            'BADAN PUSAT STATISTIK KABUPATEN TEGAL',
            'LAPORAN KINERJA INSTANSI PEMERINTAH (LKjIP) TAHUN 2026',
            'EVALUASI SISTEM AKUNTABILITAS KINERJA INSTANSI PEMERINTAH (SAKIP-01)',
            'SURAT PENGANTAR KEPALA BPS NOMOR B-305/3328/OT.010/2026',
            'BPS KABUPATEN TEGAL TAHUN 2026',
        ]);
        file_put_contents($sampleDir.'/Laporan_Kinerja_Instansi_Pemerintah_LKjIP_2026.pdf', $pdf3);

        // File 4: Daftar Informasi Publik DOCX KIP
        $this->createDocxFile(
            $sampleDir.'/Daftar_Informasi_Publik_DIP_PPID_2026.docx',
            'BADAN PUSAT STATISTIK KABUPATEN TEGAL. '.
            'SURAT KEPUTUSAN STRUKTUR PPID DAN PENETAPAN DAFTAR INFORMASI PUBLIK (DIP) KETERBUKAAN INFORMASI PUBLIK (KIP-01) '.
            'SELF ASSESSMENT QUESTIONNAIRE NOMOR B-402/3328/HM.100/2026 TAHUN 2026.'
        );
    }

    /**
     * @param  list<string>  $lines
     */
    private function createMultilinePdf(array $lines): string
    {
        $stream = 'BT /F1 12 Tf 50 720 Td ';
        foreach ($lines as $idx => $line) {
            if ($idx > 0) {
                $stream .= '0 -18 Td ';
            }
            $stream .= '('.addcslashes($line, '()\\').') Tj ';
        }
        $stream .= 'ET';

        $chunks = [];
        $offsets = [];

        $chunks[] = "%PDF-1.4\n";

        $offsets[1] = strlen(implode('', $chunks));
        $chunks[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen(implode('', $chunks));
        $chunks[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen(implode('', $chunks));
        $chunks[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";

        $len = strlen($stream);
        $offsets[4] = strlen(implode('', $chunks));
        $chunks[] = "4 0 obj\n<< /Length {$len} >>\nstream\n{$stream}\nendstream\nendobj\n";

        $offsets[5] = strlen(implode('', $chunks));
        $chunks[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $xrefOffset = strlen(implode('', $chunks));
        $chunks[] = "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $chunks[] = sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $chunks[] = "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return implode('', $chunks);
    }

    private function createDocxFile(string $outputPath, string $text): void
    {
        $zip = new ZipArchive;
        $zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body><w:p><w:r><w:t>'.htmlspecialchars($text).'</w:t></w:r></w:p></w:body>'
            .'</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();
    }
}
