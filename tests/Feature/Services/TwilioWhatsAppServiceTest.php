<?php

use App\Services\WhatsApp\TwilioWhatsAppService;
use Illuminate\Support\Facades\Http;

test('sends a WhatsApp message via the Twilio REST API', function () {
    config([
        'services.twilio.account_sid' => 'AC_test_sid',
        'services.twilio.auth_token' => 'test_token',
        'services.twilio.whatsapp_from' => 'whatsapp:+14155238886',
        'services.twilio.default_country_code' => '52',
    ]);
    Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201)]);

    app(TwilioWhatsAppService::class)->sendMessage('8711234567', 'Hola, este es un aviso.');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC_test_sid/Messages.json'
            && $request['From'] === 'whatsapp:+14155238886'
            && $request['To'] === 'whatsapp:+528711234567'
            && $request['Body'] === 'Hola, este es un aviso.';
    });
});

test('does not call Twilio when credentials are not configured', function () {
    config([
        'services.twilio.account_sid' => null,
        'services.twilio.auth_token' => null,
        'services.twilio.whatsapp_from' => null,
    ]);
    Http::fake();

    app(TwilioWhatsAppService::class)->sendMessage('8711234567', 'Hola.');

    Http::assertNothingSent();
});
