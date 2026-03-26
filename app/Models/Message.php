<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected static function booted(): void
    {
        static::created(function (Message $message): void {
            $message->chat?->touch();
        });
    }

    protected $fillable = [
        'chat_id',
        'sender_id',
        'sender_type',
        'message_type',
        'content',
        'media_path',
        'media_mime',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
