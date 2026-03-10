<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoMessageLog extends Model
{
    protected $table = 'auto_message_log';

    protected $fillable = ['user_id', 'trigger_type', 'channel', 'date_sent'];

    protected $casts = ['date_sent' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
