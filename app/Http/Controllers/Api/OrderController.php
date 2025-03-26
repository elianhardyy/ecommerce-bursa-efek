<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * OrderController constructor.
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = $this->orderService->getPaginatedOrdersWithDetails(10);
        
        $response = $orders->map(function ($order) {
            return (new OrderResource($order))->toArray($order);
        });

        return ApiResponse::success([
            'orders' => $response,
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ], 'Orders retrieved successfully');
    }

    /**
     * Display a listing of the authenticated user's orders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myOrders()
    {
        $userId = auth()->id();
        $orders = $this->orderService->getPaginatedUserOrders($userId, 10);
        
        $response = $orders->map(function ($order) {
            return (new OrderResource($order))->toArray($order);
        });

        return ApiResponse::success([
            'orders' => $response,
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ], 'Your orders retrieved successfully');
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
    public function store(StoreOrderRequest $request)
    {
        try {
            $userId = auth()->id();
            $shippingDetails = [
                'address' => $request->shipping_address,
                'city' => $request->shipping_city,
                'state' => $request->shipping_state,
                'zip' => $request->shipping_zip,
                'country' => $request->shipping_country,
            ];
            
            $order = $this->orderService->createFromCart(
                $userId,
                $shippingDetails,
                $request->payment_method
            );
            
            $order->load('orderItems.product');
            $response = new OrderResource($order);

            return ApiResponse::success($response->toArray($request), 'Order created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create order: ' . $e->getMessage(), 500);
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
        try {
            $userId = auth()->id();
            $user = auth()->user();
            
            $order = $this->orderService->getOrderWithItems($id);
            
            // Check if user is authorized to access this order
            if ($order->user_id !== $userId && !$user->hasRole(['admin', 'merchant'])) {
                return ApiResponse::forbidden('You do not have permission to view this order');
            }
            
            $response = new OrderResource($order);

            return ApiResponse::success($response->toArray($order), 'Order retrieved successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Order not found');
            }
            
            return ApiResponse::error('Failed to retrieve order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update order status.
     *
     * @param OrderStatusRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(StoreOrderRequest $request, $id)
    {
        try {
            $userId = auth()->id();
            $user = auth()->user();
            
            $order = $this->orderService->getById($id);
            
            // Check if user is authorized to update this order
            if ($order->user_id !== $userId && !$user->hasRole(['admin', 'merchant'])) {
                return ApiResponse::forbidden('You do not have permission to update this order');
            }
            
            $order = $this->orderService->updateStatus($id, $request->status);
            $order->load('orderItems.product');
            
            $response = new OrderResource($order);

            return ApiResponse::success($response->toArray($order), 'Order status updated successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Order not found');
            }
            
            return ApiResponse::error('Failed to update order status: ' . $e->getMessage(), 500);
        }
    }

    public function processPayment(StoreTransactionRequest $request, $id)
    {
        try {
            $userId = auth()->id();
            $user = auth()->user();
            
            $order = $this->orderService->getById($id);
            
            // Check if user is authorized to pay for this order
            if ($order->user_id !== $userId && !$user->hasRole('admin')) {
                return ApiResponse::forbidden('You do not have permission to pay for this order');
            }
            
            if ($order->is_paid) {
                return ApiResponse::error('This order has already been paid', 400);
            }
            
            $paymentDetails = $request->validated();
            
            $order = $this->orderService->processPayment($id, $paymentDetails);
            $order->load('orderItems.product');
            
            $response = new OrderResource($order);

            return ApiResponse::success($response->toArray($order), 'Payment processed successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Order not found');
            }
            
            return ApiResponse::error('Failed to process payment: ' . $e->getMessage(), 500);
        }
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
