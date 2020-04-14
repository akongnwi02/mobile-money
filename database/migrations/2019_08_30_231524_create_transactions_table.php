<?php

use App\Services\Constants;
use App\Services\Constants\TransactionConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('is_callback_sent')->default(false);
            $table->string('callback_url')->default(false);
            $table->smallInteger('callback_attempts')->nullable();
            $table->smallInteger('purchase_attempts')->nullable();
            $table->string('destination');
            $table->float('amount');
            $table->string('service_code');
            $table->string('internal_id');
            $table->string('external_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('name')->nullable();
            $table->string('asset')->nullable();
            $table->text('error')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', [
                TransactionConstants::CREATED,
                TransactionConstants::PROCESSING,
                TransactionConstants::ERRORED,
                TransactionConstants::SUCCESS,
                TransactionConstants::FAILED
            ]);
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
        Schema::dropIfExists('transactions');
    }
}
