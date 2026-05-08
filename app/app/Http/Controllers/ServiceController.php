<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function store(StoreServiceRequest $request): RedirectResponse
    {
        abort_unless($request->user()->canEdit(), 403);

        Service::create($request->validated());

        return back();
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        abort_unless($request->user()->canEdit(), 403);

        $service->update($request->validated());

        return back();
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        abort_unless($request->user()->canEdit(), 403);

        $service->delete();

        return back();
    }

    public function checks(Service $service, Request $request): JsonResponse
    {
        $range = $request->input('range', '24h');

        $query = $service->checks()->reorder('checked_at');

        $query = match ($range) {
            '7d'  => $query->where('checked_at', '>=', now()->subDays(7)),
            '30d' => $query->where('checked_at', '>=', now()->subDays(30)),
            'all' => $query,
            default => $query->where('checked_at', '>=', now()->subDay()),
        };

        $checks = $query->get(['status', 'response_time', 'protocol_detail', 'checked_at']);

        if ($checks->count() <= 100) {
            return response()->json($checks);
        }

        $bucketSize = (int) ceil($checks->count() / 100);

        $aggregated = $checks->chunk($bucketSize)->map(function ($bucket) {
            $responseTimes = $bucket->pluck('response_time')->filter(fn ($v) => $v !== null);

            return [
                'status'          => $bucket->contains('status', 'down') ? 'down' : 'up',
                'response_time'   => $responseTimes->isNotEmpty() ? round($responseTimes->avg(), 2) : null,
                'protocol_detail' => $bucket->last()->protocol_detail,
                'checked_at'      => $bucket->first()->checked_at,
            ];
        });

        return response()->json($aggregated->values());
    }
}
