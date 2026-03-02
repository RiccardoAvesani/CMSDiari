<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('template_type_page_type')) {
            return;
        }

        // Se qualcuno lancia “da qui in poi” senza le tabelle base, evito errori.
        if (! Schema::hasTable('template_type') || ! Schema::hasTable('page_type')) {
            return;
        }

        Schema::create('template_type_page_type', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('template_type_id')
                ->constrained('template_type')
                ->cascadeOnDelete();

            $table->foreignId('page_type_id')
                ->constrained('page_type')
                ->restrictOnDelete();

            // Dove sta nel Template Generico.
            $table->unsignedInteger('position');

            //'sort' classico
            $table->unsignedInteger('sort')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Permetto ripetizione della stessa page_type_id (occorrenze multiple),
            // ma non permetto due elementi con la stessa position nello stesso template_type.
            $table->unique(['template_type_id', 'position'], 'uq_ttpt_template_type_position');

            $table->index(['template_type_id', 'page_type_id'], 'idx_ttpt_template_type_page_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_type_page_type');
    }
};
