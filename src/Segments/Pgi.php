<?php

namespace Apfelfrisch\Edifact\Segments;

use Apfelfrisch\Edifact\DataGroups;

class Pgi extends AbstractSegment
{
    private static ?DataGroups $validationBlueprint = null;

    public static function blueprint(): DataGroups
    {
        if (self::$validationBlueprint === null) {
            self::$validationBlueprint = (new DataGroups)
                ->addValue('PGI', 'PGI', 'M|a|3')
                ->addValue('5379', '5379', 'M|an|3');
        }

        return self::$validationBlueprint;
    }

    public static function fromAttributes(string $code): self
    {
        return new self((new DataGroups)
            ->addValue('PGI', 'PGI', 'PGI')
            ->addValue('5379', '5379', $code)
        );
    }

    public function code(): ?string
    {
        return $this->elements->getValue('5379', '5379');
    }
}