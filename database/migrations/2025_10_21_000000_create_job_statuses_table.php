<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('job_name');
            $table->string('queue_name')->nullable();
            $table->enum('status', ['pending', 'running', 'done', 'failed'])->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index(['status', 'created_at']);
            $table->index('job_name');
            $table->index('queue_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_statuses');
    }
};
