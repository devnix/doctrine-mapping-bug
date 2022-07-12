<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\App;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;

/**
 * @extends ServiceEntityRepository<App>
 */
final class AppRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, App::class);
    }

    public function nextIdentity(): string
    {
        return Uuid::uuid4()->toString();
    }

    public function save(App $app)
    {
        foreach ($app->getUsers() as $user) {
            $this->getEntityManager()->persist($user);
        }
        $this->getEntityManager()->persist($app);

        $this->getEntityManager()->flush();
    }
}