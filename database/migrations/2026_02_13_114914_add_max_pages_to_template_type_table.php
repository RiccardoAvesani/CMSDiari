<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_type', function (Blueprint $table): void {
            $table
                ->unsignedSmallInteger('max_pages')
                ->nullable()
                ->after('structure');
        });
    }

    public function down(): void
    {
        Schema::table('template_type', function (Blueprint $table): void {
            $table->dropColumn('max_pages');
        });
    }
};
