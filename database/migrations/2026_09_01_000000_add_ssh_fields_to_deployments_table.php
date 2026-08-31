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
            $table->string('ssh_key_name')->nullable()->after('branch');
            $table->longText('ssh_private_key')->nullable()->after('ssh_key_name');
            $table->string('ssh_private_key_path')->nullable()->after('ssh_private_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropColumn([
                'ssh_key_name',
                'ssh_private_key',
                'ssh_private_key_path',
            ]);
        });
    }
};
