<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'success', 'cancelled', 'failed'])->default('pending');
            $table->string('receipt_url')->nullable();
            $table->bigInteger('paddle_subscription_id')->nullable();
            $table->dateTime('payment_date')->nullable()->after('start_date');
            $table->enum('action', ['start', 'cancel'])->nullable([]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('receipt_url');
            $table->dropColumn('paddle_subscription_id');
            $table->dropColumn('payment_date');
            $table->dropColumn('action');
        });
    }
}
