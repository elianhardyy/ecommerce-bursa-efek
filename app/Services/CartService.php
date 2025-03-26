<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Support\Facades\Cache;

class CartService extends BaseService
{
    /**
     * CartService constructor.
     *
     * @param Cart $cart
     */
    public function __construct(Cart $cart)
    {
        $this->model = $cart;
        $this->cacheKey = 'carts';
    }

    /**
     * Get user cart items with product.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCartItems($userId)
    {
        $cacheKey = $this->cacheKey . '.user.' . $userId;
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId) {
            return $this->model->with('product.category')
                ->where('user_id', $userId)
                ->get();
        });
    }

    /**
     * Add product to cart.
     *
     * @param int $userId
     * @param int $productId
     * @param int $quantity
     * @param float $price
     * @return Cart
     */
    public function addToCart($userId, $productId, $quantity, $price)
    {
        // Check if product already in cart
        $cartItem = $this->model->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($cartItem) {
            // Update quantity
            $cartItem->quantity += $quantity;
            $cartItem->save();
            
            $this->clearUserCache($userId);
            
            return $cartItem;
        }

        // Add new cart item
        $data = [
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price
        ];

        $cartItem = $this->create($data);
        $this->clearUserCache($userId);
        
        return $cartItem;
    }

    /**
     * Update cart item quantity.
     *
     * @param int $id
     * @param int $quantity
     * @return Cart
     */
    public function updateQuantity($id, $quantity)
    {
        $cartItem = $this->getById($id);
        $cartItem->quantity = $quantity;
        $cartItem->save();
        
        $this->clearUserCache($cartItem->user_id);
        
        return $cartItem;
    }

    /**
     * Clear cart for user.
     *
     * @param int $userId
     * @return bool
     */
    public function clearUserCart($userId)
    {
        $this->model->where('user_id', $userId)->delete();
        $this->clearUserCache($userId);
        
        return true;
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
    }
}