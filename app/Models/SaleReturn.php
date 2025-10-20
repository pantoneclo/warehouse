<?php

namespace App\Models;

use App\Models\Contracts\JsonResourceful;
use App\Traits\HasJsonResourcefulData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\SaleReturn
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property int $customer_id
 * @property int $warehouse_id
 * @property float|null $tax_rate
 * @property float|null $tax_amount
 * @property float|null $discount
 * @property float|null $shipping
 * @property float|null $grand_total
 * @property float|null $paid_amount
 * @property int|null $payment_type
 * @property string|null $note
 * @property string|null $reference_code
 * @property int|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SaleReturnItem[] $saleReturnItems
 * @property-read int|null $sale_return_items_count
 * @property-read \App\Models\Warehouse $warehouse
 *
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn query()
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereReferenceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereShipping($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereWarehouseId($value)
 * @mixin \Eloquent
 *
 * @property int|null $sale_id
 * @property-read \App\Models\Sale|null $sale
 *
 * @method static \Illuminate\Database\Eloquent\Builder|SaleReturn whereSaleId($value)
 */
class SaleReturn extends BaseModel implements HasMedia, JsonResourceful
{
    use HasFactory, InteractsWithMedia, HasJsonResourcefulData;

    protected $table = 'sales_return';

    public const JSON_API_TYPE = 'sales_return';

    public const SALE_RETURN_PDF = 'sale_return_pdf';

    // Return status constants
    const RETURN_STATUS_PENDING = 'Pending';
    const RETURN_STATUS_APPROVED = 'Approved';
    const RETURN_STATUS_PARTIALLY_APPROVED = 'Partially Approved';
    const RETURN_STATUS_REJECTED = 'Rejected';

    /**
     * @var string[]
     */
    protected $fillable = [
        'date',
        'customer_id',
        'warehouse_id',
        'tax_rate',
        'tax_amount',
        'discount',
        'shipping',
        'grand_total',
        'paid_amount',
        'payment_type',
        'note',
        'status',
        'reference_code',
        'sale_id',
        'order_number',
        'return_status',
        'approved_at',
        'approved_by',
        'currency',
        'conversion_rate',
        'grand_total_original',
        'webhook_data',
        'stock_updated',
        'stock_updated_at',
    ];

    /**
     * @var string[]
     */
    public static $rules = [
        'date' => 'date|required',
        'customer_id' => 'required|exists:customers,id',
        'warehouse_id' => 'required|exists:warehouses,id',
        'tax_rate' => 'nullable|numeric',
        'tax_amount' => 'nullable|numeric',
        'discount' => 'nullable|numeric',
        'shipping' => 'nullable|numeric',
        'grand_total' => 'nullable|numeric',
        'paid_amount' => 'numeric|nullable',
        'payment_type' => 'numeric|integer',
        'notes' => 'nullable',
        'status' => 'integer|required',
        'reference_code' => 'nullable',
    ];

    /**
     * @var string[]
     */
    public $casts = [
        'date' => 'date',
        'tax_rate' => 'double',
        'tax_amount' => 'double',
        'discount' => 'double',
        'shipping' => 'double',
        'grand_total' => 'double',
        'paid_amount' => 'double',
        'conversion_rate' => 'decimal:4',
        'grand_total_original' => 'decimal:4',
        'approved_at' => 'datetime',
        'stock_updated_at' => 'datetime',
        'stock_updated' => 'boolean',
        'webhook_data' => 'array',
    ];

    //tax type  const
    const EXCLUSIVE = 1;

    const INCLUSIVE = 2;

    // discount type const
    const PERCENTAGE = 1;

    const FIXED = 2;

    // payment type
    const CASH = 1;

    // Order status
    const RECEIVED = 1;

    const PENDING = 2;

    /**
     * @return array
     */
    public function prepareLinks(): array
    {
        return [
            'self' => route('sales-return.show', $this->id),
        ];
    }

    /**
     * @return array
     */
    public function prepareAttributes(): array
    {
        $fields = [
            'date' => $this->date,
            'sale_id' => $this->sale_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse->name,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'grand_total' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'payment_type' => $this->payment_type,
            'note' => $this->note,
            'status' => $this->status,
            'reference_code' => $this->reference_code,
            'sale_return_items' => $this->saleReturnItems,
            'created_at' => $this->created_at,
            // Include country and currency from related sale or direct fields
            'country' => $this->sale ? $this->sale->country : null,
            'currency' => $this->currency ?? ($this->sale ? $this->sale->currency : null),
        ];

        return $fields;
    }

    /**
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class, 'sale_return_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /**
     * Check if return is pending approval
     */
    public function isPending(): bool
    {
        return $this->return_status === self::RETURN_STATUS_PENDING;
    }

    /**
     * Check if return is approved
     */
    public function isApproved(): bool
    {
        return $this->return_status === self::RETURN_STATUS_APPROVED;
    }

    /**
     * Approve the return
     */
    public function approve($userId = null): bool
    {
        $this->return_status = self::RETURN_STATUS_APPROVED;
        $this->approved_at = now();
        $this->approved_by = $userId;
        return $this->save();
    }

    /**
     * Check if stock has been updated
     */
    public function isStockUpdated(): bool
    {
        return $this->stock_updated === true;
    }

    /**
     * Mark stock as updated
     */
    public function markStockUpdated(): bool
    {
        $this->stock_updated = true;
        $this->stock_updated_at = now();
        return $this->save();
    }
}
