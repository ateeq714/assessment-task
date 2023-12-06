<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Pass the necessary data to the process order method
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        $this->validate($request, [
            'order_id' => 'required|string',
            'subtotal_price' => 'required|numeric',
            'merchant_domain' => 'required|string',
            'discount_code' => 'nullable|string',
        ]);

        $this->orderService->processOrder($data);

        return response()->json(['message' => 'Order processed successfully']);
    }

}
