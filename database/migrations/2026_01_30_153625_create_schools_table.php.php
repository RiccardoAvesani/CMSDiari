<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();

            $table->string('external_id', 64)->nullable()->unique();
            $table->text('description')->nullable();

            // Soft delete “a stato” (coerente col tuo progetto)
            $table->enum('status', ['active', 'deleted'])->default('active')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
