<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\ProductRatingRequest;
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
    /**
 * Display a listing of the resource.
 *
 * @OA\Get(
 *     path="/products",
 *     summary="Get products",
 *     description="Get all products with pagination",
 *     operationId="getProducts",
 *     tags={"Products"},
 * security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     )
 * )
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

    /**
 * Store a newly created resource in storage.
 *
 * @OA\Post(
 *     path="/products",
 *     summary="Create product",
 *     description="Create a new product",
 *     operationId="createProduct",
 *     tags={"Products"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"name", "price", "product_category_id", "image"},
 *                 @OA\Property(property="name", type="string", example="Smartphone"),
 *                 @OA\Property(property="price", type="number", format="float", example=499.99),
 *                 @OA\Property(property="product_category_id", type="integer", example=1),
 *                 @OA\Property(property="image", type="string", format="binary"),
 *                 @OA\Property(property="ratings", type="number", format="float", example=4.5),
 *                 @OA\Property(property="num_reviews", type="integer", example=20)
 *             )
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
    /**
 * Display the specified resource.
 *
 * @OA\Get(
 *     path="/products/{id}",
 *     summary="Get product",
 *     description="Get a specific product",
 *     operationId="getProduct",
 *     tags={"Products"},
 * security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Product ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not Found"
 *     )
 * )
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

    /**
 * Update the specified resource in storage.
 *
 * @OA\Post(
 *     path="/products/{id}",
 *     summary="Update product",
 *     description="Update a product",
 *     operationId="updateProduct",
 *     tags={"Products"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Product ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="name", type="string", example="Updated Smartphone"),
 *                 @OA\Property(property="price", type="number", format="float", example=399.99),
 *                 @OA\Property(property="product_category_id", type="integer", example=1),
 *                 @OA\Property(property="image", type="string", format="binary"),
 *                 @OA\Property(property="ratings", type="number", format="float", example=4.8),
 *                 @OA\Property(property="num_reviews", type="integer", example=25),
 *                 @OA\Property(property="_method", type="string", example="PUT", description="Method spoofing")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not Found"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
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

    /**
 * Remove the specified resource from storage.
 *
 * @OA\Delete(
 *     path="/products/{id}",
 *     summary="Delete product",
 *     description="Delete a product",
 *     operationId="deleteProduct",
 *     tags={"Products"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Product ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not Found"
 *     )
 * )
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
    /**
 * Rate a product.
 *
 * @OA\Post(
 *     path="/products/{id}/rate",
 *     summary="Rate product",
 *     description="Rate a product",
 *     operationId="rateProduct",
 *     tags={"Products"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Product ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"rating"},
 *             @OA\Property(property="rating", type="number", format="float", example=4.5, minimum=1, maximum=5),
 *             @OA\Property(property="review", type="string", example="Great product, highly recommended!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not Found"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
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
