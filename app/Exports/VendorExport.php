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
            ->leftJoin('states as s', 's.id', '=', 'v.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'v.district_id')
            ->leftJoin('cities as c', 'c.id', '=', 'v.city_id')
            ->where('v.is_deleted', 0)
            ->select(
                'v.vendor_code',
                'v.vendor_name',
                'v.mobile',
                'v.email',
                'v.address',
                's.state_name as state',
                'd.district_name as district',
                'c.city_name as city'
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
