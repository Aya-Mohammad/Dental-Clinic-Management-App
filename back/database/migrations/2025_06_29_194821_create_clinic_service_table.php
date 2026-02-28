<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clinic_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->double('price' , 8 , 2);
            $table->enum('accessibility', ['A', 'D']); /// All, Doctor
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clinic_service');
    }
};
