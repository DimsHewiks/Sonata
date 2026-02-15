<?php

namespace Api\Posts\Controllers;

use Api\Posts\DTOs\Request\FeedCreateDTO;
use Api\Posts\DTOs\Request\QuizAnswerDTO;
use Api\Posts\DTOs\Request\CommentCreateDTO;
use Api\Posts\DTOs\Request\CommentsQueryDTO;
use Api\Posts\DTOs\Response\FeedCreateResponse;
use Api\Posts\DTOs\Response\FeedListResponse;
use Api\Posts\DTOs\Response\QuizAnswerResponse;
use Api\Posts\DTOs\Response\DeleteFeedResponse;
use Api\Posts\DTOs\Response\CommentCreateResponse;
use Api\Posts\DTOs\Response\CommentListResponse;
use Api\Posts\DTOs\Response\CommentDeleteResponse;
use Api\Posts\DTOs\Response\PostMediaListResponse;
use Api\Posts\Services\FeedService;
use Sonata\Framework\Attributes\Controller;
use Sonata\Framework\Attributes\From;
use Sonata\Framework\Attributes\Inject;
use Sonata\Framework\Attributes\Response as ResponseAttr;
use Sonata\Framework\Attributes\Route;
use Sonata\Framework\Attributes\Tag;
use Sonata\Framework\Http\Response;

#[Controller(prefix: '/api')]
#[Tag('Лента', 'Посты, опросы, квизы, статьи')]
class FeedController
{
    public function __construct(
        #[Inject] private FeedService $feedService
    ) {}

    #[Route(path: '/feed', method: 'POST', summary: 'Create feed item')]
    #[ResponseAttr(FeedCreateResponse::class)]
    public function create(#[From('formData')] FeedCreateDTO $dto): FeedCreateResponse
    {
        try {
            return $this->feedService->create($dto);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Unauthorized' => 401,
                'Forbidden' => 403,
                default => 500
            };
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to create feed item', 500, $e->getMessage());
        }
    }

    #[Route(path: '/feed', method: 'GET', summary: 'Get feed')]
    #[ResponseAttr(FeedListResponse::class)]
    public function list(): FeedListResponse
    {
        try {
            return $this->feedService->getFeed();
        } catch (\Throwable $e) {
            Response::error('Failed to load feed', 500, $e->getMessage());
        }
    }

    #[Route(path: '/feed/quiz/answer', method: 'POST', summary: 'Answer quiz')]
    #[ResponseAttr(QuizAnswerResponse::class)]
    public function answerQuiz(#[From('json')] QuizAnswerDTO $dto): QuizAnswerResponse
    {
        try {
            return $this->feedService->submitQuizAnswer($dto);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Unauthorized' => 401,
                'Forbidden' => 403,
                default => 500
            };
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to submit quiz answer', 500, $e->getMessage());
        }
    }

    #[Route(path: '/feed/{id}/comments', method: 'POST', summary: 'Create comment')]
    #[ResponseAttr(CommentCreateResponse::class)]
    public function createComment(string $id, #[From('formData')] CommentCreateDTO $dto): CommentCreateResponse
    {
        try {
            return $this->feedService->createComment($id, $dto);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Unauthorized' => 401,
                'Forbidden' => 403,
                default => 500
            };
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to create comment', 500, $e->getMessage());
        }
    }

    #[Route(path: '/feed/{id}/comments', method: 'GET', summary: 'Get comments')]
    #[ResponseAttr(CommentListResponse::class)]
    public function listComments(string $id, #[From('query')] CommentsQueryDTO $dto): CommentListResponse
    {
        try {
            return $this->feedService->getComments($id, $dto);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Unauthorized' => 401,
                'Forbidden' => 403,
                default => 500
            };
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to load comments', 500, $e->getMessage());
        }
    }

    #[Route(path: '/comments/{id}', method: 'DELETE', summary: 'Delete comment')]
    #[ResponseAttr(CommentDeleteResponse::class)]
    public function deleteComment(string $id): CommentDeleteResponse
    {
        try {
            return $this->feedService->deleteComment($id);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Unauthorized' => 401,
                'Forbidden' => 403,
                default => 500
            };
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to delete comment', 500, $e->getMessage());
        }
    }

    #[Route(path: '/feed/{id}', method: 'DELETE', summary: 'Delete feed item')]
    #[ResponseAttr(DeleteFeedResponse::class)]
    public function delete(string $id): DeleteFeedResponse
    {
        try {
            return $this->feedService->deleteFeedItem($id);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Unauthorized' => 401,
                'Forbidden' => 403,
                default => 500
            };
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to delete feed item', 500, $e->getMessage());
        }
    }

    #[Route(path: '/feed/media', method: 'GET', summary: 'Медиа из постов пользователя')]
    #[ResponseAttr(PostMediaListResponse::class)]
    public function listMyPostMedia(): PostMediaListResponse
    {
        try {
            return $this->feedService->getMyPostMedia();
        } catch (\Throwable $e) {
            Response::error('Failed to load post media', 500, $e->getMessage());
        }
    }
}
