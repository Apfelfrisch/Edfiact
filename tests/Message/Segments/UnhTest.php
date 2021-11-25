<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact\Test\Message\Segments;

use Apfelfrisch\Edifact\Delimiter;
use Apfelfrisch\Edifact\Segments\Unh;
use Apfelfrisch\Edifact\Test\TestCase;

final class UnhTest extends TestCase
{
    /** @test */
    public function test_segment(): void
    {
        $delimiter = new Delimiter();
        $seg = Unh::fromAttributes('REF', 'TYP', 'VNO', 'RNO', 'ORG', 'OCD');

        $this->assertEquals('UNH', $seg->name());
        $this->assertEquals('REF', $seg->referenz());
        $this->assertEquals('TYP', $seg->type());
        $this->assertEquals('VNO', $seg->versionNumber());
        $this->assertEquals('RNO', $seg->releaseNumber());
        $this->assertEquals('ORG', $seg->organisation());
        $this->assertEquals('OCD', $seg->organisationCode());

        $this->assertEquals($seg->toString($delimiter), Unh::fromSegLine($delimiter, $seg->toString($delimiter))->toString($delimiter));
    }
}