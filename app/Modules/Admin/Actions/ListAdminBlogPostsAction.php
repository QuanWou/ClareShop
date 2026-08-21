<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Blog\Models\BlogPost;

class ListAdminBlogPostsAction
{
    public function execute(): array
    {
        return [
            'posts' => BlogPost::query()->with(['category', 'author'])->latest()->paginate(20),
        ];
    }
}
