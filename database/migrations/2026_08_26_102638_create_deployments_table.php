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
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('type');
            $table->string('path');
            $table->string('repo_url');
            $table->string('branch');

            $table->string('pm2_name')->nullable();
            $table->string('pm2_instances')->nullable();
            
            $table->string('status')->default('unknown');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            
            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
