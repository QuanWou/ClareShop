<?php

use App\Modules\Admin\Http\Controllers\AdminAppointmentController;
use App\Modules\Admin\Http\Controllers\AdminBlogPostController;
use App\Modules\Admin\Http\Controllers\AdminBlogTaxonomyController;
use App\Modules\Admin\Http\Controllers\AdminCatalogAttributeController;
use App\Modules\Admin\Http\Controllers\AdminCatalogBrandController;
use App\Modules\Admin\Http\Controllers\AdminCatalogCategoryController;
use App\Modules\Admin\Http\Controllers\AdminCatalogProductController;
use App\Modules\Admin\Http\Controllers\AdminDashboardController;
use App\Modules\Admin\Http\Controllers\AdminMediaController;
use App\Modules\Admin\Http\Controllers\AdminOrderController;
use App\Modules\Admin\Http\Controllers\AdminProductReviewController;
use App\Modules\Admin\Http\Controllers\AdminPromotionCodeController;
use App\Modules\Admin\Http\Controllers\AdminReportController;
use App\Modules\Admin\Http\Controllers\AdminUserController;
use App\Modules\Appointments\Http\Controllers\AppointmentController;
use App\Modules\Blog\Http\Controllers\BlogController;
use App\Modules\Cart\Http\Controllers\BuyNowController;
use App\Modules\Cart\Http\Controllers\CartController;
use App\Modules\Catalog\Http\Controllers\CatalogController;
use App\Modules\Catalog\Http\Controllers\CollectionController;
use App\Modules\Catalog\Http\Controllers\HomeController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\ProductReviewController;
use App\Modules\Catalog\Http\Controllers\SearchController;
use App\Modules\Catalog\Http\Controllers\SearchSuggestionController;
use App\Modules\Catalog\Http\Controllers\WishlistController;
use App\Modules\Content\Http\Controllers\AdminSiteContentController;
use App\Modules\Customers\Http\Controllers\AuthenticationController;
use App\Modules\Customers\Http\Controllers\CustomerAccountController;
use App\Modules\Customers\Http\Controllers\CustomerAddressController;
use App\Modules\Customers\Http\Controllers\CustomerOrderController;
use App\Modules\Customers\Http\Controllers\SocialAuthenticationController;
use App\Modules\Orders\Http\Controllers\CheckoutController;
use App\Modules\Orders\Http\Controllers\CheckoutPageController;
use App\Modules\Settings\Http\Controllers\AdminSiteSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('catalog.home');

Route::get('/products', [CatalogController::class, 'index'])->name('catalog.products.index');

Route::get('/collections/{category:slug}', [CollectionController::class, 'show'])
    ->name('catalog.collections.show');

Route::get('/products/{product:slug}', [ProductController::class, 'show'])
    ->name('catalog.products.show');

