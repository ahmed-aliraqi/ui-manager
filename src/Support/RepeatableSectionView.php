<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Support;

use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\DTOs\FieldValueData;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Wraps a repeatable section's collection of items.
 * Implements IteratorAggregate so Blade can @foreach over it directly.
 *
 * @implements IteratorAggregate<int, SectionItemView>
 */
final class RepeatableSectionView implements Countable, IteratorAggregate
{
    /** @var SectionItemView[] */
    private array $items;

    /**
     * @param array<int, array<string, mixed>> $rows  Each element is a field map for one item
     */
    public function __construct(
        private readonly Section $definition,
        array $rows,
    ) {
        $this->items = array_map(
            fn (array $row) => new SectionItemView($definition, $row),
            $rows,
        );
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return SectionItemView[] */
    public function all(): array
    {
        return $this->items;
    }

    public function first(): ?SectionItemView
    {
        return $this->items[0] ?? null;
    }
}
