<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QuranFoundationClient
{
    protected ?string $accessToken = null;

    protected ?int $accessTokenExpiresAt = null;

    public function verseByKey(
        string $verseKey,
        string $language = 'ur',
        bool $includeWords = true,
        ?int $translationResourceId = null,
    ): array {
        $query = array_filter([
            'language' => $language,
            'words' => $includeWords ? 'true' : null,
            'word_fields' => $includeWords ? 'text_uthmani,translation' : null,
            'translations' => $translationResourceId,
            'fields' => 'text_uthmani',
            'per_page' => 300,
        ], static fn ($value) => $value !== null && $value !== '');

        $response = $this->request()
            ->get("verses/by_key/{$verseKey}", $query)
            ->throw()
            ->json();

        if (! is_array($response) || ! isset($response['verse']) || ! is_array($response['verse'])) {
            throw new RuntimeException("Unexpected Quran Foundation verse response for {$verseKey}.");
        }

        return $response['verse'];
    }

    protected function request(): PendingRequest
    {
        $config = config('services.quran_foundation', []);
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://apis-prelive.quran.foundation/content/api/v4'), '/');

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout((int) ($config['timeout'] ?? 30))
            ->withHeaders(array_filter([
                'x-client-id' => $config['client_id'] ?? null,
                'x-auth-token' => $this->accessToken(),
            ]));
    }

    protected function accessToken(): string
    {
        $config = config('services.quran_foundation', []);
        $staticToken = trim((string) ($config['auth_token'] ?? ''));

        if ($staticToken !== '') {
            return $staticToken;
        }

        if ($this->accessToken !== null && $this->accessTokenExpiresAt !== null && $this->accessTokenExpiresAt > time() + 30) {
            return $this->accessToken;
        }

        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException(
                'Quran Foundation credentials are missing. Set QURAN_FOUNDATION_CLIENT_ID and QURAN_FOUNDATION_CLIENT_SECRET, or provide QURAN_FOUNDATION_AUTH_TOKEN.'
            );
        }

        $authBaseUrl = rtrim((string) ($config['auth_base_url'] ?? 'https://prelive-oauth2.quran.foundation'), '/');

        $response = Http::asForm()
            ->acceptJson()
            ->timeout((int) ($config['timeout'] ?? 30))
            ->withBasicAuth($clientId, $clientSecret)
            ->post($authBaseUrl.'/oauth2/token', [
                'grant_type' => 'client_credentials',
                'scope' => 'content',
            ])
            ->throw()
            ->json();

        $token = is_array($response) ? ($response['access_token'] ?? null) : null;
        $expiresIn = is_array($response) ? (int) ($response['expires_in'] ?? 3600) : 3600;

        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('Quran Foundation auth response did not include an access token.');
        }

        $this->accessToken = trim($token);
        $this->accessTokenExpiresAt = time() + max($expiresIn, 60);

        return $this->accessToken;
    }
}
