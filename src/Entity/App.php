<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class App implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $id;

    #[ORM\OneToMany(mappedBy: 'app', targetEntity: User::class, orphanRemoval: true)]
    private $users;

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
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->users->toArray();
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

    public function getUserByName(string $userName): User
    {
        foreach ($this->users as $user) {
            if ($user->getAlias() === $userName && !$user->isDeleted()) {
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
            try {
                $this->getUserByName($userName);
            } catch (UserNotRegisteredException $e) {
                $user = User::create(
                    $this,
                    $userName,
                    $userNick,
                    $userPassword
                );

                $this->users[] = $user;

                // $this->recordDomainEvent(BibAppUserCreated::fromBibAppUser($user);

                return;
            }

            throw new UserNameAlreadyExistsException();
        }

        throw new UserNickAlreadyExistsException();
    }

    public function updateUserName(string $userNick, string $newUserName): self
    {
        $user = $this->getUserByUsername($userNick);

        $newUser = $user->updateAlias($newUserName);

        $this->users->set((int) $this->users->indexOf($user), $newUser);

//        foreach ($newUser->pullDomainEvents() as $domainEvent) {
//            $this->recordDomainEvent($domainEvent);
//        }

        return $this;
    }

    public function updateUserNick(string $userNick, string $newUserNick): self
    {
        $user = $this->getUserByUsername($userNick);

        $newUser = $user->updateUsername($newUserNick);

        $this->users->set((int) $this->users->indexOf($user), $newUser);

//        TODO
//        foreach ($newUser->pullDomainEvents() as $domainEvent) {
//            $this->recordDomainEvent($domainEvent);
//        }

        return $this;
    }

    public function updateUserPassword(string $userNick, string $newUserPassword): self
    {
        $user = $this->getUserByUsername($userNick);

        $newUser = $user->updatePassword($newUserPassword);

        $this->users->set((int) $this->users->indexOf($user), $newUser);

//        foreach ($newUser->pullDomainEvents() as $domainEvent) {
//            $this->recordDomainEvent($domainEvent);
//        }

        return $this;
    }

    public function login(string $userNick, string $userPassword): bool
    {
        try {
            $user = $this->getUserByUsername($userNick);
        } catch (UserNotRegisteredException) {
            return false;
        }

        if ($userPassword === $user->getPassword()) {
            // $this->recordDomainEvent(BibAppUserLogged::fromBibAppUser($user);
            return true;
        }

        // $this->recordDomainEvent(BibAppUserLoginFailed::fromBibAppUser($user);
        return false;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'users' => $this->users->toArray()
        ];
    }

    public function removeUser(string $username): self
    {
        $user = $this->getUserByUsername($username);

        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getApp() === $this) {
//                $user->setApp(null);
            }
        }

        return $this;
    }
}
