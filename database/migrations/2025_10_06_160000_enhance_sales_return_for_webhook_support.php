<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_return', function (Blueprint $table) {
            // Add fields for webhook support and enhanced return management
            $table->string('order_number')->nullable()->after('reference_code')->comment('Order number from external system');
            $table->string('return_status')->default('Pending')->after('status')->comment('Return status: Pending, Approved');
            $table->timestamp('approved_at')->nullable()->after('return_status')->comment('When the return was approved');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at')->comment('User who approved the return');
            $table->string('currency', 3)->nullable()->after('approved_by')->comment('Currency code (USD, EUR, BDT)');
            $table->decimal('conversion_rate', 10, 4)->default(1.0000)->after('currency')->comment('Currency conversion rate');
            $table->decimal('grand_total_original', 15, 4)->nullable()->after('conversion_rate')->comment('Grand total in original currency');
            $table->text('webhook_data')->nullable()->after('grand_total_original')->comment('Original webhook data for reference');
            $table->boolean('stock_updated')->default(false)->after('webhook_data')->comment('Whether stock has been updated in PostgreSQL');
            $table->timestamp('stock_updated_at')->nullable()->after('stock_updated')->comment('When stock was updated');
            
            // Add foreign key for approved_by
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Add indexes for better performance
            $table->index('order_number');
            $table->index('return_status');
            $table->index('stock_updated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_return', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['approved_by']);
            
            // Drop indexes
            $table->dropIndex(['order_number']);
            $table->dropIndex(['return_status']);
            $table->dropIndex(['stock_updated']);
            
            // Drop columns
            $table->dropColumn([
                'order_number',
                'return_status', 
                'approved_at',
                'approved_by',
                'currency',
                'conversion_rate',
                'grand_total_original',
                'webhook_data',
                'stock_updated',
                'stock_updated_at'
            ]);
        });
    }
};
