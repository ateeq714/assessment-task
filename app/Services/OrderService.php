<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Models\Affiliate;
use App\Models\Commission;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        $orderExists = Order::where('external_order_id', $data['order_id'])->exists();

        if ($orderExists) {
            return;
        }

        $merchant = Merchant::where('domain', $data['merchant_domain'])->first();

        if (!$merchant) {
            Log::error("Merchant not found for domain: {$data['merchant_domain']}");
            return;
        }

        $affiliate = Affiliate::where('discount_code', $data['discount_code'])->first();

        if (!$affiliate) {
            try {
                $affiliate = $this->apiService->createAffiliate($merchant, $data['discount_code']);
            } catch (AffiliateCreateException $exception) {
                // Log an error, or handle the exception based on your application's requirements
                Log::error("Error creating affiliate: {$exception->getMessage()}");
                return;
            }
        }

        $order = Order::create([
            'external_order_id' => $data['order_id'],
            'subtotal' => $data['subtotal_price'],
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate->id,
            'commission_owed' => $data['subtotal_price'] * $affiliate->commission_rate,
            'customer_email' => $data['customer_email'],
            'customer_name' => $data['customer_name'],
        ]);

        Log::info("Commission logged for order {$order->external_order_id}.");
    }
}
