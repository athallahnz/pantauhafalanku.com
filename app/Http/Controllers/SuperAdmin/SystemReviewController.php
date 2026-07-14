<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SystemReviewController extends Controller
{
    public function index(): View
    {
        $summary = [
            'total' => SystemReview::query()->count(),
            'pending' => SystemReview::query()
                ->where('status', SystemReview::STATUS_PENDING)
                ->count(),
            'published' => SystemReview::query()
                ->where('status', SystemReview::STATUS_PUBLISHED)
                ->count(),
            'hidden' => SystemReview::query()
                ->where('status', SystemReview::STATUS_HIDDEN)
                ->count(),
            'average_rating' => round(
                (float) SystemReview::query()->avg('rating'),
                1
            ),
        ];

        return view('superadmin.system-reviews.index', compact('summary'));
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->ajax(), 404);

        $query = SystemReview::query()
            ->with([
                'user:id,name,email',
                'moderator:id,name',
            ])
            ->select('system_reviews.*');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('reviewer', function (SystemReview $review): string {
                $publicMode = $review->is_anonymous
                    ? '<span class="badge bg-secondary-subtle text-secondary rounded-pill">Anonim di publik</span>'
                    : '<span class="badge bg-primary-subtle text-primary rounded-pill">Nama tampil</span>';

                return '
                    <div class="d-flex flex-column gap-1">
                        <span class="fw-bold">' . e($review->display_name) . '</span>
                        <small class="text-body-secondary">' .
                            e($review->user?->email ?? '-') .
                        '</small>
                        <div>' . $publicMode . '</div>
                    </div>
                ';
            })
            ->addColumn('rating_html', function (SystemReview $review): string {
                $stars = str_repeat(
                    '<i class="bi bi-star-fill text-warning"></i>',
                    $review->rating
                );

                $empty = str_repeat(
                    '<i class="bi bi-star text-body-secondary"></i>',
                    max(0, 5 - $review->rating)
                );

                return '
                    <div class="text-nowrap">' . $stars . $empty . '</div>
                    <small class="text-body-secondary">' .
                        $review->rating .
                        ' dari 5
                    </small>
                ';
            })
            ->addColumn('review_content', function (SystemReview $review): string {
                $title = $review->title
                    ? '<div class="fw-bold mb-1">' . e($review->title) . '</div>'
                    : '';

                return '
                    <div class="review-content-cell">
                        ' . $title . '
                        <div class="small text-body-secondary">' .
                            nl2br(e($review->review)) .
                        '</div>
                    </div>
                ';
            })
            ->addColumn('status_badge', function (SystemReview $review): string {
                return match ($review->status) {
                    SystemReview::STATUS_PUBLISHED =>
                        '<span class="badge text-bg-success rounded-pill px-3 py-2">
                            Published
                        </span>',

                    SystemReview::STATUS_HIDDEN =>
                        '<span class="badge text-bg-dark rounded-pill px-3 py-2">
                            Hidden
                        </span>',

                    default =>
                        '<span class="badge text-bg-warning rounded-pill px-3 py-2">
                            Pending
                        </span>',
                };
            })
            ->addColumn('submitted_at', fn(SystemReview $review): string =>
                $review->created_at?->translatedFormat('d M Y H:i') ?? '-'
            )
            ->addColumn('moderation', function (SystemReview $review): string {
                if (!$review->moderated_at) {
                    return '<span class="text-body-secondary small">Belum dimoderasi</span>';
                }

                return '
                    <div class="small">
                        <div class="fw-semibold">' .
                            e($review->moderator?->name ?? 'System') .
                        '</div>
                        <div class="text-body-secondary">' .
                            e($review->moderated_at->translatedFormat('d M Y H:i')) .
                        '</div>
                    </div>
                ';
            })
            ->addColumn('actions', function (SystemReview $review): string {
                $publishButton = $review->status !== SystemReview::STATUS_PUBLISHED
                    ? '
                        <button type="button"
                            class="btn btn-sm btn-success rounded-pill px-3 btn-review-visibility"
                            data-id="' . $review->id . '"
                            data-status="published"
                            data-label="' . e($review->display_name) . '">
                            <i class="bi bi-eye-fill me-1"></i>
                            Publish
                        </button>
                    '
                    : '';

                $hideButton = $review->status !== SystemReview::STATUS_HIDDEN
                    ? '
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-review-visibility"
                            data-id="' . $review->id . '"
                            data-status="hidden"
                            data-label="' . e($review->display_name) . '">
                            <i class="bi bi-eye-slash-fill me-1"></i>
                            Hidden
                        </button>
                    '
                    : '';

                return '
                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        ' . $publishButton . $hideButton . '
                    </div>
                ';
            })
            ->rawColumns([
                'reviewer',
                'rating_html',
                'review_content',
                'status_badge',
                'moderation',
                'actions',
            ])
            ->orderColumn('reviewer', 'display_name $1')
            ->orderColumn('submitted_at', 'created_at $1')
            ->make(true);
    }

    public function updateVisibility(
        Request $request,
        SystemReview $systemReview
    ): JsonResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    SystemReview::STATUS_PUBLISHED,
                    SystemReview::STATUS_HIDDEN,
                ]),
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $systemReview,
            $request
        ): void {
            $status = $validated['status'];

            $systemReview->forceFill([
                'status' => $status,
                'published_at' => $status === SystemReview::STATUS_PUBLISHED
                    ? ($systemReview->published_at ?? now())
                    : null,
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
            ])->save();
        });

        return response()->json([
            'status' => 'success',
            'message' => $systemReview->status === SystemReview::STATUS_PUBLISHED
                ? 'Review berhasil dipublikasikan di landing page.'
                : 'Review berhasil disembunyikan dari landing page.',
        ]);
    }
}
