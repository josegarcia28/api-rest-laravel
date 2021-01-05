<?php
use App\Http\Middleware\ApiAuthMiddleware;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {
    return view('welcome');
});
Route::get('/prueba', function () {
    return '<h2>Pagina de prueba</h2>';
});
Route::get('/{nombre?}', function ($nombre = null) {
    $texto = '<h2>Pagina de prueba</h2>';
    $texto .= 'Nombre: '.$nombre;

    return view('prueba', array(
        'texto' => $texto
    ));

});
Route::get('/pruebas/animales', 'PruebasController@index');
Route::get('/pruebas/test-orm', 'PruebasController@testOrm');

//Rutas de pruebas Api
// Route::get('/usuario/pruebas', 'UserController@pruebas');
// Route::get('/categoria/pruebas', 'CategoryController@pruebas');
// Route::get('/entrada/pruebas', 'PostController@pruebas');

// rutas de usuario api
Route::post('/api/register', 'UserController@register');
Route::post('/api/login', 'UserController@login');
Route::put('/api/user/update', 'UserController@update');
Route::post('/api/user/upload','UserController@upload')->middleware(ApiAuthMiddleware::class);
Route::get('/api/user/avatar/{filename}', 'UserController@getImagen');
Route::get('/api/user/detail/{id}', 'UserController@detail');

// rutas de category
Route::resource('/api/category', 'CategoryController');

// Rutas del controlador de entradas

Route::resource('/api/post', 'PostController');
Route::post('/api/post/upload','PostController@upload');
Route::get('/api/post/image/{filename}', 'PostController@getImagen');
Route::get('/api/post/category/{id}', 'PostController@getPostsByCategory');
Route::get('/api/post/user/{id}', 'PostController@getPostsByUser');
