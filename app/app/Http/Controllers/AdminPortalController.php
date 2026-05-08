<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminPortal\StoreAdminPortalRequest;
use App\Http\Requests\AdminPortal\UpdateAdminPortalRequest;
use App\Jobs\GeneratePortalScreenshot;
use App\Models\AdminPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminPortalController extends Controller
{
    public function store(StoreAdminPortalRequest $request): RedirectResponse
    {
        abort_unless($request->user()->canEdit(), 403);

        $portal = AdminPortal::create(array_merge($request->validated(), ['screenshot_path' => 'pending']));

        dispatch(new GeneratePortalScreenshot($portal));

        return back();
    }

    public function update(UpdateAdminPortalRequest $request, AdminPortal $adminPortal): RedirectResponse
    {
        abort_unless($request->user()->canEdit(), 403);

        $urlChanged = $adminPortal->url !== $request->validated()['url'];

        $adminPortal->update($request->validated());

        if ($urlChanged) {
            $adminPortal->update(['screenshot_path' => 'pending']);
            dispatch(new GeneratePortalScreenshot($adminPortal));
        }

        return back();
    }

    public function destroy(Request $request, AdminPortal $adminPortal): RedirectResponse
    {
        abort_unless($request->user()->canEdit(), 403);

        if ($adminPortal->screenshot_path) {
            $fullPath = storage_path("app/public/{$adminPortal->screenshot_path}");
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $adminPortal->delete();

        return back();
    }

    public function refreshScreenshot(Request $request, AdminPortal $adminPortal): RedirectResponse
    {
        abort_unless($request->user()->canEdit(), 403);

        $adminPortal->update(['screenshot_path' => 'pending']);
        dispatch(new GeneratePortalScreenshot($adminPortal));

        return back();
    }
}
