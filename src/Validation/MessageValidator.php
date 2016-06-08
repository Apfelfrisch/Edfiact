<?php 

namespace Proengeno\Edifact\Validation;

use Proengeno\Edifact\Interfaces\SegInterface;
use Proengeno\Edifact\Validation\SegmentValidator;
use Proengeno\Edifact\Exceptions\ValidationException;
use Proengeno\Edifact\Interfaces\SegValidatorInterface;
use Proengeno\Edifact\Exceptions\SegValidationException;
use Proengeno\Edifact\Interfaces\EdifactMessageInterface;
use Proengeno\Edifact\Interfaces\MessageValidatorInterface;
use Proengeno\Edifact\Exceptions\EdifactException;

/*
 * Todo: Klasse komplett neuschreiben, die ist Mist
 */
class MessageValidator implements MessageValidatorInterface 
{
    private $segValidator;
    private $lineCount = 1;
    private $reLoopCount = [];
    
    public function __construct($segValidator = null)
    {
        $this->segValidator = $segValidator ?: new SegmentValidator;
    }

    public function validate(EdifactMessageInterface $edifact)
    {
        try {
            $this->loop($edifact, $edifact->getValidationBlueprint());
            $segment = $edifact->getCurrentSegment();
            if ($segment->name() != 'UNZ' || $edifact->getNextSegment()) {
                throw ValidationException::unexpectedSegment($this->lineCount, @$segment->name());
            }
        } catch (EdifactException $e) {
            throw new ValidationException($e->getMessage(), $this->lineCount, null);
        }

        return $this;
    }
    
    public function loop($edifact, $blueprint)
    {
        $blueprintCount = 0;
        while ($line = $edifact->getNextSegment()) {
            // Gratz, Validation ist für die teilmenge erfolgreich durchgelaufen, gebe anzahl der durchläufe zurück
            if ($this->endOfBlueprint($blueprint, $blueprintCount)) {
                return;
            }
            $this->validateSegment($line);
            $this->validateAgainstBlueprint($line, @$blueprint[$blueprintCount]);
            
            if ($this->isSubSegmentReloop($edifact, $blueprint, $blueprintCount)) {
                $this->reLoopSubSegments($edifact, $blueprint, $blueprintCount);
            } elseif ($this->isSingleSegmentReloop($edifact, $blueprint, $blueprintCount)) {
                $blueprintCount--;
            }

            $this->lineCount++;
            $blueprintCount++;
            $edifact->pinPointer();
        }
    }

    private function endOfBlueprint($blueprint, $blueprintCount)
    {
        return !isset($blueprint[$blueprintCount]);
    }

    private function isSubSegmentReloop($edifact, $blueprint, $blueprintCount)
    {
        if (!$this->segmentIsLoopable($blueprint, $blueprintCount) ) {
            return false;
        }
        if (!isset($blueprint[$blueprintCount]['segments'])) {
            return false;
        }
        return true;
    }

    private function isSingleSegmentReloop($edifact, $blueprint, $blueprintCount)
    {
        if (!$this->segmentIsLoopable($blueprint, $blueprintCount) ) {
            return false;
        }
        if (!isset($blueprint[$blueprintCount]['segments'])) {
            $edifact->pinPointer();
            if ($edifact->getNextSegment()->name() == $blueprint[$blueprintCount]['name']) {
                $edifact->jumpToPinnedPointer();
                // Hier sollten wir nicht hochzählen, den Methodenname suggeriert etwas anderes
                isset($this->reLoopCount[$blueprintCount]) ? $this->reLoopCount[$blueprintCount] ++ : $this->reLoopCount[$blueprintCount] = 0;
                return true;
            } else {
                $edifact->jumpToPinnedPointer();
                $this->reLoopCount[$blueprintCount] = 0;
            }
        }

        return false;
    }

    private function reLoopSubSegments($edifact, $blueprint, &$blueprintCount)
    {
        $this->loop($edifact, $blueprint[$blueprintCount]['segments']);
        if ($edifact->getCurrentSegment()->name() == $blueprint[$blueprintCount]['name']) {
            isset($this->reLoopCount[$blueprintCount]) ? $this->reLoopCount[$blueprintCount] ++ : $this->reLoopCount[$blueprintCount] = 0;
            $blueprintCount --;
        } else {
            $this->reLoopCount[$blueprintCount] = 0;
        }

        $edifact->jumpToPinnedPointer();
    }

    private function segmentIsLoopable($blueprint, $blueprintCount)
    {
        $segment = $blueprint[$blueprintCount];
        if (!isset($segment['maxLoops']) && !isset($segment['segments']) ) {
            return false;
        }
        if ($this->checkReLoopCount($blueprintCount, $segment)) {
            return true;
        }
        throw ValidationException::maxLoopsExceeded($this->lineCount, $segment['name']);
    }

    private function checkReLoopCount($blueprintCount, $segment)
    {
        $maxLoops = isset($segment['maxLoops']) ? $segment['maxLoops'] : 1;
        if (!isset($this->reLoopCount[$blueprintCount])) {
            return true;
        }
        if ($this->reLoopCount[$blueprintCount] < $maxLoops) {
            return true;
        }
        return false;
    }

    private function validateAgainstBlueprint($segment, $blueprint)
    {
        if ($segment == null) {
            throw ValidationException::unexpectedEnd();
        }
        $this->validateBlueprintNames($segment, $blueprint);
        $this->validateBlueprintTemplates($segment, $blueprint);
    }

    private function validateBlueprintNames($segment, $blueprint)
    {
        if ($segment->name() != $blueprint['name']) {
            if (isset($blueprint['name'])) {
                throw ValidationException::unexpectedSegment($this->lineCount, @$segment->name(), $blueprint['name']);
            }
            throw ValidationException::unexpectedSegment($this->lineCount, @$segment->name());
        }
    }

    private function validateBlueprintTemplates($segment, array $blueprint)
    {
        if (isset($blueprint['templates'])) {
            foreach ($blueprint['templates'] as $segmendMethod => $suggestions) {
                if (!in_array($segment->$segmendMethod(), $suggestions)) {
                    throw ValidationException::illegalContent(
                        $this->lineCount, 
                        $segment->name(), 
                        $segment->$segmendMethod(), 
                        implode('" | "', $suggestions)
                    );
                }
            }
        }
    }
    
    private function validateSegment(SegInterface $segment)
    {
        try {
            $segment->validate($this->segValidator);
        } catch (SegValidationException $e) {
            throw new ValidationException($e->getMessage(), $this->lineCount);
        }
    }
}
