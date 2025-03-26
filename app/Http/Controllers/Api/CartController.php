<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Http\Responses\ApiResponse;
use App\Services\CartService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var ProductService
     */
    protected $productService;

    /**
     * CartController constructor.
     *
     * @param CartService $cartService
     * @param ProductService $productService
     */
    public function __construct(CartService $cartService, ProductService $productService)
    {
        $this->cartService = $cartService;
        $this->productService = $productService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
 * Display a listing of the user's cart items.
 *
 * @OA\Get(
 *     path="/cart",
 *     summary="Get cart items",
 *     description="Get all cart items for the authenticated user",
 *     operationId="getCartItems",
 *     tags={"Cart"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
    public function index()
    {
        $userId = auth()->id();
        $cartItems = $this->cartService->getUserCartItems($userId);
        
        $response = $cartItems->map(function ($item) {
            return (new CartResource($item))->toArray($item);
        });

        $totalPrice = $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        return ApiResponse::success([
            'cart_items' => $response,
            'total_price' => $totalPrice,
            'total_items' => $cartItems->count(),
        ], 'Cart items retrieved successfully');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     /**
 * Add a product to cart.
 *
 * @OA\Post(
 *     path="/cart",
 *     summary="Add to cart",
 *     description="Add a product to the cart",
 *     operationId="addToCart",
 *     tags={"Cart"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"product_id", "quantity"},
 *             @OA\Property(property="product_id", type="integer", example=1),
 *             @OA\Property(property="quantity", type="integer", example=2)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Created"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
    public function store(StoreCartRequest $request)
    {
        try {
            $userId = auth()->id();
            $productId = $request->product_id;
            $quantity = $request->quantity;
            
            // Get product to get price
            $product = $this->productService->getById($productId);
            $price = $product->price;
            
            $cartItem = $this->cartService->addToCart($userId, $productId, $quantity, $price);
            $cartItem->load('product.category');
            
            $response = new CartResource($cartItem);

            return ApiResponse::success($response->toArray($request), 'Product added to cart successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to add product to cart: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCartRequest $request, $id)
    {
        try {
            $userId = auth()->id();
            $quantity = $request->quantity;
            
            // Ensure cart item belongs to user
            $cartItem = $this->cartService->getById($id);
            if ($cartItem->user_id !== $userId) {
                return ApiResponse::forbidden('You do not have permission to update this cart item');
            }
            
            $cartItem = $this->cartService->updateQuantity($id, $quantity);
            $cartItem->load('product.category');
            
            $response = new CartResource($cartItem);

            return ApiResponse::success($response->toArray($request), 'Cart item updated successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Cart item not found');
            }
            
            return ApiResponse::error('Failed to update cart item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $userId = auth()->id();
            
            // Ensure cart item belongs to user
            $cartItem = $this->cartService->getById($id);
            if ($cartItem->user_id !== $userId) {
                return ApiResponse::forbidden('You do not have permission to delete this cart item');
            }
            
            $this->cartService->delete($id);

            return ApiResponse::success(null, 'Cart item removed successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Cart item not found');
            }
            
            return ApiResponse::error('Failed to remove cart item: ' . $e->getMessage(), 500);
        }
    }
}
