<?php
namespace App\Exports;

use App\Models\Employees;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class EmployeesExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $search;

    public function __construct($search = null){
        $this->search = $search;
    }

    public function collection(){
        $query = Employees::where('is_deleted', 0)
            ->where('id', '!=', 1) // Exclude record with ID = 1
            ->with(['designation', 'role']);

        if($this->search){
            $query->where(function($q){
                $q->where('employee_name','like','%'.$this->search.'%')
                  ->orWhere('employee_code','like','%'.$this->search.'%')
                  ->orWhere('employee_email','like','%'.$this->search.'%')
                  ->orWhere('employee_user_name','like','%'.$this->search.'%');
            });
        }

        $srNo = 1;
        return $query->get()->map(function($emp) use (&$srNo) {
            return [
                'Sr No'       => $srNo++,
                'Name'       => $emp->employee_name,
                'Code'       => $emp->employee_code,
                'Email'      => $emp->employee_email,
                'Username'   => $emp->employee_user_name,
                'Designation'=> $emp->designation->designation ?? '-',
                'Role'       => $emp->role->role ?? '-',
                // 'Created By' => $emp->created_by ?? '-',
                'Created At' => $emp->created_at ? Carbon::parse($emp->created_at)->setTimezone('Asia/Kolkata')->format('d-m-Y h:i:s A') : '-',
                'Status'     => $emp->is_active ? 'Active' : 'Deactive',
            ];
        });
    }

    public function headings(): array{
        return ['Sr No','Name','Code','Email','Username','Designation','Role','Created At','Status'];
    }

    public function styles(Worksheet $sheet){
        return [1 => ['font' => ['bold' => true]]];
    }

    public function registerEvents(): array{
        return [
            AfterSheet::class => function(AfterSheet $event){
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                // Thin borders
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ]);

                // Header style
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['argb'=>'952419']],
                    'font' => ['bold'=>true,'color'=>['argb'=>'ffffff']],
                    'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
                ]);

                // Auto size
                foreach(range('A', $lastColumn) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        ];
    }
}
