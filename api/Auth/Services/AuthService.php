<?php

namespace Api\Auth\Services;

use Api\Auth\Repositories\AuthRepository;
use Api\Instruments\Dto\InstrumentDto;
use Api\Instruments\Repositories\InstrumentRepository;
use Api\User\Dto\Response\UserFullDto;
use Sonata\Framework\Attributes\Inject;
use Sonata\Framework\Service\ConfigService;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    public function __construct(
        #[Inject] private AuthRepository $authRepository,
        #[Inject] private InstrumentRepository $instrumentRepository,
        #[Inject] private ConfigService $config
    ) {}

    public function login(string $identifier, string $password): ?array
    {
        $user = $this->authRepository->findUserByLoginOrEmail($identifier);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        $accessToken = $this->generateAccessToken($user['uuid'], $user['email'] ?? null);
        $refreshToken = $this->createRefreshToken($user['uuid']);

        $this->setAccessHeader($accessToken);
        $this->setRefreshCookie($refreshToken);

        return [
            'token_type' => 'Bearer',
            'expires_in' => 60*15
        ];
    }

    public function register(
        string $name,
        int $age,
        string $login,
        ?string $email,
        string $password,
        ?array $avatar
    ): void
    {
        if ($name === '' || $login === '' || $password === '' || $age <= 0) {
            throw new \InvalidArgumentException('Name, login, age and password are required');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $uuid = $this->generateUuid();
        $this->authRepository->createUser($uuid, $name, $age, $login, $email, $hash);

        if ($avatar !== null) {
            $status = !empty($avatar['uploaded']) ? 1 : 0;
            $this->authRepository->createUserAvatar($uuid, $avatar, $status);
        }
    }

    public function updateAvatar(string $userUuid, array $avatar): ?string
    {
        $this->authRepository->deactivateUserAvatars($userUuid);
        $status = !empty($avatar['uploaded']) ? 1 : 0;
        $this->authRepository->createUserAvatar($userUuid, $avatar, $status);
        return $avatar['relative_path'] ?? null;
    }

    public function refreshFromRequest(): ?array
    {
        $refreshToken = $this->getRefreshTokenFromRequest();
        if (!$refreshToken) {
            return null;
        }

        $tokenHash = hash('sha256', $refreshToken);
        $record = $this->authRepository->findActiveRefreshToken($tokenHash);

        if (!$record) {
            return null;
        }

        $this->authRepository->revokeRefreshTokenByHash($record['token_hash']);

        $newAccessToken = $this->generateAccessToken($record['user_uuid'], $record['email'] ?? null);
        $newRefreshToken = $this->createRefreshToken($record['user_uuid']);

        $this->setAccessHeader($newAccessToken);
        $this->setRefreshCookie($newRefreshToken);

        return [
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];
    }

    public function logout(string $accessToken): void
    {
        try {
            $payload = JWT::decode($accessToken, new Key($this->config->getJwtSecret(), 'HS256')); // ✅
            $this->authRepository->revokeRefreshTokensByUserUuid((string)$payload->sub);
        } catch (\Exception $e) {
            // Игнорируем ошибки при логауте
        }
    }

    public function clearRefreshCookie(): void
    {
        $cookieName = $_ENV['REFRESH_COOKIE_NAME'] ?? 'refresh_token';
        $secure = !empty($_ENV['REFRESH_COOKIE_SECURE']) && ($_ENV['REFRESH_COOKIE_SECURE'] === '1');
        $sameSite = $_ENV['REFRESH_COOKIE_SAMESITE'] ?? 'Lax';

        setcookie($cookieName, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite
        ]);
    }


    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->config->getJwtSecret(), 'HS256')); // ✅
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getProfile(string $userUuid): ?UserFullDto
    {
        $row = $this->authRepository->findUserProfileByUuid($userUuid);
        if (!$row) {
            return null;
        }

        $instrumentRows = $this->instrumentRepository->findByUserUuid($userUuid);
        $dto = new UserFullDto();
        $dto->uuid = $row['uuid'];
        $dto->name = $row['name'];
        $dto->age = (int)$row['age'];
        $dto->login = $row['login'];
        $dto->email = $row['email'] ?? null;
        $dto->avatarPath = $row['avatar_path'] ?? null;
        $dto->description = $row['profile_description'] ?? null;
        foreach ($instrumentRows as $instrumentRow) {
            $instrument = new InstrumentDto();
            $instrument->id = (int)$instrumentRow['id'];
            $instrument->name = (string)$instrumentRow['name'];
            $instrument->sticker = $instrumentRow['sticker'] !== null ? (string)$instrumentRow['sticker'] : null;
            $dto->instruments[] = $instrument;
        }

        return $dto;
    }

    public function updateProfileDescription(string $userUuid, ?string $description): void
    {
        $normalized = $description !== null ? trim($description) : null;
        if ($normalized === '') {
            $normalized = null;
        }
        $this->authRepository->updateProfileDescription($userUuid, $normalized);
    }

    // --- Вспомогательные методы ---

    private function generateAccessToken(string $userUuid, ?string $email): string
    {
        $payload = [
            'iss' => 'sonata-fw',
            'sub' => $userUuid,
            'iat' => time(),
            'exp' => time() + 60*15
        ];
        if ($email !== null && $email !== '') {
            $payload['email'] = $email;
        }
        return JWT::encode($payload, $this->config->getJwtSecret(), 'HS256');
    }

    private function createRefreshToken(string $userUuid): string
    {
        $refreshToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $refreshToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 3600));
        $this->authRepository->saveRefreshToken($userUuid, $tokenHash, $expiresAt);
        return $refreshToken;
    }

    private function generateUuid(): string
    {
        return Uuid::uuid7()->toString();
    }

    private function getRefreshTokenFromRequest(): ?string
    {
        $cookieName = $_ENV['REFRESH_COOKIE_NAME'] ?? 'refresh_token';
        if (!empty($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        return $input['refresh_token'] ?? null;
    }

    private function setAccessHeader(string $accessToken): void
    {
        header('Authorization: Bearer ' . $accessToken);
    }

    private function setRefreshCookie(string $refreshToken): void
    {
        $cookieName = $_ENV['REFRESH_COOKIE_NAME'] ?? 'refresh_token';
        $ttlDays = (int)($_ENV['REFRESH_COOKIE_TTL_DAYS'] ?? 30);
        $secure = !empty($_ENV['REFRESH_COOKIE_SECURE']) && ($_ENV['REFRESH_COOKIE_SECURE'] === '1');
        $sameSite = $_ENV['REFRESH_COOKIE_SAMESITE'] ?? 'Lax';

        setcookie($cookieName, $refreshToken, [
            'expires' => time() + ($ttlDays * 24 * 3600),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite
        ]);
    }
}
