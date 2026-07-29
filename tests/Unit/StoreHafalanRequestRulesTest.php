<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\StoreHafalanRequest;
use ReflectionMethod;
use Tests\TestCase;

final class StoreHafalanRequestRulesTest extends TestCase
{
    public function test_lulus_requires_material_and_score(): void
    {
        $request = $this->requestFor(['status' => 'lulus']);
        $rules = $request->rules();

        self::assertContains('required', $rules['hafalan_template_id']);
        self::assertContains('required', $rules['nilai_label']);
    }

    public function test_ulang_requires_material_but_not_score(): void
    {
        $request = $this->requestFor(['status' => 'ulang']);
        $rules = $request->rules();

        self::assertContains('required', $rules['hafalan_template_id']);
        self::assertContains('nullable', $rules['nilai_label']);
        self::assertNotContains('required', $rules['nilai_label']);
    }

    public function test_absence_status_does_not_require_material_or_score(): void
    {
        $request = $this->requestFor(['status' => 'izin']);
        $rules = $request->rules();

        self::assertContains('nullable', $rules['hafalan_template_id']);
        self::assertContains('nullable', $rules['nilai_label']);
    }

    public function test_prepare_for_validation_removes_score_from_ulang(): void
    {
        $request = $this->requestFor([
            'status' => 'ulang',
            'nilai_label' => 'jayyid',
            'nilai' => 75,
        ]);

        $this->invokePrepareForValidation($request);

        self::assertNull($request->input('nilai_label'));
        self::assertNull($request->input('nilai'));
    }

    public function test_prepare_for_validation_preserves_lulus_score(): void
    {
        $request = $this->requestFor([
            'status' => 'lulus',
            'nilai_label' => 'mumtaz',
            'nilai' => 95,
        ]);

        $this->invokePrepareForValidation($request);

        self::assertSame('mumtaz', $request->input('nilai_label'));
        self::assertSame(95, $request->input('nilai'));
    }

    /** @param array<string, mixed> $payload */
    private function requestFor(array $payload): StoreHafalanRequest
    {
        $request = StoreHafalanRequest::create('/', 'POST', $payload);
        $request->setContainer($this->app);

        return $request;
    }

    private function invokePrepareForValidation(
        StoreHafalanRequest $request
    ): void {
        $method = new ReflectionMethod(
            StoreHafalanRequest::class,
            'prepareForValidation'
        );
        $method->setAccessible(true);
        $method->invoke($request);
    }
}
