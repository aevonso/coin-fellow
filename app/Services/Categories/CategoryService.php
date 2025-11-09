<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Models\User;
use App\Services\Categories\DTO\CreateCategoryDTO;
use App\Services\Categories\DTO\UpdateCategoryDTO;
use App\Services\Categories\Interfaces\CategoryServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService implements CategoryServiceInterface
{
    public function getCategories(User $user): Collection
    {
        return Category::forUser($user)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    public function getCategoriesPaginated(User $user): LengthAwarePaginator
    {
        return Category::forUser($user)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(20);
    }

    public function getCategory(User $user, string $categoryId): Category
    {
        $category = Category::forUser($user)->findOrFail($categoryId);
        return $category;
    }

    public function createCategory(User $user, CreateCategoryDTO $dto): Category
    {
        $existingCategory = Category::where('name', $dto->name)
            ->where('user_id', $user->id)
            ->first();
        
        if ($existingCategory) {
            throw ValidationException::withMessages([
                'name' => ['Категория с таким названием уже существует'],
            ]);
        }

        return Category::create([
            'name' => $dto->name,
            'icon' => $dto->icon,
            'color' => $dto->color,
            'is_default' => false,
            'user_id' => $user->id
        ]);
    }

    public function updateCategory(User $user, string $categoryId, UpdateCategoryDTO $dto): Category
    {
        $category = Category::where('user_id', $user->id)->findOrFail($categoryId);

        if (!$category->isEditable()) {
            throw ValidationException::withMessages([
                'category' => ['Категории по умолчанию не могут быть изменены'],
            ]);
        }

        if ($dto->name && $dto->name !== $category->name) {
            $existingCategory = Category::where('name', $dto->name)
                ->where('user_id', $user->id)
                ->where('id', '!=', $categoryId)
                ->first();
            
            if ($existingCategory) {
                throw ValidationException::withMessages([
                    'name' => ['Категория с таким названием уже существует'],
                ]);
            }
        }

        if ($dto->name) {
            $category->name = $dto->name;
        }
        if ($dto->icon !== null) {
            $category->icon = $dto->icon;
        }
        if ($dto->color !== null) {
            $category->color = $dto->color;
        }

        $category->save();

        return $category;
    }

    public function deleteCategory(User $user, string $categoryId): void
    {
        $category = Category::where('user_id', $user->id)->findOrFail($categoryId);

        if (!$category->isDeletable()) {
            throw ValidationException::withMessages([
                'category' => ['Категория не может быть удалена'],
            ]);
        }

        $category->delete();
    }

    public function getCategoryStatistics(User $user, string $categoryId): array
    {
        $category = Category::forUser($user)->findOrFail($categoryId);

        $stats = $category->expenses()
            ->where(function($query) use ($user) {
                $query->where('payer_id', $user->id)
                      ->orWhereHas('participants', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->select(
                DB::raw('COUNT(*) as expenses_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as average_amount'),
                DB::raw('MAX(amount) as max_amount'),
                DB::raw('MIN(amount) as min_amount')
            )
            ->first();

        $recentExpenses = $category->expenses()
            ->where(function($query) use ($user) {
                $query->where('payer_id', $user->id)
                      ->orWhereHas('participants', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->with(['group', 'payer'])
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        return [
            'category' => $category,
            'statistics' => [
                'expenses_count' => (int) ($stats->expenses_count ?? 0),
                'total_amount' => (float) ($stats->total_amount ?? 0),
                'average_amount' => (float) ($stats->average_amount ?? 0),
                'max_amount' => (float) ($stats->max_amount ?? 0),
                'min_amount' => (float) ($stats->min_amount ?? 0),
            ],
            'recent_expenses' => $recentExpenses
        ];
    }

    public function getUserCategoriesStatistics(User $user): array
    {
        $categoriesStats = Category::forUser($user)
            ->withCount(['expenses' => function ($query) use ($user) {
                $query->where('payer_id', $user->id)
                      ->orWhereHas('participants', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            }])
            ->withSum(['expenses' => function ($query) use ($user) {
                $query->where('payer_id', $user->id)
                      ->orWhereHas('participants', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            }], 'amount')
            ->having('expenses_sum_amount', '>', 0)
            ->orderBy('expenses_sum_amount', 'desc')
            ->get();

        $totalAmount = $categoriesStats->sum('expenses_sum_amount');

        return [
            'categories' => $categoriesStats->map(function ($category) use ($totalAmount) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'is_default' => $category->is_default,
                    'expenses_count' => $category->expenses_count,
                    'total_amount' => (float) $category->expenses_sum_amount,
                    'percentage' => $totalAmount > 0 ? round(($category->expenses_sum_amount / $totalAmount) * 100, 2) : 0
                ];
            }),
            'total_amount' => $totalAmount,
            'total_categories' => $categoriesStats->count()
        ];
    }
}