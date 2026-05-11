<?php

namespace Zaeem2396\SchemaLens\Services\Introspection;

use Illuminate\Support\Str;

class PostgresColumnTypeFormatter
{
    /**
     * @param  object|array<string,mixed>  $c
     */
    public static function format(object|array $c): string
    {
        $dataType = strtolower((string) self::attr($c, 'data_type'));
        $udt = strtolower((string) self::attr($c, 'udt_name'));
        $precision = self::attr($c, 'numeric_precision');
        $scale = self::attr($c, 'numeric_scale');
        $maxLen = self::attr($c, 'character_maximum_length');
        $datetimePrec = self::attr($c, 'datetime_precision');

        if ($dataType === 'user-defined') {
            return $udt !== '' ? $udt : 'user-defined';
        }

        return match ($dataType) {
            'character varying' => ($maxLen !== null && $maxLen !== '') ? 'varchar('.(int) $maxLen.')' : 'varchar',
            'character' => ($maxLen !== null && $maxLen !== '') ? 'char('.(int) $maxLen.')' : 'char',
            'text' => 'text',
            'boolean' => 'boolean',
            'smallint' => 'smallint',
            'integer', 'int4' => 'integer',
            'bigint' => 'bigint',
            'real', 'float4' => 'real',
            'double precision', 'float8' => 'double precision',
            'numeric', 'decimal' => self::numericType($precision, $scale),
            'timestamp with time zone' => $datetimePrec ? 'timestamptz('.(int) $datetimePrec.')' : 'timestamptz',
            'timestamp without time zone' => $datetimePrec ? 'timestamp('.(int) $datetimePrec.')' : 'timestamp',
            'date' => 'date',
            'time with time zone' => 'timetz',
            'time without time zone' => 'time',
            'uuid' => 'uuid',
            'json' => 'json',
            'jsonb' => 'jsonb',
            'bytea' => 'bytea',
            default => $udt !== '' ? $udt : ($dataType !== '' ? $dataType : 'unknown'),
        };
    }

    public static function extraFromDefault(mixed $default): string
    {
        if (! is_string($default)) {
            return '';
        }

        if (Str::contains(strtolower($default), 'nextval')) {
            return 'auto_increment';
        }

        return '';
    }

    /**
     * @param  object|array<string,mixed>  $row
     */
    protected static function attr(object|array $row, string $key): mixed
    {
        if (is_array($row)) {
            foreach ($row as $k => $value) {
                if (strcasecmp((string) $k, $key) === 0) {
                    return $value;
                }
            }

            return null;
        }

        foreach (array_keys(get_object_vars($row)) as $prop) {
            if (strcasecmp((string) $prop, $key) === 0) {
                return $row->{$prop};
            }
        }

        return null;
    }

    protected static function numericType(mixed $precision, mixed $scale): string
    {
        if ($precision !== null && $precision !== '' && $scale !== null && $scale !== '') {
            return 'decimal('.(int) $precision.','.(int) $scale.')';
        }
        if ($precision !== null && $precision !== '') {
            return 'decimal('.(int) $precision.')';
        }

        return 'numeric';
    }
}
