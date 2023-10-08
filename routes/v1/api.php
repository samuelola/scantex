<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::group([
//     'middleware' => 'api',
//     'prefix' => 'auth'

// ], function ($router) {
//     Route::post('login', 'App\Http\Controllers\AuthController@login');
//     Route::post('register', 'App\Http\Controllers\AuthController@register');
//     Route::post('logout', 'App\Http\Controllers\AuthController@logout');
//     Route::post('refresh', 'App\Http\Controllers\AuthController@refresh');
//     Route::get('user-profile', 'App\Http\Controllers\AuthController@userProfile');
// });

//
// Route::get('prize/{prize}', 'App\Http\Controllers\PrizeController@show');
// Route::delete('prize/{prize}', 'App\Http\Controllers\PrizeController@delete');

// Auth
Route::prefix('/auth')->group(function(){
    Route::post('login', 'App\Http\Controllers\AuthController@login'); //done
    Route::get('vendor', 'App\Http\Controllers\WinController@getVendor'); //done
    Route::get('logout', 'App\Http\Controllers\AuthController@logout'); //done
    Route::post('register', 'App\Http\Controllers\AuthController@register'); //done
    Route::post('import', 'App\Http\Controllers\AuthController@importUser');

    Route::post('edit_admin_vendor', 'App\Http\Controllers\AuthController@AdminVendor');//new
    Route::post('deactivate_admin_vendor', 'App\Http\Controllers\AuthController@deactivateAdminVendor');//new

    Route::post('edit_admin_vendor_prize', 'App\Http\Controllers\AuthController@editPrizeAdminVendorPrize');//new


    Route::get('admin_all_vendors', 'App\Http\Controllers\AuthController@adminSeeAllVendors'); //new

    Route::get('vendor_redemtion_left', 'App\Http\Controllers\AuthController@vendorRedemtionLeft'); //new

    Route::get('all_admin_prize/{admin_id}', 'App\Http\Controllers\AuthController@allAdminPrize'); //new


});
// Route::delete('/prize/{id}','App\Http\Controllers\PrizeController@delete');
// Prize
Route::prefix('/prize')->group(function(){
    Route::get('', 'App\Http\Controllers\PrizeController@index');//done
    Route::post('', 'App\Http\Controllers\PrizeController@create'); //done
    Route::delete('{id}', 'App\Http\Controllers\PrizeController@delete');
    Route::post('random', 'App\Http\Controllers\PrizeController@random');
    Route::put('redeem/{id}', 'App\Http\Controllers\PrizeController@redeem');
});

// Settings
Route::prefix('/settings')->group(function(){
    Route::get('', 'App\Http\Controllers\SettingsController@index');
    Route::get('sitekey', 'App\Http\Controllers\SettingsController@showSiteKey');
    Route::post('', 'App\Http\Controllers\SettingsController@create');
    Route::post('update', 'App\Http\Controllers\SettingsController@update');
    Route::delete('refreshAuth', 'App\Http\Controllers\SettingsController@refreshAdmin');
    Route::get('{id}', 'App\Http\Controllers\SettingsController@getOne');
});

// Win
Route::prefix('/win')->group(function(){
    Route::get('', 'App\Http\Controllers\WinController@index'); //done
});

// Redeem
Route::prefix('/redeem')->group(function(){
    Route::get('', 'App\Http\Controllers\RedeemController@index');
    Route::get('my-list', 'App\Http\Controllers\RedeemController@my_list');
});

Route::get('/user', 'App\Http\Controllers\WinController@userProfile'); //done
