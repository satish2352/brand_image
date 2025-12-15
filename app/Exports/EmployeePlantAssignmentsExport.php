<?php

namespace App\Exports;

use App\Models\EmployeePlantAssignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class EmployeePlantAssignmentsExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = EmployeePlantAssignment::with(['employee', 'plant'])
                    ->where('is_deleted', 0);

        if ($this->search) {
            $query->whereHas('employee', function($q) {
                $q->where('employee_name', 'like', '%' . $this->search . '%');
            })->orWhereHas('plant', function($q) {
                $q->where('plant_name', 'like', '%' . $this->search . '%');
            });
        }

        $assignments = $query->get();
        $srNo = 1;

        return $assignments->map(function($data) use (&$srNo) {
            return [
                'Sr No'        => $srNo++,
                'Employee'     => $data->employee->employee_name ?? '-',
                'Plant'        => $data->plant->plant_name ?? '-',
                'Departments'  => $data->departments_names ?? '-',
                'Projects'     => $data->projects_names ?? '-',
                'Created At'   => $data->created_at 
                                  ? Carbon::parse($data->created_at)->setTimezone('Asia/Kolkata')->format('d-m-Y h:i:s A') 
                                  : '-',
                'Status'       => $data->is_active ? 'Active' : 'Deactive',
            ];
        });
    }

    public function headings(): array
    {
        return ['Sr No', 'Employee', 'Plant', 'Departments', 'Projects', 'Created At', 'Status'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                // Apply borders
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                        ]
                    ]
                ]);

                // Header styling
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => '952419']
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFF']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Auto-size columns
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }
}
