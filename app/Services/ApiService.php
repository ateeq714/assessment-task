<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Models\Affiliate;
use App\Models\Merchant;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * You don't need to do anything here. This is just to help
 */
class ApiService
{
    /**
     * Create a new discount code for an affiliate
     *
     * @param Merchant $merchant
     *
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => rand(0, 100000),
            'code' => Str::uuid()
        ];
    }

    /**
     * Send a payout to an email
     *
     * @param  string $email
     * @param  float $amount
     * @return void
     * @throws RuntimeException
     */
    public function sendPayout(string $email, float $amount)
    {
        //
    }

    /**
     * Create a new affiliate for the merchant with the given discount code.
     *
     * @param Merchant $merchant
     * @param string $discountCode
     * @return Affiliate
     * @throws AffiliateCreateException
     */
    public function createAffiliate(Merchant $merchant, string $discountCode): Affiliate
    {
        $commissionRate = 0.1;

        $affiliateCode = Str::uuid();

        $affiliate = Affiliate::create([
            'merchant_id' => $merchant->id,
            'discount_code' => $discountCode,
            'commission_rate' => $commissionRate,
        ]);

        if (!$affiliate) {
            throw new AffiliateCreateException("Failed to create affiliate for merchant {$merchant->id}");
        }

        return $affiliate;
    }
}
