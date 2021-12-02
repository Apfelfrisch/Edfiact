<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact\Interfaces;

use Apfelfrisch\Edifact\UnaSegment;
use Apfelfrisch\Edifact\SeglineParser;
use Iterator;

interface SegInterface
{
    public static function fromSegLine(SeglineParser $parser, string $segLine): self|static;

    /** @psalm-return Iterator<\Apfelfrisch\Edifact\Validation\Failure> */
    public function validate(SegValidatorInterface $segmentValidator): Iterator;

    public function setUnaSegment(UnaSegment $unaSegment): void;

    public function name(): string;

    public function getValue(string $elementKey, string $componentKey): ?string;

    public function getValueFromPosition(int $elementPosition, int $valuePosition): ?string;

    /** @psalm-return array<string, array<string, string|null>> */
    public function toArray(): array;
}
