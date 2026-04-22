<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * Price field — stores { "amount": 100.50, "currency": "USD" }.
 *
 * Usage:
 *   Field::price('price')->currency('USD')
 *   Field::price('price')->currency('EUR')->decimals(2)
 */
class PriceField extends BaseField
{
    protected string $currency = '';

    protected int $decimals = 2;

    /** @var array<string> */
    protected array $currencies = [];

    public function currency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    /**
     * Provide a list of selectable currencies in the UI.
     * Defaults to the single currency set via currency().
     *
     * @param array<string> $currencies  e.g. ['USD', 'EUR', 'GBP']
     */
    public function currencies(array $currencies): static
    {
        $this->currencies = $currencies;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getType(): string
    {
        return 'price';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), array_filter([
            'currency'   => $this->currency ?: null,
            'currencies' => $this->currencies ?: null,
            'decimals'   => $this->decimals,
        ], fn ($v) => $v !== null));
    }
}
