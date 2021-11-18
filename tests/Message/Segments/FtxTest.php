<?php

declare(strict_types = 1);

namespace Proengeno\Edifact\Test\Message\Segments;

use Proengeno\Edifact\Delimiter;
use Proengeno\Edifact\Segments\Ftx;
use Proengeno\Edifact\Test\TestCase;

final class FtxTest extends TestCase
{
    /** @test */
    public function test_segment(): void
    {
        $delimiter = new Delimiter();
        $seg = Ftx::fromAttributes('QAL', str_repeat('A', 2050), 'COD');

        $this->assertEquals('FTX', $seg->name());
        $this->assertEquals('QAL', $seg->qualifier());
        $this->assertEquals(str_repeat('A', 2050), $seg->message());
        $this->assertEquals('COD', $seg->code());
        $this->assertEquals($seg->toString($delimiter), Ftx::fromSegLine($delimiter, $seg->toString($delimiter))->toString($delimiter));
    }
}
