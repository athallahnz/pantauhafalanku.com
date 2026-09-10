<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class TilawahMandiriRouteTest extends TestCase
{
    public function test_tilawah_mandiri_writes_bypass_academic_day_middleware(): void
    {
        foreach ([
            'musyrif.tilawah-mandiri.store',
            'musyrif.tilawah-mandiri.update',
            'musyrif.tilawah-mandiri.destroy',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertInstanceOf(Route::class, $route);
            $this->assertNotContains(
                'academic.day.open',
                $route->middleware(),
                "Route {$routeName} tidak boleh dibatasi hari akademik."
            );
        }
    }

    public function test_tilawah_mandiri_has_a_dedicated_page_and_data_routes(): void
    {
        foreach ([
            'musyrif.tilawah-mandiri.index',
            'musyrif.tilawah-mandiri.data',
            'musyrif.tilawah-mandiri.options',
            'musyrif.tilawah-mandiri.history',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertInstanceOf(Route::class, $route);
        }
    }

    public function test_group_and_catchup_writes_keep_academic_day_middleware(): void
    {
        foreach ([
            'musyrif.tilawah.masal',
            'musyrif.tilawah.catchup.store',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertInstanceOf(Route::class, $route);
            $this->assertContains(
                'academic.day.open',
                $route->middleware(),
                "Route {$routeName} wajib tetap mengikuti hari akademik."
            );
        }
    }
}
