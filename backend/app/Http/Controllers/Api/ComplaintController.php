<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    /**
     * Menampilkan daftar pengaduan.
     *
     * Mahasiswa hanya melihat pengaduannya sendiri.
     * Admin dapat melihat seluruh pengaduan.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        if (!in_array(
            $user->role,
            ['mahasiswa', 'admin'],
            true
        )) {
            return $this->forbiddenRoleResponse();
        }

        $query = Complaint::with([
            'user:id,name,nim,email',
            'category:id,name',
            'responses.user:id,name,role',
        ]);

        if ($user->role === 'mahasiswa') {
            $query->where(
                'user_id',
                $user->id
            );
        }

        $complaints = $query
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' =>
                'Data pengaduan berhasil diambil.',
            'data' => $complaints,
        ]);
    }

    /**
     * Membuat pengaduan atau aspirasi baru.
     *
     * Hanya mahasiswa yang dapat membuat data.
     * Pengaduan disimpan terlebih dahulu sebelum
     * dianalisis oleh AI.
     */
    public function store(
        Request $request,
        ComplaintAIService $aiService
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        if ($user->role !== 'mahasiswa') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Hanya mahasiswa yang dapat membuat pengaduan atau aspirasi.',
            ], 403);
        }

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                'exists:categories,id',
            ],

            'type' => [
                'required',
                Rule::in([
                    'pengaduan',
                    'aspirasi',
                ]),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
            ],

            'attachment' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Simpan laporan terlebih dahulu
        |--------------------------------------------------------------------------
        |
        | AI merupakan fitur pendukung.
        | Jika AI gagal, laporan mahasiswa tetap harus tersimpan.
        |
        */

        $complaint = Complaint::create([
            'user_id' =>
                $user->id,

            'category_id' =>
                $validated['category_id'] ?? null,

            'type' =>
                $validated['type'],

            'title' =>
                $validated['title'],

            'description' =>
                $validated['description'],

            'attachment' =>
                $validated['attachment'] ?? null,

            'status' =>
                'menunggu',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Analisis dengan AI
        |--------------------------------------------------------------------------
        */

        $aiAnalyzed = $this->analyzeComplaint(
            $complaint,
            $aiService
        );

        /*
        |--------------------------------------------------------------------------
        | Ambil data terbaru
        |--------------------------------------------------------------------------
        */

        $complaint->refresh();

        $complaint->load([
            'user:id,name,nim,email',
            'category:id,name',
            'responses.user:id,name,role',
        ]);

        return response()->json([
            'success' => true,

            'message' => $aiAnalyzed
                ? 'Pengaduan berhasil dibuat dan dianalisis oleh AI.'
                : 'Pengaduan berhasil dibuat. Analisis AI belum tersedia.',

            'ai_analyzed' =>
                $aiAnalyzed,

            'data' =>
                $complaint,
        ], 201);
    }

    /**
     * Menampilkan detail pengaduan.
     */
    public function show(
        Request $request,
        string $id
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        if (!in_array(
            $user->role,
            ['mahasiswa', 'admin'],
            true
        )) {
            return $this->forbiddenRoleResponse();
        }

        $complaint = Complaint::with([
            'user:id,name,nim,email',
            'category:id,name',
            'responses.user:id,name,role',
        ])->find($id);

        if (!$complaint) {
            return $this->notFoundResponse();
        }

        /*
        |--------------------------------------------------------------------------
        | Mahasiswa hanya boleh melihat miliknya sendiri
        |--------------------------------------------------------------------------
        */

        if (
            $user->role === 'mahasiswa' &&
            (int) $complaint->user_id !==
            (int) $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Anda tidak memiliki akses ke pengaduan ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Detail pengaduan berhasil diambil.',
            'data' => $complaint,
        ]);
    }

    /**
     * Memperbarui pengaduan.
     *
     * Mahasiswa:
     * - hanya miliknya sendiri;
     * - hanya ketika status menunggu;
     * - dapat mengubah isi laporan.
     *
     * Admin:
     * - dapat mengubah kategori;
     * - dapat mengubah status.
     */
    public function update(
        Request $request,
        string $id,
        ComplaintAIService $aiService
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        if (!in_array(
            $user->role,
            ['mahasiswa', 'admin'],
            true
        )) {
            return $this->forbiddenRoleResponse();
        }

        $complaint = Complaint::find($id);

        if (!$complaint) {
            return $this->notFoundResponse();
        }

        /*
        |--------------------------------------------------------------------------
        | MAHASISWA
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'mahasiswa') {

            if (
                (int) $complaint->user_id !==
                (int) $user->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Anda tidak memiliki akses untuk mengubah pengaduan ini.',
                ], 403);
            }

            if ($complaint->status !== 'menunggu') {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Pengaduan yang sudah diproses tidak dapat diubah.',
                ], 403);
            }

            $validated = $request->validate([
                'category_id' => [
                    'sometimes',
                    'nullable',
                    'exists:categories,id',
                ],

                'type' => [
                    'sometimes',
                    Rule::in([
                        'pengaduan',
                        'aspirasi',
                    ]),
                ],

                'title' => [
                    'sometimes',
                    'string',
                    'max:255',
                ],

                'description' => [
                    'sometimes',
                    'string',
                ],

                'attachment' => [
                    'sometimes',
                    'nullable',
                    'string',
                ],
            ]);

            if (empty($validated)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Tidak ada data yang diperbarui.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Apakah AI perlu menganalisis ulang?
            |--------------------------------------------------------------------------
            |
            | Jika judul, deskripsi, atau jenis berubah,
            | analisis AI lama sudah tidak lagi relevan.
            |
            */

            $needsAiReanalysis =
                array_key_exists(
                    'type',
                    $validated
                ) ||
                array_key_exists(
                    'title',
                    $validated
                ) ||
                array_key_exists(
                    'description',
                    $validated
                );

            /*
            |--------------------------------------------------------------------------
            | Update data mahasiswa
            |--------------------------------------------------------------------------
            */

            $complaint->update(
                $validated
            );

            $aiAnalyzed = null;

            if ($needsAiReanalysis) {

                /*
                |--------------------------------------------------------------------------
                | Hapus hasil AI lama
                |--------------------------------------------------------------------------
                */

                $complaint->update([
                    'ai_summary' =>
                        null,

                    'ai_analysis' =>
                        null,

                    'ai_recommendation' =>
                        null,

                    'ai_priority' =>
                        null,

                    'ai_sentiment' =>
                        null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Analisis ulang
                |--------------------------------------------------------------------------
                */

                $aiAnalyzed =
                    $this->analyzeComplaint(
                        $complaint,
                        $aiService
                    );
            }

            $complaint->refresh();

            $complaint->load([
                'user:id,name,nim,email',
                'category:id,name',
                'responses.user:id,name,role',
            ]);

            return response()->json([
                'success' => true,

                'message' =>
                    $needsAiReanalysis
                        ? (
                            $aiAnalyzed
                                ? 'Pengaduan berhasil diperbarui dan dianalisis ulang oleh AI.'
                                : 'Pengaduan berhasil diperbarui. Analisis AI belum tersedia.'
                        )
                        : 'Pengaduan berhasil diperbarui.',

                'ai_analyzed' =>
                    $aiAnalyzed,

                'data' =>
                    $complaint,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'category_id' => [
                'sometimes',
                'nullable',
                'exists:categories,id',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'menunggu',
                    'diproses',
                    'selesai',
                    'ditolak',
                ]),
            ],
        ]);

        if (empty($validated)) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Tidak ada data yang diperbarui.',
            ], 422);
        }

        $complaint->update(
            $validated
        );

        $complaint->refresh();

        $complaint->load([
            'user:id,name,nim,email',
            'category:id,name',
            'responses.user:id,name,role',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Data pengaduan berhasil diperbarui.',
            'data' =>
                $complaint,
        ]);
    }

    /**
     * Menghapus pengaduan.
     */
    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return $this->unauthenticatedResponse();
        }

        if (!in_array(
            $user->role,
            ['mahasiswa', 'admin'],
            true
        )) {
            return $this->forbiddenRoleResponse();
        }

        $complaint =
            Complaint::find($id);

        if (!$complaint) {
            return $this->notFoundResponse();
        }

        /*
        |--------------------------------------------------------------------------
        | Mahasiswa
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'mahasiswa') {

            if (
                (int) $complaint->user_id !==
                (int) $user->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Anda tidak memiliki akses untuk menghapus pengaduan ini.',
                ], 403);
            }

            if ($complaint->status !== 'menunggu') {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Pengaduan yang sudah diproses tidak dapat dihapus.',
                ], 403);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus
        |--------------------------------------------------------------------------
        */

        $complaint->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Data pengaduan berhasil dihapus.',
        ]);
    }

    /**
     * Menjalankan analisis AI.
     *
     * true:
     * AI berhasil dan hasil disimpan.
     *
     * false:
     * AI gagal tetapi pengaduan tetap tersimpan.
     */
    private function analyzeComplaint(
        Complaint $complaint,
        ComplaintAIService $aiService
    ): bool {
        try {

            /*
            |--------------------------------------------------------------------------
            | Kirim data ke AI
            |--------------------------------------------------------------------------
            */

            $analysis =
                $aiService->analyze(
                    $complaint->title,
                    $complaint->description,
                    $complaint->type
                );

            /*
            |--------------------------------------------------------------------------
            | Simpan seluruh hasil AI
            |--------------------------------------------------------------------------
            */

            $complaint->update([
                'ai_summary' =>
                    $analysis['summary'],

                'ai_analysis' =>
                    $analysis['analysis'],

                'ai_recommendation' =>
                    $analysis['recommendation'],

                'ai_priority' =>
                    $analysis['priority'],

                'ai_sentiment' =>
                    $analysis['sentiment'],
            ]);

            return true;

        } catch (\Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | AI gagal tetapi proses utama tidak gagal
            |--------------------------------------------------------------------------
            */

            Log::warning(
                'Analisis AI pengaduan gagal.',
                [
                    'complaint_id' =>
                        $complaint->id,

                    'model' =>
                        config(
                            'services.atmorouter.model'
                        ),

                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * Response ketika belum login.
     */
    private function unauthenticatedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' =>
                'Unauthenticated.',
        ], 401);
    }

    /**
     * Response role tidak memiliki izin.
     */
    private function forbiddenRoleResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' =>
                'Role tidak memiliki akses.',
        ], 403);
    }

    /**
     * Response data tidak ditemukan.
     */
    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' =>
                'Data pengaduan tidak ditemukan.',
        ], 404);
    }
}