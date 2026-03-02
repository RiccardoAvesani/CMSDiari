<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Colonne previste da documentazione (aggiunta best-effort)
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'external_id')) {
                $table->string('external_id', 64)->nullable()->after('id');
            }

            if (! Schema::hasColumn('orders', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('school_id');
            }

            if (! Schema::hasColumn('orders', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('template_id');
            }

            if (! Schema::hasColumn('orders', 'deadline_collection')) {
                $table->dateTime('deadline_collection')->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('orders', 'deadline_annotation')) {
                $table->dateTime('deadline_annotation')->nullable()->after('deadline_collection');
            }

            // Colonna errata del vecchio schema
            if (Schema::hasColumn('orders', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        // 2) Normalizzazione dati status (active/deleted -> new/deleted)
        if (Schema::hasColumn('orders', 'status')) {
            DB::table('orders')->whereNull('status')->update(['status' => 'new']);
            DB::table('orders')->where('status', 'active')->update(['status' => 'new']);

            DB::table('orders')
                ->whereNotIn('status', ['new', 'collection', 'draft', 'annotation', 'approved', 'production', 'completed', 'deleted'])
                ->update(['status' => 'new']);
        }

        // 3) Prima tolgo le FK (così posso droppare l'unique senza errore 1553)
        Schema::table('orders', function (Blueprint $table): void {
            if ($this->hasForeignKey('orders', 'orders_campaign_id_foreign')) {
                $table->dropForeign('orders_campaign_id_foreign');
            }

            if ($this->hasForeignKey('orders', 'orders_school_id_foreign')) {
                $table->dropForeign('orders_school_id_foreign');
            }
        });

        // 4) Ora posso togliere l'unique campaign_id + school_id (non previsto da doc)
        Schema::table('orders', function (Blueprint $table): void {
            if ($this->hasIndex('orders', 'orders_campaign_id_school_id_unique')) {
                $table->dropUnique('orders_campaign_id_school_id_unique');
            }
        });

        // 5) Imposto ENUM status come da documentazione (MySQL/MariaDB)
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE `orders` MODIFY `status` " .
                    "ENUM('new','collection','draft','annotation','approved','production','completed','deleted') " .
                    "NOT NULL DEFAULT 'new'"
            );
        }

        // 6) Indici previsti (idempotenti: li creo solo se non esistono)
        Schema::table('orders', function (Blueprint $table): void {
            if (! $this->hasIndex('orders', 'orders_external_id_unique')) {
                $table->unique('external_id');
            }

            if (! $this->hasIndex('orders', 'orders_campaign_id_index')) {
                $table->index('campaign_id');
            }

            if (! $this->hasIndex('orders', 'orders_school_id_index')) {
                $table->index('school_id');
            }

            if (! $this->hasIndex('orders', 'orders_template_id_index')) {
                $table->index('template_id');
            }

            if (! $this->hasIndex('orders', 'orders_status_index')) {
                $table->index('status');
            }

            if (! $this->hasIndex('orders', 'orders_deadline_collection_index')) {
                $table->index('deadline_collection');
            }

            if (! $this->hasIndex('orders', 'orders_deadline_annotation_index')) {
                $table->index('deadline_annotation');
            }
        });

        // 7) Ricreo le FK con RESTRICT (come documentazione)
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        // Down best-effort: non ripristino l'unique (campaign_id, school_id) e non torno a cascade.
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `orders` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'new'");
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $dbName = DB::getDatabaseName();

            return DB::table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
        }

        return false;
    }

    private function hasForeignKey(string $table, string $foreignKeyName): bool
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $dbName = DB::getDatabaseName();

            return DB::table('information_schema.table_constraints')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('constraint_name', $foreignKeyName)
                ->where('constraint_type', 'FOREIGN KEY')
                ->exists();
        }

        return false;
    }
};
