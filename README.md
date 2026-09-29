<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Authentication

La API utiliza Laravel Sanctum con tokens personales persistidos en la colección MongoDB `personal_access_tokens`. Envía el token recibido en el login mediante el encabezado `Authorization: Bearer <token>`.

Endpoints disponibles:

- `POST /api/auth/login`: valida credenciales y emite un token Sanctum.
- `GET /api/auth/me`: devuelve el usuario del token actual.
- `POST /api/auth/logout`: revoca únicamente el token actual.
- `POST /api/auth/forgot-password`: envía un enlace de recuperación sin revelar si la cuenta existe.
- `POST /api/auth/reset-password`: consume un token de un solo uso y revoca las sesiones existentes.

Credencial local creada por el seeder del examen (solo desarrollo): `admin@example.com` / `Admin123!`.

Ejemplo de login:

```bash
curl --request POST http://localhost:8000/api/auth/login \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{"email":"admin@example.com","password":"Admin123!"}'
```

Ejemplo de una petición autenticada:

```bash
curl http://localhost:8000/api/auth/me \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer TOKEN_DEVUELTO_POR_LOGIN'
```

### Recuperación de contraseña y correo

Los tokens de recuperación se guardan como hashes SHA-256 en la colección `password_reset_tokens`, caducan después de 30 minutos y solo se aceptan una vez. El enlace enviado apunta a `FRONTEND_URL`, que debe configurarse en `.env` (por ejemplo, `http://localhost:4200`).

Las credenciales SMTP se configuran con las variables `MAIL_*`. En desarrollo puede usarse `MAIL_MAILER=log`; tras solicitar la recuperación, el mensaje y su enlace aparecerán en `storage/logs/laravel.log`. La contraseña existente nunca se incluye en el correo.

### OpenAPI / Swagger

La interfaz está disponible en `http://localhost:8000/api/documentation` y contiene el esquema Bearer para usar el botón **Authorize**. Para regenerar la especificación después de cambiar endpoints o atributos OpenAPI:

```bash
php artisan l5-swagger:generate
```

Swagger se controla mediante `L5_SWAGGER_ENABLED`. En desarrollo utiliza `true`; en producción configura `false`. Si la variable no existe, queda habilitado fuera de producción y deshabilitado automáticamente cuando `APP_ENV=production`. La interfaz, especificación JSON, assets y callback responden 404 cuando está apagado.

### Tests de autenticación

Los tests usan MongoDB y fuerzan la base aislada `products_crud_test_testing`; no ejecutan migraciones SQL ni limpian colecciones completas. MongoDB debe estar disponible usando `DB_URI` antes de ejecutar:

```bash
php artisan test
```

## Users

Todos los endpoints requieren `Authorization: Bearer <token>`:

- `GET /api/users`: listado paginado con `page`, `limit`, `search` e `is_active`.
- `POST /api/users`: alta mediante `multipart/form-data`.
- `GET /api/users/{id}`: detalle con perfiles resueltos.
- `POST /api/users/{id}`: actualización parcial multipart; todos los campos son opcionales y permite cambiar foto.
- `PATCH /api/users/{id}/status`: activa o desactiva un usuario.
- `DELETE /api/users/{id}`: eliminación física.
- `GET /api/users/export/pdf`: descarga `users-YYYY-MM-DD.pdf`.
- `GET /api/users/export/excel`: descarga `users-YYYY-MM-DD.xlsx`.

El backend genera el código `USR-XXXXXX`. La foto es obligatoria al crear, se guarda en `storage/app/public/users/{code}` y MongoDB conserva únicamente su path relativo. Ejecuta una vez `php artisan storage:link` para exponer el disco público. `profile_ids[]` acepta IDs existentes de la colección `profiles` y se almacena como un array nativo de ObjectId.

Ejemplo de creación:

```bash
curl --request POST http://localhost:8000/api/users \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer TOKEN' \
  --form 'name=Juan Pérez' \
  --form 'email=juan@example.com' \
  --form 'phone=+523141234567' \
  --form 'password=Password123!' \
  --form 'password_confirmation=Password123!' \
  --form 'profile_ids[]=ID_DE_PERFIL' \
  --form 'photo=@/ruta/a/photo.png'
```

