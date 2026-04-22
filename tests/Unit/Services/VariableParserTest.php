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

    // ------------------------------------------------------------------ modifiers

    public function test_url_modifier_returns_url_from_media_array(): void
    {
        $this->registry->value('header.logo', [
            'id'       => 1,
            'url'      => 'https://cdn.example.com/logo.png',
            'filename' => 'logo.png',
        ]);

        $result = $this->parser->parse('Logo: %header.logo:url%');

        $this->assertSame('Logo: https://cdn.example.com/logo.png', $result);
    }

    public function test_name_modifier_returns_filename_from_media_array(): void
    {
        $this->registry->value('header.logo', [
            'id'       => 1,
            'url'      => 'https://cdn.example.com/logo.png',
            'filename' => 'logo.png',
        ]);

        $result = $this->parser->parse('File: %header.logo:name%');

        $this->assertSame('File: logo.png', $result);
    }

    public function test_size_modifier_returns_size_from_media_array(): void
    {
        $this->registry->value('banner.image', [
            'id'       => 2,
            'url'      => 'https://cdn.example.com/banner.jpg',
            'filename' => 'banner.jpg',
            'size'     => 102400,
        ]);

        $result = $this->parser->parse('%banner.image:size%');

        $this->assertSame('102400', $result);
    }

    public function test_modifier_on_string_value_returns_value_unchanged(): void
    {
        $this->registry->value('app.name', 'My App');

        // String values ignore modifiers gracefully
        $result = $this->parser->parse('%app.name:url%');

        $this->assertSame('My App', $result);
    }

    public function test_modifier_on_unknown_variable_leaves_placeholder_intact(): void
    {
        $result = $this->parser->parse('%unknown.field:url%');

        $this->assertSame('%unknown.field:url%', $result);
    }

    public function test_extract_keys_includes_modifier_suffix(): void
    {
        $keys = $this->parser->extractKeys('%header.logo:url% and %app.name%');

        $this->assertContains('header.logo:url', $keys);
        $this->assertContains('app.name', $keys);
    }
}
