<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Requests;

use AhmedAliraqi\UiManager\Services\SectionRegistry;
use Illuminate\Foundation\Http\FormRequest;

class SaveSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'fields' => 'required|array',
        ];

        $definition = $this->resolveDefinition();

        if ($definition === null) {
            return $rules;
        }

        $locales = (array) config('ui-manager.locales', ['en']);

        foreach ($definition->fields() as $field) {
            $fieldRules = $field->getRules();

            if ($fieldRules === []) {
                $rules["fields.{$field->getName()}"] = 'nullable';
                continue;
            }

            if ($field->isTranslatable()) {
                foreach ($locales as $locale) {
                    $rules["fields.{$field->getName()}.{$locale}"] = $fieldRules;
                }
            } else {
                $rules["fields.{$field->getName()}"] = $fieldRules;
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];

        $definition = $this->resolveDefinition();

        if ($definition === null) {
            return $attributes;
        }

        $locales = (array) config('ui-manager.locales', ['en']);

        foreach ($definition->fields() as $field) {
            if ($field->isTranslatable()) {
                foreach ($locales as $locale) {
                    $attributes["fields.{$field->getName()}.{$locale}"] = "{$field->getLabel()} ({$locale})";
                }
            } else {
                $attributes["fields.{$field->getName()}"] = $field->getLabel();
            }
        }

        return $attributes;
    }

    private function resolveDefinition(): ?\AhmedAliraqi\UiManager\Core\Section
    {
        return app(SectionRegistry::class)->find(
            (string) $this->route('page'),
            (string) $this->route('section'),
            $this->query('layout'),
        );
    }
}
