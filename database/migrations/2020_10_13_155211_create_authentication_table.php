<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthenticationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authentications', function (Blueprint $table) {
            $table->increments('id');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->bigInteger('expires_in')->nullable();
            $table->string('service_code');
            $table->string('type')->nullable();
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
        Schema::dropIfExists('authentication');
    }
}
