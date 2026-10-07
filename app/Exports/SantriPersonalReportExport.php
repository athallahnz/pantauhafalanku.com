<?php

namespace App\Exports;

use App\Services\SantriPersonalReportService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SantriPersonalReportExport implements WithMultipleSheets
{
    public function __construct(private array $parameters, private array $histories)
    {
    }

    public function sheets(): array
    {
        $sheets = [new AcademicMonitoringSheet('Ringkasan', ['Keterangan', 'Nilai'], $this->parameters)];
        foreach ($this->histories as $name => $rows) {
            $sheets[] = new AcademicMonitoringSheet($name, SantriPersonalReportService::HEADERS, $rows);
        }
        return $sheets;
    }
}
