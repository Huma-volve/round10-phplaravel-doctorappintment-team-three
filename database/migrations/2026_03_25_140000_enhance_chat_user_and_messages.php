<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_user', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_user', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_favorite');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'media_path')) {
                $table->string('media_path')->nullable()->after('content');
            }
            if (! Schema::hasColumn('messages', 'media_mime')) {
                $table->string('media_mime')->nullable()->after('media_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_user', function (Blueprint $table) {
            if (Schema::hasColumn('chat_user', 'is_archived')) {
                $table->dropColumn('is_archived');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'media_path')) {
                $table->dropColumn('media_path');
            }
            if (Schema::hasColumn('messages', 'media_mime')) {
                $table->dropColumn('media_mime');
            }
        });
    }
};
