---
name: add-security
description: Secure the admin panel with a User entity, firewall and access control
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add Security — Sylius Stack

---

## 1. Create the User entity

Create `src/Entity/User.php`:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }
}
```

---

## 2. Create the UserRepository

Create `src/Repository/UserRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
}
```

---

## 3. Configure the user provider

Edit `config/packages/security.yaml` — add the password hasher and provider:

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
    providers:
        app_admin_user_provider:
            entity:
                class: App\Entity\User
                property: email
```

---

## 4. Configure the firewall

Edit `config/packages/security.yaml` — add the `admin` firewall **before** `main`:

```yaml
security:
    firewalls:
        admin:
            context: admin
            pattern: '/admin(?:/.*)?$'
            provider: app_admin_user_provider
            form_login:
                login_path: sylius_admin_ui_login
                check_path: sylius_admin_ui_login_check
                default_target_path: sylius_admin_ui_dashboard
            logout:
                path: sylius_admin_ui_logout
                target: sylius_admin_ui_login
        main:
            lazy: true
```

> **Important:** The `main` firewall must be placed **after** `admin`, otherwise the admin login will not work properly.

---

## 5. Configure access control

Edit `config/packages/security.yaml` — add the access control rules:

```yaml
security:
    access_control:
        - { path: ^/admin/login, roles: PUBLIC_ACCESS }
        - { path: ^/admin/logout, roles: PUBLIC_ACCESS }
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/, roles: PUBLIC_ACCESS }
```

---

## 6. Run the migration

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```