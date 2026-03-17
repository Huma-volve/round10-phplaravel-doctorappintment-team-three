<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'type')) {
                $table->string('type')->nullable();
            }
            if (!Schema::hasColumn('notifications', 'related_id')) {
                $table->unsignedBigInteger('related_id')->nullable();
            }
            if (!Schema::hasColumn('notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }

            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndexIfExists('notifications_user_id_index');
            $table->dropIndexIfExists('notifications_type_index');
            $table->dropIndexIfExists('notifications_is_read_index');
            $table->dropIndexIfExists('notifications_created_at_index');
        });
    }
};
