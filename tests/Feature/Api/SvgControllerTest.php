<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Feature\Api;

use AhmedAliraqi\UiManager\Tests\TestCase;
use Illuminate\Support\Facades\File;

final class SvgControllerTest extends TestCase
{
    private string $iconsDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Use a temp directory as the icons source for each test
        $this->iconsDir = sys_get_temp_dir() . '/ui-manager-test-icons-' . uniqid();
        mkdir($this->iconsDir, 0755, true);

        config(['ui-manager.svg.icons_path' => $this->iconsDir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->iconsDir);
        parent::tearDown();
    }

    public function test_returns_empty_list_when_no_icons(): void
    {
        $this->getJson(route('ui-manager.api.svg-icons.index'))
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_returns_svg_icons_from_configured_directory(): void
    {
        file_put_contents($this->iconsDir . '/star.svg', '<svg><polygon points="12,2 15,9 22,9"/></svg>');
        file_put_contents($this->iconsDir . '/circle.svg', '<svg><circle cx="12" cy="12" r="10"/></svg>');

        $response = $this->getJson(route('ui-manager.api.svg-icons.index'))
            ->assertOk();

        $data = $response->json('data');

        $this->assertCount(2, $data);
    }

    public function test_icon_list_is_sorted_alphabetically(): void
    {
        file_put_contents($this->iconsDir . '/zebra.svg', '<svg/>');
        file_put_contents($this->iconsDir . '/apple.svg', '<svg/>');
        file_put_contents($this->iconsDir . '/mango.svg', '<svg/>');

        $data = $this->getJson(route('ui-manager.api.svg-icons.index'))->json('data');

        $this->assertSame('apple.svg', $data[0]['name']);
        $this->assertSame('mango.svg', $data[1]['name']);
        $this->assertSame('zebra.svg', $data[2]['name']);
    }

    public function test_each_icon_includes_name_and_content(): void
    {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></svg>';
        file_put_contents($this->iconsDir . '/arrow.svg', $svgContent);

        $data = $this->getJson(route('ui-manager.api.svg-icons.index'))->json('data');

        $this->assertSame('arrow.svg', $data[0]['name']);
        $this->assertSame($svgContent, $data[0]['content']);
    }

    public function test_ignores_non_svg_files(): void
    {
        file_put_contents($this->iconsDir . '/icon.svg', '<svg/>');
        file_put_contents($this->iconsDir . '/image.png', 'binary');
        file_put_contents($this->iconsDir . '/readme.txt', 'text');

        $data = $this->getJson(route('ui-manager.api.svg-icons.index'))->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('icon.svg', $data[0]['name']);
    }

    public function test_returns_empty_when_directory_does_not_exist(): void
    {
        config(['ui-manager.svg.icons_path' => '/nonexistent/path/to/icons']);

        $this->getJson(route('ui-manager.api.svg-icons.index'))
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_uses_package_icons_path_when_config_is_null(): void
    {
        config(['ui-manager.svg.icons_path' => null]);

        // Package dir may not have icons in tests, but must not error out
        $this->getJson(route('ui-manager.api.svg-icons.index'))
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
