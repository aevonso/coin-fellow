<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\CreateCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\Collections\CategoryCollection;
use App\Services\Categories\DTO\CreateCategoryDTO;
use App\Services\Categories\DTO\UpdateCategoryDTO;
use App\Services\Categories\Interfaces\CategoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryServiceInterface $categoryService
    ) {}

    public function index(Request $request): CategoryCollection
    {
        $user = $request->user();
        $categories = $this->categoryService->getCategoriesPaginated($user);
        return new CategoryCollection($categories);
    }

    public function listAll(Request $request): CategoryCollection
    {
        $user = $request->user();
        $categories = $this->categoryService->getCategories($user);
        return new CategoryCollection($categories);
    }

    public function show(Request $request, string $categoryId): CategoryResource
    {
        $user = $request->user();
        $category = $this->categoryService->getCategory($user, $categoryId);
        return new CategoryResource($category);
    }

    public function store(CreateCategoryRequest $request): CategoryResource
    {
        $user = $request->user();
        $dto = CreateCategoryDTO::from($request->validated());
        $category = $this->categoryService->createCategory($user, $dto);

        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, string $categoryId): CategoryResource
    {
        $user = $request->user();
        $dto = UpdateCategoryDTO::from($request->validated());
        $category = $this->categoryService->updateCategory($user, $categoryId, $dto);

        return new CategoryResource($category);
    }

    public function destroy(Request $request, string $categoryId): JsonResponse
    {
        $user = $request->user();
        $this->categoryService->deleteCategory($user, $categoryId);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    public function statistics(Request $request, string $categoryId): JsonResponse
    {
        $user = $request->user();
        $stats = $this->categoryService->getCategoryStatistics($user, $categoryId);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => new CategoryResource($stats['category']),
                'statistics' => $stats['statistics'],
                'recent_expenses' => $stats['recent_expenses']
            ],
            'message' => 'Category statistics retrieved successfully'
        ]);
    }

    public function userStatistics(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->categoryService->getUserCategoriesStatistics($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'User categories statistics retrieved successfully'
        ]);
    }
}