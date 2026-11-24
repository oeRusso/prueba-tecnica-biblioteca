<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
| Rutas de la API. Se registran sin prefijo (apiPrefix: '' en bootstrap/app.php)
| para exponer exactamente los endpoints del enunciado: /books, /books/{id}, etc.
*/

// GET /books (?category={id}), POST /books, GET|PUT /books/{id}, DELETE /books/{id}
Route::apiResource('books', BookController::class)
    ->where(['book' => '[0-9]+']);

// CRUD de categorías (nombre único y bloqueo de eliminación con libros asociados)
Route::apiResource('categories', CategoryController::class)
    ->where(['category' => '[0-9]+']);
