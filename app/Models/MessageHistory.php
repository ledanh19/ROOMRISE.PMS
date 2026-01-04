<?php

namespace App\Models;

use App\Enums\MessageHistoryStatus;
use App\Enums\MessageSendStatus;
use App\Enums\MessageTag;
use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageHistory extends Model
{
    protected $table = 'messages_histories';

    protected $guarded = [];

    protected $casts = [
        'type'           => MessageType::class,
        'status'         => MessageHistoryStatus::class,
        'tag'            => MessageTag::class,
        'message_status' => MessageSendStatus::class,
        'is_admin_send'  => 'boolean',
        'nights_count'   => 'integer',
        'rooms_count'    => 'integer',
        'check_in_date'  => 'date',
        'check_out_date' => 'date',
        'booking_id'     => 'integer',
        'property_id'    => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MessageDetail::class, 'message_history_id');
    }
}
