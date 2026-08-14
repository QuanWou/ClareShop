<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;
use Illuminate\Validation\ValidationException;

class ArchiveCatalogCategoryAction
{
    public function execute(Category $category): void
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Không thể xóa nhóm còn sản phẩm. Hãy chuyển hoặc lưu trữ các sản phẩm trước.',
            ]);
        }

        $category->delete();
    }
}
