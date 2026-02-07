<?php return array (
  'openapi' => '3.1.0',
  'info' => 
  array (
    'title' => 'Sonata API',
    'version' => '1.0.0',
    'description' => 'Автоматически сгенерированная документация',
  ),
  'servers' => 
  array (
    0 => 
    array (
      'url' => 'http://localhost:8000',
      'description' => 'Текущий сервер',
    ),
  ),
  'tags' => 
  array (
    0 => 
    array (
      'name' => 'Пользователи',
      'description' => 'Работа с юзерами',
    ),
    1 => 
    array (
      'name' => 'Default',
      'description' => 'Базовые операции',
    ),
    2 => 
    array (
      'name' => 'Swagger (Документация)',
      'description' => 'Методы работы над документацией',
    ),
  ),
  'paths' => 
  array (
    '/api/users' => 
    array (
      'get' => 
      array (
        'summary' => 'Регистрация пользователя',
        'operationId' => 'listUsers',
        'tags' => 
        array (
          0 => 'Пользователи',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'array',
                  'items' => 
                  array (
                    '$ref' => '#/components/schemas/UserResponse',
                  ),
                ),
              ),
            ),
          ),
        ),
        'description' => 'Метод, позволяющий регать юзера',
      ),
    ),
    '/api/login' => 
    array (
      'post' => 
      array (
        'summary' => 'login',
        'operationId' => 'login',
        'tags' => 
        array (
          0 => 'Default',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/api/profile' => 
    array (
      'get' => 
      array (
        'summary' => 'profile',
        'operationId' => 'profile',
        'tags' => 
        array (
          0 => 'Default',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/api/reg' => 
    array (
      'post' => 
      array (
        'summary' => 'createAccount',
        'operationId' => 'createAccount',
        'tags' => 
        array (
          0 => 'Default',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/api/refresh' => 
    array (
      'post' => 
      array (
        'summary' => 'refresh',
        'operationId' => 'refresh',
        'tags' => 
        array (
          0 => 'Default',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/api/logout' => 
    array (
      'post' => 
      array (
        'summary' => 'logout',
        'operationId' => 'logout',
        'tags' => 
        array (
          0 => 'Default',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/api/products/list' => 
    array (
      'get' => 
      array (
        'summary' => 'list',
        'operationId' => 'list',
        'tags' => 
        array (
          0 => 'Default',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'array',
                  'items' => 
                  array (
                    'type' => 'object',
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/openapi.json' => 
    array (
      'get' => 
      array (
        'summary' => 'Получение документации',
        'operationId' => 'openapiSpec',
        'tags' => 
        array (
          0 => 'Swagger (Документация)',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'array',
                  'items' => 
                  array (
                    'type' => 'object',
                  ),
                ),
              ),
            ),
          ),
        ),
        'description' => 'Метод, позволяющий получить документацию для отображения',
      ),
    ),
    '/' => 
    array (
      'get' => 
      array (
        'summary' => 'test',
        'operationId' => 'test',
        'tags' => 
        array (
          0 => 'Default',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/about' => 
    array (
      'get' => 
      array (
        'summary' => 'about',
        'operationId' => 'about',
        'tags' => 
        array (
          0 => 'Default',
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Успешный ответ',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  ),
  'components' => 
  array (
    'schemas' => 
    array (
      'UserResponse' => 
      array (
        'type' => 'object',
        'properties' => 
        array (
          'id' => 
          array (
            'type' => 'integer',
            'example' => 1,
            'description' => '@OA\\Generator::UNDEFINED🙈',
          ),
          'name' => 
          array (
            'type' => 'string',
            'example' => 'Александр',
            'description' => '@OA\\Generator::UNDEFINED🙈',
          ),
          'email' => 
          array (
            'type' => 'string',
            'example' => 'alex@example.com',
            'description' => '@OA\\Generator::UNDEFINED🙈',
          ),
        ),
      ),
    ),
  ),
);