<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        $merchantUser = User::where('email', $email)->first();
        if ($merchantUser && $merchantUser->type === User::TYPE_MERCHANT) {
            throw new AffiliateCreateException("Email is already used by a merchant.");
        }

        $existingAffiliate = Affiliate::where('email', $email)->first();
        if ($existingAffiliate) {
            throw new AffiliateCreateException("Email is already used by an affiliate.");
        }

        $discountCodeData = $this->apiService->createDiscountCode($merchant);

        $affiliate = Affiliate::create([
            'merchant_id' => $merchant->id,
            'discount_code' => $discountCodeData['code'],
            'commission_rate' => $commissionRate,
            'email' => $email,
            'name' => $name,
        ]);

        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }

}
