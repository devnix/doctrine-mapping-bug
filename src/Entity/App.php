<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\App\UsernameAlreadyExistsException;
use App\Exception\App\UserNotRegisteredException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Aggregate root
 */
#[ORM\Entity]
final class App implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $id;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'app', targetEntity: User::class, cascade: ["persist", "remove", "merge"], orphanRemoval: true)]
    private Collection $users;

    private function __construct(
        string $id,
    ) {
        $this->id = $id;
        $this->users = new ArrayCollection();
    }

    public static function create(
        string $id,
    ): self {
        return new self($id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Collection<User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function getUserByUsername(string $username): User
    {
        foreach ($this->users as $user) {
            if ($user->getUsername() === $username && !$user->isDeleted()) {
                return $user;
            }
        }

        throw new UserNotRegisteredException();
    }

    public function createUser(string $userName, string $userNick, string $userPassword): void
    {
        try {
            $this->getUserByUsername($userNick);
        } catch (UserNotRegisteredException $e) {
            $user = User::create(
                $this,
                $userName,
                $userNick,
                $userPassword
            );
            $this->users->add($user);
            return;
        }
        throw new UsernameAlreadyExistsException();
    }

    public function updateUserAlias(string $username, string $newUserAlias): self
    {
        $user = $this->getUserByUsername($username);
        $newUser = $user->updateAlias($newUserAlias);
        $this->users->set((int) $this->users->indexOf($user), $newUser);
        return $this;
    }

    public function updateUserUsername(string $username, string $newUsername): self
    {
        $user = $this->getUserByUsername($username);
        try {
            $this->getUserByUsername($newUsername);
        } catch (UserNotRegisteredException) {
            $newUser = $user->updateUsername($newUsername);
            $this->users->set((int) $this->users->indexOf($user), $newUser);
            return $this;
        }
        throw new UsernameAlreadyExistsException();
    }

    public function updateUserPassword(string $userNick, string $newUserPassword): self
    {
        $user = $this->getUserByUsername($userNick);
        $newUser = $user->updatePassword($newUserPassword);
        $this->users->set((int) $this->users->indexOf($user), $newUser);
        return $this;
    }

    public function login(string $userNick, string $userPassword): bool
    {
        try {
            $user = $this->getUserByUsername($userNick);
        } catch (UserNotRegisteredException) {
            return false;
        }

        return $userPassword === $user->getPassword();
    }

    public function removeUser(string $username): self
    {
        $user = $this->getUserByUsername($username);
        $this->users->removeElement($user);
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'users' => $this->users->toArray()
        ];
    }
}
