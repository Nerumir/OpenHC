<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['display_name', 'host', 'port', 'protocol', 'description', 'is_active'])]
class Service extends Model
{
    public function checks(): HasMany
    {
        return $this->hasMany(ServiceCheck::class)->orderByDesc('checked_at');
    }

    public function latestCheck(): HasOne
    {
        return $this->hasOne(ServiceCheck::class)->latestOfMany('checked_at');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'port' => 'integer',
        ];
    }
}
