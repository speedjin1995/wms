<?php
if (PHP_SAPI !== 'cli') {
    exit;
}

use App\Integrations\Connector\MappingEngine;
use PHPUnit\Framework\TestCase;

/**
 * No database or server needed
 */
class MappingEngineTest extends TestCase
{
    private array $ctx;

    protected function setUp(): void
    {
        $input = ['n' => 5, 's' => ' ab ', 'f' => 1.5, 'j' => '{"a":1}', 'flag' => true, 'x' => '', 'kind' => 'b', 'items' => [['id' => 1], ['id' => 2]]];
        $this->ctx = ['input' => $input, 'config' => ['base' => 'v1'], 'credential' => []];
    }

    public function testPlaceholders(): void
    {
        $ctx = $this->ctx;
        $this->assertSame(5, MappingEngine::render('{{input.n}}', $ctx), 'whole placeholder keeps int type');
        $this->assertSame('x-5-y', MappingEngine::render('x-{{input.n}}-y', $ctx), 'placeholder inside text becomes string');
        $this->assertNull(MappingEngine::render('{{input.nope}}', $ctx), 'missing path renders null');
        $this->assertSame('AB', MappingEngine::render('{{input.s | trim | upper}}', ['input' => ['s' => 'ab']]), 'trim | upper chain');
        $this->assertSame('fallback', MappingEngine::render('{{input.nope | default:fallback}}', $ctx), 'default transform');
        $this->assertSame('1.50', MappingEngine::render('{{input.f | number:2}}', $ctx), 'number transform');
        $this->assertSame(['a' => 1], MappingEngine::render('{{input.j | json_decode}}', $ctx), 'json_decode transform');
        $this->assertSame('v1', MappingEngine::render('{{config.base}}', $ctx), 'config root');
    }

    public function testOperators(): void
    {
        $ctx = $this->ctx;
        $this->assertSame('yes', MappingEngine::render(['$if' => 'input.flag', 'then' => 'yes', 'else' => 'no'], $ctx), '$if true');
        $this->assertSame('no', MappingEngine::render(['$if' => 'input.x', 'then' => 'yes', 'else' => 'no'], $ctx), '$if false uses else');
        $this->assertSame(['b' => 2], MappingEngine::render(['a' => ['$if' => 'input.x', 'then' => 1], 'b' => 2], $ctx), '$if false without else drops key');

        $switch = ['$switch' => 'input.kind', 'cases' => ['a' => 'A', 'b' => 'B'], 'default' => 'D'];
        $this->assertSame('B', MappingEngine::render($switch, $ctx), '$switch match');
        $this->assertSame('D', MappingEngine::render(['$switch' => 'input.nope', 'cases' => ['a' => 'A'], 'default' => 'D'], $ctx), '$switch default');

        $this->assertSame(['b' => 1], MappingEngine::render(['a' => ['$omitIfEmpty' => '{{input.x}}'], 'b' => 1], $ctx), '$omitIfEmpty drops empty key');
        $this->assertSame(['a' => 5], MappingEngine::render(['a' => ['$omitIfEmpty' => '{{input.n}}']], $ctx), '$omitIfEmpty keeps value');

        $each = ['$each' => 'input.items', 'as' => 'it', 'template' => ['id' => '{{it.id}}']];
        $this->assertSame([['id' => 1], ['id' => 2]], MappingEngine::render($each, $ctx), '$each maps items with alias');
    }

    public function testUnknownOperatorThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class); // unknown operator throws
        MappingEngine::render(['$eval' => 'x'], $this->ctx);
    }

    public function testUnknownTransformThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class); // unknown transform throws
        MappingEngine::render('{{input.n | eval}}', $this->ctx);
    }

    public function testValidation(): void
    {
        $this->assertSame([], MappingEngine::validateRequest(['path' => ['id' => '{{input.id | int}}'], 'body' => ['$each' => 'input.items', 'as' => 'it', 'template' => '{{it.id}}']]), 'valid request spec has no errors');
        $this->assertNotSame([], MappingEngine::validateRequest(['body' => '{{response.x}}']), 'request spec: unknown root reported');
        $this->assertNotSame([], MappingEngine::validateRequest(['body' => '{{input.a | eval:1}}']), 'request spec: unknown transform reported');
        $this->assertNotSame([], MappingEngine::validateRequest(['body' => ['$eval' => 'input.a']]), 'request spec: unknown operator reported');
        $this->assertSame([], MappingEngine::validateResponse(['extract' => 'a.0.b', 'then' => ['json_decode'], 'map' => '{{response}}']), 'response spec: valid');
        $this->assertNotSame([], MappingEngine::validateResponse(['then' => ['exec']]), 'response spec: unknown transform reported');
        $this->assertNotSame([], MappingEngine::validateResponse(['map' => '{{input.a}}']), 'response spec: input root is not allowed');
    }

    public function testResponseExtraction(): void
    {
        $this->assertSame(7, MappingEngine::extract(['a' => [['b' => '7']]], ['extract' => 'a.0.b', 'then' => ['int']]), 'extract path and int transform');
        $this->assertSame('got 7', MappingEngine::extract(['a' => [['b' => '7']]], ['extract' => 'a.0.b', 'map' => 'got {{response}}']), 'extract with map');
        $this->assertSame(['x' => 1], MappingEngine::extract(['x' => 1], []), 'extract without spec returns data');
    }
}
