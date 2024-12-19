<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SettingName;
use App\Repository\SettingRepository;
use App\Setting\SettingEntry;
use App\Setting\SettingTemplate;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: SettingName::class)]
    #[Groups(['write:setting:user'])]
    private ?SettingName $name = null;

    #[ORM\Column(type: Types::STRING, length: 1000)]
    #[Groups(['write:setting:user'])]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'settings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct(SettingEntry $settingEntry, User $user)
    {
        $this
            ->setName($settingEntry->name)
            ->setValue($settingEntry->serialize())
            ->setUser($user);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?SettingName
    {
        return $this->name;
    }

    public function setName(SettingName $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): mixed
    {
        $templateSetting = SettingTemplate::getSettingEntry($this->name);

        return $templateSetting->deserialize($this->value);
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
