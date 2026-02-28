<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Street;
use App\Models\User;

return new class extends Migration
{
    public function up()
    {
        Schema::create('patient_data', function (Blueprint $table) {
            $table->id();
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('blood_type');
            $table->foreignId('street_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->index()->unique()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('patient_data');
    }
};
