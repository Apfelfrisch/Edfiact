<?php

namespace Proengeno\Edifact;

use Proengeno\Edifact\Interfaces\SegInterface;
use Proengeno\Edifact\Delimiter;
use Proengeno\Edifact\EdifactFile;
use Proengeno\Edifact\Interfaces\UnbInterface;
use Proengeno\Edifact\Interfaces\UnhInterface;
use Proengeno\Edifact\Message;
use Proengeno\Edifact\Segments\Una;
use Proengeno\Edifact\Segments\Unb;
use Proengeno\Edifact\Segments\Unh;
use Proengeno\Edifact\Segments\Unt;
use Proengeno\Edifact\Segments\Unz;

class Builder
{
    private ?string $unbRef = null;
    private ?string $unhRef = null;

    private Configuration $configuration;
    private EdifactFile $edifactFile;
    private string $filepath;
    private int $unhCounter = 0;
    private int $messageCount = 0;
    private bool $messageWasFetched = false;

    public function __construct(string $filepath = 'php://temp', ?Configuration $configuration = null)
    {
        $this->filepath = $filepath;
        $this->configuration = $configuration ?? new Configuration;
        $this->edifactFile = $this->newEdifactFile();
    }

    public function __destruct()
    {
        // Delete File if build process could not finshed (Expetion, etc)
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
            if ($segment instanceof UnbInterface) {
                $this->unbRef = $segment->reference();
            }

            if ($segment instanceof UnhInterface) {
                if ($this->unhRef !== null) {
                    $this->writeSegment(Unt::fromAttributes((string)++$this->unhCounter, $this->unhRef));
                }

                $this->unhRef = $segment->reference();
            }

            $this->writeSegment($segment);
        }
    }

    private function writeSegment(SegInterface $segment): void
    {
        $this->edifactFile->write(
            $segment->toString($this->delimiter()) . $this->delimiter()->getSegment()
        );

        $this->countSegments($segment);
    }

    public function get(): Message
    {
        return new Message($this->getEdifactFile(), $this->configuration);
    }

    public function getEdifactFile(): EdifactFile
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

        return new EdifactFile($this->filepath);
    }

    public function messageIsEmpty(): bool
    {
        return $this->edifactFile->tell() === 0;
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
        $this->edifactFile = $this->newEdifactFile(new Delimiter(
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
            $this->delimiter()->getData(),
            $this->delimiter()->getDataGroup(),
            $this->delimiter()->getDecimal(),
            $this->delimiter()->getTerminator(),
            $this->delimiter()->getEmpty(),
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

    private function newEdifactFile(?Delimiter $delimiter = null): EdifactFile
    {
        $this->edifactFile = new EdifactFile($this->filepath, 'w', $delimiter);

        foreach ($this->configuration->getWriteFilter() as $callable) {
            $this->edifactFile->addWriteFilter($callable);
        }

        return $this->edifactFile;
    }
}
