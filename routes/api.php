<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\categories\CategoriesController;
use App\Http\Controllers\admin\categories\AdminCategoriesController;
use App\Http\Controllers\admin\product\AdminProductController;
use App\Http\Controllers\admin\users\AdminUserController;
use App\Http\Controllers\admin\profile\AdminProfileController;
use App\Http\Controllers\products\ProductController;
use App\Http\Controllers\users\cart\CartController;
use App\Http\Controllers\users\wishlist\WishlistController;
use App\Http\Controllers\users\profile\UserProfileController;
use App\Http\Controllers\country\CountryController;
use App\Http\Controllers\deal\DealController;
use App\Http\Controllers\coupon\CouponController;
use App\Http\Controllers\payments\stripe\StripeController;
use App\Http\Controllers\payments\webhook\StripeWebhookController;
Use App\Http\Controllers\users\orders\UserOrderController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


//*************************AUTHENTICATED  CONTROLLER CONTROLLER **************************//
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    route::post('/apply-coupon',[CouponController::class,'applyCoupon']);
    Route::get('/coupons/{id?}',[CouponController::class,'getCoupon']);

    //**************************USER CART FUNCTION *********************//
    Route::post('/add-to-cart',[CartController::class,'addTocart']);
    Route::get('/user-cart-products/{userId}',[CartController::class,'UserCartProducts']);
    Route::post('/increase-decrease-quantity',[CartController::class,'updateCartQuantity']);   
    Route::post('/remove-cart-item',[CartController::class,'removeUserCartItems']); 
    Route::get('/count-cart-item',[CartController::class,'CartItemCount']);

    //****************************USER WISHLIST ROUTES****************************//
    Route::post('/user/add/wishlist',[WishlistController::class,"addWishlist"]);
    Route::get('/user/wishlists',[WishlistController::class,'wishlists']);
    Route::post('/remove-wishlist-item',[WishlistController::class,'removeUserWishlistItem']);
    //**************************USER PROFILE ROUTE************************//
    Route::post('/user/change/profile',[UserProfileController::class,'changeProfileImage']);
    Route::get('/user/profile/image',[UserProfileController::class,'userProfileImage']);
    Route::get('/user/profile',[UserProfileController::class,'userProfile']);
    Route::post('/user/update-profile',[UserProfileController::class,'UserUpdateProfile']);

    //*********************************USER ORDERS ROUTES************************** //
    Route::get('/order-status',[UserOrderController::class,'orderStatus']);
    Route::get('/user-orders',[UserOrderController::class,'userOrders']);
    //******************************STRIPE PAYMENTS ROUTES*****************************//
    Route::post('/create-checkout-session', [StripeController::class, 'createCheckoutSession']);

});
    //*********************************STRIPE WEBHOOK ROUTES**************************//
    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
    //*********************************END STRIPE WEBHOOK ROUTES**************************//


//****************************ADMIN ROUTE *************************//
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    //**************************CATEGORIES FUNCTION *********************//
    Route::post('/add-category/{id?}',[AdminCategoriesController::class,'addCategory']);
    Route::get('/remove-category/{id}',[AdminCategoriesController::class,'removeCategory']);
    Route::get('/categories',[AdminCategoriesController::class,'getCategories']);
    Route::post('/category-status-update/{id?}',[AdminCategoriesController::class,'updateCategoryStatus']);
    //**************************SUB CATEGORIES FUNCTION *********************//
    Route::post('/add-subcategory/{id?}',[AdminCategoriesController::class,'addSubCategory']);
    Route::get('/remove-sub-category/{id?}',[AdminCategoriesController::class,'removeSubCategory']);
    Route::get('/sub-categories',[AdminCategoriesController::class,'SubCategories']);
    Route::post('/subcategory-status-update/{id?}',[AdminCategoriesController::class,'updateSubcategoryStatus']);
  
    //**************************PRODUCTS FUNCTION *********************//
    Route::post('/add-product/{id?}',[AdminProductController::class,'addProduct']);
    Route::get('/get-products',[AdminProductController::class,'products']);
    Route::get('/remove-product/{id?}',[AdminProductController::class,'removeProduct']);

    //**************************DEAL FUNCTION *********************//
     Route::post('/create-deal',[DealController::class,'createDeal']);
    //**************************COUPON FUNCTION *********************//
    Route::post('/create-coupon',[CouponController::class,'createCoupon']);
    Route::post('/create-deal', [DealController::class, 'createDeal']);
    Route::post('/create-coupon', [CouponController::class, 'createCoupon']);

    //***************************Users FUNCTION **************************//
    Route::get('/users',[AdminUserController::class,'users']);

    // *********************************ADMIN PROFILE ***************//
    Route::post('/admin-profile',[AdminProfileController::class,'updateProfileImage']);
    Route::get('/get-profile',[AdminProfileController::class,'profile']);
    Route::post('/admin-change-password',[AdminProfileController::class,'changePassword']);
    Route::post('/admin-update-profile',[AdminProfileController::class,'updateProfile']);

});

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::get('check-email-verification/{userId}', [AuthController::class, 'checkEmailVerification']);
//*************************AUTH CONTROLLER **************************//
Route::post('/sign-up',[AuthController::class,'signUp']);
Route::post('/login',[AuthController::class,'login']);

//*************************WEBSITE DEAL CONTROLLER **************************//
Route::get('/current-deals',[DealController::class,'getCurrentDeals']);

//*************************WEBSITE COUNTRY CONTROLLER **************************//
Route::get('/countries',[CountryController::class,'countries']);

//*************************WEBSITE  CATEGORIES CONTROLLER **************************//
Route::get('/fetch-sub-category/{id}',[CategoriesController::class,'fetchSubCategory']);
Route::get('/fetch-categories',[CategoriesController::class,'Categories']);
Route::get('/search-categories/{query}',[CategoriesController::class,'searchCategories']);
Route::get('/search-subcategories/{query}/{catId}', [CategoriesController::class, 'searchSubCategories']);

//*************************WEBSITE PRODUCT CONTROLLER **************************//
Route::get('/latest-products',[ProductController::class,'latestProducts']);
Route::get('/fetch-product/{id}',[ProductController::class,'fetchProduct']);
Route::get('/product-details/{slug?}/{id?}',[ProductController::class,'productDetails']);
Route::get('/products-subcategory-price-range/{id}/{min}/{max}', [ProductController::class, 'filterPrice']); 
Route::get('/products-price-range/{min}/{max}', [ProductController::class, 'productsFilterPrice']);
Route::get('/products',[ProductController::class,'products']);
