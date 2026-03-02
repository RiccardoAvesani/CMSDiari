<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Se la tabella esiste già (DB sporco / tentativi precedenti), non devo fallire.
        if (Schema::hasTable('page')) {
            return;
        }

        Schema::create('page', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('template_id')
                ->constrained('template')
                ->cascadeOnDelete();

            $table->foreignId('page_type_id')
                ->constrained('page_type')
                ->restrictOnDelete();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('school_id')
                ->constrained('schools')
                ->restrictOnDelete();

            // Ordine della pagina dentro al template
            $table->unsignedInteger('position');

            $table->json('structure')->nullable();

            $table->unsignedInteger('sort')->nullable()->index();
            $table->string('status', 32)->default('active')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['template_id', 'position'], 'uq_page_template_position');
            $table->index(['template_id', 'page_type_id'], 'idx_page_template_page_type');
            $table->index(['school_id', 'order_id'], 'idx_page_school_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page');
    }
};
