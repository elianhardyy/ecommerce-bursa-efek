<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use App\Services\TransactionService;
use App\Services\UserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService extends BaseService
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * OrderService constructor.
     *
     * @param Order $order
     * @param CartService $cartService
     * @param TransactionService $transactionService
     * @param UserService $userService
     */
    public function __construct(
        Order $order, 
        CartService $cartService,
        TransactionService $transactionService,
        UserService $userService
    ) {
        $this->model = $order;
        $this->cartService = $cartService;
        $this->transactionService = $transactionService;
        $this->userService = $userService;
        $this->cacheKey = 'orders';
    }

    /**
     * Get user orders.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserOrders($userId)
    {
        $cacheKey = $this->cacheKey . '.user.' . $userId;
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId) {
            return $this->model->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get paginated user orders.
     *
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedUserOrders($userId, $perPage = 10)
    {
        $page = request()->get('page', 1);
        $cacheKey = $this->cacheKey . '.user.' . $userId . '.paginated.' . $perPage . '.' . $page;
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId, $perPage) {
            return $this->model->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Get order with items.
     *
     * @param int $id
     * @return Order
     */
    public function getOrderWithItems($id)
    {
        return Cache::remember($this->cacheKey . '.with_items.' . $id, $this->cacheTtl, function () use ($id) {
            return $this->model->with(['orderItems.product', 'user'])
                ->findOrFail($id);
        });
    }

    /**
     * Create order from user cart.
     *
     * @param int $userId
     * @param array $shippingDetails
     * @param string $paymentMethod
     * @return Order
     */
    public function createFromCart($userId, array $shippingDetails, $paymentMethod)
    {
        return DB::transaction(function () use ($userId, $shippingDetails, $paymentMethod) {
            // Get cart items
            $cartItems = $this->cartService->getUserCartItems($userId);
            
            if ($cartItems->isEmpty()) {
                throw new \Exception('Cart is empty');
            }
            
            // Calculate totals
            $totalAmount = $cartItems->sum(function ($item) {
                return $item->price * $item->quantity;
            });
            
            // Shipping price (you can implement your own logic)
            $shippingPrice = 10.00;
            
            // Create order
            $orderData = [
                'user_id' => $userId,
                'order_number' => 'ORD-' . Str::uuid(),
                'status' => 'pending',
                'total_amount' => $totalAmount + $shippingPrice,
                'shipping_price' => $shippingPrice,
                'payment_method' => $paymentMethod,
                'shipping_address' => $shippingDetails['address'],
                'shipping_city' => $shippingDetails['city'],
                'shipping_state' => $shippingDetails['state'],
                'shipping_zip' => $shippingDetails['zip'],
                'shipping_country' => $shippingDetails['country'],
            ];
            
            $order = $this->create($orderData);
            
            // Create order items
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->price,
                    'total_price' => $cartItem->price * $cartItem->quantity,
                ]);
            }
            
            // Clear cart
            $this->cartService->clearUserCart($userId);
            
            // Clear cache
            $this->clearUserCache($userId);
            
            return $order;
        });
    }

    /**
     * Update order status.
     *
     * @param int $id
     * @param string $status
     * @return Order
     */
    public function updateStatus($id, $status)
    {
        $order = $this->getById($id);
        
        $order->status = $status;
        
        if ($status === 'delivered') {
            $order->is_delivered = true;
            $order->delivered_at = now();
        }
        
        $order->save();
        
        // Clear cache
        $this->clearCache();
        $this->clearUserCache($order->user_id);
        
        return $order;
    }

    /**
     * Process payment for order.
     *
     * @param int $id
     * @param array $paymentDetails
     * @return Order
     */
    public function processPayment($id, array $paymentDetails)
    {
        return DB::transaction(function () use ($id, $paymentDetails) {
            $order = $this->getById($id);
            
            if ($order->is_paid) {
                throw new \Exception('Order is already paid');
            }
            
            // Process payment (you can implement your own logic)
            $paymentStatus = 'success'; // Mock successful payment
            
            if ($paymentStatus === 'success') {
                $order->is_paid = true;
                $order->paid_at = now();
                $order->status = 'processing';
                $order->save();
                
                // Create transaction
                $pointsEarned = floor($order->total_amount * 0.1); // 10% of total as points
                
                $transaction = $this->transactionService->create([
                    'transaction_number' => 'TRX-' . Str::uuid(),
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'type' => 'payment',
                    'amount' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'status' => 'success',
                    'currency' => 'IDR',
                    'points_earned' => $pointsEarned,
                    'notes' => 'Payment for order ' . $order->order_number,
                    'external_reference' => $paymentDetails['reference'] ?? null,
                ]);
                
                // Add transaction details if any
                if (isset($paymentDetails['details']) && is_array($paymentDetails['details'])) {
                    foreach ($paymentDetails['details'] as $key => $value) {
                        $this->transactionService->addTransactionDetail($transaction->id, $key, $value);
                    }
                }
                
                // Add points to user
                $this->userService->addPoints($order->user_id, $pointsEarned);
            }
            
            // Clear cache
            $this->clearCache();
            $this->clearUserCache($order->user_id);
            
            return $order;
        });
    }
    
    /**
     * Get all orders with items and user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllOrdersWithDetails()
    {
        return Cache::remember($this->cacheKey . '.with_details.all', $this->cacheTtl, function () {
            return $this->model->with(['orderItems.product', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }
    
    /**
     * Get paginated orders with items and user.
     *
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedOrdersWithDetails($perPage = 10)
    {
        $page = request()->get('page', 1);
        $cacheKey = $this->cacheKey . '.with_details.paginated.' . $perPage . '.' . $page;
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($perPage) {
            return $this->model->with(['orderItems.product', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }
    
    /**
     * Clear cache for specific user.
     *
     * @param int $userId
     * @return void
     */
    protected function clearUserCache($userId)
    {
        Cache::forget($this->cacheKey . '.user.' . $userId);
        
        for ($i = 1; $i <= 100; $i++) {
            Cache::forget($this->cacheKey . '.user.' . $userId . '.paginated.10.' . $i);
        }
    }
}