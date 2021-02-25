<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With,Authorization, Content-Type, Accept');
header('Access-Control-Expose-Headers: *');

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

Route::post('login', 'AuthController@login');
Route::post('register', 'AuthController@register');

Route::group(['middleware' => 'api.token'], function () {
    Route::get('/user', 'WebController@user');
    Route::get('/videos', 'VideoController@index');
    Route::get('/videos/delete/{slug}', 'VideoController@deleteVideo');
    Route::post('/upload', 'VideoController@upload'); // In: file | Out: file_info
    Route::post('upload_link', 'VideoController@uploadLink');

    Route::post('/videos/save_progress', 'VideoController@saveProgress');

    //Jobs
    Route::post('/transcribe', 'SubtitlesController@transcribe'); // In: slug / id | Out: id
    Route::post('/burnSRT', 'FFMpegController@burnSRT'); // In: slug / id | Out: id
    Route::post('/resize', 'FFMpegController@resize');
    Route::post('/clip', 'FFMpegController@clip');
    Route::post('/clip_resize', 'FFMpegController@saveClipResize');
    Route::post('/progress_bar', 'FFMpegController@addProgressBar');
    Route::post('/add_filter', 'FFMpegController@addFilter');

    //Immediate
    Route::post('/get_frame', 'FFMpegController@getFrame');
    Route::post('/make_gif', 'FFMpegController@makeGIF');

    Route::post('/translate', 'TranslatorController@translate');
    Route::get('/translate/languages', 'TranslatorController@getLanguages');
    
    //Results
    Route::get('/download/{slug}', 'VideoController@download');
    Route::get('/transcribe/{slug}', 'SubtitlesController@getSubtitles'); // In: id | Out: srt
    Route::get('/result/{log_id}', 'VideoController@downloadResult');

    //Payments
    Route::group(['prefix' => 'payment'], function () {
        Route::get('link', 'PaddleController@generate_payment_link');
        Route::get('history', 'PaddleController@get_transactions');
        Route::get('cancel', 'PaddleController@cancel_subscription');
        Route::get('update', 'PaddleController@update_payment');
    });

    //Watermark
    Route::group(['prefix' => 'watermark'], function () {
        Route::get('/', 'WatermarkController@get');
        Route::post('/', 'WatermarkController@set');
        Route::post('/add', 'FFMpegController@watermark');
    });
});

Route::post('webhook', 'WebhookController@post');