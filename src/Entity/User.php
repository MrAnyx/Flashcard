<?php

declare(strict_types=1);

namespace App\Entity;

use App\Attribute\Searchable;
use App\Attribute\Sortable;
use App\Enum\SettingName;
use App\FilterConverter\DateTimeConverter;
use App\Repository\UserRepository;
use App\Setting\SettingEntry;
use App\Setting\SettingTemplate;
use App\Utility\Regex;
use App\Utility\Roles;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table('`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered. Please, use this email to login or use another email')]
#[UniqueEntity(fields: ['username'], message: 'This username is already registered. Please, use this username to login or use another username')]
class User implements PasswordAuthenticatedUserInterface, UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['read:user:user'])]
    #[Sortable]
    #[Searchable]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Your email can not be blank')]
    #[Assert\Email(message: 'Your email is invalid and doesn\'t respect the email format')]
    #[Assert\Length(max: 180, maxMessage: 'Your email can not exceed {{ limit }} characters')]
    #[Groups(['read:user:user', 'write:user:user'])]
    #[Sortable]
    #[Searchable]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 30, unique: true)]
    #[Assert\NotBlank(message: 'Your username can not be blank')]
    #[Assert\Length(max: 30, maxMessage: 'Your username can not exceed {{ limit }} characters')]
    #[Assert\Regex(pattern: Regex::USERNAME_SLASH, message: 'Your username must only contain letters, numbers, dots, dashes or underscores')]
    #[Groups(['read:user:user', 'write:user:user'])]
    #[Sortable]
    #[Searchable]
    private ?string $username = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'The token can not be blank')]
    #[Assert\Length(max: 100, maxMessage: 'The token can not exceed {{ limit }} characters')]
    #[Groups(['read:user:user'])]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['read:user:user'])]
    #[Sortable]
    #[Searchable(DateTimeConverter::class, ['format' => \DateTimeInterface::ATOM])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['read:user:user'])]
    #[Sortable]
    #[Searchable(DateTimeConverter::class, ['format' => \DateTimeInterface::ATOM])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['read:user:user'])]
    private array $roles = [Roles::User];

    #[ORM\Column(type: Types::STRING)]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'Your password can not be blank', groups: ['edit:user:password'])]
    // #[Assert\NotCompromisedPassword(message: 'This password has been compromised. Please choose another password', groups: ['edit:user:password'])]
    #[Assert\Regex(pattern: Regex::PASSWORD_SLASH, groups: ['edit:user:password'])]
    #[Assert\NotEqualTo(propertyPath: 'username', message: 'You must choose a stronger password', groups: ['edit:user:password'])]
    #[Assert\NotEqualTo(propertyPath: 'email', message: 'You must choose a stronger password', groups: ['edit:user:password'])]
    #[Groups(['write:user:user'])]
    private ?string $rawPassword = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Topic::class, orphanRemoval: true)]
    private Collection $topics;

    /**
     * @var Collection<int, Setting>
     */
    #[ORM\OneToMany(targetEntity: Setting::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['read:user:user'])]
    private Collection $settings;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['read:user:user'])]
    private ?\DateTimeImmutable $premiumAt = null;

    /**
     * @var Collection<int, Session>
     */
    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $sessions;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): static
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedAt(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = Roles::User;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): static
    {
        $this->roles[] = $role;
        $this->roles = array_unique($this->roles);

        return $this;
    }

    public function removeRole(string $role): static
    {
        $this->roles = array_unique(array_diff($this->roles, [$role]));

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->rawPassword = null;
    }

    public function getRawPassword(): ?string
    {
        return $this->rawPassword;
    }

    public function setRawPassword(?string $rawPassword): static
    {
        $this->rawPassword = $rawPassword;

        return $this;
    }

    /**
     * @return Collection<int, Topic>
     */
    public function getTopics(): Collection
    {
        return $this->topics;
    }

    public function addTopic(Topic $topic): static
    {
        if (!$this->topics->contains($topic)) {
            $this->topics->add($topic);
            $topic->setAuthor($this);
        }

        return $this;
    }

    public function removeTopic(Topic $topic): static
    {
        if ($this->topics->removeElement($topic)) {
            // set the owning side to null (unless already changed)
            if ($topic->getAuthor() === $this) {
                $topic->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $settingsArray = [];

        foreach ($this->settings as $setting) {
            $settingsArray[$setting->getName()->value] = $setting->getValue();
        }

        $mergedSettings = array_merge(SettingTemplate::getAssociativeTemplate(), $settingsArray);

        return $mergedSettings;
    }

    public function getSetting(SettingName $settingName): mixed
    {
        $settings = $this->getSettings();

        return \array_key_exists($settingName->value, $settings)
            ? $settings[$settingName->value]
            : throw new \InvalidArgumentException("Unknown setting name {$settingName->value}");
    }

    public function updateSetting(SettingEntry $setting): static
    {
        /** @var ?Setting $existingSetting */
        $existingSetting = null;

        foreach ($this->settings as $existing) {
            if ($existing->getName() === $setting->name) {
                $existingSetting = $existing;
                break;
            }
        }

        if ($existingSetting !== null) {
            $existingSetting->setValue($setting->serialize());
        } else {
            $newSetting = new Setting($setting, $this);
            $this->settings->add($newSetting);
        }

        return $this;
    }

    public function getPremiumAt(): ?\DateTimeImmutable
    {
        return $this->premiumAt;
    }

    public function setPremiumAt(?\DateTimeImmutable $premiumAt): static
    {
        $this->premiumAt = $premiumAt;

        return $this;
    }

    public function enablePremium(): static
    {
        $this
            ->addRole(Roles::Premium)
            ->setPremiumAt(new \DateTimeImmutable());

        return $this;
    }

    public function disablePremium(): static
    {
        $this
            ->removeRole(Roles::Premium)
            ->setPremiumAt(null);

        return $this;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setAuthor($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getAuthor() === $this) {
                $session->setAuthor(null);
            }
        }

        return $this;
    }
}
