<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Services\ChatService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {
    }

    public function index(Request $request)
    {
        $result = $this->chatService->listForUser($request->user(), $request);
        $paginator = $result['paginator'];
        $unreadResolver = $result['unreadResolver'];

        $data = $paginator->getCollection()->map(function (Chat $chat) use ($request, $unreadResolver) {
            $chat->setAttribute('unread_count', $unreadResolver($chat, $request->user()));

            return (new ChatResource($chat))->additional(['viewer' => $request->user()])->resolve($request);
        })->values();

        return ApiResponse::success([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
        ]);

        $chat = $this->chatService->getOrCreateChat(
            $request->user(),
            isset($validated['doctor_id']) ? (int) $validated['doctor_id'] : null,
            isset($validated['patient_id']) ? (int) $validated['patient_id'] : null,
            isset($validated['appointment_id']) ? (int) $validated['appointment_id'] : null,
        );

        $chat->setAttribute('unread_count', 0);
        $chat->setAttribute('pivot_is_favorite', false);
        $chat->setAttribute('pivot_is_archived', false);
        $chat->setAttribute('pivot_last_read_at', null);

        $pivot = $chat->users()->where('users.id', $request->user()->id)->first()?->pivot;
        if ($pivot) {
            $chat->setAttribute('pivot_is_favorite', (bool) $pivot->is_favorite);
            $chat->setAttribute('pivot_is_archived', (bool) $pivot->is_archived);
            $chat->setAttribute('pivot_last_read_at', $pivot->last_read_at);
        }

        return ApiResponse::success(
            (new ChatResource($chat->load(['doctor.user', 'patient.user', 'appointment'])))->additional(['viewer' => $request->user()]),
            null,
            201
        );
    }

    public function show(Request $request, Chat $chat)
    {
        $chat = $this->chatService->resolveChatForUser($chat->id, $request->user());
        $chat->load(['doctor.user', 'patient.user', 'appointment']);

        $pivot = $chat->users()->where('users.id', $request->user()->id)->first()?->pivot;
        $chat->setAttribute('pivot_is_favorite', (bool) ($pivot?->is_favorite ?? false));
        $chat->setAttribute('pivot_is_archived', (bool) ($pivot?->is_archived ?? false));
        $chat->setAttribute('pivot_last_read_at', $pivot?->last_read_at);

        $chat->setAttribute(
            'unread_count',
            app(\App\Repositories\ChatRepository::class)->unreadCountForUser($chat, $request->user())
        );

        return ApiResponse::success(
            (new ChatResource($chat))->additional(['viewer' => $request->user()])
        );
    }

    public function messages(Request $request, Chat $chat)
    {
        $chat = $this->chatService->resolveChatForUser($chat->id, $request->user());
        $paginator = $this->chatService->paginateMessages($chat, $request->user(), $request);

        return ApiResponse::success([
            'data' => MessageResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        $chat = $this->chatService->resolveChatForUser($chat->id, $request->user());

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:51200'],
        ]);

        $message = $this->chatService->sendMessage(
            $chat,
            $request->user(),
            $validated['content'] ?? null,
            $request->file('file'),
        );

        return ApiResponse::success(new MessageResource($message), null, 201);
    }

    public function markRead(Request $request, Chat $chat)
    {
        $chat = $this->chatService->resolveChatForUser($chat->id, $request->user());
        $this->chatService->markRead($chat, $request->user());

        return ApiResponse::success(null, 'Marked as read.');
    }

    public function favorite(Request $request, Chat $chat)
    {
        $chat = $this->chatService->resolveChatForUser($chat->id, $request->user());
        $this->chatService->setFavorite($chat, $request->user(), true);

        return ApiResponse::success(['is_favorite' => true]);
    }

    public function unfavorite(Request $request, Chat $chat)
    {
        $chat = $this->chatService->resolveChatForUser($chat->id, $request->user());
        $this->chatService->setFavorite($chat, $request->user(), false);

        return ApiResponse::success(['is_favorite' => false]);
    }

    public function archive(Request $request, Chat $chat)
    {
        $chat = $this->chatService->resolveChatForUser($chat->id, $request->user());
        $this->chatService->setArchived($chat, $request->user(), true);

        return ApiResponse::success(['is_archived' => true]);
    }

    public function unarchive(Request $request, Chat $chat)
    {
        $chat = $this->chatService->resolveChatForUser($chat->id, $request->user());
        $this->chatService->setArchived($chat, $request->user(), false);

        return ApiResponse::success(['is_archived' => false]);
    }
}
