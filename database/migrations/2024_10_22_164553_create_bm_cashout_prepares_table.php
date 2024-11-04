<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bm_cashout_prepare', function (Blueprint $table) {
            $table->id('bm_cashout_id');
            $table->char('message_id', 50)->nullable()->unique();
            $table->char('transaction_id', 35)->nullable()->unique();
            $table->char('debtor_account', 34)->nullable();
            $table->char('currency', 3)->nullable();
            $table->decimal('transaction_amount', 12, 2)->nullable();
            $table->decimal('transaction_amount_aiwa_fees', 12, 2)->nullable()->comment('12.5% from transaction_amount');
            $table->decimal('final_transaction_amount', 12, 2)->nullable();
            $table->char('creditor_name', 70)->nullable();
            $table->char('creditor_account_number', 34)->nullable();
            $table->char('creditor_bank', 11)->nullable();
            $table->char('corporate_code', 50)->nullable();
            $table->char('category_code', 50)->nullable();
            $table->datetime('transaction_date_time')->nullable();
            $table->char('creditor_id', 35)->nullable();
            $table->text('signature')->nullable();
            $table->boolean('prepared_flag')->nullable();
            $table->text('response_code')->nullable();
            $table->text('response_description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bm_cashout_prepare');
    }
};
