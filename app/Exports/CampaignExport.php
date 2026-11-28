<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The quotation sheet for a saved campaign: its booked sites with the days and
 * amount the customer chose. Layout lives in QuotationExport.
 *
 * $userId limits it to that customer's campaign (the website); null skips the
 * check for the admin panel, which may export anyone's.
 */
class CampaignExport extends QuotationExport
{
    protected ?int $userId;
    protected int $campaignId;
    protected ?object $campaign = null;

    public function __construct(?int $userId, int $campaignId)
    {
        $this->userId     = $userId;
        $this->campaignId = $campaignId;
    }

    protected function campaign(): ?object
    {
        return $this->campaign ??= DB::table('campaign')
            ->where('id', $this->campaignId)
            ->when($this->userId !== null, fn ($q) => $q->where('user_id', $this->userId))
            ->first();
    }

    protected function quotationName(): string
    {
        return $this->campaign()->campaign_name ?? 'Campaign';
    }

    protected function quotationNumber(): string
    {
        return now()->format('y') . '/ ' . $this->campaignId;
    }

    protected function rows(): Collection
    {
        if (!$this->campaign()) {
            return collect();
        }

        return self::mediaQuery()
            ->join('cart_items as ci', 'ci.media_id', '=', 'm.id')
            ->addSelect('ci.from_date', 'ci.to_date', 'ci.total_days', 'ci.per_day_price', 'ci.total_price')
            ->where('ci.campaign_id', $this->campaignId)
            ->where('ci.cart_type', 'CAMPAIGN')
            ->orderBy('ci.id')
            ->get()
            ->map(function ($row) {
                $row->days = $this->days($row);

                $row->amount = !empty($row->total_price)
                    ? (float) $row->total_price
                    : (float) ($row->per_day_price ?? 0) * $row->days;

                return $row;
            });
    }

    private function days(object $row): int
    {
        if (!empty($row->total_days)) {
            return (int) $row->total_days;
        }

        if (!empty($row->from_date) && !empty($row->to_date)) {
            return Carbon::parse($row->from_date)->diffInDays(Carbon::parse($row->to_date)) + 1;
        }

        return 0;
    }
}
