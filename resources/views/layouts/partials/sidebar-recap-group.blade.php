@php
    $recapOpen = request()->routeIs($recapRole.'.monitoring.*', $recapRole.'.daily-reports.*');
    $recapLinks = [
        ['monitoring.exams', 'Rekap Ujian Tahsin', 'bi-clipboard-check'],
        ['monitoring.tilawah', 'Rekap Tilawah Mandiri', 'bi-journal-bookmark-fill'],
        ['daily-reports.tahsin', 'Rekap Tahsin Harian', 'bi-book'],
        ['daily-reports.tilawah', 'Rekap Seluruh Tilawah', 'bi-journal-text'],
    ];
@endphp
<li class="nav-item mb-1 recap-sidebar">
    <details data-sidebar-group="{{ $recapRole }}-rekap" @if($recapOpen) open @endif>
        <summary class="nav-link" style="cursor:pointer;list-style:none">
            <i class="nav-icon bi bi-collection"></i><span class="flex-grow-1">Rekap Akademik</span><i class="bi bi-chevron-down recap-chevron" aria-hidden="true"></i>
        </summary>
        <ul class="sidebar-group-items list-unstyled">
            @foreach($recapLinks as $recapLink)
                @if(\Illuminate\Support\Facades\Route::has($recapRole.'.'.$recapLink[0].'.index'))
                <li class="nav-item"><a class="nav-link {{ request()->routeIs($recapRole.'.'.$recapLink[0].'.*') ? 'active' : '' }}" href="{{ route($recapRole.'.'.$recapLink[0].'.index') }}" @if(request()->routeIs($recapRole.'.'.$recapLink[0].'.*')) aria-current="page" @endif><i class="nav-icon bi {{ $recapLink[2] }}"></i><span>{{ $recapLink[1] }}</span></a></li>
                @endif
            @endforeach
        </ul>
    </details>
</li>
