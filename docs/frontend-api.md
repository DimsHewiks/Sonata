# Frontend API Guide

Документ описывает актуальный контракт фронта с backend в этом проекте.

## 1. База API

- Base URL: `http://localhost:8080`
- Префикс API: `/api`
- Полные URL: `http://localhost:8080/api/...`

## 2. Авторизация (JWT + refresh cookie)

### 2.1 Логин

- `POST /api/login`
- `Content-Type: application/json`

Тело:

```json
{
  "login": "DimsHewik",
  "password": "301301301"
}
```

или:

```json
{
  "email": "user@example.com",
  "password": "301301301"
}
```

Ответ:

```json
{
  "token_type": "Bearer",
  "expires_in": 900
}
```

Важно:
- Access token возвращается в response header: `Authorization: Bearer <token>`.
- Refresh token ставится в `HttpOnly` cookie.

### 2.2 Refresh

- `POST /api/refresh`
- Тело можно не отправлять (refresh берется из cookie).
- Делать запрос с `credentials: 'include'`.

Ответ:

```json
{
  "token_type": "Bearer",
  "expires_in": 3600
}
```

Важно:
- Новый access token снова в response header `Authorization`.
- Refresh cookie ротируется.

### 2.3 Logout

- `POST /api/logout`
- Нужен access token в `Authorization` header.
- Рекомендуется отправлять с `credentials: 'include'`, чтобы backend очистил refresh cookie.

### 2.4 Профиль

- `GET /api/me`
- Нужен access token в `Authorization` header.

Ответ:

```json
{
  "uuid": "00000000-0000-4000-8000-000000000001",
  "name": "Алекс",
  "age": 25,
  "login": "alex",
  "email": "alex@example.com",
  "avatarPath": "upload/avatars/avatar.webp"
}
```

## 3. Регистрация

- `POST /api/registration`
- `Content-Type: multipart/form-data`

Поля:
- `name` (string, обязательно)
- `age` (number, обязательно)
- `login` (string, обязательно)
- `email` (string, опционально)
- `password` (string, обязательно)
- `avatar` (file, опционально)

## 4. Feed (Posts API)

Все feed-эндпоинты сейчас требуют access token:
- `POST /api/feed`
- `GET /api/feed`

Пользователь определяется backend через JWT (`Authorization` header). UUID пользователя с фронта не отправляется.

В ленте возвращаются только:
- посты, созданные самим авторизованным пользователем
- посты, созданные на стене авторизованного пользователя

### 4.1 Формат медиа объекта

```json
{
  "original_name": "mix.jpg",
  "saved_name": "f91a1c.jpg",
  "full_path": "/var/www/sonata/uploads/f91a1c.jpg",
  "relative_path": "uploads/f91a1c.jpg",
  "size": 245123,
  "extension": "jpg",
  "uploaded": true,
  "errors": ""
}
```

Обязательные поля:
- `relative_path`
- `extension`

Правило для frontend URL:
- итоговый URL файла: `${API_ORIGIN}/${relative_path}`
- если `relative_path` уже начинается с `/`, не добавлять второй `/`

### 4.2 Создание публикации

- `POST /api/feed`
- `Content-Type: multipart/form-data`

Общие поля:
- `type`: `post | poll | quiz | article`
- `payload`: JSON-строка (обязательно для `poll|quiz|article`)
- `media[]`: файлы (только для `post`)

#### type = post

Поля:
- `type=post`
- `text` (опционально)
- `media[]` (опционально, multiple)

Правило:
- `text` или `media[]` должно быть передано.

Пример:

```js
const fd = new FormData();
fd.append('type', 'post');
fd.append('text', 'Новый плейлист');
files.forEach((f) => fd.append('media[]', f));
```

#### type = poll

Поля:
- `type=poll`
- `payload=<JSON string>`

`payload`:

```json
{
  "question": "Какой формат хотите чаще?",
  "options": ["Плейлисты", "Гайды", "Разборы"],
  "multiple": true,
  "duration": "3 дня"
}
```

