<?php

namespace Api\Instruments\Controllers;

use Api\Auth\Auth;
use Api\Instruments\Dto\InstrumentCreateDto;
use Api\Instruments\Dto\InstrumentDto;
use Api\Instruments\Dto\InstrumentListResponse;
use Api\Instruments\Dto\UserInstrumentsUpdateDto;
use Api\Instruments\Services\InstrumentService;
use Sonata\Framework\Attributes\Controller;
use Sonata\Framework\Attributes\From;
use Sonata\Framework\Attributes\Inject;
use Sonata\Framework\Attributes\Response as ResponseAttr;
use Sonata\Framework\Attributes\Route;
use Sonata\Framework\Attributes\Tag;
use Sonata\Framework\Http\Response;

#[Controller(prefix: '/api')]
#[Tag('Инструменты', 'Справочник инструментов и выбор пользователя')]
class InstrumentController
{
    public function __construct(
        #[Inject] private InstrumentService $instrumentService
    ) {}

    #[Route(path: '/instruments', method: 'GET', summary: 'Список инструментов')]
    #[ResponseAttr(InstrumentListResponse::class)]
    public function list(): InstrumentListResponse
    {
        try {
            return $this->instrumentService->list();
        } catch (\Throwable $e) {
            Response::error('Failed to load instruments', 500, $e->getMessage());
        }
    }

    #[Route(path: '/instruments', method: 'POST', summary: 'Создать инструмент')]
    #[ResponseAttr(InstrumentDto::class)]
    public function create(#[From('json')] InstrumentCreateDto $dto): InstrumentDto
    {
        try {
            return $this->instrumentService->create($dto);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            Response::error('Failed to create instrument', 500, $e->getMessage());
        }
    }

    #[Route(path: '/me/instruments', method: 'PUT', summary: 'Выбор инструментов пользователя')]
    #[ResponseAttr(InstrumentListResponse::class)]
    public function updateMyInstruments(#[From('json')] UserInstrumentsUpdateDto $dto): InstrumentListResponse
    {
        try {
            $instrumentIds = $dto->instrumentIds ?? [];
            return $this->instrumentService->setUserInstruments(Auth::getOrThrow()->uuid, $instrumentIds);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            $status = $e->getMessage() === 'Unauthorized' ? 401 : 500;
            Response::error($e->getMessage(), $status);
        } catch (\Throwable $e) {
            Response::error('Failed to update instruments', 500, $e->getMessage());
        }
    }
}
