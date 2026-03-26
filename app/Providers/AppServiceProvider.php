<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SessionFeedback;
use App\Observers\AppointmentObserver;
use App\Observers\PaymentObserver;
use App\Observers\ReviewObserver;
use App\Observers\SessionFeedbackObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Appointment::observe(AppointmentObserver::class);
        Review::observe(ReviewObserver::class);
        Payment::observe(PaymentObserver::class);
        SessionFeedback::observe(SessionFeedbackObserver::class);
    }
}