Правила:
- `options`: от 2 до 6.

#### type = quiz

Поля:
- `type=quiz`
- `payload=<JSON string>`

`payload`:

```json
{
  "question": "Какой эффект делает вокал шире?",
  "options": ["Limiter", "Stereo chorus", "Noise gate"],
  "correctOptionId": "b",
  "explanation": "Chorus расширяет стереобазу."
}
```

Правила:
- `options`: 3-4.
- финальный контракт: `correctOptionId` (`a|b|c|d` по позиции в `options`).
- для обратной совместимости backend пока принимает и `correctOptionIndex`.

### 4.5 Ответ на викторину

- `POST /api/feed/quiz/answer`
- `Content-Type: application/json`

Тело:

```json
{
  "feedId": "quiz-550e8400-e29b-41d4-a716-446655440000",
  "answerId": "b"
}
```

Ответ:

```json
{
  "result": {
    "feedId": "quiz-550e8400-e29b-41d4-a716-446655440000",
    "userAnswerId": "b",
    "isCorrect": true,
    "correctOptionId": "c"
  }
}
```

Поведение:
- если пользователь уже отвечал, backend вернет его же сохраненный результат.
- `correctOptionId` в ленте появляется только после ответа.

### 4.6 Удаление поста

- `DELETE /api/feed/{id}`
- `{id}` — это `id` поста из ленты (формат `post-uuid`)

Ответ:

```json
{
  "deleted": true,
  "feedId": "post-550e8400-e29b-41d4-a716-446655440000"
}
```

Правила:
- удалить может автор поста или владелец стены.

## 7. Комментарии

Комментарии поддерживают вложенность любой глубины. Ответ содержит дерево комментариев, каждый comment содержит `children`.

Комментарии видны всем, без ограничений доступа.

### 7.1 Создание комментария

- `POST /api/feed/{id}/comments`
- `Content-Type: multipart/form-data`
- `{id}` — id поста из ленты (формат `type-uuid`)

Поля:
- `text` (опционально)
- `parentId` (опционально, формат `comment-uuid`)
- `media[]` (опционально, multiple)

Правило:
- `text` или `media[]` обязательно.

Ответ:

```json
{
  "item": {
    "id": "comment-550e8400-e29b-41d4-a716-446655440000",
    "author": {
      "name": "Марина",
      "login": "marina",
      "avatar": { "relative_path": "avatars/a1.jpg", "extension": "jpg" }
    },
    "createdAt": "2026-02-12T10:10:00Z",
    "text": "Класс!",
    "media": [
      { "relative_path": "uploads/f91a1c.jpg", "extension": "jpg" }
    ],
    "parentId": null,
    "children": []
  }
}
```

### 7.2 Получение комментариев

- `GET /api/feed/{id}/comments?order=asc|desc`

Ответ:

```json
{
  "items": [
    {
      "id": "comment-1",
      "author": {
        "name": "Марина",
        "login": "marina",
        "avatar": { "relative_path": "avatars/a1.jpg", "extension": "jpg" }
      },
      "createdAt": "2026-02-12T10:10:00Z",
      "text": "Первый",
      "parentId": null,
      "children": [
        {
          "id": "comment-2",
          "author": {
            "name": "Иван",
            "login": "ivan",
            "avatar": { "relative_path": "avatars/a2.jpg", "extension": "jpg" }
          },
          "createdAt": "2026-02-12T10:12:00Z",
          "text": "Ответ",
          "parentId": "comment-1",
          "children": []
        }
      ]
    }
  ]
}
```

### 7.3 Удаление комментария

- `DELETE /api/comments/{id}`
- `{id}` — `comment-uuid`

Ответ:

```json
{
  "deleted": true,
  "commentId": "comment-550e8400-e29b-41d4-a716-446655440000"
}
```

Правило:
- при удалении комментария удаляются все его ответы (дерево).

#### type = article

