<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact\Test\Message\Segments;

use Apfelfrisch\Edifact\Delimiter;
use Apfelfrisch\Edifact\Segments\Ucs;
use Apfelfrisch\Edifact\Test\TestCase;

final class UcsTest extends TestCase
{
    /** @test */
    public function test_segment(): void
    {
        $delimiter = new Delimiter();
        $seg = Ucs::fromAttributes('500', '12');

        $this->assertEquals('UCS', $seg->name());
        $this->assertEquals('500', $seg->position());
        $this->assertEquals('12', $seg->error());
        $this->assertEquals($seg->toString($delimiter), Ucs::fromSegLine($delimiter, $seg->toString($delimiter))->toString($delimiter));
    }
}