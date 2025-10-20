# Sales Return, Currency, and Stock Update Deployment Guide

## Overview
This deployment adds comprehensive sales return management, currency-based calculations, and enhanced stock update functionality to the WHM (Warehouse Management) system.

## Features Added

### 1. Enhanced Sales Return Management
- **Two-status workflow**: Pending → Approved
- **Stock integration**: Stock updates only when approved
- **Webhook support**: External system integration for return status changes
- **Approve button**: Manual approval functionality in the UI

### 2. Currency-Based Sales Calculations
- **Country-currency mapping**: Automatic currency selection based on country
- **Conversion rate handling**: Proper currency conversion for all monetary fields
- **Original amount tracking**: Store both converted and original amounts

### 3. New Webhook Endpoint
- **Endpoint**: `/webhook/order/return/status-changed`
- **Functionality**: Accept return status changes from external systems
- **Stock updates**: Automatic PostgreSQL stock updates for approved returns

## Database Changes

### Migration Files Applied
1. `2025_10_06_160000_enhance_sales_return_for_webhook_support.php`
2. `2025_10_06_160100_enhance_sales_for_currency_support.php`
3. `2025_10_06_160200_add_currency_to_countries.php`

### Tables Modified

#### `sales_return` table
**New columns added:**
- `order_number` (string, nullable, indexed) - External order reference
- `return_status` (string, default 'Pending', indexed) - Return approval status
- `approved_at` (timestamp, nullable) - When return was approved
- `approved_by` (foreign key to users) - Who approved the return
- `currency` (string, 3 chars) - Currency code used
- `conversion_rate` (decimal 10,4, default 1.0000) - Currency conversion rate
- `grand_total_original` (decimal 15,4) - Amount in original currency
- `webhook_data` (text, nullable) - Raw webhook payload data
- `stock_updated` (boolean, default false, indexed) - Stock update flag
- `stock_updated_at` (timestamp, nullable) - When stock was updated

#### `sales` table
**New columns added:**
- `country_id` (foreign key to countries, nullable) - Country relationship
- `currency_id` (foreign key to currencies, nullable) - Currency relationship
- `grand_total_original` (decimal 15,4, nullable) - Original amount before conversion

**Enhanced columns:**
- `currency` (string, 3 chars, nullable) - Enhanced currency code field
- `conversion_rate` (decimal 10,4, default 1.0000) - Enhanced precision

#### `countries` table
**New columns added:**
- `currency_id` (foreign key to currencies, nullable) - Default currency for country
- `currency_code` (string, 3 chars, nullable) - Currency code shortcut

## Code Changes

### Models Enhanced

#### `SaleReturn` Model
**New constants:**
```php
const RETURN_STATUS_PENDING = 'Pending';
const RETURN_STATUS_APPROVED = 'Approved';
const RETURN_STATUS_REJECTED = 'Rejected';
```

**New methods:**
- `isPending()` - Check if return is pending
- `isApproved()` - Check if return is approved
- `approve($userId)` - Approve return and set approval details
- `isStockUpdated()` - Check if stock has been updated
- `markStockUpdated()` - Mark stock as updated
- `approvedBy()` - Relationship to user who approved

#### `Sale` Model
**New relationships:**
- `country()` - Belongs to Country
- `currencyRelation()` - Belongs to Currency

#### `Country` Model
**New relationship:**
- `currency()` - Belongs to Currency

### Controllers Enhanced

#### `StockManagementAPIController`
**New method:**
- `webHookOrderReturnStatusChanged(Request $request)` - Handle return status webhooks
- `updateStockForReturn(SaleReturn $saleReturn)` - Update stock for approved returns

#### `SaleReturnAPIController`
**New method:**
- `approve($id)` - Approve return and update stock

#### `CurrencyAPIController`
**New method:**
- `getCurrencyByCountry($countryId)` - Get currency by country ID

### Routes Added

#### API Routes
```php
// Sales return approval
Route::post('sales-return/{id}/approve', [SaleReturnAPIController::class, 'approve']);

// Currency by country
Route::get('currencies/by-country/{countryId}', [CurrencyAPIController::class, 'getCurrencyByCountry']);

// Webhook for return status changes
Route::post('/webhook/order/return/status-changed', [StockManagementAPIController::class, 'webHookOrderReturnStatusChanged']);
```

## Deployment Steps

### 1. Pre-Deployment Backup
```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup codebase
cp -r /path/to/project /path/to/backup/project_$(date +%Y%m%d_%H%M%S)
```

### 2. Deploy Code Changes
```bash
# Pull latest code
git pull origin main

# Install/update dependencies if needed
composer install --no-dev --optimize-autoloader
```

