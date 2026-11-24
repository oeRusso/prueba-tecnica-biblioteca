# Biblioteca API — Prueba técnica Laravel

API REST para gestionar una biblioteca de libros y sus categorías, construida con **Laravel 13** y **PHP 8.5**.

Permite listar, crear, editar y eliminar libros, filtrarlos por categoría y administrar categorías, con validación mediante Form Requests y respuestas/errores HTTP en JSON.

## Requisitos

- PHP >= 8.3 con extensiones `pdo_mysql`, `mbstring`, `xml`, `intl`
- Composer 2
- MySQL 8+ (o MariaDB). Los tests usan SQLite en memoria, sin configuración extra.

## Instalación

```bash
git clone https://github.com/oeRusso/prueba-tecnica-biblioteca.git
cd prueba-tecnica-biblioteca

composer install
cp .env.example .env
php artisan key:generate
```

Crear la base de datos y configurar las credenciales en `.env`:

```sql
CREATE DATABASE biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'biblioteca'@'localhost' IDENTIFIED BY 'biblioteca_pass';
GRANT ALL PRIVILEGES ON biblioteca.* TO 'biblioteca'@'localhost';
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=biblioteca
DB_USERNAME=biblioteca
DB_PASSWORD=biblioteca_pass
```

Ejecutar migraciones con datos de ejemplo y levantar el servidor:

```bash
php artisan migrate --seed
php artisan serve
```

La API queda disponible en `http://127.0.0.1:8000`.

## Tests

```bash
php artisan test
```

Feature tests que cubren el CRUD, el filtro, todas las validaciones y las reglas de negocio (`tests/Feature`).

## Modelo de datos

| Tabla        | Campo            | Tipo                      | Reglas                            |
|--------------|------------------|---------------------------|-----------------------------------|
| `categories` | `id`             | BigInt autoincremental PK |                                   |
|              | `name`           | String                    | Requerido, **único**              |
| `books`      | `id`             | BigInt autoincremental PK |                                   |
|              | `title`          | String                    | Requerido, mínimo 3 caracteres    |
|              | `author`         | String                    | Requerido                         |
|              | `description`    | Text                      | Opcional                          |
|              | `published_year` | SmallInt unsigned         | Requerido, año válido (1000–actual) |
|              | `category_id`    | BigInt FK → `categories`  | Requerido, debe existir           |

Relaciones Eloquent: `Category hasMany Book` y `Book belongsTo Category`. La FK usa `restrictOnDelete`, así que la base de datos también impide borrar una categoría con libros.

## Endpoints

Todas las respuestas son JSON. Se recomienda enviar `Accept: application/json` y `Content-Type: application/json`.

### Libros

| Método   | Ruta                           | Descripción                   | Éxito | Errores    |
|----------|--------------------------------|-------------------------------|-------|------------|
| `GET`    | `/books`                       | Lista todos los libros        | 200   |            |
| `GET`    | `/books?category={category_id}`| Filtra libros por categoría   | 200   | 422        |
| `POST`   | `/books`                       | Crea un libro                 | 201   | 422        |
| `GET`    | `/books/{id}`                  | Muestra un libro              | 200   | 404        |
| `PUT`    | `/books/{id}`                  | Actualiza un libro            | 200   | 404, 422   |
| `DELETE` | `/books/{id}`                  | Elimina un libro              | 200   | 404        |

### Categorías

| Método   | Ruta                | Descripción                                  | Éxito | Errores       |
|----------|---------------------|----------------------------------------------|-------|---------------|
| `GET`    | `/categories`       | Lista categorías con cantidad de libros      | 200   |               |
| `POST`   | `/categories`       | Crea una categoría (nombre único)            | 201   | 422           |
| `GET`    | `/categories/{id}`  | Muestra una categoría                        | 200   | 404           |
| `PUT`    | `/categories/{id}`  | Actualiza una categoría                      | 200   | 404, 422      |
| `DELETE` | `/categories/{id}`  | Elimina una categoría **sin libros**         | 200   | 404, 409      |

### Ejemplos

```bash
# Listar y filtrar
curl http://127.0.0.1:8000/books
curl "http://127.0.0.1:8000/books?category=1"

# Crear
curl -X POST http://127.0.0.1:8000/books \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"title":"El Aleph","author":"Jorge Luis Borges","published_year":1949,"category_id":1}'

# Actualizar
curl -X PUT http://127.0.0.1:8000/books/1 \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"title":"Cien años de soledad","author":"Gabriel García Márquez","published_year":1967,"category_id":1}'

# Eliminar
curl -X DELETE http://127.0.0.1:8000/books/1
```

Respuesta de creación (`201 Created`):

```json
{
  "data": {
    "id": 10,
    "title": "El Aleph",
    "author": "Jorge Luis Borges",
    "description": null,
    "published_year": 1949,
    "category_id": 1,
    "category": { "id": 1, "name": "Novela" },
    "created_at": "2026-11-24T22:26:50.000000Z",
    "updated_at": "2026-11-24T22:26:50.000000Z"
  }
}
```

Error de validación (`422 Unprocessable Content`):

```json
{
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "title": ["El título debe tener al menos 3 caracteres."],
    "category_id": ["La categoría seleccionada no existe."]
  }
}
```

Recurso inexistente (`404 Not Found`):

```json
{ "message": "El libro con ID 999 no existe." }
```

Categoría con libros (`409 Conflict`):

```json
{ "message": "No se puede eliminar la categoría porque tiene libros asociados.", "books_count": 3 }
```

## Arquitectura

```
app/
├── Http/
│   ├── Controllers/   BookController, CategoryController
│   ├── Requests/      StoreBookRequest, UpdateBookRequest, IndexBookRequest,
│   │                  StoreCategoryRequest, UpdateCategoryRequest
│   └── Resources/     BookResource, CategoryResource
└── Models/            Book, Category
bootstrap/app.php      Registro de rutas API sin prefijo y manejo centralizado de errores JSON
database/
├── migrations/        create_categories_table, create_books_table
├── factories/         BookFactory, CategoryFactory
└── seeders/           CategorySeeder, BookSeeder
routes/api.php         Rutas /books y /categories
tests/Feature/         BookTest, CategoryTest
```

- **MVC**: los modelos encapsulan relaciones y el scope `ofCategory`. Los controladores coordinan el flujo y los API Resources definen la representación JSON.
- **Validación**: toda la validación vive en Form Requests, con mensajes en español.
- **Reglas de negocio**:
  - `DELETE /books/{id}` comprueba explícitamente que el libro exista antes de eliminarlo (404 si no existe).
  - `DELETE /categories/{id}` responde 409 si la categoría tiene libros asociados.
- **Errores HTTP**: `bootstrap/app.php` centraliza las respuestas JSON para 404 (modelo o ruta), 405, 409 (integridad referencial) y 422.