Route::get('/search', SearchController::class)->name('catalog.search');
Route::get('/search/suggestions', SearchSuggestionController::class)->name('catalog.search.suggestions');
Route::get('/cam-hung', [BlogController::class, 'index'])->name('blog.index');
Route::get('/cam-hung/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/{provider}/redirect', [SocialAuthenticationController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthenticationController::class, 'callback'])->name('social.callback');
    Route::get('/login', [AuthenticationController::class, 'createLogin'])->name('login');
    Route::post('/login', [AuthenticationController::class, 'storeLogin'])->name('login.store');
    Route::get('/register', [AuthenticationController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthenticationController::class, 'storeRegister'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticationController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'active-user'])->group(function (): void {
    Route::post('/wishlist/{product:slug}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/products/{product:slug}/reviews', [ProductReviewController::class, 'store'])->name('catalog.products.reviews.store');
    Route::get('/account', [CustomerAccountController::class, 'show'])->name('account.show');
    Route::patch('/account/profile', [CustomerAccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::patch('/account/password', [CustomerAccountController::class, 'updatePassword'])->name('account.password.update');
    Route::put('/account/default-address', [CustomerAccountController::class, 'updateDefaultAddress'])->name('account.address.update');
    Route::post('/account/addresses', [CustomerAddressController::class, 'store'])->name('account.addresses.store');
    Route::patch('/account/addresses/{address}', [CustomerAddressController::class, 'update'])->name('account.addresses.update');
    Route::patch('/account/addresses/{address}/default', [CustomerAddressController::class, 'setDefault'])->name('account.addresses.default');
    Route::delete('/account/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('account.addresses.destroy');
    Route::delete('/account', [CustomerAccountController::class, 'destroy'])->name('account.destroy');
    Route::get('/account/orders/{order:number}', [CustomerOrderController::class, 'show'])->name('account.orders.show');

    Route::get('/checkout', [CheckoutPageController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutPageController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/orders/{orderNumber}/complete', [CheckoutPageController::class, 'complete'])
        ->middleware('signed')
        ->where('orderNumber', '[A-Z0-9-]+')
        ->name('checkout.complete');

    Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
    Route::post('/checkout/orders', [CheckoutController::class, 'store'])->name('checkout.orders.store');
});

Route::prefix('admin')->as('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/reviews', [AdminProductReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}', [AdminProductReviewController::class, 'update'])->name('reviews.update');

    Route::get('/blog', [AdminBlogPostController::class, 'index'])->name('blog.posts.index');
    Route::get('/blog/create', [AdminBlogPostController::class, 'create'])->name('blog.posts.create');
    Route::post('/blog', [AdminBlogPostController::class, 'store'])->name('blog.posts.store');
    Route::get('/blog/{post}/edit', [AdminBlogPostController::class, 'edit'])->name('blog.posts.edit');
    Route::patch('/blog/{post}', [AdminBlogPostController::class, 'update'])->name('blog.posts.update');
    Route::delete('/blog/{post}', [AdminBlogPostController::class, 'destroy'])->name('blog.posts.destroy');
    Route::get('/blog-taxonomy', [AdminBlogTaxonomyController::class, 'index'])->name('blog.taxonomy.index');
    Route::post('/blog-taxonomy', [AdminBlogTaxonomyController::class, 'store'])->name('blog.taxonomy.store');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::patch('/orders/{order}/payments/{payment}/status', [AdminOrderController::class, 'updatePaymentStatus'])
        ->name('orders.payment-status.update');

    Route::prefix('catalog')->as('catalog.')->scopeBindings()->group(function (): void {
        Route::get('/attributes', [AdminCatalogAttributeController::class, 'index'])->name('attributes.index');
        Route::get('/attributes/create', [AdminCatalogAttributeController::class, 'create'])->name('attributes.create');
        Route::post('/attributes', [AdminCatalogAttributeController::class, 'store'])->name('attributes.store');
        Route::get('/attributes/{attribute}/edit', [AdminCatalogAttributeController::class, 'edit'])->name('attributes.edit');
        Route::patch('/attributes/{attribute}', [AdminCatalogAttributeController::class, 'update'])->name('attributes.update');
        Route::post('/attributes/{attribute}/values', [AdminCatalogAttributeController::class, 'storeValue'])->name('attributes.values.store');
        Route::patch('/attributes/{attribute}/values/{value}', [AdminCatalogAttributeController::class, 'updateValue'])->name('attributes.values.update');
        Route::delete('/attributes/{attribute}/values/{value}', [AdminCatalogAttributeController::class, 'destroyValue'])->name('attributes.values.destroy');

        Route::get('/brands', [AdminCatalogBrandController::class, 'index'])->name('brands.index');
        Route::get('/brands/create', [AdminCatalogBrandController::class, 'create'])->name('brands.create');
        Route::post('/brands', [AdminCatalogBrandController::class, 'store'])->name('brands.store');
        Route::get('/brands/{brand}/edit', [AdminCatalogBrandController::class, 'edit'])->name('brands.edit');
        Route::patch('/brands/{brand}', [AdminCatalogBrandController::class, 'update'])->name('brands.update');
        Route::delete('/brands/{brand}', [AdminCatalogBrandController::class, 'destroy'])->name('brands.destroy');

        Route::get('/categories', [AdminCatalogCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [AdminCatalogCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [AdminCatalogCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [AdminCatalogCategoryController::class, 'edit'])->name('categories.edit');
        Route::patch('/categories/{category}', [AdminCatalogCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminCatalogCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/products', [AdminCatalogProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AdminCatalogProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AdminCatalogProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminCatalogProductController::class, 'edit'])->name('products.edit');
        Route::patch('/products/{product}', [AdminCatalogProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminCatalogProductController::class, 'destroy'])->name('products.destroy');
        Route::patch('/products/{product}/restore', [AdminCatalogProductController::class, 'restore'])->withTrashed()->name('products.restore');
        Route::post('/products/{product}/variants', [AdminCatalogProductController::class, 'storeVariant'])->name('products.variants.store');
        Route::patch('/products/{product}/variants/{variant}', [AdminCatalogProductController::class, 'updateVariant'])->name('products.variants.update');
        Route::delete('/products/{product}/variants/{variant}', [AdminCatalogProductController::class, 'destroyVariant'])->name('products.variants.destroy');
        Route::patch('/products/{product}/variants/{variant}/restore', [AdminCatalogProductController::class, 'restoreVariant'])->withTrashed()->name('products.variants.restore');
        Route::post('/products/{product}/images', [AdminCatalogProductController::class, 'storeImage'])->name('products.images.store');
        Route::delete('/products/{product}/images/{image}', [AdminCatalogProductController::class, 'destroyImage'])->name('products.images.destroy');
    });

    Route::get('/promotions', [AdminPromotionCodeController::class, 'index'])->name('promotions.index');
    Route::get('/promotions/create', [AdminPromotionCodeController::class, 'create'])->name('promotions.create');
    Route::post('/promotions', [AdminPromotionCodeController::class, 'store'])->name('promotions.store');
    Route::get('/promotions/{promotion}/edit', [AdminPromotionCodeController::class, 'edit'])->name('promotions.edit');
    Route::patch('/promotions/{promotion}', [AdminPromotionCodeController::class, 'update'])->name('promotions.update');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('/content', [AdminSiteContentController::class, 'edit'])->name('content.edit');
    Route::patch('/content', [AdminSiteContentController::class, 'update'])->name('content.update');
    Route::get('/settings', [AdminSiteSettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [AdminSiteSettingsController::class, 'update'])->name('settings.update');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{asset}', [AdminMediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/appointments', [AdminAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{appointment}', [AdminAppointmentController::class, 'show'])->name('appointments.show');
    Route::patch('/appointments/{appointment}/status', [AdminAppointmentController::class, 'updateStatus'])
        ->name('appointments.status.update');
});

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::post('/buy-now', BuyNowController::class)->name('buy-now');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

Route::get('/appointments', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
Route::get('/appointments/{appointmentNumber}/complete', [AppointmentController::class, 'complete'])
    ->middleware('signed')
    ->where('appointmentNumber', '[A-Z0-9-]+')
    ->name('appointments.complete');
