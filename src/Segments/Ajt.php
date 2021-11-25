<?php

namespace Apfelfrisch\Edifact\Segments;

use Apfelfrisch\Edifact\DataGroups;

class Ajt extends AbstractSegment
{
    private static ?DataGroups $validationBlueprint = null;

    public static function blueprint(): DataGroups
    {
        if (self::$validationBlueprint === null) {
            self::$validationBlueprint = (new DataGroups)
                ->addValue('AJT', 'AJT', 'M|a|3')
                ->addValue('4465', '4465', 'M|an|3');
        }

        return self::$validationBlueprint;
    }

    public static function fromAttributes(string $code): self
    {
        return new self((new DataGroups)
            ->addValue('AJT', 'AJT', 'AJT')
            ->addValue('4465', '4465', $code)
        );
    }

    public function code(): ?string
    {
        return $this->elements->getValue('4465', '4465');
    }
}