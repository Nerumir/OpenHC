<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['display_name', 'url', 'description', 'is_active', 'last_http_status', 'last_status', 'last_checked_at', 'screenshot_path'])]
class AdminPortal extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_status' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }
}