Поля:
- `type=article`
- `payload=<JSON string>`

`payload`:

```json
{
  "title": "Как собрать лайв сет",
  "description": "Подборка практических советов..."
}
```

### 4.3 Ответ на создание

Ответ всегда в формате:

```json
{
  "item": {}
}
```

Для `poll` в `item` также возвращается `userVoteIds` (по умолчанию `[]`).
Для `quiz` в `item` также возвращается `userAnswerId` (по умолчанию `null`).

`post` пример:

```json
{
  "item": {
    "id": "post-550e8400-e29b-41d4-a716-446655440000",
    "type": "post",
    "author": {
      "name": "Марина",
      "login": "marina",
      "avatar": { "relative_path": "avatars/a1.jpg", "extension": "jpg" }
    },
    "createdAt": "2026-02-12T10:10:00Z",
    "text": "Новый плейлист",
    "media": [
      {
        "original_name": "mix.jpg",
        "saved_name": "f91a1c.jpg",
        "full_path": "/var/www/sonata/uploads/f91a1c.jpg",
        "relative_path": "uploads/f91a1c.jpg",
        "size": 245123,
        "extension": "jpg",
        "uploaded": true,
        "errors": ""
      }
    ],
    "stats": { "likes": 0, "comments": 0 }
  }
}
```

### 4.4 Получение ленты

- `GET /api/feed`

Ответ:

```json
{
  "items": []
}
```

`post` в списке:
- `id` (строка в формате `type-uuid`), `type`, `author`, `createdAt`
- `text` (если есть)
- `media` (в компактном формате: `relative_path`, `extension`)
- `stats`

`poll` в списке:
- `id`, `type`, `author`, `createdAt`
- `question`, `options`, `multiple`, `totalVotes`, `duration`
- `userVoteIds` (массив выбранных опций текущим пользователем)

`quiz` в списке:
- `id`, `type`, `author`, `createdAt`
- `question`, `options`
- `userAnswerId` (`null` или id выбранной опции текущим пользователем)
- `isCorrect` (только если пользователь уже отвечал)
- `correctOptionId` возвращается только если пользователь уже отвечал

`article` в списке:
- `id`, `type`, `author`, `createdAt`
- `title`, `description`, `readTime`

## 5. Как работать с токеном на фронте

Минимальный flow:

1. `POST /api/login` с `credentials: 'include'`.
2. Забрать access token из response header `Authorization`.
3. Сохранить access token в памяти приложения.
4. На protected-запросы передавать:
   - `Authorization: Bearer <access_token>`
5. При `401` сделать `POST /api/refresh` с `credentials: 'include'`, взять новый token из header, повторить исходный запрос.

Пример helper для `fetch`:

```js
let accessToken = null;

function setAccessTokenFromResponse(res) {
  const auth = res.headers.get('Authorization');
  if (!auth) return;
  const [type, token] = auth.split(' ');
  if (type === 'Bearer' && token) accessToken = token;
}

async function api(path, options = {}) {
  const headers = new Headers(options.headers || {});
  if (accessToken) headers.set('Authorization', `Bearer ${accessToken}`);

  let res = await fetch(`http://localhost:8080${path}`, {
    ...options,
    headers,
    credentials: 'include'
  });

  setAccessTokenFromResponse(res);

  if (res.status === 401) {
    const refreshRes = await fetch('http://localhost:8080/api/refresh', {
      method: 'POST',
      credentials: 'include'
    });
    setAccessTokenFromResponse(refreshRes);

    if (refreshRes.ok && accessToken) {
      headers.set('Authorization', `Bearer ${accessToken}`);
      res = await fetch(`http://localhost:8080${path}`, {
        ...options,
        headers,
        credentials: 'include'
      });
      setAccessTokenFromResponse(res);
    }
  }

  return res;
}
```

## 6. Ошибки API

Стандартный формат ошибок:

```json
{
  "error": {
    "code": 400,
    "message": "Ошибка валидации",
    "details": null
  }
}
```
