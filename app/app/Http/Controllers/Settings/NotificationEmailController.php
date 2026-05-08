<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationEmail;
use App\Models\SmtpSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class NotificationEmailController extends Controller implements HasMiddleware
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

    public function index(Request $request): Response
    {
        return Inertia::render('settings/NotificationEmails', [
            'emails'                        => NotificationEmail::orderBy('email')->get(),
            'notification_interval_minutes' => SmtpSetting::value('notification_interval_minutes') ?? 60,
            'status'                        => $request->session()->get('status'),
        ]);
    }

    public function updateInterval(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notification_interval_minutes' => ['required', 'integer', 'in:5,10,15,30,60,120,240,480,1440'],
        ]);

        SmtpSetting::where('id', 1)->update($validated);

        return back()->with('status', 'interval-saved');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:notification_emails,email'],
            'display_name' => ['nullable', 'string', 'max:255'],
        ]);

        NotificationEmail::create($validated);

        return back();
    }

    public function destroy(NotificationEmail $notificationEmail): RedirectResponse
    {
        $notificationEmail->delete();

        return back();
    }

    public function toggle(NotificationEmail $notificationEmail): RedirectResponse
    {
        $notificationEmail->update(['is_active' => !$notificationEmail->is_active]);

        return back();
    }
}
