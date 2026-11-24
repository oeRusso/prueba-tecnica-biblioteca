<?php

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Es una API JSON: todas las respuestas de error se devuelven en JSON.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);

        // 422: errores de validación de los Form Requests, con mensaje general en español.
        $exceptions->render(fn (ValidationException $e) => response()->json([
            'message' => 'Los datos enviados no son válidos.',
            'errors' => $e->errors(),
        ], $e->status));

        // 404: recurso (modelo) o ruta inexistente.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                $resource = match ($previous->getModel()) {
                    Book::class => 'El libro',
                    Category::class => 'La categoría',
                    default => 'El recurso',
                };
                $id = implode(', ', $previous->getIds());

                return response()->json([
                    'message' => "{$resource} con ID {$id} no existe.",
                ], 404);
            }

            return response()->json([
                'message' => 'Ruta no encontrada.',
            ], 404);
        });

        // 405: método HTTP no permitido para la ruta.
        $exceptions->render(fn (MethodNotAllowedHttpException $e) => response()->json([
            'message' => 'Método HTTP no permitido para esta ruta.',
        ], 405));

        // 409: violación de integridad referencial (respaldo de la regla de negocio en BD).
        $exceptions->render(function (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'La operación viola una restricción de integridad de la base de datos.',
                ], 409);
            }
        });
    })->create();
