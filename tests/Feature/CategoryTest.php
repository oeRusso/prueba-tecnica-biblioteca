<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lista_las_categorias_con_cantidad_de_libros(): void
    {
        $category = Category::factory()->create();
        Book::factory()->count(2)->for($category)->create();

        $this->getJson('/categories')
            ->assertOk()
            ->assertJsonPath('data.0.books_count', 2);
    }

    #[Test]
    public function crea_una_categoria(): void
    {
        $this->postJson('/categories', ['name' => 'Ensayo'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ensayo');

        $this->assertDatabaseHas('categories', ['name' => 'Ensayo']);
    }

    #[Test]
    public function el_nombre_es_obligatorio(): void
    {
        $this->postJson('/categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function el_nombre_debe_ser_unico(): void
    {
        Category::factory()->create(['name' => 'Novela']);

        $this->postJson('/categories', ['name' => 'Novela'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Ya existe una categoría con ese nombre.']);
    }

    #[Test]
    public function actualizar_conservando_el_mismo_nombre_es_valido(): void
    {
        $category = Category::factory()->create(['name' => 'Novela']);

        $this->putJson("/categories/{$category->id}", ['name' => 'Novela'])
            ->assertOk();
    }

    #[Test]
    public function no_permite_eliminar_una_categoria_con_libros(): void
    {
        $category = Category::factory()->create();
        Book::factory()->for($category)->create();

        $this->deleteJson("/categories/{$category->id}")
            ->assertConflict()
            ->assertJsonPath('books_count', 1);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    #[Test]
    public function elimina_una_categoria_sin_libros(): void
    {
        $category = Category::factory()->create();

        $this->deleteJson("/categories/{$category->id}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function eliminar_una_categoria_inexistente_devuelve_404(): void
    {
        $this->deleteJson('/categories/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'La categoría con ID 999 no existe.');
    }
}
