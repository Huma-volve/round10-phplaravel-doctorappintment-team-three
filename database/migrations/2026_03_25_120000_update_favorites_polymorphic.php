<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->upSqlite();

            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('favorites', function (Blueprint $table) {
            $table->string('favoritable_type')->nullable()->after('patient_id');
            $table->unsignedBigInteger('favoritable_id')->nullable()->after('favoritable_type');
        });

        DB::table('favorites')->update([
            'favoritable_type' => \App\Models\Doctor::class,
            'favoritable_id' => DB::raw('doctor_id'),
        ]);

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropUnique(['patient_id', 'doctor_id']);
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn('doctor_id');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->string('favoritable_type')->nullable(false)->change();
            $table->unsignedBigInteger('favoritable_id')->nullable(false)->change();
            $table->unique(['patient_id', 'favoritable_type', 'favoritable_id'], 'favorites_patient_favoritable_unique');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->downSqlite();

            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropUnique('favorites_patient_favoritable_unique');
            $table->unsignedBigInteger('doctor_id')->nullable()->after('patient_id');
        });

        DB::table('favorites')
            ->where('favoritable_type', \App\Models\Doctor::class)
            ->update(['doctor_id' => DB::raw('favoritable_id')]);

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn(['favoritable_type', 'favoritable_id']);
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->unique(['patient_id', 'doctor_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    private function upSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('favorites_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('favoritable_type');
            $table->unsignedBigInteger('favoritable_id');
            $table->timestamps();
            $table->unique(['patient_id', 'favoritable_type', 'favoritable_id'], 'favorites_patient_favoritable_unique');
        });

        foreach (DB::table('favorites')->cursor() as $row) {
            DB::table('favorites_new')->insert([
                'id' => $row->id,
                'patient_id' => $row->patient_id,
                'favoritable_type' => \App\Models\Doctor::class,
                'favoritable_id' => $row->doctor_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('favorites');
        Schema::rename('favorites_new', 'favorites');

        Schema::enableForeignKeyConstraints();
    }

    private function downSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('favorites_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['patient_id', 'doctor_id']);
        });

        foreach (DB::table('favorites')->cursor() as $row) {
            if ($row->favoritable_type !== \App\Models\Doctor::class) {
                continue;
            }

            DB::table('favorites_old')->insert([
                'id' => $row->id,
                'patient_id' => $row->patient_id,
                'doctor_id' => $row->favoritable_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('favorites');
        Schema::rename('favorites_old', 'favorites');

        Schema::enableForeignKeyConstraints();
    }
};
