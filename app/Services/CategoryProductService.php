<?php

namespace App\Services;

use App\Models\CategoryProduct;
use Illuminate\Support\Facades\Cache;

class CategoryProductService extends BaseService
{
    /**
     * CategoryProductService constructor.
     *
     * @param CategoryProduct $categoryProduct
     */
    public function __construct(CategoryProduct $categoryProduct)
    {
        $this->model = $categoryProduct;
        $this->cacheKey = 'category_products';
    }

    /**
     * Get category with products.
     *
     * @param int $id
     * @return CategoryProduct
     */
    public function getCategoryWithProducts($id)
    {
        return Cache::remember($this->cacheKey . '.with_products.' . $id, $this->cacheTtl, function () use ($id) {
            return $this->model->with('products')->findOrFail($id);
        });
    }
}