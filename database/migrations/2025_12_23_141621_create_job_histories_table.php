<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_histories', function (Blueprint $table) {
            $table->id();
            $table->string('job_uuid')->index();
            $table->string('status');

            // Mirroring jobs table columns
            $table->unsignedBigInteger('job_db_id')->nullable();
            $table->string('queue')->nullable();
            $table->longText('payload')->nullable();
            $table->unsignedTinyInteger('attempts')->nullable();
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at')->nullable();
            $table->unsignedInteger('job_created_at')->nullable();

            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_histories');
    }
};
