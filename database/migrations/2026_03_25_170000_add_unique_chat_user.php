<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_user', function (Blueprint $table) {
            $table->unique(['user_id', 'chat_id'], 'chat_user_user_chat_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chat_user', function (Blueprint $table) {
            $table->dropUnique('chat_user_user_chat_unique');
        });
    }
};
