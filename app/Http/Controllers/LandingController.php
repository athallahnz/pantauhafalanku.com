<?php

namespace App\Http\Controllers;

use App\Models\SystemReview;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $publishedReviews = SystemReview::query()
            ->published();

        $reviewStats = [
            'total' => (clone $publishedReviews)->count(),
            'average' => round(
                (float) (clone $publishedReviews)->avg('rating'),
                1
            ),
        ];

        $systemReviews = SystemReview::query()
            ->publiclyVisible()
            ->limit(8)
            ->get();

        return view('welcome', compact(
            'systemReviews',
            'reviewStats'
        ));
    }
}
