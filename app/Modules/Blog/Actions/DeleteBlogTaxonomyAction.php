<?php

namespace App\Modules\Blog\Actions;

use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Blog\Models\BlogTag;

class DeleteBlogTaxonomyAction
{
    public function execute(BlogCategory|BlogTag $taxonomy): void
    {
        $taxonomy->delete();
    }
}
