<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();

            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('telephone', 50)->nullable()->index();
            $table->string('email', 255)->nullable()->index();

            $table->unsignedInteger('sort')->default(0)->index();
            $table->enum('status', ['active', 'deleted'])->default('active')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
