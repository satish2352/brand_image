<?php

namespace App\Exports;

use App\Models\PlantMasters;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PlantsExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = PlantMasters::where('is_deleted', 0);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('plant_code', 'like', '%' . $this->search . '%')
                  ->orWhere('plant_name', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%')
                  ->orWhere('city', 'like', '%' . $this->search . '%')
                  ->orWhere('plant_short_name', 'like', '%' . $this->search . '%');
            });
        }

        $plants = $query->select(
            'plant_code',
            'plant_name',
            'address',
            'city',
            'plant_short_name',
            'created_by',
            'created_at',
            'is_active'
        )->get();

        $srNo = 1;
        return $plants->map(function($plant) use (&$srNo) {
            return [
                'Sr No'       => $srNo++,
                'Plant Code'       => $plant->plant_code,
                'Plant Name'       => $plant->plant_name,
                'Address'          => $plant->address ?? '-',
                'City'             => $plant->city ?? '-',
                'Short Name'       => $plant->plant_short_name ?? '-',
                'Created By'       => $plant->created_by ?? '-',
                'Created Date'     => $plant->created_at
                                        ? Carbon::parse($plant->created_at)
                                                ->setTimezone('Asia/Kolkata')
                                                ->format('d-m-Y h:i:s A')
                                        : '-',
                'Status'           => $plant->is_active == 1 ? 'Active' : 'Deactive',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Plant Code',
            'Plant Name',
            'Address',
            'City',
            'Short Name',
            'Created By',
            'Created Date',
            'Status',
        ];
    }

    // Make header bold
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Row 1 (headers)
        ];
    }

    // Add borders, background color, and alignment
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                // Apply thin borders to all cells
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                // Header style: background + bold + centered
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => '952419'], // dark red/pink
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'ffffff'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Auto size all columns
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
