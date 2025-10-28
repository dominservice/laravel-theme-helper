<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Schema;

final class FormatValidator
{
    public static function isUrl(mixed $v): bool
    {
        return \is_string($v) && $v !== '' && (bool)\filter_var($v, \FILTER_VALIDATE_URL);
    }

    public static function isIso8601Date(mixed $v): bool
    {
        return \is_string($v) && $v !== ''
            && (bool)\preg_match('/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2})?(Z|[+\-]\d{2}:\d{2})?)?$/', $v);
    }

    public static function isIso8601Duration(mixed $v): bool
    {
        return \is_string($v) && $v !== ''
            && (bool)\preg_match('/^P(?!$)(\d+Y)?(\d+M)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$/', $v);
    }
}
