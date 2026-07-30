@php
    $dayContext = $academicDayContext ?? null;
    $dayState = $dayContext['state'] ?? 'unconfigured';
    $isOpen = $dayContext['academic_input_open'] ?? false;
    $tone = match ($dayState) {
        'open' => 'success',
        'input_locked' => 'warning',
        'holiday' => 'info',
        default => 'danger',
    };
    $icon = match ($dayState) {
        'open' => 'bi-calendar2-check-fill',
        'input_locked' => 'bi-lock-fill',
        'holiday' => 'bi-calendar2-x-fill',
        default => 'bi-exclamation-triangle-fill',
    };
@endphp

@if ($dayContext)
    <div class="alert alert-{{ $tone }} border-0 shadow-sm rounded-4 d-flex align-items-start gap-3 mb-4"
        role="status">
        <i class="bi {{ $icon }} fs-4 mt-1"></i>
        <div>
            <div class="fw-bold">{{ $dayContext['label'] }}</div>
            <div class="small">{{ $dayContext['message'] }}</div>
            @if ($dayContext['calendar_day']?->keterangan)
                <div class="small mt-1 opacity-75">
                    {{ $dayContext['calendar_day']->keterangan }}
                </div>
            @endif
        </div>
    </div>
@endif
