<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->time('start');
            $table->time('end');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('working_hours');
    }
};
