<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;
use Illuminate\Validation\ValidationException;

class EnsureValidCategoryParentAction
{
    public function execute(?Category $category, ?int $parentId): void
    {
        if ($parentId === null || $category === null) {
            return;
        }

        if ($category->getKey() === $parentId) {
            throw ValidationException::withMessages([
                'parent_id' => 'Danh mục không thể là danh mục cha của chính nó.',
            ]);
        }

        $cursor = Category::query()->find($parentId);

        while ($cursor !== null) {
            if ($cursor->getKey() === $category->getKey()) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Không thể chọn một danh mục con làm danh mục cha.',
                ]);
            }

            $cursor = $cursor->parent_id === null
                ? null
                : Category::query()->find($cursor->parent_id);
        }
    }
}
