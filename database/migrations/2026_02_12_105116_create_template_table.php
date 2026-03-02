<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('template')) {
            return;
        }

        Schema::create('template', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('template_type_id')
                ->constrained('template_type')
                ->restrictOnDelete();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('school_id')
                ->nullable()
                ->constrained('schools')
                ->restrictOnDelete();

            $table->text('description')->nullable();
            $table->json('structure')->nullable();

            $table->unsignedInteger('sort')->nullable()->index();
            $table->string('status', 32)->default('active')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Un Modello Compilato per Ordine (coerente con orders.template_id).
            $table->unique('order_id', 'uq_template_order_id');

            $table->index(['school_id', 'order_id'], 'idx_template_school_order');
            $table->index('template_type_id', 'idx_template_template_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template');
    }
};
