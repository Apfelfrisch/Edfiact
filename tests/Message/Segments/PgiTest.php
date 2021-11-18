<?php

declare(strict_types = 1);

namespace Proengeno\Edifact\Test\Message\Segments;

use Proengeno\Edifact\Delimiter;
use Proengeno\Edifact\Segments\Pgi;
use Proengeno\Edifact\Test\TestCase;

final class PgiTest extends TestCase
{
    /** @test */
    public function test_segment(): void
    {
        $delimiter = new Delimiter();
        $seg = Pgi::fromAttributes('COD');

        $this->assertEquals('PGI', $seg->name());
        $this->assertEquals('COD', $seg->code());
        $this->assertEquals($seg->toString($delimiter), Pgi::fromSegLine($delimiter, $seg->toString($delimiter))->toString($delimiter));
    }
}
