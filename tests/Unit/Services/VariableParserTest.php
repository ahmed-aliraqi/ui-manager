<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Unit\Services;

use AhmedAliraqi\UiManager\Services\VariableParser;
use AhmedAliraqi\UiManager\Tests\TestCase;
use AhmedAliraqi\UiManager\Variables\VariableRegistry;

final class VariableParserTest extends TestCase
{
    private VariableRegistry $registry;
    private VariableParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new VariableRegistry();
        $this->parser   = new VariableParser($this->registry);
    }

    public function test_parses_simple_variable(): void
    {
        $this->registry->value('app.name', 'My App');

        $result = $this->parser->parse('Welcome to %app.name%!');

        $this->assertSame('Welcome to My App!', $result);
    }

    public function test_parses_multiple_variables(): void
    {
        $this->registry->value('site.name', 'Acme');
        $this->registry->value('site.tagline', 'We rock');

        $result = $this->parser->parse('%site.name% — %site.tagline%');

        $this->assertSame('Acme — We rock', $result);
    }

    public function test_leaves_unknown_variable_intact(): void
    {
        $result = $this->parser->parse('Hello %unknown.key% world');

        $this->assertSame('Hello %unknown.key% world', $result);
    }

    public function test_prevents_infinite_recursion(): void
    {
        // A resolves to B, B resolves to A — would be infinite without depth guard
        $this->registry->register('a', fn () => '%b%');
        $this->registry->register('b', fn () => '%a%');

        // Should not throw or loop forever; depth guard kicks in
        $result = $this->parser->parse('%a%');

        $this->assertIsString($result);
    }

    public function test_resolver_callable_is_called_lazily(): void
    {
        $called = 0;
        $this->registry->register('count', function () use (&$called) {
            $called++;
            return 'value';
        });

        $this->parser->parse('no variables here');
        $this->assertSame(0, $called, 'Resolver should not be called if key not present');

        $this->parser->parse('%count%');
        $this->assertSame(1, $called);
    }

    public function test_extract_keys_finds_all_placeholders(): void
    {
        $keys = $this->parser->extractKeys('Hello %name%, welcome to %site.name%!');

        $this->assertContains('name', $keys);
        $this->assertContains('site.name', $keys);
    }

    public function test_placeholder_method_wraps_key(): void
    {
        $this->assertSame('%my.key%', $this->parser->placeholder('my.key'));
    }

    public function test_registry_has_method(): void
    {
        $this->registry->value('x', 1);

        $this->assertTrue($this->registry->has('x'));
        $this->assertFalse($this->registry->has('y'));
    }
}
