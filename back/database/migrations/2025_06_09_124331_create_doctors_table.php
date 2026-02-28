<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->enum('specialization', ['C', 'G', 'E', 'O'])->nullable(); /// g for general, others are some specializations
            $table->integer('experience_years')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('user_id')->index()->unique()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('doctors');
    }
};
