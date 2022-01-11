<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact\Segment;

final class Elements
{
    private ?string $name = null;

    /** @psalm-var array<string, array<string, string|null>> */
    private array $elements = [];

    public function addValue(string $elementKey, string $componentKey, ?string $value): self
    {
        $this->elements[$elementKey][$componentKey] = $value;

        return $this;
    }

    public function getName(): string
    {
        return $this->name ??= $this->getValueFromPosition(0, 0) ?? '';
    }

    public function getValueFromPosition(int $elementPosition, int $valuePosition): ?string
    {
        $components = array_values($this->elements)[$elementPosition] ?? [];

        return array_values($components)[$valuePosition] ?? null;
    }

    public function getValue(string|int $elementKey, string|int $componentKey): ?string
    {
        return $this->elements[$elementKey][$componentKey] ?? null;
    }

    /**
     * @psalm-return array<string, string|null>
     */
    public function getElement(string|int $elementKey): array
    {
        return $this->elements[$elementKey] ?? [];
    }

    /**
     * @psalm-return list<string>
     */
    public function getElementKeys(): array
    {
        return array_keys($this->toArray());
    }

    /**
     * @psalm-return list<string>
     */
    public function getComponentKeys(string|int $elementKey): array
    {
        return array_keys($this->getElement($elementKey));
    }

    /**
     * @psalm-return array<string, array<string, string|null>>
     */
    public function toArray(): array
    {
        return $this->elements;
    }
}
