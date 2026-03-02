<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('template_type')) {
            return;
        }

        Schema::create('template_type', function (Blueprint $table): void {
            $table->id();

            $table->text('description')->nullable();
            $table->json('structure')->nullable();

            $table->unsignedInteger('sort')->nullable()->index();
            $table->string('status', 32)->default('active')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_type');
    }
};
