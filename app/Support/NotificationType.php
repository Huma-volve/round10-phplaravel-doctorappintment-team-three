<?php

namespace App\Support;

final class NotificationType
{
    public const APPOINTMENT_UPCOMING = 'appointment_upcoming';

    public const APPOINTMENT_CANCELLED = 'appointment_cancelled';

    public const APPOINTMENT_RESCHEDULED = 'appointment_rescheduled';

    public const APPOINTMENT_SUBMITTED_FOR_PATIENT = 'appointment_submitted_for_patient';

    public const APPOINTMENT_NEW_FOR_DOCTOR = 'appointment_new_for_doctor';

    public const REVIEW_NEW_FOR_DOCTOR = 'review_new_for_doctor';

    public const CHAT_NEW_MESSAGE = 'chat_new_message';

    public const PAYMENT_UPDATE_FOR_DOCTOR = 'payment_update_for_doctor';

    public const ADMIN_SYSTEM_ALERT = 'admin_system_alert';

    public const ADMIN_BROADCAST = 'admin_broadcast';

    public const SESSION_FEEDBACK_FOR_ADMIN = 'session_feedback_for_admin';
}
