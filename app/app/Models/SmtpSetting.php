<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name', 'notification_interval_minutes', 'last_notified_at'])]
class SmtpSetting extends Model
{
    protected function casts(): array
    {
        return [
            'port'                           => 'integer',
            'notification_interval_minutes'  => 'integer',
            'last_notified_at'               => 'datetime',
        ];
    }
}
