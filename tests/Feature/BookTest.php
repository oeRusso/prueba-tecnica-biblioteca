<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Ficciones',
            'author' => 'Jorge Luis Borges',
            'description' => 'Colección de cuentos.',
            'published_year' => 1944,
            'category_id' => Category::factory()->create()->id,
        ], $overrides);
    }

    #[Test]
    public function lista_todos_los_libros_con_su_categoria(): void
    {
        Book::factory()->count(3)->create();

        $this->getJson('/books')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'author', 'description', 'published_year', 'category_id', 'category' => ['id', 'name']]],
            ]);
    }

    #[Test]
    public function filtra_los_libros_por_categoria(): void
    {
        $novela = Category::factory()->create(['name' => 'Novela']);
        $poesia = Category::factory()->create(['name' => 'Poesía']);
        Book::factory()->count(2)->for($novela)->create();
        Book::factory()->count(3)->for($poesia)->create();

        $this->getJson("/books?category={$novela->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.category_id', $novela->id)
            ->assertJsonPath('data.1.category_id', $novela->id);
    }

    #[Test]
    public function el_filtro_por_categoria_inexistente_devuelve_422(): void
    {
        $this->getJson('/books?category=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    #[Test]
    public function crea_un_libro(): void
    {
        $payload = $this->validPayload();

        $this->postJson('/books', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Ficciones')
            ->assertJsonPath('data.category.id', $payload['category_id']);

        $this->assertDatabaseHas('books', ['title' => 'Ficciones', 'published_year' => 1944]);
    }

    #[Test]
    public function la_descripcion_es_opcional(): void
    {
        $this->postJson('/books', $this->validPayload(['description' => null]))
            ->assertCreated();
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'título ausente' => [['title' => null], 'title'],
            'título menor a 3 caracteres' => [['title' => 'ab'], 'title'],
            'autor ausente' => [['author' => null], 'author'],
            'año ausente' => [['published_year' => null], 'published_year'],
            'año no numérico' => [['published_year' => 'mil'], 'published_year'],
            'año de 3 dígitos' => [['published_year' => 999], 'published_year'],
            'año futuro' => [['published_year' => (int) date('Y') + 1], 'published_year'],
            'categoría ausente' => [['category_id' => null], 'category_id'],
            'categoría inexistente' => [['category_id' => 999], 'category_id'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPayloads')]
    public function valida_los_datos_al_crear(array $overrides, string $field): void
    {
        $this->postJson('/books', $this->validPayload($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('books', 0);
    }

    #[Test]
    public function muestra_un_libro(): void
    {
        $book = Book::factory()->create();

        $this->getJson("/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $book->id);
    }

    #[Test]
    public function actualiza_un_libro(): void
    {
        $book = Book::factory()->create();
        $payload = $this->validPayload(['title' => 'El Aleph', 'published_year' => 1949]);

        $this->putJson("/books/{$book->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.title', 'El Aleph')
            ->assertJsonPath('data.published_year', 1949);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'El Aleph', 'category_id' => $payload['category_id']]);
    }

    #[Test]
    public function valida_los_datos_al_actualizar(): void
    {
        $book = Book::factory()->create();

        $this->putJson("/books/{$book->id}", $this->validPayload(['title' => 'ab']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    #[Test]
    public function actualizar_un_libro_inexistente_devuelve_404(): void
    {
        $this->putJson('/books/999', $this->validPayload())
            ->assertNotFound()
            ->assertJsonPath('message', 'El libro con ID 999 no existe.');
    }

    #[Test]
    public function elimina_un_libro_existente(): void
    {
        $book = Book::factory()->create();

        $this->deleteJson("/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Libro eliminado correctamente.');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    #[Test]
    public function eliminar_un_libro_inexistente_devuelve_404(): void
    {
        $this->deleteJson('/books/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'El libro con ID 999 no existe.');
    }
}
