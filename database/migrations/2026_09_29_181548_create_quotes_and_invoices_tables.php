<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quotes')) {
            Schema::create('quotes', function (Blueprint $table) {
                $table->id();
                $table->string('quote_number')->unique();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->string('title')->nullable();
                $table->string('status')->default('draft'); // draft, sent, approved, declined, invoiced
                $table->date('issue_date')->nullable();
                $table->date('expiration_date')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->json('items')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('quote_id')->nullable();
                $table->string('title')->nullable();
                $table->string('status')->default('draft'); // draft, sent, paid, overdue, cancelled
                $table->date('issue_date')->nullable();
                $table->date('due_date')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->json('items')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotes');
    }
};
