<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // utente registrato (futuro)

            $table->string('email', 255);
            $table->text('subject')->nullable();
            $table->longText('message')->nullable();

            $table->string('access_token', 128)->unique();

            $table->enum('role', [
                'admin|admin',
                'internal|redattore',
                'internal|grafico',
                'external|referente',
                'external|collaboratore',
            ])->index();

            $table->enum('status', [
                'read',
                'invited',
                'received',
                'expired',
                'registered',
                'active',
                'deleted',
            ])->default('ready')->index();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('registered_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};