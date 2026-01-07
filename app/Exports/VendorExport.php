<?php
// app/Exports/VendorExport.php
namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VendorExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return DB::table('vendors as v')
            ->join('tbl_location as s', 's.location_id', '=', 'v.state_id')
            ->join('tbl_location as d', 'd.location_id', '=', 'v.district_id')
            ->join('tbl_location as c', 'c.location_id', '=', 'v.city_id')
            ->where('v.is_deleted', 0)
            ->select(
                'v.vendor_code',
                'v.vendor_name',
                'v.mobile',
                'v.email',
                'v.address',
                's.name as state',
                'd.name as district',
                'c.name as city'
            )->get();
    }

    public function headings(): array
    {
        return [
            'Vendor Code',
            'Vendor Name',
            'Mobile',
            'Email',
            'Address',
            'State',
            'District',
            'City'
        ];
    }
}
