<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('template_type')) {
            return;
        }

        Schema::table('template_type', function (Blueprint $table): void {
            if (! Schema::hasColumn('template_type', 'size')) {
                $table->string('size', 1)->default('M')->after('description')->index();
            }

            if (! Schema::hasColumn('template_type', 'constraints')) {
                $table->json('constraints')->nullable()->after('size');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('template_type')) {
            return;
        }

        Schema::table('template_type', function (Blueprint $table): void {
            if (Schema::hasColumn('template_type', 'constraints')) {
                $table->dropColumn('constraints');
            }

            if (Schema::hasColumn('template_type', 'size')) {
                $table->dropColumn('size');
            }
        });
    }
};
