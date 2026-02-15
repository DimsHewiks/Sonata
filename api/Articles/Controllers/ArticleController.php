<?php

namespace Api\Articles\Controllers;

use Api\Articles\Dto\Request\ArticleCreateDto;
use Api\Articles\Dto\Request\ArticleUpdateDto;
use Api\Articles\Dto\Response\ArticleCoverUploadResponseDto;
use Api\Articles\Dto\Response\ArticleDto;
use Api\Articles\Dto\Response\ArticleMediaUploadResponseDto;
use Api\Articles\Dto\Response\ArticlePublishResponseDto;
use Api\Articles\Services\ArticleService;
use Api\Auth\Auth;
use Sonata\Framework\Attributes\Controller;
use Sonata\Framework\Attributes\From;
use Sonata\Framework\Attributes\Inject;
use Sonata\Framework\Attributes\Response as ResponseAttr;
use Sonata\Framework\Attributes\Route;
use Sonata\Framework\Attributes\Tag;
use Sonata\Framework\Http\Response;

#[Controller(prefix: '/api')]
#[Tag('Статьи')]
class ArticleController
{
    public function __construct(
        #[Inject] private ArticleService $articleService
    ) {}

    #[Route(path: '/articles', method: 'POST', summary: 'Создать статью (черновик)')]
    #[ResponseAttr(ArticleDto::class)]
    public function create(#[From('json')] ArticleCreateDto $dto): ArticleDto
    {
        try {
            return $this->articleService->createDraft($dto, Auth::getOrThrow()->uuid);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Unauthorized' ? 401 : 403;
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to create article', 500, $e->getMessage());
        }
    }

    #[Route(path: '/articles/{id}', method: 'PUT', summary: 'Обновить статью')]
    #[ResponseAttr(ArticleDto::class)]
    public function update(string $id, #[From('json')] ArticleUpdateDto $dto): ArticleDto
    {
        try {
            return $this->articleService->update($id, Auth::getOrThrow()->uuid, $dto);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Unauthorized' ? 401 : 403;
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to update article', 500, $e->getMessage());
        }
    }

    #[Route(path: '/articles/{id}', method: 'GET', summary: 'Получить статью')]
    #[ResponseAttr(ArticleDto::class)]
    public function get(string $id): ArticleDto
    {
        try {
            return $this->articleService->get($id, Auth::getOrThrow()->uuid);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Unauthorized' ? 401 : 403;
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to load article', 500, $e->getMessage());
        }
    }

    #[Route(path: '/articles/{id}/publish', method: 'POST', summary: 'Публикация статьи')]
    #[ResponseAttr(ArticlePublishResponseDto::class)]
    public function publish(string $id): ArticlePublishResponseDto
    {
        try {
            return $this->articleService->publish($id, Auth::getOrThrow()->uuid);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Unauthorized' ? 401 : 403;
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to publish article', 500, $e->getMessage());
        }
    }

    #[Route(path: '/articles/cover', method: 'POST', summary: 'Загрузка обложки статьи')]
    #[ResponseAttr(ArticleCoverUploadResponseDto::class)]
    public function uploadCover(): ArticleCoverUploadResponseDto
    {
        try {
            return $this->articleService->uploadCover();
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            Response::error('Failed to upload cover', 500, $e->getMessage());
        }
    }

    #[Route(path: '/articles/media', method: 'POST', summary: 'Загрузка медиа статьи')]
    #[ResponseAttr(ArticleMediaUploadResponseDto::class)]
    public function uploadMedia(): ArticleMediaUploadResponseDto
    {
        try {
            return $this->articleService->uploadMedia();
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            Response::error('Failed to upload media', 500, $e->getMessage());
        }
    }
}
