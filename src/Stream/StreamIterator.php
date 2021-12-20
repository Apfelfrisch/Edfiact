<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact\Stream;

use Apfelfrisch\Edifact\Exceptions\EdifactException;
use Apfelfrisch\Edifact\Segment\SegmentFactory;
use Apfelfrisch\Edifact\Segment\SegmentInterface;
use Apfelfrisch\Edifact\Segment\UnaSegment;
use Iterator;

final class StreamIterator implements Iterator
{
    private int $currentSegmentNumber = 0;
    private ?string $segline = null;

    public function __construct(
        private Stream $stream,
        private SegmentFactory $segmentFactory
    ) {
        $stream->rewind();
    }

    /**
     * @deprecated
     */
    public function getFirst(): ?SegmentInterface
    {
        $this->rewind();

        if (! $this->valid()) {
            return null;
        }

        return $this->current();
    }

    /**
     * @deprecated
     */
    public function getCurrent(): ?SegmentInterface
    {
        if (! $this->valid()) {
            return null;
        }

        return $this->current();
    }

    /**
     * @psalm-return list<SegmentInterface>
     */
    public function getAll(): array
    {
        return array_values(iterator_to_array($this));
    }

    public function currentSegline(): string
    {
        return (string)$this->segline;
    }

    public function current(): SegmentInterface
    {
        if (! $this->valid()) {
            return throw new EdifactException("No Segment available.");
        }

        return $this->getSegmentObject($this->currentSegline());
    }

    public function key(): int
    {
        return $this->currentSegmentNumber;
    }

    public function next(): void
    {
        $this->currentSegmentNumber++;

        $this->segline = $this->getNextSegLine();
    }

    public function rewind(): void
    {
        $this->stream->rewind();
        $this->currentSegmentNumber = 0;
        $this->segline = $this->getNextSegLine();
        if ($this->segline !== null && str_starts_with($this->segline, UnaSegment::UNA)) {
            $this->segline = $this->getNextSegLine();
        }
    }

    public function valid(): bool
    {
        return $this->segline !== null;
    }

    private function getSegmentObject(string $segLine): SegmentInterface
    {
        return $this->segmentFactory->build($segLine);
    }

    private function getNextSegLine(): ?string
    {
        if ('' !== $segline = $this->stream->getSegment()) {
            return $segline;
        }
        return null;
    }
}
