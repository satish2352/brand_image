<?php

namespace App\Http\Services\RoadStar;

use App\Models\ApiUsageLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * RoadStar Audience API client — "RoadStar API for Audience Data",
 * API Documentation V2 (Relu Ai Systems). Every endpoint is a JSON POST with
 * HTTP Basic auth (Authorization: Basic base64(username:password)):
 *
 *   /sitedata                   [{arr, st, ed}, …]          audience per site
 *   /addsite                    {arr, latitude, longitude}  register a site
 *   /addcampaign                [{camp_id, arr, st, ed}, …] create a campaign
 *   /fetchcampaigndata          {camp_id}                   campaign report
 *   /modify_end_date            [{camp_id, arr, st, ed}, …] change site end dates
 *   /delete_site_from_campaign  {arr, campid, st, ed}       remove site from campaign
 *   /delete_site                {arr}                       remove site entirely
 *
 * Where the PDF text and its Python examples disagree, the Python examples
 * win (as the documentation's working code), checked against the dev server:
 *  - /fetchcampaigndata takes an object {camp_id}; the array form the text
 *    shows returns HTTP 500.
 *  - /sitedata returns each site's metrics as a JSON-encoded string on the
 *    dev server, where the PDF shows a nested array. Both are accepted
 *    (see RoadStarSiteDataParser).
 * Not verifiable without writing to RoadStar, so the PDF's parameter table is
 * followed: /modify_end_date sends "arr" (its Python example says "siteid").
 *
 * The documentation states no rate limit, pagination or batch size; those
 * are our own settings (config/services.php → roadstar).
 *
 * Every method returns, never throws:
 *   ['ok' => bool, 'http_status' => ?int, 'data' => mixed, 'error' => ?string,
 *    'error_type' => ?string, 'retry_after' => ?int, 'duration_ms' => int]
 *
 * Username and password are only ever placed on the outgoing request. They
 * are scrubbed from every message this class returns or logs, and never
 * written to the database.
 */
class RoadStarService
{
    public const ERR_NOT_CONFIGURED   = 'not_configured';
    public const ERR_INVALID_INPUT    = 'invalid_input';
    public const ERR_CONNECTION       = 'connection';
    public const ERR_UNAUTHORIZED     = 'unauthorized';
    public const ERR_FORBIDDEN        = 'forbidden';
    public const ERR_NOT_FOUND        = 'not_found';
    public const ERR_RATE_LIMITED     = 'rate_limited';
    public const ERR_BAD_REQUEST      = 'bad_request';
    public const ERR_SERVER           = 'server_error';
    public const ERR_INVALID_RESPONSE = 'invalid_response';

    /** Failures worth trying again later (the request itself was fine). */
    public const RETRYABLE = [self::ERR_CONNECTION, self::ERR_RATE_LIMITED, self::ERR_SERVER, self::ERR_INVALID_RESPONSE];

    public const PROVIDER = 'roadstar';

    /** Who/what is calling, for api_usage_logs. Set with using(). */
    private string $source = 'admin';
    private ?int $mediaId = null;
    private ?int $triggeredBy = null;

    public function isConfigured(): bool
    {
        return trim((string) config('services.roadstar.base_url')) !== ''
            && trim((string) config('services.roadstar.username')) !== ''
            && (string) config('services.roadstar.password') !== '';
    }

    /**
     * A copy of this client that records calls against a source
     * (admin | scheduler | queue | console), a media id and an admin user.
     */
    public function using(string $source, ?int $mediaId = null, ?int $triggeredBy = null): static
    {
        $clone = clone $this;
        $clone->source = mb_substr($source, 0, 20);
        $clone->mediaId = $mediaId;
        $clone->triggeredBy = $triggeredBy;

        return $clone;
    }

    /* ============================ ENDPOINTS ============================ */

    /**
     * POST /sitedata — audience for one or more sites.
     *
     * @param array<int, array{arr: string, st: string, ed: string}> $sites
     * @return array 'data' => [siteId => value as returned] (value: metrics
     *               array or JSON string, a message string, or null)
     */
    public function siteData(array $sites): array
    {
        $payload = [];
        foreach ($sites as $site) {
            $arr = trim((string) ($site['arr'] ?? ''));
            $st = (string) ($site['st'] ?? '');
            $ed = (string) ($site['ed'] ?? '');

            if ($arr === '') {
                return $this->invalid('Every site needs a RoadStar site id (arr).');
            }
            if ($error = $this->dateRangeError($st, $ed)) {
                return $this->invalid($error);
            }
            $payload[] = ['arr' => $arr, 'st' => $st, 'ed' => $ed];
        }
        if (!$payload) {
            return $this->invalid('No sites to request.');
        }

        $result = $this->post('/sitedata', $payload, true, count($payload) . ' site(s)', $payload[0]['arr']);
        if (!$result['ok']) {
            return $result;
        }

        // [{siteId: value}, {siteId: value}, …] → [siteId => value]
        $data = $result['data'];
        if ($result['http_status'] === 204 || $data === null) {
            $result['data'] = [];
            return $result;
        }
        if (!is_array($data) || !array_is_list($data)) {
            return $this->unexpected($result, is_array($data) && isset($data['error']) && is_string($data['error'])
                ? 'RoadStar: ' . $this->scrub(mb_substr($data['error'], 0, 300))
                : 'RoadStar returned an unexpected /sitedata response (not a list of sites).');
        }

        $bySite = [];
        foreach ($data as $entry) {
            if (!is_array($entry)) {
                return $this->unexpected($result, 'RoadStar returned an unexpected /sitedata entry.');
            }
            foreach ($entry as $siteId => $value) {
                $bySite[(string) $siteId] = $value;
            }
        }
        $result['data'] = $bySite;

        return $result;
    }

    /**
     * POST /addsite — register a site (our identifier + coordinates).
     * Success body: {"<arr>": "Site added successfully"}.
     */
    public function addSite(string $arr, float $latitude, float $longitude): array
    {
        $arr = trim($arr);
        if ($arr === '') {
            return $this->invalid('A RoadStar site id (arr) is required.');
        }
        if (!is_finite($latitude) || $latitude < -90 || $latitude > 90
            || !is_finite($longitude) || $longitude < -180 || $longitude > 180) {
            return $this->invalid('Latitude/longitude must be valid decimal coordinates.');
        }

        // Not retried: a timeout after RoadStar received it must not add twice.
        $result = $this->post('/addsite', ['arr' => $arr, 'latitude' => $latitude, 'longitude' => $longitude], false, 'addsite', $arr);

        return $this->requireSuccessMessage($result, $arr, 'added successfully');
    }

    /**
     * POST /addcampaign — one campaign, one or more sites.
     *
     * @param array<int, array{arr: string, st: string, ed: string}> $sites
     */
    public function addCampaign(string $campId, array $sites): array
    {
        $campId = trim($campId);
        if ($campId === '') {
            return $this->invalid('A campaign id is required.');
        }

        $payload = [];
        foreach ($sites as $site) {
            $arr = trim((string) ($site['arr'] ?? ''));
            if ($arr === '' || ($error = $this->dateRangeError((string) ($site['st'] ?? ''), (string) ($site['ed'] ?? '')))) {
                return $this->invalid($error ?? 'Every site needs a RoadStar site id (arr).');
            }
            $payload[] = ['camp_id' => $campId, 'arr' => $arr, 'st' => $site['st'], 'ed' => $site['ed']];
        }
        if (!$payload) {
            return $this->invalid('A campaign needs at least one site.');
        }

        return $this->post('/addcampaign', $payload, false, 'addcampaign', $campId);
    }

    /** POST /fetchcampaigndata — campaign report ({"<camp_id>": data}). */
    public function fetchCampaignData(string $campId): array
    {
        $campId = trim($campId);
        if ($campId === '') {
            return $this->invalid('A campaign id is required.');
        }

        $result = $this->post('/fetchcampaigndata', ['camp_id' => $campId], true, 'fetchcampaigndata', $campId);
        if (!$result['ok'] || !is_array($result['data'])) {
            return $result;
        }

        // [{"3714": "<json>"}] → the decoded report for this campaign.
        $data = $result['data'];
        $value = array_is_list($data) ? ($data[0][$campId] ?? null) : ($data[$campId] ?? null);
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }
        $result['data'] = $value;

        return $result;
    }

    /**
     * POST /modify_end_date
     *
     * @param array<int, array{arr: string, st: string, ed: string}> $sites
     */
    public function modifyEndDate(string $campId, array $sites): array
    {
        $campId = trim($campId);
        $payload = [];
        foreach ($sites as $site) {
            $arr = trim((string) ($site['arr'] ?? ''));
            if ($campId === '' || $arr === '' || ($error = $this->dateRangeError((string) ($site['st'] ?? ''), (string) ($site['ed'] ?? '')))) {
                return $this->invalid($error ?? 'Campaign id and site id (arr) are required.');
            }
            $payload[] = ['camp_id' => $campId, 'arr' => $arr, 'st' => $site['st'], 'ed' => $site['ed']];
        }
        if (!$payload) {
            return $this->invalid('No sites to modify.');
        }

        return $this->post('/modify_end_date', $payload, false, 'modify_end_date', $campId);
    }

    /** POST /delete_site_from_campaign — {"message": "Site deleted successfully from campaign."} */
    public function deleteSiteFromCampaign(string $arr, string $campId, string $st, string $ed): array
    {
        $arr = trim($arr);
        $campId = trim($campId);
        if ($arr === '' || $campId === '') {
            return $this->invalid('Site id (arr) and campaign id are required.');
        }
        if ($error = $this->dateRangeError($st, $ed)) {
            return $this->invalid($error);
        }

        return $this->post('/delete_site_from_campaign', ['arr' => $arr, 'campid' => $campId, 'st' => $st, 'ed' => $ed], false, 'delete_site_from_campaign', $arr);
    }

    /** POST /delete_site — {"message": "Site deleted successfully from display data."} */
    public function deleteSite(string $arr): array
    {
        $arr = trim($arr);
        if ($arr === '') {
            return $this->invalid('A RoadStar site id (arr) is required.');
        }

        return $this->post('/delete_site', ['arr' => $arr], false, 'delete_site', $arr);
    }

    /**
     * Reachability + credentials check with no side effects: a /sitedata
     * request for a site id nobody registers. RoadStar answers 200 with
     * "Site not available…" when the credentials are good, 401 when not.
     */
    public function testConnection(string $st, string $ed): array
    {
        return $this->siteData([['arr' => 'BI/CONNECTION-TEST', 'st' => $st, 'ed' => $ed]]);
    }

    /* ============================ TRANSPORT ============================ */

    private function post(string $endpoint, array $payload, bool $retry, string $summary, ?string $externalId): array
    {
        $started = microtime(true);
        $result = [
            'ok'          => false,
            'http_status' => null,
            'data'        => null,
            'error'       => null,
            'error_type'  => null,
            'retry_after' => null,
            'duration_ms' => 0,
        ];

        if (!$this->isConfigured()) {
            $result['error'] = 'RoadStar is not configured (ROADSTAR_BASE_URL / ROADSTAR_USERNAME / ROADSTAR_PASSWORD).';
            $result['error_type'] = self::ERR_NOT_CONFIGURED;
            $this->record($endpoint, $summary, $externalId, $result, ApiUsageLog::NOT_CONFIGURED);
            return $result;
        }

        try {
            $request = Http::withBasicAuth((string) config('services.roadstar.username'), (string) config('services.roadstar.password'))
                ->acceptJson()
                ->asJson()
                ->timeout(max(1, (int) config('services.roadstar.timeout', 60)))
                ->connectTimeout(max(1, (int) config('services.roadstar.connect_timeout', 10)));

            if ($retry) {
                // Short in-request retry for a dropped connection or a gateway
                // error only. Plain 500 is not retried: RoadStar answers 500 to
                // an invalid date too, so it is not always temporary. Longer
                // back-off belongs to the queue (SyncRoadStarSites).
                $request = $request->retry(2, 1000, function (Throwable $e) {
                    return $e instanceof ConnectionException
                        || ($e instanceof RequestException && in_array($e->response->status(), [502, 503, 504], true));
                }, throw: false);
            }

            $response = $request->post($this->url($endpoint), $payload);
            $status = $response->status();
            $result['http_status'] = $status;

            $body = trim($response->body());
            $json = null;
            $jsonOk = true;
            if ($body !== '') {
                $json = json_decode($body, true);
                $jsonOk = json_last_error() === JSON_ERROR_NONE;
            }
            $detail = is_array($json) && isset($json['error']) && is_string($json['error'])
                ? $this->scrub(mb_substr($json['error'], 0, 300))
                : null;

            if ($status === 401) {
                $result['error'] = 'RoadStar rejected the credentials (HTTP 401). Check ROADSTAR_USERNAME / ROADSTAR_PASSWORD.';
                $result['error_type'] = self::ERR_UNAUTHORIZED;
            } elseif ($status === 403) {
                $result['error'] = 'RoadStar refused access (HTTP 403).' . ($detail ? ' ' . $detail : '');
                $result['error_type'] = self::ERR_FORBIDDEN;
            } elseif ($status === 404) {
                $result['error'] = 'RoadStar endpoint not found (HTTP 404): ' . $endpoint . '. Check ROADSTAR_BASE_URL.';
                $result['error_type'] = self::ERR_NOT_FOUND;
            } elseif ($status === 429) {
                $retryAfter = $response->header('Retry-After');
                $result['retry_after'] = is_numeric($retryAfter) ? max(1, (int) $retryAfter) : null;
                $result['error'] = 'RoadStar rate limit reached (HTTP 429).';
                $result['error_type'] = self::ERR_RATE_LIMITED;
            } elseif ($status >= 500) {
                $result['error'] = 'RoadStar server error (HTTP ' . $status . ')' . ($detail ? ': ' . $detail : '.');
                $result['error_type'] = self::ERR_SERVER;
            } elseif ($status >= 400) {
                $result['error'] = 'RoadStar rejected the request (HTTP ' . $status . ')' . ($detail ? ': ' . $detail : '.');
                $result['error_type'] = self::ERR_BAD_REQUEST;
            } elseif (!$jsonOk) {
                $result['error'] = 'RoadStar returned a response that is not valid JSON (HTTP ' . $status . ').';
                $result['error_type'] = self::ERR_INVALID_RESPONSE;
            } else {
                $result['ok'] = true;
                $result['data'] = $json;
            }
        } catch (ConnectionException $e) {
            $result['error'] = 'Could not reach RoadStar (timeout, DNS or network error). The test server is available Mon–Fri 10:00–20:00.';
            $result['error_type'] = self::ERR_CONNECTION;
            Log::warning('RoadStar connection failed', ['endpoint' => $endpoint, 'message' => $this->scrub($e->getMessage())]);
        } catch (Throwable $e) {
            $result['error'] = 'Unexpected error while calling RoadStar.';
            $result['error_type'] = self::ERR_CONNECTION;
            Log::error('RoadStar call failed', ['endpoint' => $endpoint, 'exception' => get_class($e), 'message' => $this->scrub($e->getMessage())]);
        }

        $result['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

        if (!$result['ok'] && $result['error_type'] !== self::ERR_CONNECTION) {
            Log::warning('RoadStar request failed', [
                'endpoint'    => $endpoint,
                'http_status' => $result['http_status'],
                'error_type'  => $result['error_type'],
                'message'     => $result['error'],
                'duration_ms' => $result['duration_ms'],
            ]);
        }

        $this->record($endpoint, $summary, $externalId, $result,
            $result['ok'] ? ApiUsageLog::SUCCESS
                : ($result['error_type'] === self::ERR_RATE_LIMITED ? ApiUsageLog::QUOTA_EXCEEDED : ApiUsageLog::FAILED));

        return $result;
    }

    /* ============================ HELPERS ============================ */

    /**
     * A write endpoint answers 200 with {"<id>": "<message>"} or {"error": …}.
     * Only the documented success wording counts as success.
     */
    private function requireSuccessMessage(array $result, string $key, string $needle): array
    {
        if (!$result['ok']) {
            return $result;
        }

        $data = $result['data'];
        $message = is_array($data) ? ($data[$key] ?? $data['error'] ?? $data['message'] ?? null) : null;
        if (is_array($data) && array_is_list($data) && isset($data[0]) && is_array($data[0])) {
            $message = $data[0][$key] ?? $data[0]['error'] ?? null;
        }
        $message = is_string($message) ? $this->scrub(mb_substr($message, 0, 300)) : null;

        if ($message !== null && stripos($message, $needle) !== false) {
            $result['data'] = ['message' => $message];
            return $result;
        }

        $result['ok'] = false;
        $result['error'] = $message !== null ? 'RoadStar: ' . $message : 'RoadStar returned an unexpected response.';
        $result['error_type'] = $message !== null ? self::ERR_BAD_REQUEST : self::ERR_INVALID_RESPONSE;
        Log::warning('RoadStar request not accepted', ['message' => $result['error']]);

        return $result;
    }

    private function dateRangeError(string $st, string $ed): ?string
    {
        foreach (['Start' => $st, 'End' => $ed] as $label => $date) {
            $parsed = \DateTime::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                return $label . ' date must be a valid yyyy-mm-dd date.';
            }
        }

        return $st > $ed ? 'Start date must be on or before the end date.' : null;
    }

    private function invalid(string $message): array
    {
        return [
            'ok' => false, 'http_status' => null, 'data' => null, 'error' => $message,
            'error_type' => self::ERR_INVALID_INPUT, 'retry_after' => null, 'duration_ms' => 0,
        ];
    }

    private function unexpected(array $result, string $message): array
    {
        $result['ok'] = false;
        $result['data'] = null;
        $result['error'] = $message;
        $result['error_type'] = self::ERR_INVALID_RESPONSE;
        Log::warning('RoadStar response not understood', ['http_status' => $result['http_status'], 'message' => $message]);

        return $result;
    }

    /** One api_usage_logs row per request (never fails the call). */
    private function record(string $endpoint, string $summary, ?string $externalId, array $result, string $status): void
    {
        try {
            ApiUsageLog::create([
                'provider'         => self::PROVIDER,
                'media_id'         => $this->mediaId,
                'query'            => mb_substr('POST ' . $endpoint . ' — ' . $summary, 0, 500),
                'status'           => $status,
                'http_status'      => $result['http_status'],
                'credits_consumed' => 0,
                'result_count'     => $result['ok'] && is_array($result['data']) ? min(65535, count($result['data'])) : null,
                'external_id'      => $externalId !== null ? mb_substr($externalId, 0, 100) : null,
                'error_message'    => $result['error'] !== null ? mb_substr($result['error'], 0, 500) : null,
                'duration_ms'      => $result['duration_ms'],
                'source'           => $this->source,
                'triggered_by'     => $this->triggeredBy,
                'requested_at'     => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not record RoadStar API usage', ['message' => $this->scrub($e->getMessage())]);
        }
    }

    private function url(string $endpoint): string
    {
        return rtrim((string) config('services.roadstar.base_url'), '/') . $endpoint;
    }

    /** Removes the username, password and their Basic-auth encoding from text. */
    public function scrub(string $text): string
    {
        $user = (string) config('services.roadstar.username');
        $pass = (string) config('services.roadstar.password');

        $secrets = array_filter([
            $user !== '' && $pass !== '' ? base64_encode($user . ':' . $pass) : '',
            $pass,
            $user,
        ], fn($s) => $s !== '');

        foreach ($secrets as $secret) {
            $text = str_replace($secret, '[redacted]', $text);
        }

        return preg_replace('/(Authorization:\s*Basic\s+)\S+/i', '$1[redacted]', $text);
    }
}
