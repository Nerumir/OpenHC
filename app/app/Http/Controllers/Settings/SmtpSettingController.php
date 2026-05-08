<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SmtpSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SmtpSettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                abort_unless($request->user()->is_admin, 403);
                return $next($request);
            }),
        ];
    }

    public function edit(Request $request): Response
    {
        $settings = SmtpSetting::first();

        return Inertia::render('settings/Smtp', [
            'status' => $request->session()->get('status'),
            'settings' => $settings ? [
                'id' => $settings->id,
                'host' => $settings->host,
                'port' => $settings->port,
                'username' => $settings->username,
                'encryption' => $settings->encryption,
                'from_address' => $settings->from_address,
                'from_name' => $settings->from_name,
            ] : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['required', 'string', 'in:tls,ssl,none'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = encrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        SmtpSetting::updateOrCreate(['id' => 1], $validated);

        return back()->with('status', 'smtp-saved');
    }

    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'host'         => ['required', 'string', 'max:255'],
            'port'         => ['required', 'integer', 'min:1', 'max:65535'],
            'username'     => ['nullable', 'string', 'max:255'],
            'password'     => ['nullable', 'string', 'max:255'],
            'encryption'   => ['required', 'in:tls,ssl,none'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name'    => ['required', 'string', 'max:255'],
        ]);

        if (empty($validated['password'])) {
            $stored = SmtpSetting::first();
            $validated['password'] = $stored?->password ? decrypt($stored->password) : null;
        }

        Config::set('mail.mailers.openhc_test', [
            'transport'  => 'smtp',
            'host'       => $validated['host'],
            'port'       => (int) $validated['port'],
            'encryption' => $validated['encryption'] === 'none' ? null : $validated['encryption'],
            'username'   => $validated['username'],
            'password'   => $validated['password'],
            'timeout'    => 10,
        ]);

        try {
            Mail::mailer('openhc_test')->raw(
                'This is a test email sent by OpenHC to verify your SMTP configuration is working correctly.',
                fn ($message) => $message
                    ->to($validated['from_address'])
                    ->from($validated['from_address'], $validated['from_name'])
                    ->subject('[OpenHC] SMTP test'),
            );

            return response()->json(['message' => 'Test email sent to ' . $validated['from_address'] . '.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
