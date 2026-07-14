<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Models\Musyrif;
use App\Models\SystemReview;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user && $user->role === 'musyrif', 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'review' => ['required', 'string', 'min:20', 'max:1200'],
            'show_name' => ['nullable', 'boolean'],
            'consent_publication' => ['accepted'],
        ], [
            'rating.required' => 'Silakan pilih rating untuk sistem.',
            'rating.between' => 'Rating harus antara 1 sampai 5.',
            'review.required' => 'Review belum diisi.',
            'review.min' => 'Review minimal terdiri dari 20 karakter.',
            'review.max' => 'Review maksimal terdiri dari 1.200 karakter.',
            'consent_publication.accepted' => 'Persetujuan publikasi harus dicentang.',
        ]);

        if (SystemReview::query()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'status' => 'already_submitted',
                'message' => 'Anda sudah pernah memberikan review untuk sistem ini.',
            ], 409);
        }

        $musyrif = $user->musyrif
            ?? Musyrif::query()->where('user_id', $user->id)->first();

        if (!$musyrif) {
            return response()->json([
                'status' => 'profile_missing',
                'message' => 'Profil Musyrif tidak ditemukan. Hubungi administrator.',
            ], 422);
        }

        try {
            $review = DB::transaction(function () use (
                $validated,
                $user,
                $musyrif,
                $request
            ): SystemReview {
                return SystemReview::query()->create([
                    'user_id' => $user->id,
                    'musyrif_id' => $musyrif->id,
                    'display_name' => $musyrif->nama ?: $user->name,
                    'role_label' => 'Musyrif',
                    'rating' => (int) $validated['rating'],
                    'title' => filled($validated['title'] ?? null)
                        ? trim($validated['title'])
                        : null,
                    'review' => trim($validated['review']),
                    'is_anonymous' => !$request->boolean('show_name'),
                    'status' => SystemReview::STATUS_PENDING,
                ]);
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return response()->json([
                    'status' => 'already_submitted',
                    'message' => 'Review untuk akun ini sudah tersimpan.',
                ], 409);
            }

            throw $exception;
        }

        $request->session()->forget('system_review_prompted');

        return response()->json([
            'status' => 'success',
            'message' => 'Terima kasih. Review Anda sudah dikirim dan menunggu moderasi Super Admin.',
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'review_status' => $review->status,
            ],
        ], 201);
    }
}
