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
        Schema::create('service_experts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expert_id');
            $table->foreign('expert_id')->references('id')->on('experts')->onDelete('cascade');
            $table->unsignedBigInteger('service_id');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_experts');
    }
};
