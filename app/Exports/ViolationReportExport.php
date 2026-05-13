<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ViolationReportExport implements FromCollection, WithHeadings
{
    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function collection()
    {
        return collect($this->data)->map(function ($item) {
            /** * STATUS ANALYSIS */ $status = 'Sangat Baik';
            if ($item->total_alpha > 15) {
                $status = 'Kontrol Lemah';
            } elseif ($item->total_alpha > 5) {
                $status = 'Waspada';
            }
            /** * RISK LEVEL */ $risk = 'Low Risk';
            if ($item->total_poin >= 100) {
                $risk = 'Critical';
            } elseif ($item->total_poin >= 50) {
                $risk = 'High';
            } elseif ($item->total_poin >= 20) {
                $risk = 'Medium';
            }
            $rekomendasi = match ($risk) {
                'Critical' => 'Evaluasi halaqah & pendampingan intensif',
                'High' => 'Monitoring mingguan diperlukan',
                'Medium' => 'Pembinaan berkala',
                default => 'Kondisi terkendali'
            };
            return ['Musyrif' => $item->musyrif->nama ?? '-', 'Total Santri' => $item->total_santri ?? 0, 'Total Alpha' => $item->total_alpha, 'Total Poin' => $item->total_poin, 'Avg Poin/Santri' => $item->total_santri > 0 ? round($item->total_poin / $item->total_santri, 2) : 0, 'Status Kontrol' => $status, 'Risk Level' => $risk, 'Rekomendasi' => $rekomendasi,];
        });
    }
    public function headings(): array
    {
        return ['Musyrif', 'Total Santri', 'Total Alpha', 'Total Poin', 'Avg Poin/Santri', 'Status Kontrol', 'Risk Level', 'Rekomendasi',];
    }
}
