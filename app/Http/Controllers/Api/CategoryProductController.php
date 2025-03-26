<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryProduct\StoreCategoryProductRequest;
use App\Http\Requests\CategoryProduct\UpdateCategoryProductRequest;
use App\Http\Resources\CategoryProductResource;
use App\Http\Responses\ApiResponse;
use App\Services\CategoryProductService;
use Illuminate\Http\Request;

class CategoryProductController extends Controller
{
    /**
     * @var CategoryProductService
     */
    protected $categoryProductService;

    /**
     * CategoryProductController constructor.
     *
     * @param CategoryProductService $categoryProductService
     */
    public function __construct(CategoryProductService $categoryProductService)
    {
        $this->categoryProductService = $categoryProductService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = $this->categoryProductService->getPaginated(10);
        
        $response = $categories->map(function ($category) {
            return (new CategoryProductResource($category))->toArray($category);
        });

        return ApiResponse::success([
            'categories' => $response,
            'pagination' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
            ],
        ], 'Categories retrieved successfully');
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
    public function store(StoreCategoryProductRequest $request)
    {
        try {
            $category = $this->categoryProductService->create($request->validated());
            $response = new CategoryProductResource($category);

            return ApiResponse::success($response->toArray($request), 'Category created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Category creation failed: ' . $e->getMessage(), 500);
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
            $category = $this->categoryProductService->getById($id);
            $response = new CategoryProductResource($category);

            return ApiResponse::success($response->toArray($category), 'Category retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::notFound('Category not found');
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
    public function update(UpdateCategoryProductRequest $request, $id)
    {
        try {
            $category = $this->categoryProductService->update($id, $request->validated());
            $response = new CategoryProductResource($category);

            return ApiResponse::success($response->toArray($request), 'Category updated successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Category not found');
            }
            
            return ApiResponse::error('Category update failed: ' . $e->getMessage(), 500);
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
            $this->categoryProductService->delete($id);

            return ApiResponse::success(null, 'Category deleted successfully');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::notFound('Category not found');
            }
            
            return ApiResponse::error('Category deletion failed: ' . $e->getMessage(), 500);
        }
    }
}
