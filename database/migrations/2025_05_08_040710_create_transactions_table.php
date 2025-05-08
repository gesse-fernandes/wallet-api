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
            $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payee_id')->constrained('users');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['deposit', 'transfer', 'reversal']);
            $table->enum('status', ['pending', 'completed', 'reversed'])->default('pending');
            $table->foreignId('reversed_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->json('metadata')->nullable();
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
