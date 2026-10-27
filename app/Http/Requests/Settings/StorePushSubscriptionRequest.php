<?php

namespace App\Http\Requests\Settings;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Corpo = `PushSubscription.toJSON()` del browser (+ contentEncoding).
 */
class StorePushSubscriptionRequest extends FormRequest
{
    /**
     * Host dei push service dei browser. Il server fa POST all'endpoint: senza
     * allowlist sarebbe un vettore SSRF.
     */
    private const PUSH_SERVICE_HOSTS = [
        'fcm.googleapis.com',
        'android.googleapis.com',
        'updates.push.services.mozilla.com',
        'web.push.apple.com',
        '*.push.apple.com',
        '*.notify.windows.com',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'url:https', 'max:500', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Str::is(self::PUSH_SERVICE_HOSTS, (string) parse_url((string) $value, PHP_URL_HOST))) {
                    $fail('Servizio di notifiche non supportato.');
                }
            }],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ];
    }
}
