<?php

namespace App\Modules\Blog\Actions;

use App\Modules\Blog\Models\BlogPost;

class ArchiveBlogPostAction
{
    public function execute(BlogPost $post): void
    {
        $post->delete();
    }
}
