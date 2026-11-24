<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    /**
     * GET /categories
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * POST /categories
     * El nombre debe ser único (validado en StoreCategoryRequest).
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /categories/{category}
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('books'));
    }

    /**
     * PUT /categories/{category}
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category->loadCount('books'));
    }

    /**
     * DELETE /categories/{category}
     * No permite eliminar una categoría que tenga libros asociados.
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->books()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la categoría porque tiene libros asociados.',
                'books_count' => $category->books()->count(),
            ], Response::HTTP_CONFLICT);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada correctamente.',
        ], Response::HTTP_OK);
    }
}
