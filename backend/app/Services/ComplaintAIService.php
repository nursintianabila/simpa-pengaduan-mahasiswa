<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ComplaintAIService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.atmorouter.base_url'),
            '/'
        );

        $this->apiKey = config(
            'services.atmorouter.api_key'
        );

        $this->model = config(
            'services.atmorouter.model'
        );
    }

    /**
     * Menganalisis pengaduan atau aspirasi mahasiswa.
     */
    public function analyze(
        string $title,
        string $description,
        string $type = 'pengaduan'
    ): array {
        if (!$this->apiKey) {
            throw new RuntimeException(
                'API key AtmoRouter belum dikonfigurasi.'
            );
        }

        $prompt = <<<PROMPT
Anda adalah AI pendukung pada Sistem Informasi Pengaduan dan Aspirasi Mahasiswa.

Tugas Anda adalah membantu administrator memahami laporan mahasiswa
secara objektif, jelas, dan sesuai konteks perguruan tinggi.

Berikut laporan mahasiswa:

Jenis laporan:
{$type}

Judul:
{$title}

Isi laporan:
{$description}

Lakukan analisis berikut:

1. SUMMARY
Buat ringkasan inti laporan dalam Bahasa Indonesia yang formal,
jelas, singkat, dan mudah dipahami administrator.

Jangan hanya menyalin atau memparafrase isi laporan.
Identifikasi inti masalah atau kebutuhan utama mahasiswa.

2. ANALYSIS
Jelaskan masalah utama yang terjadi serta dampaknya terhadap mahasiswa,
kegiatan akademik, kenyamanan, fasilitas, layanan, atau lingkungan kampus.

Analisis harus berdasarkan informasi yang terdapat pada laporan.
Jangan membuat fakta baru yang tidak disebutkan mahasiswa.

Jika laporan berupa aspirasi, analisis kebutuhan atau tujuan
yang ingin dicapai mahasiswa.

3. RECOMMENDATION
Berikan rekomendasi penanganan yang realistis dan dapat
dipertimbangkan oleh pihak kampus.

Rekomendasi harus:
- relevan dengan masalah;
- bersifat konkret;
- tidak berlebihan;
- dapat dilaksanakan secara bertahap jika diperlukan;
- tidak menyatakan bahwa rekomendasi merupakan keputusan final.

Contoh tindakan yang dapat direkomendasikan:
- pemeriksaan atau perbaikan fasilitas;
- penyediaan atau penambahan sarana;
- koordinasi dengan unit terkait;
- evaluasi layanan;
- peningkatan akses internet;
- penyediaan ruang belajar;
- penyesuaian prosedur;
- penyampaian informasi kepada mahasiswa;
- tindakan lain yang sesuai konteks laporan.

4. PRIORITY
Tentukan tingkat prioritas laporan berdasarkan dampak dan urgensi.

Gunakan salah satu nilai berikut:

rendah:
Masalah tidak mendesak, dampaknya kecil, atau berupa masukan umum
yang tidak mengganggu kegiatan utama mahasiswa.

sedang:
Masalah perlu ditindaklanjuti dan berdampak terhadap kenyamanan,
efektivitas belajar, fasilitas, atau layanan mahasiswa,
tetapi tidak bersifat kritis atau darurat.

tinggi:
Masalah bersifat mendesak, berdampak besar, mengganggu kegiatan penting,
menyangkut keselamatan, keamanan, layanan utama kampus,
atau membutuhkan penanganan segera.

5. SENTIMENT
Tentukan sentimen utama dari laporan mahasiswa.

Gunakan salah satu nilai:
positif
netral
negatif

Aturan penting:
- Jangan menganggap semua pengaduan otomatis negatif.
- Aspirasi konstruktif dapat bernilai netral atau positif.
- Gunakan isi dan nada laporan sebagai dasar penentuan sentimen.

Kembalikan HANYA JSON valid dengan format berikut:

{
  "summary": "ringkasan inti laporan",
  "analysis": "analisis masalah dan dampaknya",
  "recommendation": "rekomendasi penanganan untuk pihak kampus",
  "priority": "rendah|sedang|tinggi",
  "sentiment": "positif|netral|negatif"
}

Gunakan Bahasa Indonesia yang profesional dan mudah dipahami.

Jangan gunakan markdown.
Jangan gunakan code block.
Jangan tambahkan teks atau penjelasan di luar JSON.
PROMPT;

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->retry(2, 1000)
                ->timeout(60)
                ->post(
                    $this->baseUrl . '/chat/completions',
                    [
                        'model' => $this->model,

                        'messages' => [
                            [
                                'role' => 'system',
                                'content' =>
                                    'Anda adalah AI pendukung administrator pada Sistem Informasi Pengaduan dan Aspirasi Mahasiswa. Analisis laporan secara objektif, profesional, dan jangan membuat fakta yang tidak terdapat dalam laporan. Selalu kembalikan JSON valid sesuai format yang diminta.',
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],

                        'temperature' => 0.2,
                    ]
                );

            if ($response->failed()) {
                Log::error(
                    'AtmoRouter request gagal.',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                throw new RuntimeException(
                    'AtmoRouter gagal merespons. HTTP ' .
                    $response->status()
                );
            }

            $content = $response->json(
                'choices.0.message.content'
            );

            if (!$content) {
                throw new RuntimeException(
                    'Respons AI tidak memiliki content.'
                );
            }

            $result = $this->extractJson($content);

            return $this->validateResult($result);

        } catch (\Throwable $exception) {

            Log::error(
                'Complaint AI analysis gagal.',
                [
                    'message' => $exception->getMessage(),
                    'model' => $this->model,
                ]
            );

            throw $exception;
        }
    }

    /**
     * Mengambil objek JSON dari respons AI.
     *
     * Beberapa model atau provider dapat menambahkan footer
     * atau teks tambahan setelah JSON.
     */
    protected function extractJson(string $content): array
    {
        $content = trim($content);

        /*
        |--------------------------------------------------------------------------
        | Percobaan pertama
        |--------------------------------------------------------------------------
        |
        | Jika respons sudah berupa JSON murni.
        |
        */

        $decoded = json_decode(
            $content,
            true
        );

        if (is_array($decoded)) {
            return $decoded;
        }

        /*
        |--------------------------------------------------------------------------
        | Percobaan kedua
        |--------------------------------------------------------------------------
        |
        | Cari objek JSON dari tanda { pertama sampai } terakhir.
        |
        */

        $start = strpos(
            $content,
            '{'
        );

        $end = strrpos(
            $content,
            '}'
        );

        if (
            $start !== false &&
            $end !== false &&
            $end > $start
        ) {
            $json = substr(
                $content,
                $start,
                $end - $start + 1
            );

            $decoded = json_decode(
                $json,
                true
            );

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning(
            'Respons AI tidak dapat diparsing sebagai JSON.',
            [
                'content' => $content,
            ]
        );

        throw new RuntimeException(
            'Respons AI bukan JSON yang valid.'
        );
    }

    /**
     * Memvalidasi struktur dan nilai hasil AI.
     */
    protected function validateResult(
        array $result
    ): array {
        $summary = trim(
            (string) ($result['summary'] ?? '')
        );

        $analysis = trim(
            (string) ($result['analysis'] ?? '')
        );

        $recommendation = trim(
            (string) ($result['recommendation'] ?? '')
        );

        $priority = strtolower(
            trim(
                (string) ($result['priority'] ?? '')
            )
        );

        $sentiment = strtolower(
            trim(
                (string) ($result['sentiment'] ?? '')
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Validasi isi
        |--------------------------------------------------------------------------
        */

        if ($summary === '') {
            throw new RuntimeException(
                'AI tidak menghasilkan ringkasan.'
            );
        }

        if ($analysis === '') {
            throw new RuntimeException(
                'AI tidak menghasilkan analisis masalah.'
            );
        }

        if ($recommendation === '') {
            throw new RuntimeException(
                'AI tidak menghasilkan rekomendasi.'
            );
        }

        if (!in_array(
            $priority,
            [
                'rendah',
                'sedang',
                'tinggi',
            ],
            true
        )) {
            throw new RuntimeException(
                'Nilai prioritas AI tidak valid.'
            );
        }

        if (!in_array(
            $sentiment,
            [
                'positif',
                'netral',
                'negatif',
            ],
            true
        )) {
            throw new RuntimeException(
                'Nilai sentimen AI tidak valid.'
            );
        }

        return [
            'summary' => $summary,
            'analysis' => $analysis,
            'recommendation' => $recommendation,
            'priority' => $priority,
            'sentiment' => $sentiment,
        ];
    }
}