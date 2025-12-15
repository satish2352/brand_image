<?php

namespace App\Exports;

use App\Models\Departments;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class DepartmentsExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = Departments::where('departments.is_deleted', 0)
            ->join('plant_masters', 'departments.plant_id', '=', 'plant_masters.id')
            ->select(
                'plant_masters.plant_name',
                'departments.department_code',
                'departments.department_name',
                'departments.department_short_name',
                'departments.created_by',
                'departments.created_at',
                'departments.is_active'
            );

        if ($this->search) {
            $query->where(function($q) {
                $q->where('departments.department_code', 'like', '%' . $this->search . '%')
                  ->orWhere('departments.department_name', 'like', '%' . $this->search . '%')
                  ->orWhere('departments.department_short_name', 'like', '%' . $this->search . '%')
                  ->orWhere('plant_masters.plant_name', 'like', '%' . $this->search . '%');
            });
        }

        $departments = $query->get();

        $srNo = 1;
        return $departments->map(function($dept) use (&$srNo) {
            return [
                'Sr No'       => $srNo++,
                'Plant Name'            => $dept->plant_name,
                'Department Code'       => $dept->department_code,
                'Department Name'       => $dept->department_name,
                'Department Short Name' => $dept->department_short_name ?? '-',
                'Created By'            => $dept->created_by ?? '-',
                'Created Date'          => $dept->created_at 
                                                ? Carbon::parse($dept->created_at)
                                                        ->setTimezone('Asia/Kolkata')
                                                        ->format('d-m-Y h:i:s A')
                                                : '-',
                'Status'                => $dept->is_active == 1 ? 'Active' : 'Deactive',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Plant Name',
            'Department Code',
            'Department Name',
            'Department Short Name',
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
                        'startColor' => ['argb' => '952419'], // light pink
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
