<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('description', 255);
            $table->string('address', 255)->nullable();

            $table->unsignedInteger('sort')->default(0)->index();
            $table->enum('status', ['active', 'deleted'])->default('active')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
