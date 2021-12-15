<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact\Segment;

use Apfelfrisch\Edifact\Segment\UnaSegment;
use Apfelfrisch\Edifact\Segment\SeglineParser;

interface SegmentInterface
{
    public static function fromSegLine(SeglineParser $parser, string $segLine): self|static;

    public function setUnaSegment(UnaSegment $unaSegment): void;

    public function name(): string;

    public function getValue(string $elementKey, string $componentKey): ?string;

    public function getValueFromPosition(int $elementPosition, int $valuePosition): ?string;

    /** @psalm-return array<string, array<string, string|null>> */
    public function toArray(): array;
}