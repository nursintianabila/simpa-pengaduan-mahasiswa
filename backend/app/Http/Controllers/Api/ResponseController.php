<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    public function index(
        Request $request,
        string $complaintId
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $complaint = Complaint::find($complaintId);

        if (!$complaint) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengaduan tidak ditemukan.',
            ], 404);
        }

        // Mahasiswa hanya melihat tanggapan
        // dari pengaduannya sendiri.
        if (
            $user->role === 'mahasiswa' &&
            $complaint->user_id !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses.',
            ], 403);
        }

        if (
            $user->role !== 'mahasiswa' &&
            $user->role !== 'admin'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak memiliki akses.',
            ], 403);
        }

        $responses = Response::with([
            'user:id,name,email,role',
        ])
            ->where(
                'complaint_id',
                $complaint->id
            )
            ->oldest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data tanggapan berhasil diambil.',
            'data' => $responses,
        ]);
    }

    public function store(
        Request $request,
        string $complaintId
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat memberikan tanggapan.',
            ], 403);
        }

        $complaint = Complaint::find($complaintId);

        if (!$complaint) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengaduan tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'response' => [
                'required',
                'string',
            ],
        ]);

        $response = Response::create([
            'complaint_id' => $complaint->id,
            'user_id' => $user->id,
            'response' => $validated['response'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Otomatis ubah menjadi diproses
        |--------------------------------------------------------------------------
        */

        if ($complaint->status === 'menunggu') {
            $complaint->update([
                'status' => 'diproses',
            ]);
        }

        $response->load([
            'user:id,name,email,role',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tanggapan berhasil ditambahkan.',
            'data' => $response,
        ], 201);
    }

    public function destroy(
        Request $request,
        string $complaintId,
        string $responseId
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat menghapus tanggapan.',
            ], 403);
        }

        $complaint = Complaint::find($complaintId);

        if (!$complaint) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengaduan tidak ditemukan.',
            ], 404);
        }

        $response = Response::where(
            'complaint_id',
            $complaint->id
        )
            ->where('id', $responseId)
            ->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggapan tidak ditemukan.',
            ], 404);
        }

        $response->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tanggapan berhasil dihapus.',
        ]);
    }
}