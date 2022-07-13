<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity of the aggregate App
 */
#[ORM\Entity]
final class User implements \JsonSerializable
{
    private const IMMUTABLE_CLASS = false;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    private string $alias;

    #[ORM\Column(type: 'string')]
    private string $username;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\ManyToOne(targetEntity: App::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private App $app;

    private function __construct(
        App $app,
        string $alias,
        string $username,
        string $password,
    ) {
        $this->app = $app;
        $this->alias = $alias;
        $this->username = $username;
        $this->password = $password;
    }

    public static function create(
        App $app,
        string $alias,
        string $username,
        string $password
    ): self {

        // $this->recordDomainEvent(...);
        return new self($app, $alias, $username, $password);
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function updateAlias(string $alias): self
    {
        $user = $this->duplicate();

        if ($this->alias === $alias) {
            return $this;
        }

        $user->alias = $alias;

        // $this-->recordDomainEvent(...);
        return $user;
    }

    public function updateUsername(string $username): self
    {
        $user = $this->duplicate();
        if ($this->username === $username) {
            return $user;
        }
        $user->username = $username;
        return $user;
    }

    public function updatePassword(string $password): self
    {
        $user = $this->duplicate();
        if ($this->password === $password) {
            return $user;
        }
        $user->password = $password;
        return $user;
    }

    public function isDeleted(): bool
    {
        return false;
    }

    private function duplicate(): User
    {
        /** @phpstan-ignore-next-line */
        if (self::IMMUTABLE_CLASS) {
            $newUser = new self(
                $this->app,
                $this->alias,
                $this->username,
                $this->password
            );

            $newUser->id = $this->id;

            return $newUser;
        }

        /** @phpstan-ignore-next-line */
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'alias' => $this->alias,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}
