<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->longText('ssh_config')->nullable()->after('ssh_private_key_path');
            $table->string('ssh_config_path')->nullable()->after('ssh_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropColumn([
                'ssh_config',
                'ssh_config_path',
            ]);
        });
    }
};
