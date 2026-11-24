<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    /**
     * GET /books  |  GET /books?category={category_id}
     * Lista los libros, opcionalmente filtrados por categoría.
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $books = Book::query()
            ->with('category')
            ->when(
                $request->filled('category'),
                fn ($query) => $query->ofCategory($request->integer('category')),
            )
            ->orderBy('title')
            ->get();

        return BookResource::collection($books);
    }

    /**
     * POST /books
     * Crea un nuevo libro asociado a una categoría.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($request->validated());

        return (new BookResource($book->load('category')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /books/{book}
     * Muestra un libro. Si no existe, el route model binding responde 404.
     */
    public function show(Book $book): BookResource
    {
        return new BookResource($book->load('category'));
    }

    /**
     * PUT /books/{book}
     * Actualiza la información de un libro existente.
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $book->update($request->validated());

        return new BookResource($book->load('category'));
    }

    /**
     * DELETE /books/{id}
     * Verifica explícitamente que el libro exista antes de eliminarlo.
     */
    public function destroy(int $id): JsonResponse
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'message' => "El libro con ID {$id} no existe.",
            ], Response::HTTP_NOT_FOUND);
        }

        $book->delete();

        return response()->json([
            'message' => 'Libro eliminado correctamente.',
        ], Response::HTTP_OK);
    }
}
