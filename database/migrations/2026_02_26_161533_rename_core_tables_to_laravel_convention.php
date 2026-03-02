<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->withForeignKeyChecksDisabled(function (): void {
            $this->renameTableIfNeeded('page', 'pages');
            $this->renameTableIfNeeded('page_type', 'page_types');

            $this->renameTableIfNeeded('template', 'templates');
            $this->renameTableIfNeeded('template_type', 'template_types');
        });
    }

    public function down(): void
    {
        $this->withForeignKeyChecksDisabled(function (): void {
            $this->renameTableIfNeeded('pages', 'page');
            $this->renameTableIfNeeded('page_types', 'page_type');

            $this->renameTableIfNeeded('templates', 'template');
            $this->renameTableIfNeeded('template_types', 'template_type');
        });
    }

    private function renameTableIfNeeded(string $from, string $to): void
    {
        if (! Schema::hasTable($from)) {
            return;
        }

        if (Schema::hasTable($to)) {
            return;
        }

        Schema::rename($from, $to);
    }

    private function withForeignKeyChecksDisabled(callable $callback): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            $callback();
        } finally {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }
};
