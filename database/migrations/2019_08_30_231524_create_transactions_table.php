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
            $table->boolean('to_be_verified')->default(false);
            $table->string('callback_url')->default(false);
            $table->smallInteger('callback_attempts')->default(0);
            $table->smallInteger('purchase_attempts')->default(0);
            $table->smallInteger('verification_attempts')->default(0);
            $table->string('destination');
            $table->float('amount');
            $table->string('service_code');
            $table->string('internal_id');
            $table->string('external_id')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('name')->nullable();
            $table->string('asset')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error')->nullable();
            $table->text('reference')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', [
                TransactionConstants::CREATED,
                TransactionConstants::PROCESSING,
                TransactionConstants::ERRORED,
                TransactionConstants::SUCCESS,
                TransactionConstants::FAILED,
                TransactionConstants::VERIFICATION,
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
