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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('cnpj')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->boolean('paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('bill_path')->nullable();
            $table->string('invoice')->nullable();
            // installment can be a letter or a number, letter from A to J and number from 1 to 10
            $table->string('installment')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
