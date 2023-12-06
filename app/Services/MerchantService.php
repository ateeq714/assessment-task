<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['api_key']),
            'type' => User::TYPE_MERCHANT,
        ]);

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'domain' => $data['domain'],
            // Add other fields as needed
        ]);

        return $merchant;
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['api_key']),
        ]);

        $user->merchant->update([
            'domain' => $data['domain'],
            'display_name' => $data['name'],
            // Update other fields as needed
        ]);
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)->where('type', User::TYPE_MERCHANT)->first();

        return $user ? $user->merchant : null;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $unpaidOrders = $affiliate->orders()->where('is_paid', false)->get();

        foreach ($unpaidOrders as $order) {
            dispatch(new PayoutOrderJob($order));
        }
    }

    public function getOrderStats(Merchant $merchant, Carbon $fromDate, Carbon $toDate): array
    {
        $orderStats = [
            'count' => $merchant->orders()->whereBetween('created_at', [$fromDate, $toDate])->count(),
            'commission_owed' => $merchant->orders()->whereBetween('created_at', [$fromDate, $toDate])->sum('commission_owed'),
            'revenue' => $merchant->orders()->whereBetween('created_at', [$fromDate, $toDate])->sum('subtotal_price'),
        ];

        return $orderStats;
    }
}
