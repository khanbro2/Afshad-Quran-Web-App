<?php

namespace Tests\Unit;

use App\Support\QuranFoundationClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuranFoundationClientTest extends TestCase
{
    public function test_it_fetches_an_oauth_token_before_loading_a_verse(): void
    {
        config()->set('services.quran_foundation.base_url', 'https://apis-prelive.quran.foundation/content/api/v4');
        config()->set('services.quran_foundation.auth_base_url', 'https://prelive-oauth2.quran.foundation');
        config()->set('services.quran_foundation.client_id', 'client-id');
        config()->set('services.quran_foundation.client_secret', 'client-secret');
        config()->set('services.quran_foundation.auth_token', null);
        config()->set('services.quran_foundation.timeout', 30);

        Http::fake([
            'https://prelive-oauth2.quran.foundation/oauth2/token' => Http::response([
                'access_token' => 'token-123',
                'expires_in' => 3600,
            ], 200),
            'https://apis-prelive.quran.foundation/content/api/v4/verses/by_key/1:1*' => Http::response([
                'verse' => [
                    'text_uthmani' => 'بِسْمِ اللَّهِ',
                    'words' => [
                        ['position' => 1, 'translation' => ['text' => 'شروع']],
                    ],
                ],
            ], 200),
        ]);

        $client = new QuranFoundationClient();
        $verse = $client->verseByKey('1:1', 'ur', true, 123);

        $this->assertSame('بِسْمِ اللَّهِ', $verse['text_uthmani']);
        $this->assertSame('شروع', $verse['words'][0]['translation']['text']);

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://prelive-oauth2.quran.foundation/oauth2/token'
                && $request['grant_type'] === 'client_credentials'
                && $request['scope'] === 'content';
        });
        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apis-prelive.quran.foundation/content/api/v4/verses/by_key/1:1')
                && $request->header('x-client-id')[0] === 'client-id'
                && $request->header('x-auth-token')[0] === 'token-123';
        });
    }
}
