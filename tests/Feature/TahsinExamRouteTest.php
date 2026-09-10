<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class TahsinExamRouteTest extends TestCase
{
    public function test_tahsin_exam_has_dedicated_routes(): void
    {
        foreach ([
            'musyrif.tahsin-exams.index',
            'musyrif.tahsin-exams.data',
            'musyrif.tahsin-exams.options',
            'musyrif.tahsin-exams.history',
            'musyrif.tahsin-exams.store',
            'musyrif.tahsin-exams.update',
            'musyrif.tahsin-exams.destroy',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertInstanceOf(Route::class, $route);
        }
    }

    public function test_exam_writes_are_not_daily_academic_transactions(): void
    {
        foreach ([
            'musyrif.tahsin-exams.store',
            'musyrif.tahsin-exams.update',
            'musyrif.tahsin-exams.destroy',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertInstanceOf(Route::class, $route);
            $this->assertNotContains(
                'academic.day.open',
                $route->middleware(),
                "Route {$routeName} tidak boleh bergantung pada hari input harian."
            );
        }
    }
}
