<?php

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->time('duration');
            $table->text('description')->nullable();
            $table->boolean('notified_48h')->default(false);
            $table->boolean('notified_2h')->default(false);
            $table->enum('status', ['C', 'UC', 'X'])->default('UC'); //Copmleted , UpComing , canceled
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade');
            $table->foreignId('treatment_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
