<?php

namespace App\Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Blog\Actions\ListPublishedBlogPostsAction;
use App\Modules\Blog\Actions\ShowPublishedBlogPostAction;
use App\Modules\Blog\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request, ListPublishedBlogPostsAction $action): View
    {
        return view('blog.index', $action->execute($request->string('category')->toString() ?: null));
    }

    public function show(BlogPost $post, ShowPublishedBlogPostAction $action): View
    {
        return view('blog.show', $action->execute($post));
    }
}
