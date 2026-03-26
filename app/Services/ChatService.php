<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Appointment;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Repositories\ChatRepository;
use App\Support\NotificationType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public function __construct(
        private readonly ChatRepository $chats,
        private readonly NotificationDispatcher $notifications,
    ) {
    }

    /**
     * @return array{paginator: LengthAwarePaginator, unreadResolver: callable(Chat, User): int}
     */
    public function listForUser(User $user, Request $request): array
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $archived = null;
        if ($request->has('archived')) {
            $archived = filter_var($request->input('archived'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($archived === null) {
                $archived = false;
            }
        } else {
            $archived = false;
        }

        $favorite = null;
        if ($request->has('favorite')) {
            $f = filter_var($request->input('favorite'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $favorite = $f === null ? null : $f;
        }

        $paginator = $this->chats->paginateForUser($user, $archived, $favorite, $perPage);

        return [
            'paginator' => $paginator,
            'unreadResolver' => fn (Chat $chat, User $u) => $this->chats->unreadCountForUser($chat, $u),
        ];
    }

    public function resolveChatForUser(int $chatId, User $user): Chat
    {
        $chat = $this->chats->findForUser($chatId, $user);

        if (! $chat) {
            abort(404, 'Chat not found.');
        }

        return $chat;
    }

    public function getOrCreateChat(User $auth, ?int $doctorId, ?int $patientId, ?int $appointmentId): Chat
    {
        if ($auth->isPatient()) {
            $patient = $auth->patient;
            if (! $patient) {
                throw ValidationException::withMessages(['patient' => ['Patient profile not found.']]);
            }
            if (! $doctorId) {
                throw ValidationException::withMessages(['doctor_id' => ['doctor_id is required.']]);
            }
            $patientId = $patient->id;
        } elseif ($auth->isDoctor()) {
            $doctor = $auth->doctor;
            if (! $doctor) {
                throw ValidationException::withMessages(['doctor' => ['Doctor profile not found.']]);
            }
            if (! $patientId) {
                throw ValidationException::withMessages(['patient_id' => ['patient_id is required.']]);
            }
            $doctorId = $doctor->id;
        } else {
            abort(403, 'Only patients and doctors can open chats.');
        }

        if ($appointmentId !== null) {
            $appointment = Appointment::query()->find($appointmentId);
            if (! $appointment || (int) $appointment->patient_id !== (int) $patientId || (int) $appointment->doctor_id !== (int) $doctorId) {
                throw ValidationException::withMessages(['appointment_id' => ['Invalid appointment for this patient and doctor.']]);
            }
        }

        return DB::transaction(function () use ($patientId, $doctorId, $appointmentId) {
            $chat = $this->chats->firstOrCreateChat((int) $patientId, (int) $doctorId, $appointmentId);
            $this->chats->syncParticipants($chat);

            return $chat->fresh(['doctor.user', 'patient.user', 'appointment']);
        });
    }

    public function paginateMessages(Chat $chat, User $user, Request $request): LengthAwarePaginator
    {
        $this->ensureMember($chat, $user);
        $perPage = min(max((int) $request->input('per_page', 30), 1), 100);

        return $this->chats->paginateMessages($chat, $perPage);
    }

    public function sendMessage(Chat $chat, User $sender, ?string $text, ?UploadedFile $file): Message
    {
        $this->ensureMember($chat, $sender);

        if (! $text && ! $file) {
            throw ValidationException::withMessages(['content' => ['Text or file is required.']]);
        }

        $messageType = 'text';
        $content = $text;
        $mediaPath = null;
        $mediaMime = null;

        if ($file) {
            $mime = (string) $file->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $messageType = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $messageType = 'video';
            } else {
                throw ValidationException::withMessages(['file' => ['Only image and video uploads are allowed.']]);
            }

            $path = $file->store('chat-media', 'public');
            $mediaPath = $path;
            $mediaMime = $mime;
            $content = $content ?: null;
        }

        $message = Message::query()->create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'sender_type' => 'user',
            'message_type' => $messageType,
            'content' => $content,
            'media_path' => $mediaPath,
            'media_mime' => $mediaMime,
            'is_read' => false,
        ]);

        $message->load('sender:id,name');

        broadcast(new MessageSent($message));

        $recipient = $this->recipientUser($chat, $sender);
        if ($recipient) {
            $this->notifications->sendToUser(
                $recipient->id,
                NotificationType::CHAT_NEW_MESSAGE,
                'New message',
                'You have a new message in your chat.',
                $chat->id,
            );
        }

        return $message;
    }

    public function markRead(Chat $chat, User $user): void
    {
        $this->ensureMember($chat, $user);
        $this->chats->updatePivot($chat, $user, ['last_read_at' => now()]);
    }

    public function setFavorite(Chat $chat, User $user, bool $favorite): void
    {
        $this->ensureMember($chat, $user);
        $this->chats->updatePivot($chat, $user, ['is_favorite' => $favorite]);
    }

    public function setArchived(Chat $chat, User $user, bool $archived): void
    {
        $this->ensureMember($chat, $user);
        $this->chats->updatePivot($chat, $user, ['is_archived' => $archived]);
    }

    private function ensureMember(Chat $chat, User $user): void
    {
        if (! $this->chats->findForUser($chat->id, $user)) {
            abort(403, 'You are not a participant in this chat.');
        }
    }

    private function recipientUser(Chat $chat, User $sender): ?User
    {
        $chat->loadMissing('patient.user', 'doctor.user');

        if ($chat->patient?->user_id === $sender->id) {
            return $chat->doctor?->user;
        }

        if ($chat->doctor?->user_id === $sender->id) {
            return $chat->patient?->user;
        }

        return null;
    }
}
