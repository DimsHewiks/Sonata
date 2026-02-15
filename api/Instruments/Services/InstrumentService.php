<?php

namespace Api\Instruments\Services;

use Api\Instruments\Dto\InstrumentCreateDto;
use Api\Instruments\Dto\InstrumentDto;
use Api\Instruments\Dto\InstrumentListResponse;
use Api\Instruments\Repositories\InstrumentRepository;
use Sonata\Framework\Attributes\Inject;

class InstrumentService
{
    public function __construct(
        #[Inject] private InstrumentRepository $instrumentRepository
    ) {}

    public function list(): InstrumentListResponse
    {
        $rows = $this->instrumentRepository->listAll();
        return $this->buildListResponse($rows);
    }

    public function create(InstrumentCreateDto $dto): InstrumentDto
    {
        $name = trim((string)$dto->name);
        if ($name === '') {
            throw new \InvalidArgumentException('name is required');
        }

        $sticker = $dto->sticker !== null ? trim($dto->sticker) : null;
        if ($sticker === '') {
            $sticker = null;
        }

        $id = $this->instrumentRepository->create($name, $sticker);
        $row = $this->instrumentRepository->findById($id);
        if (!$row) {
            throw new \RuntimeException('Failed to load created instrument');
        }

        return $this->mapRowToDto($row);
    }

    /**
     * @param array<int, int> $instrumentIds
     */
    public function setUserInstruments(string $userUuid, array $instrumentIds): InstrumentListResponse
    {
        $normalizedIds = [];
        foreach ($instrumentIds as $instrumentId) {
            $id = (int)$instrumentId;
            if ($id > 0) {
                $normalizedIds[$id] = $id;
            }
        }
        $normalizedIds = array_values($normalizedIds);

        if (!empty($normalizedIds)) {
            $rows = $this->instrumentRepository->findByIds($normalizedIds);
            if (count($rows) !== count($normalizedIds)) {
                throw new \InvalidArgumentException('Some instruments not found');
            }
        }

        $this->instrumentRepository->setUserInstruments($userUuid, $normalizedIds);
        $rows = $this->instrumentRepository->findByUserUuid($userUuid);
        return $this->buildListResponse($rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function buildListResponse(array $rows): InstrumentListResponse
    {
        $response = new InstrumentListResponse();
        foreach ($rows as $row) {
            $response->items[] = $this->mapRowToDto($row);
        }
        return $response;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToDto(array $row): InstrumentDto
    {
        $dto = new InstrumentDto();
        $dto->id = (int)$row['id'];
        $dto->name = (string)$row['name'];
        $dto->sticker = $row['sticker'] !== null ? (string)$row['sticker'] : null;
        return $dto;
    }
}
