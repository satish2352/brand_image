<?php

namespace App\Http\Services\Insights;

/**
 * A source of nearby places for Media Location Insights.
 *
 * Implementations: GeoapifyPlacesService (default), TomTomPlacesService, SerpApiService.
 * Chosen by config('services.insights.places_provider').
 *
 * Every provider returns places in one normalised shape, containing only
 * fields the provider actually sent:
 *   title       place name (null for unnamed places)
 *   place_id    provider's id
 *   category    readable label, e.g. "College", "Shopping Mall"
 *   group       broad group, e.g. "Education", "Healthcare"
 *   categories  provider's raw category keys
 *   address     formatted address
 *   lat, lng    coordinates
 *   rating, reviews   (SerpApi only)
 */
interface PlacesProvider
{
    /** geoapify | tomtom | serpapi — stored on every result and usage log. */
    public function name(): string;

    public function isConfigured(): bool;

    /** The request settings a saved result answers (categories/limit/radius, or query/zoom). */
    public function signature(): string;

    /** Decimal places lat/lng are rounded to for the shared location cache. */
    public function coordPrecision(): int;

    /** Search radius in metres, or null when the provider has no radius. */
    public function radiusMeters(): ?int;

    /** Days a saved result is reused before a refresh may spend credits. */
    public function cacheDays(): int;

    /**
     * Our own spending ceiling:
     *   period    'day' | 'month'
     *   limit     credits allowed per period
     *   max_cost  most credits one request can cost
     */
    public function budget(): array;

    /**
     * One nearby-places request.
     *
     * @return array{ok: bool, http_status: ?int, places: array, external_id: ?string,
     *               error: ?string, credits: int, duration_ms: int, quota_exceeded: bool}
     */
    public function fetch(float $lat, float $lng): array;

    /** True when the provider itself reports no credits left (checked before spending). */
    public function remoteQuotaExhausted(): bool;

    /** Provider-reported account usage for the admin page, or null. */
    public function accountUsage(): ?array;

    /** Attribution the provider's terms require next to its data, or null. */
    public function attribution(): ?array;

    /** Remove the API key from text before it is logged, stored or shown. */
    public function scrub(string $text): string;
}
