<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();

            $table->string('description', 512);
            $table->text('instructions')->nullable();

            $table->boolean('is_active')->default(true);

            // JSON nativo MySQL; può essere NULL.
            $table->json('value')->nullable();

            $table->enum('environment', ['development', 'preview', 'production'])->default('production');

            $table->enum('permission', ['1', '2', '3', '4', '5', '6'])->default('3');

            // Relazione con User (nullable: obbligatoria solo per permission=6, che gestisco a livello form / logica).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Audit.
            $table->dateTime('created_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['active', 'deleted'])->default('active')->index();

            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['environment']);
            $table->index(['permission']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
