<?php

namespace Apfelfrisch\Edifact\Interfaces;

use Apfelfrisch\Edifact\DataGroups;

interface SegValidatorInterface
{
    public function validate(DataGroups $blueprint, DataGroups $elements): self;
}