### 3. Run Database Migrations
```bash
# Run the specific migrations
php artisan migrate --path=database/migrations/2025_10_06_160000_enhance_sales_return_for_webhook_support.php
php artisan migrate --path=database/migrations/2025_10_06_160100_enhance_sales_for_currency_support.php
php artisan migrate --path=database/migrations/2025_10_06_160200_add_currency_to_countries.php
```

### 4. Clear Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 5. Verify Deployment
```bash
# Check migration status
php artisan migrate:status

# Test webhook endpoint
curl -X POST http://your-domain.com/api/webhook/order/return/status-changed \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: your-api-key" \
  -d '{
    "status": "Pending",
    "order_number": "TEST123",
    "products": [
      {"code": "PR_001", "quantity": 1}
    ]
  }'
```

## API Documentation

### New Webhook Endpoint

#### POST `/webhook/order/return/status-changed`
**Headers:**
- `Content-Type: application/json`
- `X-API-KEY: {your-api-key}`

**Request Body:**
```json
{
  "status": "Pending|Approved|Rejected",
  "order_number": "ORDER123",
  "products": [
    {
      "code": "PRODUCT_CODE",
      "quantity": 2
    }
  ]
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Sales return status updated successfully",
  "data": {
    "return_id": 123,
    "order_number": "ORDER123",
    "return_status": "Approved",
    "stock_updated": true,
    "total_amount": 150.00
  }
}
```

### Sales Return Approval

#### POST `/api/sales-return/{id}/approve`
**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Response:**
```json
{
  "success": true,
  "message": "Sales return approved and stock updated successfully",
  "data": {
    "id": 123,
    "return_status": "Approved",
    "approved_at": "2025-10-06T15:30:00Z",
    "approved_by": 1,
    "stock_updated": true
  }
}
```

### Currency by Country

#### GET `/api/currencies/by-country/{countryId}`
**Response:**
```json
{
  "success": true,
  "message": "Currency retrieved successfully",
  "data": {
    "currency": {
      "id": 1,
      "name": "US Dollar",
      "code": "USD",
      "symbol": "$",
      "conversion_rate": 1.0000
    },
    "country": {
      "id": 1,
      "name": "United States",
      "short_code": "US"
    }
  }
}
```

## Rollback Procedures

### 1. Database Rollback
```bash
# Rollback migrations in reverse order
php artisan migrate:rollback --path=database/migrations/2025_10_06_160200_add_currency_to_countries.php
php artisan migrate:rollback --path=database/migrations/2025_10_06_160100_enhance_sales_for_currency_support.php
php artisan migrate:rollback --path=database/migrations/2025_10_06_160000_enhance_sales_return_for_webhook_support.php
```

### 2. Code Rollback
```bash
# Restore from backup
git checkout previous_commit_hash

# Or restore from file backup
cp -r /path/to/backup/project_TIMESTAMP/* /path/to/project/
```

### 3. Database Restore (if needed)
```bash
# Restore from backup
mysql -u username -p database_name < backup_TIMESTAMP.sql
```

## Testing Checklist

### Functional Tests
- [ ] Sales return creation with Pending status
- [ ] Sales return approval functionality
- [ ] Stock updates after approval
- [ ] Webhook endpoint accepts valid requests
- [ ] Currency selection by country
- [ ] Conversion rate calculations
- [ ] PostgreSQL stock synchronization

### Integration Tests
- [ ] Webhook with external systems
- [ ] Frontend approve button functionality
- [ ] Currency dropdown in sales forms
- [ ] Stock quantity verification

### Performance Tests
- [ ] Large return processing
- [ ] Bulk stock updates
- [ ] Database query performance

## Monitoring

### Key Metrics to Monitor
- Sales return processing time
- Stock update accuracy
- Webhook response times
- Database performance
- Error rates in logs

### Log Files to Watch
- `storage/logs/laravel.log` - Application errors
- Database slow query logs
- Web server access logs

## Support Information

### Key Files Modified
- `app/Models/SaleReturn.php`
- `app/Models/Sale.php`
- `app/Models/Country.php`
- `app/Http/Controllers/API/StockManagementAPIController.php`
- `app/Http/Controllers/API/SaleReturnAPIController.php`
- `app/Http/Controllers/API/CurrencyAPIController.php`
- `routes/api.php`

### Backup Files Created
- `app/Http/Controllers/API/StockManagementAPIController_backup_TIMESTAMP.php`

### Contact Information
For deployment issues or questions, contact the development team with:
- Error logs from `storage/logs/laravel.log`
- Database migration status: `php artisan migrate:status`
- System environment details

---

**Deployment Date:** 2025-10-06  
**Version:** 1.0.0  
**Deployed By:** Development Team
