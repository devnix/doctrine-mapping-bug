<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class User implements \JsonSerializable
{
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

    public function getApp(): App
    {
        return $this->app;
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
        if ($this->alias === $alias) {
            return $this;
        }

        $this->alias = $alias;

        // $this-->recordDomainEvent(...);
        return $this;
    }

    public function updateUsername(string $username): self
    {
        if ($this->username === $username) {
            return $this;
        }

        $this->username = $username;

        return $this;
    }

    public function updatePassword(string $password): self
    {
        if ($this->password === $password) {
            return $this;
        }

        $this->password = $password;
        return $this;
    }


    public function isDeleted(): bool
    {
        return false;
    }

    private function duplicate(): User
    {
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
