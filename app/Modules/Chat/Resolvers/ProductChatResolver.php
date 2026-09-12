<?php

namespace App\Modules\Chat\Resolvers;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Chat\Data\ChatReply;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Support\GeminiService;
use App\Modules\Chat\Support\NormalizesChatText;
use App\Modules\Shared\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class ProductChatResolver implements WebsiteChatResolver
{
    use NormalizesChatText;

    public function __construct(private readonly GeminiService $gemini) {}

    public function supports(string $message): bool
    {
        return $this->containsAny($message, [
            'sản phẩm', 'đèn', 'đèn ngủ', 'mẫu', 'còn hàng', 'hết hàng', 'giá',
            'dưới', 'trên', 'giảm giá', 'sale', 'catalog', 'danh mục', 'phân tích',
            'đánh giá sản phẩm', 'nhận xét sản phẩm',
        ]);
    }

    public function resolve(string $message, ?User $customer, ChatConversation $conversation, ?int $productId = null): ChatReply
    {
        $normalized = $this->normalize($message);
        [$minimum, $maximum] = $this->priceRange($normalized);
        $discountedOnly = str_contains($normalized, 'giam gia') || str_contains($normalized, 'sale');
        $inStockOnly = str_contains($normalized, 'con hang');
        $analysisRequested = $this->containsAny($message, [
            'phân tích sản phẩm', 'đánh giá sản phẩm', 'nhận xét sản phẩm',
        ]);

        if ($analysisRequested && ($productId !== null || preg_match('/#(\d+)/', $normalized, $match) === 1)) {
            $requestedProductId = $productId ?? (int) $match[1];
            $product = $this->productQuery()->whereKey($requestedProductId)->first();

            return $product instanceof Product
                ? $this->analysisReply($product)
                : new ChatReply('Mẫu bạn chọn hiện không còn được hiển thị. Bạn muốn mình tìm một mẫu tương tự không?');
        }

        $contextProductIds = [];
        if (str_contains($normalized, 'mau nay') || str_contains($normalized, 'san pham nay')) {
            $previousProductMessage = $conversation->messages()
                ->where('message_type', 'product_card')
                ->latest('id')
                ->first();
            $contextProductIds = array_values(array_filter(
                data_get($previousProductMessage?->metadata, 'context_product_ids', []),
                'is_numeric',
            ));
        }

        $products = $this->productQuery()
            ->when($contextProductIds !== [], fn ($query) => $query->whereKey($contextProductIds))
            ->when($minimum !== null || $maximum !== null, function ($query) use ($minimum, $maximum): void {
                $query->whereHas('activeVariants', function ($variant) use ($minimum, $maximum): void {
                    $variant->when($minimum !== null, fn ($q) => $q->where('price', '>=', $minimum));
                    $variant->when($maximum !== null, fn ($q) => $q->where('price', '<=', $maximum));
                });
            })
            ->when($discountedOnly, fn ($query) => $query->whereHas(
                'activeVariants',
                fn ($variant) => $variant->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price'),
            ))
            ->when($inStockOnly, fn ($query) => $query->whereHas(
                'activeVariants',
                fn ($variant) => $variant->where('stock_quantity', '>', 0),
            ))
            ->latest('published_at')
            ->limit(60)
            ->get();

        $terms = collect(explode(' ', $normalized))
            ->reject(fn (string $term): bool => strlen($term) < 3 || in_array($term, [
                'san', 'pham', 'den', 'mau', 'gia', 'con', 'hang', 'duoi', 'tren', 'trieu', 'nghin', 'sale', 'giam',
                'phan', 'tich', 'danh', 'nhan', 'xet',
            ], true))
            ->values();

        $ranked = $products->map(function (Product $product) use ($terms): array {
            $haystack = $this->normalize(implode(' ', [
                $product->name,
                $product->short_description,
                $product->category?->name,
            ]));
            $score = $terms->sum(fn (string $term): int => str_contains($haystack, $term) ? 2 : 0);

            return ['product' => $product, 'score' => $score];
        });

        if ($terms->isNotEmpty() && $ranked->contains(fn (array $row): bool => $row['score'] > 0)) {
            $ranked = $ranked->filter(fn (array $row): bool => $row['score'] > 0);
        }

        /** @var Collection<int, Product> $matches */
        $matches = $ranked->sortByDesc('score')->pluck('product')->take(4)->values();

        if ($matches->isEmpty()) {
            return new ChatReply('Mình chưa tìm thấy mẫu phù hợp. Bạn thử nói rõ loại đèn, khoảng giá hoặc không gian muốn dùng nhé.');
        }

        if ($analysisRequested) {
            return $this->analysisReply($matches->first());
        }

        $cards = $matches->map(fn (Product $product): array => $this->cardFor($product))->all();

        $intro = count($cards) === 1
            ? 'Mình tìm thấy mẫu này phù hợp với bạn.'
            : 'Mình tìm thấy '.count($cards).' mẫu phù hợp nhất.';

        return new ChatReply($intro, 'product_card', [
            'products' => $cards,
            'context_product_ids' => $matches->pluck('id')->all(),
            'more_url' => route('catalog.products.index'),
        ]);
    }

    private function productQuery(): Builder
    {
        return Product::query()
            ->published()
            ->withStorefrontSummary()
            ->with([
                'category:id,name,slug',
                'brand:id,name',
                'images:id,product_id,disk,path,alt_text,sort_order',
                'activeVariants:id,product_id,sku,color_name,price,compare_at_price,stock_quantity,is_active,sort_order',
            ]);
    }

    /** @return array<string, mixed> */
    private function cardFor(Product $product): array
    {
        $variants = $product->activeVariants;
        $lowest = $variants->sortBy(fn ($variant): float => (float) $variant->price)->first();
        $compareAt = $lowest?->isDiscounted() ? $lowest->compare_at_price : null;
        $stock = $variants->sum('stock_quantity');

        return [
            'id' => $product->getKey(),
            'name' => $product->name,
            'image' => $product->images->first()?->url ?? '/images/catalog/product-placeholder.svg',
            'price' => Money::formatVnd($lowest?->price ?? 0),
            'compare_at_price' => $compareAt ? Money::formatVnd($compareAt) : null,
            'stock_label' => $stock > 0 ? 'Còn hàng' : 'Tạm hết hàng',
            'url' => route('catalog.products.show', $product),
        ];
    }

    private function analysisReply(Product $product): ChatReply
    {
        $product->loadCount(['reviews as approved_reviews_count' => fn ($query) => $query->approved()]);
        $product->loadAvg(['reviews as approved_reviews_average' => fn ($query) => $query->approved()], 'rating');

        $variants = $product->activeVariants->sortBy(fn ($variant): float => (float) $variant->price)->values();
        $lowest = $variants->first();
        $highest = $variants->last();
        $stock = (int) $variants->sum('stock_quantity');
        $colors = $variants->pluck('color_name')->filter()->unique()->take(4)->implode(', ');
        $price = $lowest && $highest && (float) $lowest->price !== (float) $highest->price
            ? Money::formatVnd($lowest->price).' – '.Money::formatVnd($highest->price)
            : Money::formatVnd($lowest?->price ?? 0);
        $reviewCount = (int) ($product->approved_reviews_count ?? 0);
        $reviewSummary = $reviewCount > 0
            ? number_format((float) $product->approved_reviews_average, 1, ',', '.').'/5 từ '.$reviewCount.' đánh giá đã duyệt'
            : 'chưa có đủ đánh giá đã duyệt';
        $facts = [
            'name' => $product->name,
            'short_description' => $product->short_description,
            'category' => $product->category?->name,
            'brand' => $product->brand?->name,
            'material' => $product->material,
            'dimensions' => $product->dimensions,
            'price' => $price,
            'variant_count' => $variants->count(),
            'colors' => $colors,
            'stock_quantity' => $stock,
            'review_count' => $reviewCount,
            'average_rating' => $reviewCount > 0 ? round((float) $product->approved_reviews_average, 1) : null,
        ];

        try {
            $message = $this->gemini->analyzeProduct($facts);
        } catch (Throwable) {
            $availability = $stock > 0 ? 'hiện còn '.$stock.' sản phẩm' : 'hiện đang tạm hết hàng';
            $message = $product->name.' '.mb_lcfirst($product->short_description ?: 'là một mẫu đèn được Clare tuyển chọn cho không gian nghỉ ngơi.').' Giá của mẫu nằm ở mức '.$price.', với '.$variants->count().' lựa chọn'.($colors !== '' ? ' gồm '.$colors : '').' và '.$availability.'. '.($reviewCount > 0 ? 'Khách mua đang chấm mẫu này '.$reviewSummary.'.' : 'Mẫu còn khá mới nên bạn có thể cân nhắc thêm chất liệu và kích thước trước khi chọn.');
        }

        return new ChatReply($message, 'product_card', [
            'products' => [$this->cardFor($product)],
            'context_product_ids' => [$product->getKey()],
            'more_url' => route('catalog.products.index'),
        ]);
    }

    /** @return array{0: ?int, 1: ?int} */
    private function priceRange(string $text): array
    {
        $amount = null;
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(trieu|tr)/', $text, $match)) {
            $amount = (int) round((float) str_replace(',', '.', $match[1]) * 1_000_000);
        } elseif (preg_match('/(\d+)\s*(nghin|k)/', $text, $match)) {
            $amount = (int) $match[1] * 1_000;
        }

        if ($amount === null) {
            return [null, null];
        }

        if (str_contains($text, 'tren') || str_contains($text, 'tu ')) {
            return [$amount, null];
        }

        return [null, $amount];
    }
}
