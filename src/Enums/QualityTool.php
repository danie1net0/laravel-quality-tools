<?php

namespace DDR\LaravelQualityTools\Enums;

enum QualityTool: string
{
    case PHPSTAN = 'phpstan';

    case PINT = 'pint';

    case HUSKY = 'husky';

    case PRETTIER = 'prettier';

    case COMMITLINT = 'commitlint';

    case COMMITIZEN = 'commitizen';

    public function getLabel(): string
    {
        return match ($this) {
            self::PHPSTAN => 'LaravelStan',
            self::PINT => 'Pint',
            self::HUSKY => 'Husky',
            self::PRETTIER => 'Prettier',
            self::COMMITLINT => 'Commitlint',
            self::COMMITIZEN => 'Commitizen',
        };
    }

    public static function options(): array
    {
        return array_merge(...array_map(fn (self $item) => [$item->value => $item->getLabel()], self::cases()));
    }
}
