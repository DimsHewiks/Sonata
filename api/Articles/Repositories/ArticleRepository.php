<?php

namespace Api\Articles\Repositories;

use PDO;
use Ramsey\Uuid\Uuid;

class ArticleRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function createDraft(
        string $uuid,
        string $authorUuid,
        string $title,
        string $type,
        string $format
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO articles (uuid, author_uuid, title, type, format, body, status)
             VALUES (UUID_TO_BIN(?), UUID_TO_BIN(?), ?, ?, ?, '', 'draft')"
        );
        $stmt->execute([$uuid, $authorUuid, $title, $type, $format]);
    }

    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                BIN_TO_UUID(a.uuid) AS uuid,
                BIN_TO_UUID(a.author_uuid) AS author_uuid,
                a.title,
                a.type,
                a.format,
                a.body,
                a.excerpt,
                a.status,
                BIN_TO_UUID(a.cover_media_uuid) AS cover_media_uuid,
                a.cover_position,
                a.embeds,
                a.chords_notation,
                BIN_TO_UUID(a.feed_item_uuid) AS feed_item_uuid,
                a.created_at,
                a.updated_at,
                a.published_at,
                am.relative_path AS cover_relative_path,
                am.extension AS cover_extension
            FROM articles a
            LEFT JOIN article_media am ON am.uuid = a.cover_media_uuid
            WHERE a.uuid = UUID_TO_BIN(?)
            LIMIT 1"
        );
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    public function updateArticle(string $uuid, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        $sets = [];
        $values = [];
        foreach ($fields as $key => $value) {
            if ($key === 'cover_media_uuid' && $value !== null) {
                $sets[] = "$key = UUID_TO_BIN(?)";
                $values[] = $value;
                continue;
            }
            $sets[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $uuid;

        $sql = "UPDATE articles SET " . implode(', ', $sets) . ", updated_at = CURRENT_TIMESTAMP WHERE uuid = UUID_TO_BIN(?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }

    public function publish(string $uuid): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE articles
             SET status = 'published',
                 published_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE uuid = UUID_TO_BIN(?)"
        );
        $stmt->execute([$uuid]);
    }

    public function setFeedItemUuid(string $uuid, string $feedItemUuid): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE articles
             SET feed_item_uuid = UUID_TO_BIN(?), updated_at = CURRENT_TIMESTAMP
             WHERE uuid = UUID_TO_BIN(?)"
        );
        $stmt->execute([$feedItemUuid, $uuid]);
    }

    public function findCoverMediaByUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT BIN_TO_UUID(uuid) AS uuid, relative_path, extension, width, height
             FROM article_media
             WHERE uuid = UUID_TO_BIN(?)
             LIMIT 1"
        );
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    public function findMediaByUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT BIN_TO_UUID(uuid) AS uuid, relative_path, extension, width, height
             FROM article_media
             WHERE uuid = UUID_TO_BIN(?)
             LIMIT 1"
        );
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    public function createCoverMedia(array $media): string
    {
        return $this->createArticleMedia($media, 'cover');
    }

    public function createArticleMedia(array $media, string $kind = 'image'): string
    {
        $uuid = Uuid::uuid7()->toString();
        $stmt = $this->pdo->prepare(
            "INSERT INTO article_media (
                uuid, original_name, saved_name, full_path, relative_path, size, extension, width, height, uploaded, errors, kind
            ) VALUES (UUID_TO_BIN(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $errors = $media['errors'] ?? null;
        if (is_array($errors)) {
            $errors = json_encode($errors, JSON_UNESCAPED_UNICODE);
        }

        $stmt->execute([
            $uuid,
            (string)($media['original_name'] ?? ''),
            (string)($media['saved_name'] ?? ''),
            (string)($media['full_path'] ?? ''),
            (string)($media['relative_path'] ?? ''),
            (int)($media['size'] ?? 0),
            (string)($media['extension'] ?? ''),
            isset($media['width']) ? (int)$media['width'] : null,
            isset($media['height']) ? (int)$media['height'] : null,
            !empty($media['uploaded']) ? 1 : 0,
            $errors,
            $kind
        ]);

        return $uuid;
    }
}
