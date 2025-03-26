<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TransactionService extends BaseService
{
    /**
     * TransactionService constructor.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;
        $this->cacheKey = 'transactions';
    }

    /**
     * Get user transactions.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserTransactions($userId)
    {
        $cacheKey = $this->cacheKey . '.user.' . $userId;
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId) {
            return $this->model->with(['order', 'transactionDetails'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get paginated user transactions.
     *
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedUserTransactions($userId, $perPage = 10)
    {
        $page = request()->get('page', 1);
        $cacheKey = $this->cacheKey . '.user.' . $userId . '.paginated.' . $perPage . '.' . $page;
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId, $perPage) {
            return $this->model->with(['order', 'transactionDetails'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Get transaction with details.
     *
     * @param int $id
     * @return Transaction
     */
    public function getTransactionWithDetails($id)
    {
        return Cache::remember($this->cacheKey . '.with_details.' . $id, $this->cacheTtl, function () use ($id) {
            return $this->model->with(['order.orderItems.product', 'user', 'transactionDetails'])
                ->findOrFail($id);
        });
    }

    /**
     * Add transaction detail.
     *
     * @param int $transactionId
     * @param string $key
     * @param string $value
     * @return TransactionDetail
     */
    public function addTransactionDetail($transactionId, $key, $value)
    {
        $detail = TransactionDetail::create([
            'transaction_id' => $transactionId,
            'key' => $key,
            'value' => $value,
        ]);
        
        // Clear cache
        Cache::forget($this->cacheKey . '.with_details.' . $transactionId);
        
        return $detail;
    }

    /**
     * Process refund.
     *
     * @param int $orderId
     * @param float $amount
     * @param string $reason
     * @return Transaction
     */
    public function processRefund($orderId, $amount, $reason)
    {
        // Get original transaction
        $originalTransaction = $this->model->where('order_id', $orderId)
            ->where('type', 'payment')
            ->firstOrFail();
        
        // Create refund transaction
        $transaction = $this->create([
            'transaction_number' => 'REF-' . Str::uuid(),
            'user_id' => $originalTransaction->user_id,
            'order_id' => $orderId,
            'type' => 'refund',
            'amount' => $amount,
            'payment_method' => $originalTransaction->payment_method,
            'status' => 'success',
            'currency' => $originalTransaction->currency,
            'points_earned' => 0,
            'notes' => 'Refund for order ' . $originalTransaction->order->order_number . ': ' . $reason,
            'external_reference' => null,
        ]);
        
        // Add transaction detail for reason
        $this->addTransactionDetail($transaction->id, 'reason', $reason);
        $this->addTransactionDetail($transaction->id, 'original_transaction', $originalTransaction->transaction_number);
        
        return $transaction;
    }

    /**
     * Get user points earned from transactions.
     *
     * @param int $userId
     * @return int
     */
    public function getUserPointsEarned($userId)
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'success')
            ->sum('points_earned');
    }

    /**
     * Generate transaction report.
     *
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public function generateReport($userId, array $filters = [])
    {
        $query = $this->model->where('user_id', $userId);
        
        // Apply filters
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->get();
        
        // Calculate summary
        $totalAmount = $transactions->sum('amount');
        $totalSuccess = $transactions->where('status', 'success')->count();
        $totalFailed = $transactions->where('status', 'failed')->count();
        $totalPending = $transactions->where('status', 'pending')->count();
        $totalCanceled = $transactions->where('status', 'canceled')->count();
        
        // Group by type
        $byType = [];
        foreach ($transactions->groupBy('type') as $type => $items) {
            $byType[$type] = [
                'count' => $items->count(),
                'amount' => $items->sum('amount')
            ];
        }
        
        // Group by month
        $byMonth = [];
        foreach ($transactions->groupBy(function($transaction) {
            return $transaction->created_at->format('Y-m');
        }) as $month => $items) {
            $byMonth[$month] = [
                'count' => $items->count(),
                'amount' => $items->sum('amount')
            ];
        }
        
        return [
            'total_transactions' => $transactions->count(),
            'total_amount' => $totalAmount,
            'status_summary' => [
                'success' => $totalSuccess,
                'failed' => $totalFailed,
                'pending' => $totalPending,
                'canceled' => $totalCanceled
            ],
            'by_type' => $byType,
            'by_month' => $byMonth,
            'points_earned' => $transactions->sum('points_earned'),
            'transactions' => $transactions->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'points_earned' => $transaction->points_earned,
                    'date' => $transaction->created_at->toDateTimeString(),
                    'order_number' => $transaction->order->order_number ?? null
                ];
            })
        ];
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