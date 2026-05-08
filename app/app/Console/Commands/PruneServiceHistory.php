<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneServiceHistory extends Command
{
    protected $signature = 'services:prune-history
                            {--dry-run : Affiche ce qui serait supprimé sans supprimer}';

    protected $description = 'Supprime les service_checks anciens selon une politique de rétention étagée';

    // [age_from_seconds, age_to_seconds_or_null, bucket_seconds, label]
    // bucket_seconds = 1 / (records_per_second souhaités)
    private const WINDOWS = [
        [0,         86400,    864,     '0–24h    (100 total)'],
        [86400,     604800,   7200,    '1–7j     (12/j)'],
        [604800,    2592000,  21600,   '7–30j    (4/j)'],
        [2592000,   31536000, 288000,  '30j–1an  (0.3/j)'],
        [31536000,  null,     2592000, '>1an     (1/mois)'],
    ];

    public function handle(): int
    {
        $dryRun     = $this->option('dry-run');
        $serviceIds = DB::table('services')->pluck('id');
        $total      = 0;

        foreach ($serviceIds as $serviceId) {
            foreach (self::WINDOWS as [$fromSec, $toSec, $bucketSec, $label]) {
                $deleted = $this->pruneWindow($serviceId, $fromSec, $toSec, $bucketSec, $dryRun);
                $total  += $deleted;

                if ($deleted > 0) {
                    $verb = $dryRun ? 'à supprimer' : 'supprimées';
                    $this->line("Service #{$serviceId} [{$label}] : {$deleted} lignes {$verb}");
                }
            }
        }

        $verb = $dryRun ? '[DRY-RUN] Lignes à supprimer' : 'Lignes supprimées';
        $this->info("{$verb} : {$total}");

        return self::SUCCESS;
    }

    private function pruneWindow(int $serviceId, int $fromSec, ?int $toSec, int $bucketSec, bool $dryRun): int
    {
        // windowEnd = bord récent de la fenêtre (les plus vieux que $fromSec)
        $windowEnd   = now()->subSeconds($fromSec);
        $windowStart = $toSec ? now()->subSeconds($toSec) : null;

        $base = fn () => DB::table('service_checks')
            ->where('service_id', $serviceId)
            ->where('checked_at', '<=', $windowEnd)
            ->when($windowStart, fn ($q) => $q->where('checked_at', '>=', $windowStart));

        // Un représentant par bucket (le plus ancien = MIN(id))
        $keepIds = $base()
            ->selectRaw('MIN(id) as id')
            ->groupByRaw('FLOOR(UNIX_TIMESTAMP(checked_at) / ?)', [$bucketSec])
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return 0;
        }

        $victims = $base()->whereNotIn('id', $keepIds);

        return $dryRun ? $victims->count() : $victims->delete();
    }
}
