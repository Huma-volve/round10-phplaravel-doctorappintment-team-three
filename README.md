# Doctor Appointment API

Backend API for a doctor appointment platform built with Laravel 12 and Sanctum.
The system supports role-based users (patient, doctor, admin), booking lifecycle, doctor discovery, favorites, reviews, in-app notifications, and real-time chat broadcasting.

## Tech Stack

- PHP 8.2+
- Laravel 12
- MySQL / SQLite
- Laravel Sanctum (token auth)
- Laravel Broadcasting + Pusher

## Core Domain Modules

- Auth and account management
- Doctor search and nearby discovery
- Appointments and status updates
- Favorites (doctors and clinics)
- Reviews and session feedback
- In-app notifications (with real-time broadcasting)
- Patient-doctor chat (text/image/video)
- Payment status updates

## Project Structure

Main application files:

- `app/Http/Controllers/API` - API endpoints
- `app/Services` - use-case/business logic
- `app/Repositories` - data access and query logic
- `app/Models` - Eloquent models
- `app/Events` - broadcasted events
- `app/Observers` - model lifecycle notification triggers
- `routes/api.php` - API routes
- `routes/channels.php` - broadcast channel authorization
- `database/migrations` - schema history
- `database/seeders` - local/dev seed data

## Implemented Features

### 1) Favorites

- Patient can favorite/unfavorite doctors
- Patient can favorite/unfavorite clinics
- Unified favorites listing endpoint
- Favorites persisted polymorphically in `favorites` table (`favoritable_type`, `favoritable_id`)

Key endpoints:

- `GET /api/favorites`
- `POST /api/doctors/{doctor}/favorite`
- `DELETE /api/doctors/{doctor}/favorite`
- `POST /api/clinics/{clinic}/favorite`
- `DELETE /api/clinics/{clinic}/favorite`
- `DELETE /api/favorites/{favorite}`

### 2) Notifications

- User notification inbox with unread count and read/mark-all-read actions
- Admin manual broadcast notifications by target audience
- Auto notifications from domain events (appointment/review/payment/session feedback/chat)

Key endpoints:

- `GET /api/notifications`
- `GET /api/notifications/unread-count`
- `POST /api/notifications/{inAppNotification}/read`
- `POST /api/notifications/read-all`
- `POST /api/admin/notifications/broadcast` (admin)

Notification model and types:

- `app/Models/InAppNotification.php`
- `app/Support/NotificationType.php`

### 3) Real-Time Chat

- Patient-doctor chat thread creation
- Messages pagination
- Send text/image/video
- Mark chat read
- Favorite/archive chat per user (via `chat_user` pivot)
- Message broadcast event + receiver notification

Key endpoints:

- `GET /api/chats`
- `POST /api/chats`
- `GET /api/chats/{chat}`
- `GET /api/chats/{chat}/messages`
- `POST /api/chats/{chat}/messages`
- `POST /api/chats/{chat}/read`
- `POST /api/chats/{chat}/favorite`
- `DELETE /api/chats/{chat}/favorite`
- `POST /api/chats/{chat}/archive`
- `DELETE /api/chats/{chat}/archive`

Broadcast channels:

- `private-user.{id}`
- `private-chat.{chatId}`

### 4) Reviews and Session Feedback

- Patient can add one review per completed appointment
- Patient can submit one session feedback per completed appointment
- Doctor is notified on new review

Key endpoints:

- `GET /api/doctors/{doctor}/reviews`
- `POST /api/appointments/{appointment}/review`
- `POST /api/appointments/{appointment}/feedback`

## Setup

1. Install dependencies:

```bash
composer install
```

2. Configure environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Set database credentials in `.env`

4. Run migrations and seed demo data:

```bash
php artisan migrate:fresh --seed
```

5. Start the app:

```bash
php artisan serve
```

## Pusher / Broadcasting Configuration

Set these environment variables:

- `BROADCAST_CONNECTION=pusher`
- `PUSHER_APP_ID=...`
- `PUSHER_APP_KEY=...`
- `PUSHER_APP_SECRET=...`
- `PUSHER_APP_CLUSTER=...`
- `PUSHER_HOST=`
- `PUSHER_PORT=443`
- `PUSHER_SCHEME=https`

Broadcast auth route uses Sanctum and channel authorization is defined in `routes/channels.php`.

## Seeder Data

Seeder entrypoint:

- `database/seeders/DatabaseSeeder.php`

Main feature seeder:

- `database/seeders/DoctorTestSeeder.php`

This seeder creates realistic sample data for API and Postman testing:

- 1 admin user + admin record
- 2 patient users and profiles
- 3 doctor users and doctor profiles in 3 clinics
- 3 specializations
- Completed, confirmed, and rescheduled appointments
- Reviews
- Session feedback
- Doctor and clinic favorites
- Chat thread, participants, and sample messages
- Paid and pending payments
- In-app notifications for patient, doctor, and admin scenarios

## Seeded Test Accounts

Use these credentials after `php artisan migrate:fresh --seed`:

- Admin: `admin@example.com` / `password`
- Patient 1: `patient@example.com` / `password`
- Patient 2: `patient2@example.com` / `password`
- Doctor 0: `doctor0@example.com` / `password`
- Doctor 1: `doctor1@example.com` / `password`
- Doctor 2: `doctor2@example.com` / `password`

## Useful Commands

```bash
php artisan route:list --path=api
php artisan test
php artisan appointments:send-upcoming-reminders
```

## API Response Contract

Success:

```json
{
  "success": true,
  "message": null,
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

## Notes

- Most business APIs require `Authorization: Bearer <sanctum_token>`.
- `GET /api/doctors/{doctor}/reviews` is public.
- Notifications and chat broadcasts require valid Pusher credentials for real-time delivery.
