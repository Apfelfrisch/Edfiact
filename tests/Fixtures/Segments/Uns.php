<?php

namespace Proengeno\Edifact\Test\Fixtures\Segments;

use Proengeno\Edifact\Message\DataGroupCollection;
use Proengeno\Edifact\Templates\AbstractSegment;

class Uns extends AbstractSegment
{
    protected static $validationBlueprint = [
        'UNS' => ['UNS' => 'M|a|3'],
        '0081' => ['0081' => 'M|a|1'],
    ];

    public static function fromAttributes($code = 'S')
    {
        return new static(
            (new DataGroupCollection(static::getBuildDelimiter()))
                ->addValue('UNS', 'UNS', 'UNS')
                ->addValue('0081', '0081', $docCode)
        );
    }

    public function code()
    {
        return @$this->elements['0081']['0081'] ?: null;
    }
}
