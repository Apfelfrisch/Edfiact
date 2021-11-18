<?php

declare(strict_types = 1);

namespace Proengeno\Edifact\Test\Message\Segments;

use Proengeno\Edifact\Delimiter;
use Proengeno\Edifact\Segments\Ucd;
use Proengeno\Edifact\Test\TestCase;

final class UcdTest extends TestCase
{
    /** @test */
    public function test_segment(): void
    {
        $delimiter = new Delimiter();
        $seg = Ucd::fromAttributes('ECD', '66', '666');

        $this->assertEquals('UCD', $seg->name());
        $this->assertEquals('ECD', $seg->errorCode());
        $this->assertEquals('66', $seg->segmentPosition());
        $this->assertEquals('666', $seg->dataGroupPosition());
        $this->assertEquals($seg->toString($delimiter), Ucd::fromSegLine($delimiter, $seg->toString($delimiter))->toString($delimiter));
    }
}
