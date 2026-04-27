<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

abstract class BaseField
{
    protected string $name;

    protected string $label = '';

    protected string $helpText = '';

    /** @var array<int, string|array<mixed>> */
    protected array $rules = [];

    protected mixed $default = null;

    /** @var array<string, mixed> */
    protected array $props = [];

    protected bool $translatable = false;

    protected bool $hasVariable = false;

    final public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function help(string $text): static
    {
        $this->helpText = $text;

        return $this;
    }

    /**
     * @param array<int, string|array<mixed>> $rules
     */
    public function rules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $props
     */
    public function props(array $props): static
    {
        $this->props = array_merge($this->props, $props);

        return $this;
    }

    public function required(): static
    {
        if (! in_array('required', $this->rules, true)) {
            array_unshift($this->rules, 'required');
        }

        return $this;
    }

    public function nullable(): static
    {
        if (! in_array('nullable', $this->rules, true)) {
            $this->rules[] = 'nullable';
        }

        return $this;
    }

    /**
     * Mark this field as translatable.
     * The stored value becomes { "en": "...", "ar": "..." } keyed by locale.
     */
    public function translatable(): static
    {
        $this->translatable = true;

        return $this;
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    /**
     * Mark this field's value as a variable source — e.g. %general.phone% becomes usable elsewhere.
     */
    public function hasVariable(): static
    {
        $this->hasVariable = true;

        return $this;
    }

    public function isVariableEnabled(): bool
    {
        return $this->hasVariable;
    }

    /**
     * Return the list of variable placeholder strings available for this field.
     * Subclasses override this to provide type-specific formats.
     *
     * @return array<string>
     */
    public function getVariableFormats(string $sectionName): array
    {
        return ["%{$sectionName}.{$this->name}%"];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label !== ''
            ? $this->label
            : ucwords(str_replace(['-', '_'], ' ', $this->name));
    }

    public function getHelpText(): string
    {
        return $this->helpText;
    }

    /**
     * @return array<int, string|array<mixed>>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProps(): array
    {
        return $this->props;
    }

    abstract public function getType(): string;

    /**
     * Variable placeholder key, e.g. "banner.title".
     */
    public function getVariableKey(string $sectionName): string
    {
        return "{$sectionName}.{$this->name}";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $base = array_filter([
            'name'      => $this->name,
            'type'      => $this->getType(),
            'label'     => $this->getLabel(),
            'help'      => $this->helpText,
            'rules'     => $this->rules,
            'default'   => $this->default,
            'props'     => $this->props,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);

        if ($this->translatable) {
            $base['translatable'] = true;
        }

        return $base;
    }
}
