<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_balances', function (Blueprint $table) {
            $table->id();
            $table->integer('opening_balance')->nullable()->default(0);
            $table->integer('closing_balance');

            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('branch_id');

            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->foreign('branch_id')->references('id')->on('branches');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_balances');
    }
};