Para actualizar utiliza `POST /api/users/{id}` con `multipart/form-data`. Solo se modifican los campos enviados; los demás conservan su valor. Al desactivar un usuario se revocan sus tokens y Auth rechaza nuevos inicios de sesión con HTTP 403.

La creación, edición, cambio de estado y eliminación de usuarios se registra en `audit_logs`. La bitácora nunca almacena contraseñas ni hashes.

## Products

Todos los endpoints requieren un token Bearer y acceso a la sección `products`:

- `GET /api/products`: listado paginado con `page`, `limit` y `search`.
- `POST /api/products`: crea un producto con código automático.
- `GET /api/products/{id}`: muestra el detalle.
- `PUT /api/products/{id}`: actualiza parcialmente nombre, marca o precio.
- `DELETE /api/products/{id}`: elimina físicamente un producto.
- `GET /api/products/export/pdf`: descarga `products-YYYY-MM-DD.pdf`.
- `GET /api/products/export/excel`: descarga `products-YYYY-MM-DD.xlsx`.

El precio acepta de `0` a `999.99` y máximo dos decimales. La foto es opcional, acepta JPEG, JPG, PNG o WEBP de hasta 5 MB y se guarda en `storage/app/public/products/{code}`. Para editarla mediante `multipart/form-data` utiliza `POST /api/products/{id}`; `PUT` continúa disponible para JSON. Las exportaciones muestran fechas como `DD/MM/YYYY HH:MM`. Crear, editar o eliminar productos genera una entrada en `audit_logs` con el usuario responsable y los valores anteriores y nuevos.

## Profiles

Todos los endpoints requieren un token Bearer y que alguno de los perfiles del usuario contenga la sección `profiles`:

- `GET /api/profiles`: listado paginado con `page`, `limit` y `search`.
- `POST /api/profiles`: crea un perfil y genera su código automáticamente.
- `GET /api/profiles/{id}`: muestra código, nombre, secciones y fechas.
- `PUT /api/profiles/{id}`: actualiza nombre o secciones.
- `DELETE /api/profiles/{id}`: elimina un perfil si no está asignado a usuarios.
- `GET /api/profiles/export/pdf`: descarga `profiles-YYYY-MM-DD.pdf`.
- `GET /api/profiles/export/excel`: descarga `profiles-YYYY-MM-DD.xlsx`.

Las secciones base son `products`, `users` y `profiles`, y pueden agregarse secciones personalizadas desde el módulo Sections. El middleware `section:<slug>` verifica los perfiles relacionados con el usuario autenticado. Las rutas de Users usan `section:users`, las de Profiles usan `section:profiles` y el módulo de Products puede protegerse con `section:products`.

## Sections

Las rutas requieren un token Bearer y acceso a `profiles`:

- `GET /api/sections`: devuelve el catálogo completo para asignarlo a perfiles.
- `POST /api/sections`: crea una sección con código automático `SEC-XXXXXX`; recibe `name` y un `slug` opcional.
- `DELETE /api/sections/{id}`: elimina una sección personalizada que no esté asignada.

Las secciones base `products`, `users` y `profiles` son creadas por `SectionSeeder` como secciones del sistema y no se pueden eliminar. Para ejecutar el seeder:

```bash
php artisan db:seed --class=SectionSeeder
```

El seeder crea o actualiza estos perfiles sin duplicarlos:

- `PRF-000001`: Administrador, con acceso a `products`, `users` y `profiles`.
- `PRF-000002`: Operador de productos, con acceso únicamente a `products`.
- `PRF-000003`: Gestor de usuarios, con acceso únicamente a `users`.

Para ejecutar solamente este seeder:

```bash
php artisan db:seed --class=ProfileSeeder
```

La creación, edición y eliminación de perfiles también se registra en `audit_logs`, incluyendo las secciones anteriores y nuevas.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
