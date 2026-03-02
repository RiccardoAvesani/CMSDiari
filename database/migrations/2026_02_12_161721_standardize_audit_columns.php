<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'schools',
            'locations',
            'contacts',
            'campaigns',
            'orders',
            'invitations',
            'settings',
            'page',
            'pagetype',
            'template',
            'templatetype',
            'templatetypepagetype',
            'exports',
            'imports',
            'failed_import_rows',
            'notifications',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $this->standardizeAuditColumns($table);
        }
    }

    public function down(): void
    {
        // Down “best-effort”: utile in dev, non garantito in produzione.
        $tables = [
            'users',
            'schools',
            'locations',
            'contacts',
            'campaigns',
            'orders',
            'invitations',
            'settings',
            'page',
            'pagetype',
            'template',
            'templatetype',
            'templatetypepagetype',
            'exports',
            'imports',
            'failed_import_rows',
            'notifications',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $this->reverseStandardizeAuditColumns($table);
        }
    }

    private function standardizeAuditColumns(string $table): void
    {
        // Date columns
        $this->renameColumnIfNeeded($table, 'createdat', 'created_at');
        $this->renameColumnIfNeeded($table, 'modifiedat', 'updated_at');
        $this->renameColumnIfNeeded($table, 'updatedat', 'updated_at');

        // User FK columns
        $this->renameForeignIdToUsersIfNeeded($table, 'createdby', 'created_by');
        $this->renameForeignIdToUsersIfNeeded($table, 'modifiedby', 'updated_by');
        $this->renameForeignIdToUsersIfNeeded($table, 'updatedby', 'updated_by');

        // Ensure timestamps exist (nullable, non invasivo)
        $this->ensureTimestampColumn($table, 'created_at');
        $this->ensureTimestampColumn($table, 'updated_at');

        // Ensure blameable columns exist (nullable)
        $this->ensureUserForeignIdColumn($table, 'created_by');
        $this->ensureUserForeignIdColumn($table, 'updated_by');
    }

    private function reverseStandardizeAuditColumns(string $table): void
    {
        $this->renameForeignIdToUsersIfNeeded($table, 'created_by', 'createdby');
        $this->renameForeignIdToUsersIfNeeded($table, 'updated_by', 'updatedby');

        $this->renameColumnIfNeeded($table, 'created_at', 'createdat');
        $this->renameColumnIfNeeded($table, 'updated_at', 'updatedat');
    }

    private function renameColumnIfNeeded(string $table, string $from, string $to): void
    {
        if (! Schema::hasColumn($table, $from)) {
            return;
        }

        if (Schema::hasColumn($table, $to)) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` RENAME COLUMN `%s` TO `%s`',
                $table,
                $from,
                $to,
            ));

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($from, $to): void {
            $blueprint->renameColumn($from, $to);
        });
    }

    private function renameForeignIdToUsersIfNeeded(string $table, string $from, string $to): void
    {
        if (! Schema::hasColumn($table, $from)) {
            return;
        }

        if (Schema::hasColumn($table, $to)) {
            return;
        }

        $this->dropForeignKeyIfExists($table, $from);

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` RENAME COLUMN `%s` TO `%s`',
                $table,
                $from,
                $to,
            ));
        } else {
            Schema::table($table, function (Blueprint $blueprint) use ($from, $to): void {
                $blueprint->renameColumn($from, $to);
            });
        }

        if (Schema::hasColumn($table, $to) && Schema::hasTable('users')) {
            $this->addForeignKeyToUsersIfMissing($table, $to);
        }
    }

    private function ensureTimestampColumn(string $table, string $column): void
    {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->timestamp($column)->nullable();
        });
    }

    private function ensureUserForeignIdColumn(string $table, string $column): void
    {
        if (Schema::hasColumn($table, $column)) {
            $this->addForeignKeyToUsersIfMissing($table, $column);

            return;
        }

        if (! Schema::hasTable('users')) {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                $blueprint->unsignedBigInteger($column)->nullable();
                $blueprint->index($column);
            });

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
        });
    }

    private function addForeignKeyToUsersIfMissing(string $table, string $column): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->getForeignKeyName($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->foreign($column)->references('id')->on('users')->nullOnDelete();
        });
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $fk = $this->getForeignKeyName($table, $column);

        if (! $fk) {
            return;
        }

        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
            $table,
            $fk,
        ));
    }

    private function getForeignKeyName(string $table, string $column): ?string
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return null;
        }

        $rows = DB::select(
            'SELECT CONSTRAINT_NAME as name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$table, $column],
        );

        return $rows[0]->name ?? null;
    }
};
