<?php

namespace App\Exports;

use Illuminate\Support\Collection;

/**
 * The quotation sheet for a shortlist ticked on /search or the Map.
 *
 * A shortlist has no booking dates, so each site is quoted for one month at
 * its listed monthly price. Layout lives in QuotationExport.
 */
class ShortlistExport extends QuotationExport
{
    private const QUOTE_DAYS = 30;

    /** @var int[] */
    protected array $mediaIds;

    public function __construct(array $mediaIds)
    {
        $this->mediaIds = array_values(array_map('intval', $mediaIds));
    }

    protected function quotationName(): string
    {
        return 'Media Shortlist';
    }

    protected function quotationNumber(): string
    {
        // No saved record behind a shortlist, so the number is the moment it
        // was made: year / month-day-time.
        return now()->format('y') . '/ ' . now()->format('md-Hi');
    }

    protected function rows(): Collection
    {
        $rows = self::mediaQuery()->whereIn('m.id', $this->mediaIds)->get();

        // In the order the team ticked them.
        $order = array_flip($this->mediaIds);

        return $rows
            ->sortBy(fn ($row) => $order[$row->media_id] ?? PHP_INT_MAX)
            ->values()
            ->map(function ($row) {
                $row->days   = self::QUOTE_DAYS;
                $row->amount = (float) ($row->monthly_price ?? 0);

                return $row;
            });
    }
}
