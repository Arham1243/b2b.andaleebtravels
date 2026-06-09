<?php

namespace App\Support;

final class SabreApplicationResultsParser
{
    /**
     * @param  array<string, mixed>|null  $responseData
     * @return list<string>
     */
    public static function messages(?array $responseData, string $rootKey = 'CreatePassengerNameRecordRS'): array
    {
        if ($responseData === null || $responseData === []) {
            return [];
        }

        $messages = [];

        foreach (['Error', 'Warning'] as $type) {
            $items = data_get($responseData, "{$rootKey}.ApplicationResults.{$type}", []);
            foreach (self::asList($items) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                foreach (self::asList($item['SystemSpecificResults'] ?? null) as $result) {
                    if (! is_array($result)) {
                        continue;
                    }

                    foreach (self::collectMessageValues($result['Message'] ?? null) as $message) {
                        if (! self::isLoggerPlaceholder($message)) {
                            $messages[] = $message;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($messages));
    }

    /**
     * @return list<string>
     */
    private static function collectMessageValues(mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? [$value] : [];
        }

        if (! is_array($value)) {
            return [];
        }

        $messages = [];
        foreach ($value as $item) {
            foreach (self::collectMessageValues($item) as $message) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    private static function isLoggerPlaceholder(string $message): bool
    {
        return str_contains($message, 'Over 9 levels deep, aborting normalization')
            || str_contains($message, 'aborting normalization');
    }

    /**
     * @return list<mixed>
     */
    private static function asList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [$value];
        }

        return array_is_list($value) ? $value : [$value];
    }
}
