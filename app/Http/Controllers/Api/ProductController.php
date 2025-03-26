<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\ProductRatingRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Services\ProductService;

class ProductController extends Controller
{
    /**
     * @var ProductService
     */
    protected $productService;

    /**
     * ProductController constructor.
     *
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $products = $this->productService->getPaginatedWithCategory(10);
        
        $response = $products->map(function ($product) {
            return (new ProductResource($product))->toArray($product);
        });

        return ApiResponse::success([
            'products' => $response,
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ], 'Products retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ProductRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->createWithImage($request->validated());
            $product->load('category');
            $response = new ProductResource($product);

            return ApiResponse::success($response->toArray($request), 'Product created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Product creation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = $this->productService->getByIdWithCategory($id);
            $response = new ProductResource($product);

            return ApiResponse::success($response->toArray($product), 'Product retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::notFound('Product not found');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ProductRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $product = $this->productService->updateWithImage($id, $request->validated());
            $product->load('category');
            $response = new ProductResource($product);

            return ApiResponse::success($response->toArray($request), 'Product updated successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Product not found');
            }
            
            return ApiResponse::error('Product update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->productService->deleteWithImage($id);

            return ApiResponse::success(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Product not found');
            }
            
            return ApiResponse::error('Product deletion failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Rate a product.
     *
     * @param  ProductRatingRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rateProduct(ProductRatingRequest $request, $id)
    {
        try {
            $product = $this->productService->updateRating($id, $request->rating);
            $product->load('category');
            $response = new ProductResource($product);

            return ApiResponse::success($response->toArray($request), 'Product rating updated successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Product not found');
            }
            
            return ApiResponse::error('Product rating failed: ' . $e->getMessage(), 500);
        }
    }
}
