<?php

namespace App\Models;

use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageDetail extends Model
{
    protected $table = 'messages_detail';

    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'type'           => MessageType::class,
        'is_admin_send'  => 'boolean',
        'user_id'        => 'integer',
        'message_history_id' => 'integer',
        'created_at'     => 'immutable_datetime',
    ];

    public function history(): BelongsTo
    {
        return $this->belongsTo(MessageHistory::class, 'message_history_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
