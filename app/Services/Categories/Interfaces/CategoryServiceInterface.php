<?php

namespace App\Services\Categories\Interfaces;

use App\Models\Category;
use App\Models\User;
use App\Services\Categories\DTO\CreateCategoryDTO;
use App\Services\Categories\DTO\UpdateCategoryDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoryServiceInterface
{
    public function getCategories(User $user): Collection;
    public function getCategoriesPaginated(User $user): LengthAwarePaginator;
    public function getCategory(User $user, string $categoryId): Category;
    public function createCategory(User $user, CreateCategoryDTO $dto): Category;
    public function updateCategory(User $user, string $categoryId, UpdateCategoryDTO $dto): Category;
    public function deleteCategory(User $user, string $categoryId): void;
    public function getCategoryStatistics(User $user, string $categoryId): array;
    public function getUserCategoriesStatistics(User $user): array;
}