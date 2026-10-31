<?php
// MappingEngine self-check, no DB or server needed. Run: php php/tests/connector/mappingCheck.php
if (PHP_SAPI !== 'cli') {
    exit;
}

require_once __DIR__ . '/../../bootstrap.php';

use App\Integrations\Connector\MappingEngine;

$failed = 0;

function check(string $name, bool $ok): void
{
    global $failed;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
}

function throwsOn(callable $fn): bool
{
    try {
        $fn();
    } catch (\UnexpectedValueException $e) {
        return true;
    }

    return false;
}

$input = ['n' => 5, 's' => ' ab ', 'f' => 1.5, 'j' => '{"a":1}', 'flag' => true, 'x' => '', 'kind' => 'b', 'items' => [['id' => 1], ['id' => 2]]];
$ctx = ['input' => $input, 'config' => ['base' => 'v1'], 'credential' => []];

// Placeholders
check('whole placeholder keeps int type', MappingEngine::render('{{input.n}}', $ctx) === 5);
check('placeholder inside text becomes string', MappingEngine::render('x-{{input.n}}-y', $ctx) === 'x-5-y');
check('missing path renders null', MappingEngine::render('{{input.nope}}', $ctx) === null);
check('trim | upper chain', MappingEngine::render('{{input.s | trim | upper}}', ['input' => ['s' => 'ab']]) === 'AB');
check('default transform', MappingEngine::render('{{input.nope | default:fallback}}', $ctx) === 'fallback');
check('number transform', MappingEngine::render('{{input.f | number:2}}', $ctx) === '1.50');
check('json_decode transform', MappingEngine::render('{{input.j | json_decode}}', $ctx) === ['a' => 1]);
check('config root', MappingEngine::render('{{config.base}}', $ctx) === 'v1');

// Operators
check('$if true', MappingEngine::render(['$if' => 'input.flag', 'then' => 'yes', 'else' => 'no'], $ctx) === 'yes');
check('$if false uses else', MappingEngine::render(['$if' => 'input.x', 'then' => 'yes', 'else' => 'no'], $ctx) === 'no');
check('$if false without else drops key', MappingEngine::render(['a' => ['$if' => 'input.x', 'then' => 1], 'b' => 2], $ctx) === ['b' => 2]);
$switch = ['$switch' => 'input.kind', 'cases' => ['a' => 'A', 'b' => 'B'], 'default' => 'D'];
check('$switch match', MappingEngine::render($switch, $ctx) === 'B');
check('$switch default', MappingEngine::render(['$switch' => 'input.nope', 'cases' => ['a' => 'A'], 'default' => 'D'], $ctx) === 'D');
check('$omitIfEmpty drops empty key', MappingEngine::render(['a' => ['$omitIfEmpty' => '{{input.x}}'], 'b' => 1], $ctx) === ['b' => 1]);
check('$omitIfEmpty keeps value', MappingEngine::render(['a' => ['$omitIfEmpty' => '{{input.n}}']], $ctx) === ['a' => 5]);
$each = ['$each' => 'input.items', 'as' => 'it', 'template' => ['id' => '{{it.id}}']];
check('$each maps items with alias', MappingEngine::render($each, $ctx) === [['id' => 1], ['id' => 2]]);

// Errors while rendering
check('unknown operator throws', throwsOn(fn () => MappingEngine::render(['$eval' => 'x'], $ctx)));
check('unknown transform throws', throwsOn(fn () => MappingEngine::render('{{input.n | eval}}', $ctx)));

// Validation
check('valid request spec has no errors', MappingEngine::validateRequest(['path' => ['id' => '{{input.id | int}}'], 'body' => ['$each' => 'input.items', 'as' => 'it', 'template' => '{{it.id}}']]) === []);
check('request spec: unknown root reported', MappingEngine::validateRequest(['body' => '{{response.x}}']) !== []);
check('request spec: unknown transform reported', MappingEngine::validateRequest(['body' => '{{input.a | eval:1}}']) !== []);
check('request spec: unknown operator reported', MappingEngine::validateRequest(['body' => ['$eval' => 'input.a']]) !== []);
check('response spec: valid', MappingEngine::validateResponse(['extract' => 'a.0.b', 'then' => ['json_decode'], 'map' => '{{response}}']) === []);
check('response spec: unknown transform reported', MappingEngine::validateResponse(['then' => ['exec']]) !== []);
check('response spec: input root is not allowed', MappingEngine::validateResponse(['map' => '{{input.a}}']) !== []);

// Response extraction
check('extract path and int transform', MappingEngine::extract(['a' => [['b' => '7']]], ['extract' => 'a.0.b', 'then' => ['int']]) === 7);
check('extract with map', MappingEngine::extract(['a' => [['b' => '7']]], ['extract' => 'a.0.b', 'map' => 'got {{response}}']) === 'got 7');
check('extract without spec returns data', MappingEngine::extract(['x' => 1], []) === ['x' => 1]);

echo PHP_EOL . ($failed === 0 ? 'ALL PASSED' : "$failed FAILED") . PHP_EOL;
exit($failed === 0 ? 0 : 1);
