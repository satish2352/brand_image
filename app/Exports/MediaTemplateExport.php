<?php

namespace App\Exports;

use App\Exports\Sheets\MediaTemplateDataSheet;
use App\Exports\Sheets\MediaTemplateInstructionSheet;
use App\Exports\Sheets\MediaTemplateMasterSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The downloadable sample template handed out on the Import tab:
 * Media Data (headers + examples), Instructions, Master Reference.
 */
class MediaTemplateExport implements WithMultipleSheets
{
    protected array $reference;

    protected array $sampleRows;

    protected array $columns;

    /**
     * @param array $columns column subset for a category template; empty means
     *                       every column (the all-categories template).
     */
    public function __construct(array $reference = [], array $sampleRows = [], array $columns = [])
    {
        $this->reference = $reference;
        $this->sampleRows = $sampleRows;
        $this->columns = $columns;
    }

    public function sheets(): array
    {
        return [
            new MediaTemplateDataSheet($this->sampleRows, $this->columns),
            new MediaTemplateInstructionSheet(),
            new MediaTemplateMasterSheet($this->reference),
        ];
    }
}
