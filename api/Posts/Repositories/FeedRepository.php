<?php

namespace Api\Posts\Repositories;

use PDO;
use Ramsey\Uuid\Uuid;

class FeedRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function createFeedItem(
        string $feedItemUuid,
        string $userUuid,
        string $wallUserUuid,
        string $type,
        ?string $text,
        ?string $payloadJson
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO feed_items (uuid, user_uuid, wall_user_uuid, type, text, payload_json)
             VALUES (UUID_TO_BIN(?), UUID_TO_BIN(?), UUID_TO_BIN(?), ?, ?, ?)"
        );
        $stmt->execute([$feedItemUuid, $userUuid, $wallUserUuid, $type, $text, $payloadJson]);
    }

    public function updateFeedItemPayload(string $feedItemUuid, ?string $payloadJson): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE feed_items SET payload_json = ? WHERE uuid = UUID_TO_BIN(?)"
        );
        $stmt->execute([$payloadJson, $feedItemUuid]);
    }

    /**
     * @param array<int, array<string, mixed>> $mediaItems
     */
    public function createFeedMediaBatch(string $feedItemUuid, array $mediaItems): void
    {
        if (empty($mediaItems)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO feed_item_media (
                uuid, feed_item_uuid, original_name, saved_name, full_path, relative_path, size, extension, uploaded, errors
            ) VALUES (UUID_TO_BIN(?), UUID_TO_BIN(?), ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($mediaItems as $media) {
            $errors = $media['errors'] ?? null;
            if (is_array($errors)) {
                $errors = json_encode($errors, JSON_UNESCAPED_UNICODE);
            }

            $stmt->execute([
                Uuid::uuid7()->toString(),
                $feedItemUuid,
                (string)($media['original_name'] ?? ''),
                (string)($media['saved_name'] ?? ''),
                (string)($media['full_path'] ?? ''),
                (string)($media['relative_path'] ?? ''),
                (int)($media['size'] ?? 0),
                (string)($media['extension'] ?? ''),
                !empty($media['uploaded']) ? 1 : 0,
                $errors
            ]);
        }
    }

    public function createComment(
        string $commentUuid,
        string $feedItemUuid,
        string $userUuid,
        ?string $parentUuid,
        ?string $text
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO feed_comments (uuid, feed_item_uuid, user_uuid, parent_uuid, text)
             VALUES (UUID_TO_BIN(?), UUID_TO_BIN(?), UUID_TO_BIN(?), UUID_TO_BIN(?), ?)"
        );
        $stmt->execute([$commentUuid, $feedItemUuid, $userUuid, $parentUuid, $text]);
    }

    public function incrementCommentsCount(string $feedItemUuid): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE feed_items SET comments_count = comments_count + 1 WHERE uuid = UUID_TO_BIN(?)"
        );
        $stmt->execute([$feedItemUuid]);
    }

    public function decrementCommentsCount(string $feedItemUuid, int $by = 1): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE feed_items
             SET comments_count = CASE
                 WHEN comments_count > ? THEN comments_count - ?
                 ELSE 0
             END
             WHERE uuid = UUID_TO_BIN(?)"
        );
        $stmt->execute([$by, $by, $feedItemUuid]);
    }

    /**
     * @param array<int, array<string, mixed>> $mediaItems
     */
    public function createCommentMediaBatch(string $commentUuid, array $mediaItems): void
    {
        if (empty($mediaItems)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO feed_comment_media (
                uuid, comment_uuid, original_name, saved_name, full_path, relative_path, size, extension, uploaded, errors
            ) VALUES (UUID_TO_BIN(?), UUID_TO_BIN(?), ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($mediaItems as $media) {
            $errors = $media['errors'] ?? null;
            if (is_array($errors)) {
                $errors = json_encode($errors, JSON_UNESCAPED_UNICODE);
            }

            $stmt->execute([
                Uuid::uuid7()->toString(),
                $commentUuid,
                (string)($media['original_name'] ?? ''),
                (string)($media['saved_name'] ?? ''),
                (string)($media['full_path'] ?? ''),
                (string)($media['relative_path'] ?? ''),
                (int)($media['size'] ?? 0),
                (string)($media['extension'] ?? ''),
                !empty($media['uploaded']) ? 1 : 0,
                $errors
            ]);
        }
    }

    public function findCommentByUuid(string $commentUuid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                BIN_TO_UUID(fc.uuid) AS comment_uuid,
                BIN_TO_UUID(fc.feed_item_uuid) AS feed_item_uuid,
                BIN_TO_UUID(fc.user_uuid) AS comment_user_uuid,
                BIN_TO_UUID(fi.user_uuid) AS feed_user_uuid,
                BIN_TO_UUID(fi.wall_user_uuid) AS wall_user_uuid,
                BIN_TO_UUID(fc.parent_uuid) AS parent_uuid,
                fc.text,
                fc.created_at,
                u.name AS author_name,
                u.login AS author_login,
                ua.relative_path AS author_avatar_relative_path,
                ua.extension AS author_avatar_extension
            FROM feed_comments fc
            JOIN feed_items fi ON fi.uuid = fc.feed_item_uuid
            JOIN users u ON u.uuid = fc.user_uuid
            LEFT JOIN users_avatars ua ON ua.id = (
                SELECT ua2.id
                FROM users_avatars ua2
                WHERE ua2.user_uuid = u.uuid
                  AND ua2.status = 1
                ORDER BY ua2.created_at DESC, ua2.id DESC
                LIMIT 1
            )
            WHERE fc.uuid = UUID_TO_BIN(?)
            LIMIT 1"
        );
        $stmt->execute([$commentUuid]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCommentsByFeedItemUuid(string $feedItemUuid, string $order = 'asc'): array
    {
        $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
        $stmt = $this->pdo->prepare(
            "SELECT
                BIN_TO_UUID(fc.uuid) AS comment_uuid,
                BIN_TO_UUID(fc.feed_item_uuid) AS feed_item_uuid,
                BIN_TO_UUID(fc.user_uuid) AS comment_user_uuid,
                BIN_TO_UUID(fc.parent_uuid) AS parent_uuid,
                fc.text,
                fc.created_at,
                u.name AS author_name,
                u.login AS author_login,
                ua.relative_path AS author_avatar_relative_path,
                ua.extension AS author_avatar_extension
            FROM feed_comments fc
            JOIN users u ON u.uuid = fc.user_uuid
            LEFT JOIN users_avatars ua ON ua.id = (
                SELECT ua2.id
                FROM users_avatars ua2
                WHERE ua2.user_uuid = u.uuid
                  AND ua2.status = 1
                ORDER BY ua2.created_at DESC, ua2.id DESC
                LIMIT 1
            )
            WHERE fc.feed_item_uuid = UUID_TO_BIN(?)
            ORDER BY fc.created_at $order"
        );
        $stmt->execute([$feedItemUuid]);
        return $stmt->fetchAll();
    }

    /**
     * @param array<int, string> $commentUuids
     * @return array<int, array<string, mixed>>
     */
    public function findCommentMediaByCommentUuids(array $commentUuids): array
    {
        if (empty($commentUuids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($commentUuids), 'UUID_TO_BIN(?)'));
        $stmt = $this->pdo->prepare(
            "SELECT BIN_TO_UUID(comment_uuid) AS comment_uuid, original_name, saved_name, full_path, relative_path, size, extension, uploaded, errors
             FROM feed_comment_media
             WHERE comment_uuid IN ($placeholders)
             ORDER BY created_at ASC"
        );
        $stmt->execute($commentUuids);
        return $stmt->fetchAll();
    }

    public function deleteCommentByUuid(string $commentUuid): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM feed_comments WHERE uuid = UUID_TO_BIN(?)");
        $stmt->execute([$commentUuid]);
    }

    public function countCommentSubtree(string $commentUuid): int
    {
        $stmt = $this->pdo->prepare(
            "WITH RECURSIVE cte AS (
                SELECT uuid
                FROM feed_comments
                WHERE uuid = UUID_TO_BIN(?)
                UNION ALL
                SELECT fc.uuid
                FROM feed_comments fc
                JOIN cte ON fc.parent_uuid = cte.uuid
            )
            SELECT COUNT(*) AS cnt FROM cte"
        );
        $stmt->execute([$commentUuid]);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    public function countCommentsByFeedItemUuid(string $feedItemUuid): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS cnt FROM feed_comments WHERE feed_item_uuid = UUID_TO_BIN(?)"
        );
        $stmt->execute([$feedItemUuid]);
        $row = $stmt->fetch();
        return $row ? (int)$row['cnt'] : 0;
    }

    public function findFeedItemByUuid(string $feedItemUuid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                BIN_TO_UUID(fi.uuid) AS feed_item_uuid,
                fi.type,
                fi.text,
                fi.payload_json,
                fi.likes_count,
                fi.comments_count,
                fi.created_at,
                BIN_TO_UUID(u.uuid) AS user_uuid,
                BIN_TO_UUID(fi.wall_user_uuid) AS wall_user_uuid,
                u.name AS author_name,
                u.login AS author_login,
                ua.relative_path AS author_avatar_relative_path,
                ua.extension AS author_avatar_extension
            FROM feed_items fi
            JOIN users u ON u.uuid = fi.user_uuid
            LEFT JOIN users_avatars ua ON ua.id = (
                SELECT ua2.id
                FROM users_avatars ua2
                WHERE ua2.user_uuid = u.uuid
                  AND ua2.status = 1
                ORDER BY ua2.created_at DESC, ua2.id DESC
                LIMIT 1
            )
            WHERE fi.uuid = UUID_TO_BIN(?)
            LIMIT 1"
        );
        $stmt->execute([$feedItemUuid]);
        return $stmt->fetch() ?: null;
    }

    public function deleteFeedItemByUuid(string $feedItemUuid): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM feed_items WHERE uuid = UUID_TO_BIN(?)");
        $stmt->execute([$feedItemUuid]);
    }

    public function createFeedDeleteAudit(string $feedItemUuid, string $deletedByUuid): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO feed_item_delete_audit (uuid, feed_item_uuid, deleted_by_uuid)
             VALUES (UUID_TO_BIN(?), UUID_TO_BIN(?), UUID_TO_BIN(?))"
        );
        $stmt->execute([Uuid::uuid7()->toString(), $feedItemUuid, $deletedByUuid]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findFeedMediaByItemUuid(string $feedItemUuid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT original_name, saved_name, full_path, relative_path, size, extension, uploaded, errors
             FROM feed_item_media
             WHERE feed_item_uuid = UUID_TO_BIN(?)
             ORDER BY created_at ASC"
        );
        $stmt->execute([$feedItemUuid]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findFeedItemsForUser(string $userUuid, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                BIN_TO_UUID(fi.uuid) AS feed_item_uuid,
                fi.type,
                fi.text,
                fi.payload_json,
                fi.likes_count,
                fi.comments_count,
                fi.created_at,
                BIN_TO_UUID(u.uuid) AS user_uuid,
                BIN_TO_UUID(fi.wall_user_uuid) AS wall_user_uuid,
                u.name AS author_name,
                u.login AS author_login,
                ua.relative_path AS author_avatar_relative_path,
                ua.extension AS author_avatar_extension
            FROM feed_items fi
            JOIN users u ON u.uuid = fi.user_uuid
            LEFT JOIN users_avatars ua ON ua.id = (
                SELECT ua2.id
                FROM users_avatars ua2
                WHERE ua2.user_uuid = u.uuid
                  AND ua2.status = 1
                ORDER BY ua2.created_at DESC, ua2.id DESC
                LIMIT 1
            )
            WHERE fi.user_uuid = UUID_TO_BIN(?)
               OR fi.wall_user_uuid = UUID_TO_BIN(?)
            ORDER BY fi.created_at DESC, fi.uuid DESC
            LIMIT ?"
        );
        $stmt->bindValue(1, $userUuid);
        $stmt->bindValue(2, $userUuid);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findQuizAnswerByItemUuidAndUser(string $feedItemUuid, string $userUuid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT answer_id, is_correct
             FROM feed_quiz_answers
             WHERE feed_item_uuid = UUID_TO_BIN(?)
               AND user_uuid = UUID_TO_BIN(?)
             LIMIT 1"
        );
        $stmt->execute([$feedItemUuid, $userUuid]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param array<int, string> $feedItemUuids
     * @return array<int, array<string, mixed>>
     */
    public function findQuizAnswersByItemUuidsForUser(array $feedItemUuids, string $userUuid): array
    {
        if (empty($feedItemUuids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($feedItemUuids), 'UUID_TO_BIN(?)'));
        $stmt = $this->pdo->prepare(
            "SELECT BIN_TO_UUID(feed_item_uuid) AS feed_item_uuid, answer_id, is_correct
             FROM feed_quiz_answers
             WHERE feed_item_uuid IN ($placeholders)
               AND user_uuid = UUID_TO_BIN(?)"
        );
        $stmt->execute([...$feedItemUuids, $userUuid]);
        return $stmt->fetchAll();
    }

    public function createQuizAnswer(
        string $feedItemUuid,
        string $userUuid,
        string $answerId,
        bool $isCorrect
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO feed_quiz_answers (uuid, feed_item_uuid, user_uuid, answer_id, is_correct)
             VALUES (UUID_TO_BIN(?), UUID_TO_BIN(?), UUID_TO_BIN(?), ?, ?)
             ON DUPLICATE KEY UPDATE answer_id = answer_id"
        );
        $stmt->execute([
            Uuid::uuid7()->toString(),
            $feedItemUuid,
            $userUuid,
            $answerId,
            $isCorrect ? 1 : 0
        ]);
    }

    /**
     * @param array<int, string> $feedItemUuids
     * @return array<int, array<string, mixed>>
     */
    public function findFeedMediaByItemUuids(array $feedItemUuids): array
    {
        if (empty($feedItemUuids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($feedItemUuids), 'UUID_TO_BIN(?)'));
        $stmt = $this->pdo->prepare(
            "SELECT BIN_TO_UUID(feed_item_uuid) AS feed_item_uuid, relative_path, extension
             FROM feed_item_media
             WHERE feed_item_uuid IN ($placeholders)
             ORDER BY created_at ASC"
        );
        $stmt->execute($feedItemUuids);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findPostMediaForUser(string $userUuid, int $limit = 200): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                BIN_TO_UUID(fi.uuid) AS feed_item_uuid,
                fim.relative_path,
                fim.extension,
                fim.created_at
            FROM feed_item_media fim
            JOIN feed_items fi ON fi.uuid = fim.feed_item_uuid
            WHERE fi.user_uuid = UUID_TO_BIN(?)
              AND fi.type = 'post'
              AND fim.extension IN ('jpg','jpeg','png','webp','gif')
            ORDER BY fim.created_at DESC
            LIMIT ?"
        );
        $stmt->bindValue(1, $userUuid);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
