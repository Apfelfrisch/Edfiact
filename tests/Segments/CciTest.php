<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact\Test\Segments;

use Apfelfrisch\Edifact\Delimiter;
use Apfelfrisch\Edifact\Segments\Cci;
use Apfelfrisch\Edifact\Test\TestCase;

final class CciTest extends TestCase
{
    /** @test */
    public function test_ajt_segment()
    {
        $delimiter = new Delimiter();
        $seg = Cci::fromAttributes('TYP', 'COD', 'MARK', 'LST', 'RES');

        $this->assertEquals('CCI', $seg->name());
        $this->assertEquals('TYP', $seg->type());
        $this->assertEquals('COD', $seg->code());
        $this->assertEquals('MARK', $seg->mark());
        $this->assertEquals('LST', $seg->codeList());
        $this->assertEquals('RES', $seg->codeResponsible());
        $this->assertEquals($seg->toString($delimiter), Cci::fromSegLine($delimiter, $seg->toString($delimiter))->toString($delimiter));
    }
}