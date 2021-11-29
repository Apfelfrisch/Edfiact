<?php

declare(strict_types = 1);

namespace Apfelfrisch\Edifact\Interfaces;

interface FormatterInterface
{
    public function prefixUna(): self;

    public function format(SegInterface ...$segments): string;
}