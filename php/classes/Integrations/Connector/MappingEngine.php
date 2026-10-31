<?php
namespace App\Integrations\Connector;

/**
 * JSON mapping for request_spec, response_spec and error_spec. Data only: no eval, no user regex.
 *   "{{input.a.b | trim | default:x}}"  whole string keeps the value's type, inside text it becomes a string
 *   {"$if": path, "then": T, "else": E}
 *   {"$switch": path, "cases": {value: T}, "default": D}
 *   {"$omitIfEmpty": T}                 the key is dropped when T is empty
 *   {"$each": path, "as": alias, "template": T}
 */
class MappingEngine
{
    private const PLACEHOLDER = '/\{\{\s*([A-Za-z0-9_.]+)\s*((?:\|[^|{}]*)*)\}\}/';
    private const OPERATORS = ['$if', '$switch', '$omitIfEmpty', '$each'];
    private const TRANSFORMS = ['trim', 'upper', 'lower', 'int', 'float', 'string', 'bool', 'json_encode', 'json_decode', 'default', 'number', 'date'];

    /**
     * Renders $tpl against $ctx (roots: input, config, credential, plus any $each alias). Throws on a bad template.
     */
    public static function render(mixed $tpl, array $ctx): mixed
    {
        $value = self::renderNode($tpl, $ctx);

        return $value === self::missing() ? null : $value;
    }

    /**
     * Pulls a value out of a decoded response, runs the transforms, then renders the optional map with root response
     */
    public static function extract(mixed $data, array $spec): mixed
    {
        $value = isset($spec['extract']) ? self::pick($data, (string)$spec['extract']) : $data;
        foreach ($spec['then'] ?? [] as $name) {
            $value = self::transform($value, (string)$name);
        }

        return isset($spec['map']) ? self::render($spec['map'], ['response' => $value]) : $value;
    }

    /**
     * Problems in a request_spec as messages. Empty when valid.
     */
    public static function validateRequest(array $spec): array
    {
        $errors = [];
        self::check($spec, ['input', 'config', 'credential'], $errors);

        return $errors;
    }

    /**
     * Problems in a response_spec or error_spec as messages. Empty when valid.
     */
    public static function validateResponse(array $spec): array
    {
        $errors = [];
        foreach ($spec['then'] ?? [] as $name) {
            if (!self::knownTransform((string)$name)) {
                $errors[] = 'Unknown transform ' . $name;
            }
        }
        if (isset($spec['map'])) {
            self::check($spec['map'], ['response'], $errors);
        }

        return $errors;
    }

