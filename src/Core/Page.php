<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Core;

abstract class Page
{
    /** Unique slug-style identifier for this page. */
    protected string $name;

    /** Whether this page appears in the dashboard sidebar. */
    protected bool $visible = true;

    /** Sort order within the sidebar. */
    protected int $order = 0;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $this->name));
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Return the page as an array suitable for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'         => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'visible'      => $this->isVisible(),
            'order'        => $this->getOrder(),
        ];
    }
}
