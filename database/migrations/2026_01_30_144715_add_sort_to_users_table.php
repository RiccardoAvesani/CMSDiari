<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('sort')->default(0)->after('id')->index();
        });

        // Popola un ordine iniziale stabile (1..n)
        $ids = DB::table('users')->orderBy('sort')->pluck('id');
        $pos = 1;

        foreach ($ids as $id) {
            DB::table('users')->where('id', $id)->update(['sort' => $pos++]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sort');
        });
    }
};
