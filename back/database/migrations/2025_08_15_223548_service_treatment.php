<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_treatment', function(Blueprint $table) {
            $table->id();
            $table->tinyInteger('order');
            $table->tinyInteger('stage_number')->default(1);
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('treatment_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_treatment');
    }
};
