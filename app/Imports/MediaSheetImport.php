<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Thin reader used with Excel::toArray().
 *
 * Headings are deliberately NOT mapped by the package: MediaImportExportService
 * normalises them itself so the sheet tolerates spacing / casing / alias
 * differences in whatever the team pastes together.
 */
class MediaSheetImport implements ToArray
{
    public array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }
}
