<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template', function (Blueprint $table): void {
            if (! Schema::hasColumn('template', 'description')) {
                $table->string('description')->nullable()->after('school_id');
            }

            if (! Schema::hasColumn('template', 'size')) {
                $table->string('size', 10)->nullable()->after('description');
            }

            if (! Schema::hasColumn('template', 'constraints')) {
                $table->json('constraints')->nullable()->after('size');
            }

            if (! Schema::hasColumn('template', 'max_pages')) {
                $table->unsignedInteger('max_pages')->nullable()->after('constraints');
            }
        });

        $templateTypeTable = null;

        if (Schema::hasTable('template_type')) {
            $templateTypeTable = 'template_type';
        } elseif (Schema::hasTable('template_types')) {
            $templateTypeTable = 'template_types';
        }

        if ($templateTypeTable) {
            DB::table('template')
                ->join($templateTypeTable, 'template.template_type_id', '=', $templateTypeTable . '.id')
                ->whereNull('template.description')
                ->update([
                    'template.description' => DB::raw($templateTypeTable . '.description'),
                    'template.size' => DB::raw($templateTypeTable . '.size'),
                    'template.constraints' => DB::raw($templateTypeTable . '.constraints'),
                    'template.max_pages' => DB::raw($templateTypeTable . '.max_pages'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('template', function (Blueprint $table): void {
            if (Schema::hasColumn('template', 'max_pages')) {
                $table->dropColumn('max_pages');
            }

            if (Schema::hasColumn('template', 'constraints')) {
                $table->dropColumn('constraints');
            }

            if (Schema::hasColumn('template', 'size')) {
                $table->dropColumn('size');
            }

            if (Schema::hasColumn('template', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
