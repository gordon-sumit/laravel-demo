<?php

use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', function () {
    return view('welcome');
});

//register
Route::post('/user', ['App\Http\Controllers\Controller', 'register']);
Route::post('/user/confirm-register', ['App\Http\Controllers\Controller', 'confirm']);

//login
Route::post('/user/login', ['App\Http\Controllers\Controller', 'login']);
Route::post('/user/auth/respondToAuthChallenge', ['App\Http\Controllers\Controller', 'confirmLogin']);
Route::post('/user/auth/verifySoftwareToken', ['App\Http\Controllers\Controller', 'verifySoftwareToken']);

Route::get('/test',function (){
   $url = 'otpauth: //totp/vercel-demo-app:cexew86940@inikale.com?secret=U7DFO33CXNH3K2RLL727WQHOFQOZRAC6E2FHXWJNNAM5UMPAVFXQ&issuer=vercel-demo-app';
    $qrCode = QrCode::size(300)->format('png')->generate($url);
    $qrCodeImage = base64_encode($qrCode);

    return $qrCodeImage;

//    return response($qrCode)
//        ->header('Content-type', 'image/png');
});
