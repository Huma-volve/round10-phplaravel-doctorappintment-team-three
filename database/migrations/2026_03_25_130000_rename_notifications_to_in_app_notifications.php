<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications') && ! Schema::hasTable('in_app_notifications')) {
            Schema::rename('notifications', 'in_app_notifications');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('in_app_notifications') && ! Schema::hasTable('notifications')) {
            Schema::rename('in_app_notifications', 'notifications');
        }
    }
};
