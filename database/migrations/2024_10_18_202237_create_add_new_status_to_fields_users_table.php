<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change(); // Разрешаем быть пустым
            $table->string('email')->nullable()->change(); // Разрешаем быть пустым
            $table->string('password')->nullable()->change(); // Разрешаем быть пустым
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change(); // Возвращаем обратно обязательное поле
            $table->string('email')->nullable(false)->change(); // Возвращаем обратно обязательное поле
            $table->string('password')->nullable(false)->change(); // Возвращаем обратно обязательное поле
        });
    }
};
