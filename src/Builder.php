<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact;

use Apfelfrisch\Edifact\Delimiter;
use Apfelfrisch\Edifact\Interfaces\SegInterface;
use Apfelfrisch\Edifact\Segments\Una;
use Apfelfrisch\Edifact\Segments\Unt;
use Apfelfrisch\Edifact\Segments\Unz;
use Apfelfrisch\Edifact\Stream;

class Builder
{
    private ?string $unbRef = null;
    private ?string $unhRef = null;

    private Stream $edifactFile;
    private string $filepath;
    private int $unhCounter = 0;
    private int $messageCount = 0;
    private bool $messageWasFetched = false;

    public function __construct(string $filepath = 'php://temp')
    {
        $this->filepath = $filepath;

        $this->edifactFile = new Stream($this->filepath, 'w');
    }

    public function addStreamFilter(string $filtername, mixed $params = null): self
    {
        $this->edifactFile->addWriteFilter($filtername, $params);

        return $this;
    }

    public function __destruct()
    {
        // Delete File if build process could not finshed
        $filepath = $this->edifactFile->getRealPath();
        if ($this->messageWasFetched === false && file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    public function writeSegments(SegInterface ...$segments): void
    {
        if ($this->messageIsEmpty()) {
            $this->prepareEdfactFile($segments[0]);
        }

        foreach ($segments as $segment) {
            if ($segment->name() === 'UNB') {
                $this->unbRef = $segment->getValueFromPosition(5, 0) ?? '';
            }

            if ($segment->name() === 'UNH') {
                if ($this->unhRef !== null) {
                    $this->writeSegment(Unt::fromAttributes((string)++$this->unhCounter, $this->unhRef));
                }

                $this->unhRef = $segment->getValueFromPosition(1, 0) ?? '';
            }

            $this->writeSegment($segment);
        }
    }

    private function writeSegment(SegInterface $segment): void
    {
        $segment->setDelimiter($this->delimiter());
        $this->edifactFile->write(
            $segment->toString() . $this->delimiter()->getSegmentTerminator()
        );

        $this->countSegments($segment);
    }

    public function get(): Stream
    {
        if (! $this->messageIsEmpty()) {
            if ($this->unhRef !== null) {
                $this->writeSegment(Unt::fromAttributes((string)++$this->unhCounter, $this->unhRef));
                $this->unhRef = null;
            }
            if ($this->unbRef !== null) {
                $this->writeSegment(Unz::fromAttributes((string)$this->messageCount, $this->unbRef));
                $this->unbRef = null;
            }
        }

        $this->messageWasFetched = true;

        if (str_starts_with($this->filepath, 'php://')) {
            return $this->edifactFile;
        }

        return new Stream($this->filepath);
    }

    public function messageIsEmpty(): bool
    {
        return $this->edifactFile->isEmpty();
    }

    private function prepareEdfactFile(SegInterface $segment): void
    {
        if ($segment->name() !== 'UNA') {
            $this->writeSegment($this->buildUnaFromDelimter());

            return;
        }

        /**
         * @var Una $segment
         * @psalm-suppress PossiblyNullArgument: segment is alwas set, cause it was fromString initialized
         */
        $this->edifactFile->setDelimiter(new Delimiter(
            $segment->data(),
            $segment->dataGroup(),
            $segment->decimal(),
            $segment->terminator(),
            $segment->emptyChar(),
            $segment->segment(),
        ));
    }

    private function delimiter(): Delimiter
    {
        return $this->edifactFile->getDelimiter();
    }

    private function buildUnaFromDelimter(): Una
    {
        return Una::fromAttributes(
            $this->delimiter()->getComponentSeparator(),
            $this->delimiter()->getElementSeparator(),
            $this->delimiter()->getDecimalPoint(),
            $this->delimiter()->getEscapeCharacter(),
            $this->delimiter()->getSpaceCharacter(),
        );
    }

    private function countSegments(SegInterface $segment): void
    {
        if ($segment->name() === 'UNA' || $segment->name() === 'UNB') {
            return;
        }

        if (strtoupper($segment->name()) === 'UNH') {
            $this->unhCounter = 1;
            $this->messageCount++;
            return;
        }

        $this->unhCounter++;
    }
}
