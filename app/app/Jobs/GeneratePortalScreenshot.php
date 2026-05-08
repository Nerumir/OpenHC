<?php

namespace App\Jobs;

use App\Models\AdminPortal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Browsershot\Browsershot;

class GeneratePortalScreenshot implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(public AdminPortal $portal) {}

    public function handle(): void
    {
        $dir = storage_path('app/public/portals');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "screenshot_{$this->portal->id}.png";
        $fullPath = "{$dir}/{$filename}";

        try {
            $shot = Browsershot::url($this->portal->url)
                ->noSandbox()
                ->windowSize(1280, 800)
                ->setDelay(1500)
                ->dismissDialogs()
                ->addChromiumArguments([
                    'disable-gpu',
                    'disable-dev-shm-usage',
                    'disable-setuid-sandbox',
                ]);

            $chromePath = env('CHROME_PATH');
            if ($chromePath) {
                $shot->setChromePath($chromePath);
            }

            $shot->save($fullPath);

            $this->portal->update(['screenshot_path' => "portals/{$filename}"]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Portal screenshot failed for [{$this->portal->id}] {$this->portal->url}: " . $e->getMessage());
            $this->portal->update(['screenshot_path' => 'failed']);
        }
    }
}
