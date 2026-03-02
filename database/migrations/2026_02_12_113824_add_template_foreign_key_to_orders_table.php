<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'template_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->foreignId('template_id')
                    ->nullable()
                    ->after('school_id')
                    ->index();
            });
        }

        if (! Schema::hasTable('template')) {
            return;
        }

        // Aggiungo FK solo se non esiste già (best-effort).
        if ($this->foreignKeyExists('orders', 'fk_orders_template_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('template_id', 'fk_orders_template_id')
                ->references('id')
                ->on('template')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (! $this->foreignKeyExists('orders', 'fk_orders_template_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign('fk_orders_template_id');
        });

        // Non droppo la colonna: potrebbe essere ancora usata dal progetto.
    }

    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $dbName = DB::getDatabaseName();

        return DB::table('information_schema.table_constraints')
            ->where('table_schema', $dbName)
            ->where('table_name', $table)
            ->where('constraint_name', $foreignKeyName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
