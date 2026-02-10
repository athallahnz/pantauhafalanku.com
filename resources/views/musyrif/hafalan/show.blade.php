@extends('layouts.app')

@section('title', 'Detail Setoran Hafalan')

@section('content')
    <div class="card">
        <div class="card-header">Detail Setoran</div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Santri</dt>
                <dd class="col-sm-9">{{ $hafalan->santri->nama ?? '-' }}</dd>

                <dt class="col-sm-3">Kelas</dt>
                <dd class="col-sm-9">{{ $hafalan->santri->kelas->nama_kelas ?? '-' }}</dd>

                <dt class="col-sm-3">Juz</dt>
                <dd class="col-sm-9">{{ $hafalan->juz }}</dd>

                <dt class="col-sm-3">Surah / Ayat</dt>
                <dd class="col-sm-9">{{ $hafalan->range_ayat }}</dd>

                <dt class="col-sm-3">Tanggal Setoran</dt>
                <dd class="col-sm-9">{{ $hafalan->tanggal }}</dd>

                <dt class="col-sm-3">Nilai</dt>
                <dd class="col-sm-9">{{ $hafalan->nilai }}</dd>

                <dt class="col-sm-3">Catatan Musyrif</dt>
                <dd class="col-sm-9">{{ $hafalan->catatan }}</dd>
            </dl>
        </div>
    </div>
@endsection
