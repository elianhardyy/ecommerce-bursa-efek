<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
        $rules = [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'product_category_id' => 'required|exists:category_products,id',
            'ratings' => 'nullable|numeric|min:0|max:5',
            'num_reviews' => 'nullable|integer|min:0',
        ];

        // Only require image for new products
        if ($this->isMethod('post')) {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        } else {
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'name.required' => 'Product name is required',
            'price.required' => 'Product price is required',
            'price.numeric' => 'Product price must be a number',
            'price.min' => 'Product price must be at least 0',
            'product_category_id.required' => 'Product category is required',
            'product_category_id.exists' => 'Selected product category does not exist',
            'image.required' => 'Product image is required',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif',
            'image.max' => 'Image may not be greater than 2MB',
            'ratings.numeric' => 'Ratings must be a number',
            'ratings.min' => 'Ratings must be at least 0',
            'ratings.max' => 'Ratings must not exceed 5',
            'num_reviews.integer' => 'Number of reviews must be an integer',
            'num_reviews.min' => 'Number of reviews must be at least 0',
        ];
    }
}
