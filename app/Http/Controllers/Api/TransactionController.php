<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Responses\ApiResponse;
use App\Services\TransactionService;
use App\Services\UserService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
   /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * TransactionController constructor.
     *
     * @param TransactionService $transactionService
     * @param UserService $userService
     */
    public function __construct(TransactionService $transactionService, UserService $userService)
    {
        $this->transactionService = $transactionService;
        $this->userService = $userService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = auth()->id();
        $transactions = $this->transactionService->getPaginatedUserTransactions($userId, 10);
        
        $response = $transactions->map(function ($transaction) {
            return (new TransactionResource($transaction))->toArray($transaction);
        });

        return ApiResponse::success([
            'transactions' => $response,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ], 'Transactions retrieved successfully');
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
    public function store(Request $request)
    {
        //
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
            
            $transaction = $this->transactionService->getTransactionWithDetails($id);
            
            // Check if user is authorized to access this transaction
            if ($transaction->user_id !== $userId && !$user->hasRole(['admin', 'merchant'])) {
                return ApiResponse::forbidden('You do not have permission to view this transaction');
            }
            
            $response = new TransactionResource($transaction);

            return ApiResponse::success($response->toArray($transaction), 'Transaction retrieved successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Transaction not found');
            }
            
            return ApiResponse::error('Failed to retrieve transaction: ' . $e->getMessage(), 500);
        }
    }
    public function refund(UpdateTransactionRequest $request)
    {
        try {
            $userId = auth()->id();
            $user = auth()->user();
            
            // Validate order exists and belongs to user or admin
            $orderId = $request->order_id;
            $order = \App\Models\Order::findOrFail($orderId);
            
            if ($order->user_id !== $userId && !$user->hasRole('admin')) {
                return ApiResponse::forbidden('You do not have permission to refund this order');
            }
            
            if (!$order->is_paid) {
                return ApiResponse::error('This order has not been paid yet', 400);
            }
            
            $transaction = $this->transactionService->processRefund(
                $orderId,
                $request->amount,
                $request->reason
            );
            
            $transaction->load('order', 'transactionDetails');
            $response = new TransactionResource($transaction);

            return ApiResponse::success($response->toArray($request), 'Refund processed successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Order not found');
            }
            
            return ApiResponse::error('Failed to process refund: ' . $e->getMessage(), 500);
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
