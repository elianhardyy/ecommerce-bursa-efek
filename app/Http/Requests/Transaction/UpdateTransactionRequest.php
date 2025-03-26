<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'Selected order does not exist',
            'amount.required' => 'Refund amount is required',
            'amount.numeric' => 'Refund amount must be a number',
            'amount.min' => 'Refund amount must be at least 0',
            'reason.required' => 'Refund reason is required',
            'reason.max' => 'Refund reason must not exceed 1000 characters',
        ];
    }
}
