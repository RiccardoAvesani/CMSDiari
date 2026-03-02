<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page', function (Blueprint $table): void {
            if (! Schema::hasColumn('page', 'description')) {
                $table->string('description')->nullable()->after('sort');
            }

            if (! Schema::hasColumn('page', 'page_type_description')) {
                $table->string('page_type_description')->nullable()->after('page_type_id');
            }

            if (! Schema::hasColumn('page', 'page_type_space')) {
                $table->decimal('page_type_space', 10, 2)->nullable()->after('page_type_description');
            }

            if (! Schema::hasColumn('page', 'page_type_max_pages')) {
                $table->unsignedInteger('page_type_max_pages')->nullable()->after('page_type_space');
            }

            if (! Schema::hasColumn('page', 'page_type_icon_url')) {
                $table->string('page_type_icon_url')->nullable()->after('page_type_max_pages');
            }

            if (! Schema::hasColumn('page', 'page_type_structure')) {
                $table->json('page_type_structure')->nullable()->after('page_type_icon_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('page', function (Blueprint $table): void {
            if (Schema::hasColumn('page', 'page_type_structure')) {
                $table->dropColumn('page_type_structure');
            }

            if (Schema::hasColumn('page', 'page_type_icon_url')) {
                $table->dropColumn('page_type_icon_url');
            }

            if (Schema::hasColumn('page', 'page_type_max_pages')) {
                $table->dropColumn('page_type_max_pages');
            }

            if (Schema::hasColumn('page', 'page_type_space')) {
                $table->dropColumn('page_type_space');
            }

            if (Schema::hasColumn('page', 'page_type_description')) {
                $table->dropColumn('page_type_description');
            }

            if (Schema::hasColumn('page', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
