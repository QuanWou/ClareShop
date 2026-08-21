<?php

namespace App\Modules\Blog\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['author_id', 'blog_category_id', 'title', 'slug', 'excerpt', 'content', 'featured_image_path', 'featured_image_alt', 'status', 'is_featured', 'seo_title', 'seo_description', 'published_at'])]
class BlogPost extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'published_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'blog_post_product');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image_path ? asset('storage/'.$this->featured_image_path) : null;
    }
}
