# Configuration

```bash
docker-compose up -d
```

```bash
docker-compose exec phpunit bin/console doctrine:database:create
docker-compose exec phpunit bin/console doctrine:migrations:migrate
```

# Run test suite

```bash
docker-compose exec phpunit bin/phpunit --testdox 
```

See as test passes. Now go to [src/Entity/User.php]() and change the
constant `App\Entity\User::IMMUTABLE_CLASS` from  `false` to `true`, 
and see how the check `it_should_not_login_if_credentials_are_wrong`, 
which updates an existing user, but using an inmutable pattern for
the `User` entity.