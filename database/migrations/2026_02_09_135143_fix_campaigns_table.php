<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Dati: rimappo status pre-esistenti (active/deleted) verso i valori ufficiali.
        if (Schema::hasTable('campaigns') && Schema::hasColumn('campaigns', 'status')) {
            DB::table('campaigns')->where('status', 'active')->update(['status' => 'planned']);
            DB::table('campaigns')->whereNull('status')->update(['status' => 'planned']);

            DB::table('campaigns')
                ->whereNotIn('status', ['planned', 'started', 'completed', 'deleted'])
                ->update(['status' => 'planned']);
        }

        // 2) Schema: tolgo UNIQUE su year e converto tipi/enum secondo documentazione.
        Schema::table('campaigns', function (Blueprint $table): void {
            // Il nome indice è quello standard Laravel per unique('year')
            if ($this->hasIndex('campaigns', 'campaigns_year_unique')) {
                $table->dropUnique('campaigns_year_unique');
            }
        });

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `campaigns` MODIFY `year` VARCHAR(4) NOT NULL");
            DB::statement("ALTER TABLE `campaigns` MODIFY `description` TEXT NULL");
            DB::statement("ALTER TABLE `campaigns` MODIFY `status` ENUM('planned','started','completed','deleted') NOT NULL DEFAULT 'planned'");
        } else {
            // Best-effort: su driver diversi (es. sqlite) evito operazioni non supportate in modo affidabile.
            // Qui lasciamo i tipi come sono; l'ambiente target è MySQL/MariaDB.
        }

        // 3) Indici: in doc year è indicizzato, non unique.
        Schema::table('campaigns', function (Blueprint $table): void {
            if (! $this->hasIndex('campaigns', 'campaigns_year_index')) {
                $table->index('year');
            }

            // status era già index nella migration errata; se manca lo ripristino.
            if (! $this->hasIndex('campaigns', 'campaigns_status_index')) {
                $table->index('status');
            }
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // Revert ai tipi originari “errati” (utile solo se devi tornare indietro).
            DB::statement("ALTER TABLE `campaigns` MODIFY `year` INT UNSIGNED NOT NULL");
            DB::statement("ALTER TABLE `campaigns` MODIFY `description` VARCHAR(255) NULL");
            DB::statement("ALTER TABLE `campaigns` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'active'");
        }

        Schema::table('campaigns', function (Blueprint $table): void {
            if ($this->hasIndex('campaigns', 'campaigns_year_index')) {
                $table->dropIndex('campaigns_year_index');
            }

            if (! $this->hasIndex('campaigns', 'campaigns_year_unique')) {
                $table->unique('year');
            }
        });

        if (Schema::hasTable('campaigns') && Schema::hasColumn('campaigns', 'status')) {
            DB::table('campaigns')->where('status', 'planned')->update(['status' => 'active']);
            DB::table('campaigns')->whereNotIn('status', ['active', 'deleted'])->update(['status' => 'active']);
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $dbName = DB::getDatabaseName();

            $count = DB::table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->count();

            return $count > 0;
        }

        return false;
    }
};
