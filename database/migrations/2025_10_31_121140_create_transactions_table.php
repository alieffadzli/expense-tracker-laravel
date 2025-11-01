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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['income', 'expense'])->default('income');
            $table->integer('amount');
            $table->string('notes')->unique();
            $table->json('edit_history')->nullable();


            $table->unsignedBigInteger('initial_balance_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');

            $table->foreign('initial_balance_id')->references('id')->on('initial_balances');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('branch_id')->references('id')->on('branches');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
