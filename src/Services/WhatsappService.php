<?php

declare(strict_types=1);

namespace BsoutVendas\Services;

class WhatsappService
{
    public function buildLink(string $productName, float $price, string $phone, string $currency = 'R$'): string
    {
        $digitsPhone = preg_replace('/\D+/', '', $phone);
        $provider = (string) env('WHATSAPP_PROVIDER', 'zapi');
        $message = sprintf('Oi! Interesse no %s por %s%.2f.', $productName, $currency, $price);
        $encodedMessage = urlencode($message);

        if ($provider === 'twilio') {
            $accountSid = (string) env('TWILIO_ACCOUNT_SID', '');
            $authToken = (string) env('WHATSAPP_TOKEN', '');
            $fromNumber = (string) env('WHATSAPP_FROM_NUMBER', '');
            if ($accountSid && $authToken && $fromNumber && $digitsPhone) {
                return sprintf(
                    'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json?To=whatsapp:%s&From=whatsapp:%s&Body=%s',
                    rawurlencode($accountSid),
                    rawurlencode('+' . $digitsPhone),
                    rawurlencode($fromNumber),
                    $encodedMessage
                );
            }
        }

        if ($provider === 'zapi') {
            $instance = (string) env('ZAPI_INSTANCE', '');
            $token = (string) env('WHATSAPP_TOKEN', '');
            if ($instance && $token && $digitsPhone) {
                return sprintf(
                    'https://api.z-api.io/instances/%s/token/%s/send-text?phone=%s&message=%s',
                    rawurlencode($instance),
                    rawurlencode($token),
                    rawurlencode($digitsPhone),
                    $encodedMessage
                );
            }
        }

        if (!$digitsPhone) {
            $digitsPhone = '55';
        }

        return sprintf('https://wa.me/%s?text=%s', rawurlencode($digitsPhone), $encodedMessage);
    }
}
