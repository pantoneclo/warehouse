# Scheduled Stock Update Command

## Overview

The `ScheduledStockUpdateCommand` is a Laravel console command that automatically runs the `manageStockForCodeAndWarehouse` function from `StockHelper` at 3:00 AM Bangladesh Time (BDT) daily.

## Features

- **Automatic Scheduling**: Runs daily at 3:00 AM BDT
- **Comprehensive Processing**: Updates stock for all products and combos across all warehouses
- **Flexible Options**: Can be run manually with specific parameters
- **Progress Tracking**: Shows progress bars and detailed logging
- **Error Handling**: Comprehensive error logging and recovery
- **Memory Management**: Optimized for large datasets

## Scheduled Execution

The command is automatically scheduled in `app/Console/Kernel.php`:

```php
$schedule->command('stock:scheduled-update')
         ->dailyAt('03:00')
         ->timezone('Asia/Dhaka')
         ->withoutOverlapping()
         ->runInBackground();
```

### Schedule Features:
- **Time**: 3:00 AM Bangladesh Time (UTC+6)
- **Frequency**: Daily
- **Overlap Protection**: Prevents multiple instances running simultaneously
- **Background**: Runs in background to avoid blocking other processes

## Manual Execution

You can also run the command manually with various options:

### Run for all warehouses (same as scheduled):
```bash
php artisan stock:scheduled-update
```

### Run for specific warehouse:
```bash
php artisan stock:scheduled-update --warehouse-id=1
```

### Run for specific product in specific warehouse:
```bash
php artisan stock:scheduled-update --warehouse-id=1 --product-code=PROD123
```

### Run for specific combo in specific warehouse:
```bash
php artisan stock:scheduled-update --warehouse-id=1 --combo-code=COMBO123
```

## What It Does

1. **Product Processing**: 
   - Finds all products with stock in each warehouse
   - Calls `StockHelper::manageStockForCodeAndWarehouse()` for each product
   - Updates quantities in both MySQL and PostgreSQL databases

2. **Combo Processing**:
   - Finds all combo products in each warehouse
   - Calculates minimum quantities from component products
   - Updates combo quantities accordingly

3. **Database Updates**:
   - Updates `manage_stocks` table (MySQL)
   - Updates `product_meta` table (PostgreSQL)
   - Handles warehouse-specific logic (warehouse 3 special handling)

## Logging

The command provides comprehensive logging:

- **Console Output**: Real-time progress and status updates
- **Laravel Logs**: Detailed execution logs in `storage/logs/laravel.log`
- **Execution Metrics**: Start time, end time, duration, processed items

## Error Handling

- **Exception Catching**: All errors are caught and logged
- **Graceful Failure**: Command returns proper exit codes
- **Detailed Error Logs**: Full stack traces for debugging
- **Recovery**: Can be safely re-run if it fails

## Performance Considerations

- **Memory Limit**: Set to 2GB for large datasets
- **Execution Time**: Unlimited execution time
- **Progress Tracking**: Visual progress bars for long operations
- **Background Execution**: Scheduled to run in background

## Monitoring

To monitor the scheduled execution:

1. **Check Laravel Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep "Scheduled stock update"
   ```

2. **Check Cron Logs** (if using cron):
   ```bash
   grep "stock:scheduled-update" /var/log/cron
   ```

3. **Manual Test Run**:
   ```bash
   php artisan stock:scheduled-update --warehouse-id=1
   ```

## Setup Requirements

1. **Cron Job**: Ensure Laravel's scheduler is running:
   ```bash
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Database Connections**: Both MySQL and PostgreSQL connections must be configured

3. **Timezone**: Server should be configured for proper timezone handling

## Troubleshooting

### Command Not Running
- Check if Laravel scheduler is set up in cron
- Verify timezone configuration
- Check for overlapping executions

### Memory Issues
- Increase PHP memory limit if needed
- Monitor server resources during execution

### Database Issues
- Verify both MySQL and PostgreSQL connections
- Check database permissions
- Monitor database locks during execution

## Related Files

- **Command**: `app/Console/Commands/ScheduledStockUpdateCommand.php`
- **Helper**: `app/Helpers/StockHelper.php`
- **Scheduler**: `app/Console/Kernel.php`
- **Models**: `app/Models/Product.php`, `app/Models/ComboProduct.php`, `app/Models/ManageStock.php`
