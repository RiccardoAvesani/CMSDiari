<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addFlagsToTemplateType();
        $this->addFlagsToTemplate();

        $this->normalizeExistingRows();
    }

    public function down(): void
    {
        // Scelta intenzionale: non rimuovo colonne già in uso.
    }

    private function addFlagsToTemplateType(): void
    {
        if (! Schema::hasTable('template_type')) {
            return;
        }

        Schema::table('template_type', function (Blueprint $table): void {
            if (! Schema::hasColumn('template_type', 'is_custom_finale')) {
                $table->boolean('is_custom_finale')->default(false)->index();
            }

            if (! Schema::hasColumn('template_type', 'is_giustificazioni')) {
                $table->boolean('is_giustificazioni')->default(false);
            }

            if (! Schema::hasColumn('template_type', 'is_permessi')) {
                $table->boolean('is_permessi')->default(false);
            }

            if (! Schema::hasColumn('template_type', 'is_visite')) {
                $table->boolean('is_visite')->default(false);
            }
        });
    }

    private function addFlagsToTemplate(): void
    {
        if (! Schema::hasTable('template')) {
            return;
        }

        Schema::table('template', function (Blueprint $table): void {
            if (! Schema::hasColumn('template', 'is_custom_finale')) {
                $table->boolean('is_custom_finale')->default(false)->index();
            }

            if (! Schema::hasColumn('template', 'is_giustificazioni')) {
                $table->boolean('is_giustificazioni')->default(false);
            }

            if (! Schema::hasColumn('template', 'is_permessi')) {
                $table->boolean('is_permessi')->default(false);
            }

            if (! Schema::hasColumn('template', 'is_visite')) {
                $table->boolean('is_visite')->default(false);
            }
        });
    }

    private function normalizeExistingRows(): void
    {
        if (Schema::hasTable('template_type') && Schema::hasColumn('template_type', 'is_custom_finale')) {
            DB::table('template_type')
                ->where(function ($q): void {
                    $q->whereNull('is_custom_finale')->orWhere('is_custom_finale', 0);
                })
                ->update([
                    'is_custom_finale' => 0,
                    'is_giustificazioni' => 0,
                    'is_permessi' => 0,
                    'is_visite' => 0,
                ]);

            DB::table('template_type')
                ->whereNull('is_giustificazioni')
                ->update(['is_giustificazioni' => 0]);

            DB::table('template_type')
                ->whereNull('is_permessi')
                ->update(['is_permessi' => 0]);

            DB::table('template_type')
                ->whereNull('is_visite')
                ->update(['is_visite' => 0]);
        }

        if (Schema::hasTable('template') && Schema::hasColumn('template', 'is_custom_finale')) {
            DB::table('template')
                ->where(function ($q): void {
                    $q->whereNull('is_custom_finale')->orWhere('is_custom_finale', 0);
                })
                ->update([
                    'is_custom_finale' => 0,
                    'is_giustificazioni' => 0,
                    'is_permessi' => 0,
                    'is_visite' => 0,
                ]);

            DB::table('template')
                ->whereNull('is_giustificazioni')
                ->update(['is_giustificazioni' => 0]);

            DB::table('template')
                ->whereNull('is_permessi')
                ->update(['is_permessi' => 0]);

            DB::table('template')
                ->whereNull('is_visite')
                ->update(['is_visite' => 0]);
        }
    }
};
