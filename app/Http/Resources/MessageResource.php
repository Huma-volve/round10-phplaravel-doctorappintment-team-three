<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MessageResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $mediaUrl = null;
        if ($this->media_path) {
            $mediaUrl = Storage::disk('public')->url($this->media_path);
        }

        return [
            'id' => $this->id,
            'chat_id' => $this->chat_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name,
            'message_type' => $this->message_type,
            'content' => $this->content,
            'media_url' => $mediaUrl,
            'media_mime' => $this->media_mime,
            'created_at' => $this->created_at,
        ];
    }
}
