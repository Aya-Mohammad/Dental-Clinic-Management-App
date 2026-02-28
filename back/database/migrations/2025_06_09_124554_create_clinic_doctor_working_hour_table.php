<?php

use App\Models\ClinicDoctor;
use App\Models\WorkingHour;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clinic_doctor_working_hour', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_doctor_id')->constrained('clinic_doctor')->onDelete('cascade');
            $table->foreignId('working_hour_id')->constrained()->onDelete('cascade');
            $table->string('working_day');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clinic_doctor_working_hour');
    }
};
