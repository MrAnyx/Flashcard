<?php

declare(strict_types=1);

namespace App\Enum;

enum SettingName: string
{
    use EnumUtility;

    case ITEMS_PER_PAGE = 'items_per_page';

    case FLASHCARD_PER_SESSION = 'flashcard_per_session';

    case COLOR_THEME = 'color_theme';

    case PRIMARY_COLOR = 'primary_color';

    case GRAY_COLOR = 'gray_color';

    case SHOW_SESSION_INTRODUCTION = 'show_session_introduction';

    case REVIEWS_PER_DAY_GOAL = 'reviews_per_day_goal';
}
