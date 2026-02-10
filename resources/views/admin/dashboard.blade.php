@extends('layouts.app')

@section('title', 'Dashboard Kepala Departemen')

@section('content')
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $jumlahKelas ?? 0 }}</div>
                    <div class="text-medium-emphasis text-uppercase small">Kelas di Departemen</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $jumlahMusyrif ?? 0 }}</div>
                    <div class="text-medium-emphasis text-uppercase small">Musyrif</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $setoranBulanIni ?? 0 }}</div>
                    <div class="text-medium-emphasis text-uppercase small">Setoran Bulan Ini</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Ringkasan Hafalan per Kelas</div>
        <div class="card-body">
            {{-- nanti bisa diganti chart / tabel ringkasan --}}
            <p>Di sini nanti bisa ditampilkan grafik atau tabel rekap hafalan per kelas.</p>
        </div>
    </div>
@endsection
