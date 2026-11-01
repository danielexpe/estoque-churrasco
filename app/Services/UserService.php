<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use DomainException;
use PDOException;
use Throwable;

final class UserService
{
    public const EMAIL_TAKEN = 1;
    public const LAST_ADMIN = 2;
    public const NOT_FOUND = 3;

    public function all(): array
    {
        return User::all();
    }

    public function find(int $id): array
    {
        $user = User::find($id);

        if ($user === null) {
            throw new DomainException('Usuário não encontrado.', self::NOT_FOUND);
        }

        return $user;
    }

    public function create(string $name, string $email, string $role, string $password): int
    {
        $email = $this->normalizeEmail($email);
        $this->assertEmailAvailable($email);

        try {
            return User::create(
                trim($name),
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException(
                    'Já existe um usuário com este e-mail.',
                    self::EMAIL_TAKEN
                );
            }

            throw $exception;
        }
    }

    public function update(int $id, string $name, string $email, string $role, ?string $password): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $this->lockActiveAdmins();

            $user = $this->find($id);
            $email = $this->normalizeEmail($email);
            $this->assertEmailAvailable($email, $id);

            if (
                $user['role'] === 'admin'
                && (int) $user['is_active'] === 1
                && $role !== 'admin'
                && User::countActiveAdmins() <= 1
            ) {
                throw new DomainException(
                    'Não é possível remover o perfil do último administrador ativo.',
                    self::LAST_ADMIN
                );
            }

            try {
                User::update($id, trim($name), $email, $role);
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    throw new DomainException(
                        'Já existe um usuário com este e-mail.',
                        self::EMAIL_TAKEN
                    );
                }

                throw $exception;
            }

            if ($password !== null && $password !== '') {
                User::updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function setActive(int $id, bool $active): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $this->lockActiveAdmins();

            $user = $this->find($id);

            if (
                !$active
                && $user['role'] === 'admin'
                && (int) $user['is_active'] === 1
                && User::countActiveAdmins() <= 1
            ) {
                throw new DomainException(
                    'Não é possível desativar o último administrador ativo.',
                    self::LAST_ADMIN
                );
            }

            User::setActive($id, $active);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function assertEmailAvailable(string $email, ?int $exceptId = null): void
    {
        if (User::emailExists($email, $exceptId)) {
            throw new DomainException(
                'Já existe um usuário com este e-mail.',
                self::EMAIL_TAKEN
            );
        }
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function lockActiveAdmins(): void
    {
        Database::connection()
            ->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")
            ->fetchAll();
    }
}
