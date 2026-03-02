<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_type')) {
            return;
        }

        if (Schema::hasColumn('page_type', 'max_pages')) {
            return;
        }

        Schema::table('page_type', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_pages')
                ->default(1)
                ->after('space');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('page_type')) {
            return;
        }

        if (! Schema::hasColumn('page_type', 'max_pages')) {
            return;
        }

        Schema::table('page_type', function (Blueprint $table): void {
            $table->dropColumn('max_pages');
        });
    }
};
