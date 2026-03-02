<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'user',
        'school',
        'campaign',
        'invitation',
        'order',
        'template_type',
        'template',
        'draft',
        'annotation',
        'log',
        'setting',
        'appsetting',
        'location',
        'contact',
        'page_type',
        'page',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'sort')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedInteger('sort')->nullable()->index();
            });

            if (Schema::hasColumn($table, 'id')) {
                DB::statement("UPDATE `{$table}` SET `sort` = `id` WHERE `sort` IS NULL");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, 'sort')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('sort');
            });
        }
    }
};
