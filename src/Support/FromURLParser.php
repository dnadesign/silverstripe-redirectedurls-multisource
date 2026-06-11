<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Support;

final class FromURLParser
{
    /**
     * @return array{0:string,1:string|null}
     */
    public static function parseFromURL(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['', null];
        }

        $value = ltrim($value, ',');
        $value = rtrim($value, ',');

        if (str_contains($value, '?')) {
            [$base, $querystring] = explode('?', $value, 2);
        } else {
            $base = $value;
            $querystring = null;
        }

        return [
            self::normaliseBase($base),
            self::normaliseQuerystring($querystring),
        ];
    }

    public static function formatFromURL(string $base, ?string $querystring): string
    {
        if ($querystring) {
            return sprintf('%s?%s', $base, $querystring);
        }

        return $base;
    }

    public static function normaliseBase(string $base): string
    {
        $base = trim($base);

        if ($base === '') {
            return '';
        }

        if ($base[0] !== '/') {
            $base = '/' . $base;
        }

        if ($base !== '/') {
            $base = rtrim($base, '/');
        }

        return rtrim($base, '?');
    }

    public static function normaliseQuerystring(?string $querystring): ?string
    {
        if ($querystring === null) {
            return null;
        }

        $querystring = trim($querystring);
        if ($querystring === '') {
            return null;
        }

        $querystring = rtrim($querystring, '?');

        return strtolower($querystring);
    }
}
