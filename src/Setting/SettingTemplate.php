<?php

declare(strict_types=1);

namespace App\Setting;

use App\Enum\SettingName;
use App\Serializer\BooleanSerializer;
use App\Serializer\IntegerSerializer;
use App\Serializer\StringSerializer;
use Symfony\Component\Validator\Constraints as Assert;

class SettingTemplate
{
    /**
     * @return SettingEntry[]
     */
    public static function getTemplate(): array
    {
        return [
            new SettingEntry(SettingName::ITEMS_PER_PAGE, 50, new IntegerSerializer(), [
                new Assert\Range(min: 1, max: 1000),
            ]),
            new SettingEntry(SettingName::FLASHCARD_PER_SESSION, 20, new IntegerSerializer(), [
                new Assert\Range(min: 1, max: 50),
            ]),
            new SettingEntry(SettingName::COLOR_THEME, 'system', new StringSerializer(), [
                new Assert\Choice(['light', 'dark', 'system']),
            ]),
            new SettingEntry(SettingName::PRIMARY_COLOR, 'sky', new StringSerializer(), [
                new Assert\Choice(['red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose']),
            ]),
            new SettingEntry(SettingName::GRAY_COLOR, 'cool', new StringSerializer(), [
                new Assert\Choice(['slate', 'cool', 'zinc', 'neutral', 'stone']),
            ]),
            new SettingEntry(SettingName::SHOW_SESSION_INTRODUCTION, true, new BooleanSerializer()),
            new SettingEntry(SettingName::REVIEWS_PER_DAY_GOAL, 20, new IntegerSerializer(), [
                new Assert\Range(min: 1, max: 1000),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getAssociativeTemplate(): array
    {
        $template = [];

        foreach (self::getTemplate() as $setting) {
            $template[$setting->name->value] = $setting->getValue();
        }

        return $template;
    }

    public static function getSettingEntry(SettingName $name): SettingEntry
    {
        $settings = self::getTemplate();

        foreach ($settings as $setting) {
            if ($setting->name === $name) {
                return $setting;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Unknown setting name %s, allowed setting name are %s', $name->value, implode(', ', array_keys($settings))));
    }
}
