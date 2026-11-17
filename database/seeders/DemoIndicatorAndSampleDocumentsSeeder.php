<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use ZipArchive;

class DemoIndicatorAndSampleDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan Lembar Kerja contoh tersedia bila seeder dijalankan terpisah
        $lkDefaults = [
            'ZI' => ['name' => 'LKE Zona Integritas', 'category' => 'internal', 'evaluation_interval' => '12 Bulan'],
            'PEKPPP' => ['name' => 'Pemantauan & Evaluasi Kinerja Penyelenggara Pelayanan Publik', 'category' => 'internal', 'evaluation_interval' => '6 Bulan'],
            'SAKIP' => ['name' => 'Sistem Akuntabilitas Kinerja Instansi Pemerintah', 'category' => 'internal', 'evaluation_interval' => '12 Bulan'],
        ];

        foreach ($lkDefaults as $code => $info) {
            LembarKerja::firstOrCreate(
                ['code' => $code, 'period' => 2026],
                [
                    'name' => $info['name'],
                    'category' => $info['category'],
                    'is_active' => true,
                    'evaluation_interval' => $info['evaluation_interval'],
                    'description' => "Instrumen evaluasi {$info['name']} untuk periode {$info['evaluation_interval']}.",
                ]
            );
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
        $pdf1Path = $sampleDir.'/SK_Tim_Pembangunan_Zona_Integritas_2026.pdf';
        if (! is_file($pdf1Path)) {
            file_put_contents($pdf1Path, $pdf1);
        }

        // File 2: Standar Pelayanan Publik PEKPPP
        $pdf2 = $this->createMultilinePdf([
            'BADAN PUSAT STATISTIK KABUPATEN TEGAL',
            'SURAT KEPUTUSAN KEPALA BPS NOMOR B-201/3328/VS.020/2026',
            'TENTANG PENETAPAN STANDAR PELAYANAN STATISTIK TERPADU (PST)',
            'DAN MAKLUMAT PELAYANAN PUBLIK',
            'PEMANTAUAN DAN EVALUASI KINERJA PENYELENGGARA PELAYANAN PUBLIK (PEKPPP-01)',
            'BPS KABUPATEN TEGAL TAHUN 2026',
        ]);
        $pdf2Path = $sampleDir.'/Standar_Pelayanan_Publik_BPS_2026.pdf';
        if (! is_file($pdf2Path)) {
            file_put_contents($pdf2Path, $pdf2);
        }

        // File 3: Laporan Kinerja LKjIP SAKIP
        $pdf3 = $this->createMultilinePdf([
            'BADAN PUSAT STATISTIK KABUPATEN TEGAL',
            'LAPORAN KINERJA INSTANSI PEMERINTAH (LKjIP) TAHUN 2026',
            'EVALUASI SISTEM AKUNTABILITAS KINERJA INSTANSI PEMERINTAH (SAKIP-01)',
            'SURAT PENGANTAR KEPALA BPS NOMOR B-305/3328/OT.010/2026',
            'BPS KABUPATEN TEGAL TAHUN 2026',
        ]);
        $pdf3Path = $sampleDir.'/Laporan_Kinerja_Instansi_Pemerintah_LKjIP_2026.pdf';
        if (! is_file($pdf3Path)) {
            file_put_contents($pdf3Path, $pdf3);
        }

        // File 4: Daftar Informasi Publik DOCX KIP
        $docxPath = $sampleDir.'/Daftar_Informasi_Publik_DIP_PPID_2026.docx';
        if (! is_file($docxPath)) {
            $this->createDocxFile(
                $docxPath,
                'BADAN PUSAT STATISTIK KABUPATEN TEGAL. '.
                'SURAT KEPUTUSAN STRUKTUR PPID DAN PENETAPAN DAFTAR INFORMASI PUBLIK (DIP) KETERBUKAAN INFORMASI PUBLIK (KIP-01) '.
                'SELF ASSESSMENT QUESTIONNAIRE NOMOR B-402/3328/HM.100/2026 TAHUN 2026.'
            );
        }

        // 3. Daftarkan dokumen bukti dukung demo berstatus disetujui beserta document_lk_links resmi
        $actor = User::whereIn('role', ['admin', 'pegawai', 'super_admin'])->where('is_active', true)->first()
            ?? User::create([
                'id' => (string) Str::uuid(),
                'name' => 'Admin Demo BPS',
                'email' => 'admin.demo.seeder@bps.go.id',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
                'auth_provider' => 'sso',
            ]);

        $demoDocs = [
            [
                'lk_code' => 'ZI',
                'document_name' => 'SK Tim Kerja Pembangunan Zona Integritas Tahun 2026',
                'document_number' => 'B-105/3328/KP.010/2026',
                'document_type' => 'SK',
                'original_filename' => 'SK_Tim_Pembangunan_Zona_Integritas_2026.pdf',
                'mime_type' => 'application/pdf',
                'content' => $pdf1,
                'extracted_text' => 'KEPUTUSAN KEPALA BPS NOMOR B-105/3328/KP.010/2026 TENTANG PEMBENTUKAN TIM KERJA PEMBANGUNAN ZONA INTEGRITAS (ZI-1.1)',
            ],
            [
                'lk_code' => 'PEKPPP',
                'document_name' => 'SK Standar Pelayanan Statistik Terpadu dan Maklumat Pelayanan 2026',
                'document_number' => 'B-201/3328/VS.020/2026',
                'document_type' => 'SK',
                'original_filename' => 'Standar_Pelayanan_Publik_BPS_2026.pdf',
                'mime_type' => 'application/pdf',
                'content' => $pdf2,
                'extracted_text' => 'SURAT KEPUTUSAN KEPALA BPS NOMOR B-201/3328/VS.020/2026 TENTANG PENETAPAN STANDAR PELAYANAN STATISTIK TERPADU (PEKPPP-01)',
            ],
            [
                'lk_code' => 'SAKIP',
                'document_name' => 'Laporan Kinerja Instansi Pemerintah (LKjIP) Tahun 2026',
                'document_number' => 'B-305/3328/OT.010/2026',
                'document_type' => 'Laporan',
                'original_filename' => 'Laporan_Kinerja_Instansi_Pemerintah_LKjIP_2026.pdf',
                'mime_type' => 'application/pdf',
                'content' => $pdf3,
                'extracted_text' => 'LAPORAN KINERJA INSTANSI PEMERINTAH (LKjIP) TAHUN 2026 EVALUASI SISTEM AKUNTABILITAS KINERJA INSTANSI PEMERINTAH (SAKIP-01)',
            ],
        ];

        foreach ($demoDocs as $item) {
            $hash = hash('sha256', $item['content']);
            $doc = EvidenceDocument::withTrashed()->where('file_hash', $hash)->first();

            if ($doc !== null && $doc->trashed()) {
                $doc->restore();
            }

            if ($doc === null) {
                $docUuid = (string) Str::uuid();
                $doc = EvidenceDocument::create([
                    'id' => $docUuid,
                    'document_name' => $item['document_name'],
                    'document_number' => $item['document_number'],
                    'year' => 2026,
                    'document_type' => $item['document_type'],
                    'file_path' => "evidence/{$docUuid}.pdf",
                    'original_filename' => $item['original_filename'],
                    'mime_type' => $item['mime_type'],
                    'file_size_bytes' => strlen($item['content']),
                    'file_hash' => $hash,
                    'extracted_text' => $item['extracted_text'],
                    'processing_status' => 'selesai',
                    'approval_status' => 'disetujui',
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                    'source_type' => 'upload',
                    'uploaded_by' => $actor->id,
                ]);
            } else {
                $doc->update([
                    'approval_status' => 'disetujui',
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                    'source_type' => 'upload',
                ]);
            }

            $lk = LembarKerja::where('code', $item['lk_code'])->where('period', 2026)->first();
            if ($lk !== null) {
                $existingLink = DocumentLkLink::where('document_id', $doc->id)
                    ->where('lk_id', $lk->id)
                    ->whereNull('indicator_id')
                    ->first();

                if ($existingLink !== null) {
                    $existingLink->update(['link_source' => 'admin', 'created_by' => $actor->id]);
                } else {
                    DocumentLkLink::create([
                        'document_id' => $doc->id,
                        'lk_id' => $lk->id,
                        'indicator_id' => null,
                        'link_source' => 'admin',
                        'created_by' => $actor->id,
                    ]);
                }
            }
        }
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
