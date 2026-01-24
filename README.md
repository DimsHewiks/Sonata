# 🎼 Sonata — минималистичный PHP-фреймворк
Лёгкий, гибкий и расширяемый фреймворк для быстрой разработки API с поддержкой современных практик: атрибуты, DI-контейнер, автоматическая маршрутизация.

# 🚀 Быстрый старт
Требования
- PHP 8.1+
- Composer
- MariaDB / MySQL (опционально)
- docker (всё обернуто в контейнер)
- Установка 
bash:
  
        git clone https://github.com/DimsHewiks/Sonata.git
        cd sonata
        composer install
        cp .env.example .env

- для запуска

        docker-compose build --no-cache
        docker-compose up -d



# 🧩 Основные возможности
1. Автоматическая маршрутизация через атрибуты

        namespace Api\User\Controller;

        use Core\Attributes\Controller;
        use Core\Attributes\Route;
        use Core\Attributes\From;
        use Api\User\Dto\UserParams;
        
        #[Controller(prefix: '/api')]
        class UserController

        #[Route(path: '/users', method: 'GET')]
        public function list(#[From('query')] UserParams $params): array
        {
            return ['data' => $params];
        }
        
   → Маршрут /api/users автоматически зарегистрирован.


2. DI-контейнер (Dependency Injection)
   Все зависимости внедряются автоматически:


        class UserController
        {
            public function __construct(
              private UserRepository $userRepo,
              private NotificationService $notifier
            ) {}
        }
Репозитории и сервисы регистрируются автоматически, если:

- находятся в папках api/, view/, commands/,
  - имя класса заканчивается на Repository или Service.

    1. Работа с данными
       DTO из запроса
       Поддержка источников:

            #[From('query')] → $_GET
            #[From('json')] → JSON-тело
            #[From('formData')] → $_POST + $_FILES
       Пример DTO:

            class UserParams extends \Core\Http\ParamsDTO
              {
              public ?string $name;
              public ?string $email;
              public function validate(): array { /* ... */ }
            }
    Репозитории с доступом к БД


                namespace Api\User\Repository;
            
                use Core\Storage\PDOStorage;
                
                class UserRepository extends PDOStorage
                {
                    public function findById(int $id): ?array
                    {
                    $stmt = $this->getPdo()->prepare("SELECT * FROM users WHERE id = ?");
                }

1. Структура проекта

       .
       ├── api/            # Контроллеры и логика API
       ├── view/           # Веб-контроллеры (если нужны)
       ├── commands/       # Консольные команды
       ├── core/           # Ядро фреймворка
       ├── public/         # Публичные файлы (index.php)
       ├── bootstrap.php   # Инициализация DI и окружения
       └── index.php       # Точка входа
       ⚙️ Конфигурация
       Основные настройки — в .env:


        APP_ENV=dev
        DB_HOST=127.0.0.1
        DB_NAME=sonata
        DB_USER=root
        DB_PASSWORD=
        JWT_SECRET=auto-generated
При первом запуске JWT_SECRET генерируется автоматически.

# 🛠 Разработка
- Все контроллеры должны иметь атрибут #[Controller].
- Для отладки включите APP_ENV=dev — отключается кэширование маршрутов.
- Используйте error_log() или Xdebug для диагностики.
# 📦 Зависимости
- vlucas/phpdotenv — загрузка .env
- (опционально) predis/predis — работа с Redis
- (опционально) symfony/validator — валидация
# 📝 Лицензия
MIT