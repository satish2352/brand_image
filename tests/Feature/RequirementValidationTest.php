<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RequirementValidationTest extends TestCase
{
    private function valid(array $override = []): array
    {
        return array_merge([
            'full_name'            => 'Ravi Sharma',
            'mobile_no'            => '9876543210',
            'email'                => 'ravi@company.com',
            'city'                 => 'Nashik',
            'area_location'        => 'College Road',
            'media_type'           => 'Hoarding',
            'campaign_start_date'  => now()->addDay()->toDateString(),
            'campaign_end_date'    => now()->addDays(10)->toDateString(),
            'campaign_duration'    => '30 days',
            'required_media_count' => '8',
            'approx_budget'        => '200000',
            'source'               => 'direct',
        ], $override);
    }

    public function test_blank_form_reports_every_mandatory_field(): void
    {
        $this->post('/share-requirement', ['source' => 'direct'])
            ->assertSessionHasErrors([
                'full_name', 'mobile_no', 'city', 'area_location', 'media_type',
                'campaign_start_date', 'campaign_end_date', 'campaign_duration',
                'required_media_count',
            ])
            ->assertSessionDoesntHaveErrors(['email', 'campaign_name', 'approx_budget']);
    }

    public static function badValues(): array
    {
        return [
            'name with digits'   => ['full_name', 'Ravi123'],
            'name too short'     => ['full_name', 'R'],
            'mobile too short'   => ['mobile_no', '98765'],
            'mobile wrong start' => ['mobile_no', '5876543210'],
            'malformed email'    => ['email', 'nope@@x'],
            'budget under floor' => ['approx_budget', '50'],
            'count not a number' => ['required_media_count', 'many'],
            'count zero'         => ['required_media_count', '0'],
            'city with digits'   => ['city', 'Nashik 42'],
        ];
    }

    #[DataProvider('badValues')]
    public function test_a_bad_value_is_rejected(string $field, string $value): void
    {
        $this->post('/share-requirement', $this->valid([$field => $value]))
            ->assertSessionHasErrors($field);
    }

    public function test_start_date_cannot_be_in_the_past(): void
    {
        $this->post('/share-requirement', $this->valid([
            'campaign_start_date' => now()->subDay()->toDateString(),
        ]))->assertSessionHasErrors('campaign_start_date');
    }

    public function test_end_date_cannot_precede_the_start(): void
    {
        $this->post('/share-requirement', $this->valid([
            'campaign_start_date' => now()->addDays(10)->toDateString(),
            'campaign_end_date'   => now()->addDays(2)->toDateString(),
        ]))->assertSessionHasErrors('campaign_end_date');
    }

    /**
     * What a real client pastes: a +91 number, a rupee-formatted budget and a
     * count written out in words. All of it should be read, not rejected.
     */
    public function test_pasted_formatting_passes_validation(): void
    {
        $this->post('/share-requirement', $this->valid([
            'full_name'            => '  Ravi   Sharma ',
            'mobile_no'            => '+91 98765 43210',
            'approx_budget'        => '₹2,00,000',
            'required_media_count' => '8 hoardings',
        ]))->assertSessionHasNoErrors();
    }
}
