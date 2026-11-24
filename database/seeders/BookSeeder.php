<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = [
            ['Cien años de soledad', 'Gabriel García Márquez', 1967, 'Novela', 'La historia de la familia Buendía a lo largo de siete generaciones en Macondo.'],
            ['Rayuela', 'Julio Cortázar', 1963, 'Novela', 'Novela de estructura abierta que puede leerse en distintos órdenes.'],
            ['Don Quijote de la Mancha', 'Miguel de Cervantes', 1605, 'Novela', null],
            ['Dune', 'Frank Herbert', 1965, 'Ciencia ficción', 'Intrigas políticas y ecológicas en el planeta desértico Arrakis.'],
            ['Fundación', 'Isaac Asimov', 1951, 'Ciencia ficción', 'La psicohistoria intenta reducir la edad oscura tras la caída del Imperio Galáctico.'],
            ['El Hobbit', 'J. R. R. Tolkien', 1937, 'Fantasía', 'Bilbo Bolsón emprende un viaje para recuperar el tesoro custodiado por el dragón Smaug.'],
            ['Sapiens: De animales a dioses', 'Yuval Noah Harari', 2011, 'Historia', 'Breve historia de la humanidad desde la Edad de Piedra.'],
            ['Clean Code', 'Robert C. Martin', 2008, 'Tecnología', 'Principios y buenas prácticas para escribir código limpio y mantenible.'],
            ['Veinte poemas de amor y una canción desesperada', 'Pablo Neruda', 1924, 'Poesía', null],
        ];

        $categoryIds = Category::pluck('id', 'name');

        foreach ($books as [$title, $author, $year, $category, $description]) {
            Book::firstOrCreate(
                ['title' => $title],
                [
                    'author' => $author,
                    'published_year' => $year,
                    'category_id' => $categoryIds[$category],
                    'description' => $description,
                ],
            );
        }
    }
}