    private static function renderNode(mixed $tpl, array $ctx): mixed
    {
        if (is_string($tpl)) {
            return self::renderString($tpl, $ctx);
        }
        if (!is_array($tpl)) {
            return $tpl;
        }

        $op = self::operatorKey($tpl);
        if ($op !== null) {
            return self::renderOperator($op, $tpl, $ctx);
        }

        $out = [];
        foreach ($tpl as $key => $child) {
            $value = self::renderNode($child, $ctx);
            if ($value !== self::missing()) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private static function renderOperator(string $op, array $tpl, array $ctx): mixed
    {
        switch ($op) {
            case '$if':
                $branch = !empty(self::pick($ctx, (string)$tpl['$if'])) ? 'then' : 'else';

                return array_key_exists($branch, $tpl) ? self::renderNode($tpl[$branch], $ctx) : self::missing();

            case '$switch':
                $value = self::text(self::pick($ctx, (string)$tpl['$switch']));
                if (array_key_exists($value, $tpl['cases'] ?? [])) {
                    return self::renderNode($tpl['cases'][$value], $ctx);
                }

                return array_key_exists('default', $tpl) ? self::renderNode($tpl['default'], $ctx) : self::missing();

            case '$omitIfEmpty':
                $value = self::renderNode($tpl['$omitIfEmpty'], $ctx);

                return $value === null || $value === '' || $value === [] ? self::missing() : $value;

            case '$each':
                $items = self::pick($ctx, (string)$tpl['$each']);
                $alias = (string)($tpl['as'] ?? 'item');
                $out = [];
                foreach (is_array($items) ? $items : [] as $item) {
                    $value = self::renderNode($tpl['template'] ?? null, array_merge($ctx, [$alias => $item]));
                    if ($value !== self::missing()) {
                        $out[] = $value;
                    }
                }

                return $out;
        }

        throw new \UnexpectedValueException('Unknown operator ' . $op);
    }

    private static function renderString(string $tpl, array $ctx): mixed
    {
        if (!preg_match(self::PLACEHOLDER, $tpl, $m)) {
            return $tpl;
        }
        if ($m[0] === $tpl) {
            return self::resolve($m, $ctx);
        }

        return preg_replace_callback(self::PLACEHOLDER, function ($hit) use ($ctx) {
            return self::text(self::resolve($hit, $ctx));
        }, $tpl);
    }

    private static function resolve(array $hit, array $ctx): mixed
    {
        $value = self::pick($ctx, $hit[1]);
        foreach (explode('|', $hit[2] ?? '') as $name) {
            $name = trim($name);
            if ($name !== '') {
                $value = self::transform($value, $name);
            }
        }

        return $value;
    }

    private static function transform(mixed $value, string $name): mixed
    {
        [$fn, $arg] = array_pad(explode(':', $name, 2), 2, null);

        switch ($fn) {
            case 'trim':
                return trim(self::text($value));
            case 'upper':
                return strtoupper(self::text($value));
            case 'lower':
                return strtolower(self::text($value));
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'string':
                return self::text($value);
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json_encode':
                return json_encode($value);
            case 'json_decode':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'default':
                return $value === null || $value === '' ? $arg : $value;
            case 'number':
                return number_format((float)$value, (int)$arg, '.', '');
            case 'date':
                $date = date_create(self::text($value));
                if (!$date) {
                    throw new \UnexpectedValueException('Not a date: ' . self::text($value));
                }

                return $date->format((string)$arg);
        }

        throw new \UnexpectedValueException('Unknown transform ' . $fn);
    }

    /**
     * Walks a template and records problems: unknown roots, operators and transforms
     */
    private static function check(mixed $tpl, array $roots, array &$errors): void
    {
        if (is_string($tpl)) {
            preg_match_all(self::PLACEHOLDER, $tpl, $hits, PREG_SET_ORDER);
            foreach ($hits as $hit) {
                self::checkPath($hit[1], $roots, $errors);
                foreach (explode('|', $hit[2] ?? '') as $name) {
                    $name = trim($name);
                    if ($name !== '' && !self::knownTransform($name)) {
                        $errors[] = 'Unknown transform ' . $name;
                    }
                }
            }

            return;
        }
        if (!is_array($tpl)) {
            return;
        }

        $op = self::operatorKey($tpl);
        if ($op === null) {
            foreach ($tpl as $child) {
                self::check($child, $roots, $errors);
            }

            return;
        }
        if (!in_array($op, self::OPERATORS, true)) {
            $errors[] = 'Unknown operator ' . $op;

            return;
        }

        switch ($op) {
            case '$if':
                self::checkPath((string)$tpl['$if'], $roots, $errors);
                self::check($tpl['then'] ?? null, $roots, $errors);
                self::check($tpl['else'] ?? null, $roots, $errors);
                break;
            case '$switch':
                self::checkPath((string)$tpl['$switch'], $roots, $errors);
                foreach ($tpl['cases'] ?? [] as $case) {
                    self::check($case, $roots, $errors);
                }
                self::check($tpl['default'] ?? null, $roots, $errors);
                break;
            case '$omitIfEmpty':
                self::check($tpl['$omitIfEmpty'], $roots, $errors);
                break;
            case '$each':
                self::checkPath((string)$tpl['$each'], $roots, $errors);
                self::check($tpl['template'] ?? null, array_merge($roots, [(string)($tpl['as'] ?? 'item')]), $errors);
                break;
        }
    }

    private static function checkPath(string $path, array $roots, array &$errors): void
    {
        if (!in_array(explode('.', $path)[0], $roots, true)) {
            $errors[] = 'Unknown root in ' . $path;
        }
    }

    private static function operatorKey(array $tpl): ?string
    {
        $key = (string)array_key_first($tpl);

        return $key !== '' && $key[0] === '$' ? $key : null;
    }

    private static function knownTransform(string $name): bool
    {
        return in_array(explode(':', $name)[0], self::TRANSFORMS, true);
    }

    private static function pick(mixed $value, string $path): mixed
    {
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private static function text(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string)$value;
    }

    private static function missing(): object
    {
        static $missing = null;

        return $missing ??= new \stdClass();
    }
}
