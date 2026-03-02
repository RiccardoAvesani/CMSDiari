<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_type', function (Blueprint $table): void {
            $table->id();

            $table->text('description')->nullable();

            // Es: 0.25, 0.50, 1.00, 2.00
            $table->decimal('space', 5, 2)->default(1.00);

            $table->json('structure')->nullable();

            // Per ora semplice URL/path, in futuro potrà diventare file upload gestito meglio
            $table->string('icon_url', 512)->nullable();

            $table->unsignedInteger('sort')->nullable()->index();

            $table->string('status', 32)->default('active')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_type');
    }
};
