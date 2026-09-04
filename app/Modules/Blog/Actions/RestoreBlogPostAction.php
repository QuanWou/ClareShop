<?php

namespace App\Modules\Blog\Actions;

use App\Modules\Blog\Models\BlogPost;

class RestoreBlogPostAction
{
    public function execute(BlogPost $post): void
    {
        $post->restore();
    }
}
