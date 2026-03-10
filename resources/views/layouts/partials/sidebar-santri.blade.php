<li class="nav-title">Menu</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('santri.dashboard') ? 'active' : '' }}"
        href="{{ route('santri.dashboard') }}">
        <i class="nav-icon bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
    </a>
</li>

{{-- Jika fitur riwayat hafalan nanti diaktifkan --}}
{{--
<li class="nav-title">Hafalan Saya</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('santri.hafalan.*') ? 'active' : '' }}" href="{{ route('santri.hafalan.index') }}">
        <i class="nav-icon bi bi-journal-bookmark-fill"></i> Riwayat Hafalan
    </a>
</li>
--}}
