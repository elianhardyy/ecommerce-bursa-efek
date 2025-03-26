<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductService extends BaseService
{
    /**
     * ProductService constructor.
     *
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->model = $product;
        $this->cacheKey = 'products';
    }

    /**
     * Get all products with category.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllWithCategory()
    {
        return Cache::remember($this->cacheKey . '.with_category.all', $this->cacheTtl, function () {
            return $this->model->with('category')->get();
        });
    }

    /**
     * Get paginated products with category.
     *
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedWithCategory($perPage = 10)
    {
        $page = request()->get('page', 1);
        $cacheKey = $this->cacheKey . '.with_category.paginated.' . $perPage . '.' . $page;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($perPage) {
            return $this->model->with('category')->paginate($perPage);
        });
    }

    /**
     * Get product by id with category.
     *
     * @param int $id
     * @return Product
     */
    public function getByIdWithCategory($id)
    {
        return Cache::remember($this->cacheKey . '.with_category.id.' . $id, $this->cacheTtl, function () use ($id) {
            return $this->model->with('category')->findOrFail($id);
        });
    }

    /**
     * Create new product with image.
     *
     * @param array $data
     * @return Product
     */
    public function createWithImage(array $data)
    {
        if (isset($data['image']) && $data['image']->isValid()) {
            $path = $data['image']->store('products', 'public');
            $data['image'] = $path;
        }

        return $this->create($data);
    }

    /**
     * Update product with image.
     *
     * @param int $id
     * @param array $data
     * @return Product
     */
    public function updateWithImage($id, array $data)
    {
        $product = $this->getById($id);

        if (isset($data['image']) && $data['image']->isValid()) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $path = $data['image']->store('products', 'public');
            $data['image'] = $path;
        }

        return $this->update($id, $data);
    }

    /**
     * Update product rating.
     *
     * @param int $id
     * @param float $rating
     * @return Product
     */
    public function updateRating($id, $rating)
    {
        $product = $this->getById($id);
        
        $currentNumReviews = $product->num_reviews;
        $currentRating = $product->ratings;
        
        // Calculate new rating
        $newNumReviews = $currentNumReviews + 1;
        $newRating = (($currentRating * $currentNumReviews) + $rating) / $newNumReviews;
        
        return $this->update($id, [
            'ratings' => round($newRating, 2),
            'num_reviews' => $newNumReviews
        ]);
    }

    /**
     * Delete product with image.
     *
     * @param int $id
     * @return bool
     */
    public function deleteWithImage($id)
    {
        $product = $this->getById($id);

        // Delete image if exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        return $this->delete($id);
    }
}