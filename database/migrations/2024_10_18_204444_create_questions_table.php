<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('question'); // Наименование вопроса
            $table->string('option1'); // Вариант ответа 1
            $table->string('option2'); // Вариант ответа 2
            $table->string('option3'); // Вариант ответа 3
            $table->unsignedTinyInteger('correct_answer'); // Правильный ответ, номер варианта
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('questions');
    }
};
