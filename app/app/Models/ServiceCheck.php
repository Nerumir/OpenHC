<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_id', 'status', 'response_time', 'protocol_detail', 'error_message', 'checked_at'])]
class ServiceCheck extends Model
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    protected function casts(): array
    {
        return [
            'response_time' => 'float',
            'checked_at' => 'datetime',
        ];
    }
}
