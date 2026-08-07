<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class TwilioWhatsAppService
{
    public function sendMessage(string $phone, string $message): void
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $from = config('services.twilio.whatsapp_from');

        if (! $accountSid || ! $authToken || ! $from) {
            return;
        }

        Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To' => 'whatsapp:'.$this->toE164($phone),
                'Body' => $message,
            ])
            ->throw();
    }

    private function toE164(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return '+'.config('services.twilio.default_country_code', '52').$digits;
    }
}
