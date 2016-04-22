<?php

use Mockery as m;
use Proengeno\Edifact\Message\Delimiter;
use Proengeno\Edifact\Message\Segments\Unh;
use Proengeno\Edifact\Exceptions\SegValidationException;
use Proengeno\Edifact\Validation\SegmentValidator;
use Proengeno\Edifact\Interfaces\SegValidatorInterface;

class SegFrameworkTest extends TestCase 
{
    protected function setUp()
    {
        Segment::setDelimiter(null);
        Segment::setValidator(null);
    }

    /** @test */
    public function it_can_set_a_costum_delimter()
    {
        $customDelimiter = new Delimiter;
        $segment = Segment::fromAttributes('A');

        $segment->setDelimiter($customDelimiter);

        $this->assertEquals($customDelimiter, $segment->getDelimiter());
    }

    /** @test */
    public function it_gives_a_standard_delimiter_if_none_was_set()
    {
        $segment = Segment::fromAttributes('A');

        $this->assertInstanceOf(Delimiter::class, $segment->getDelimiter());
    }

    /** @test */
    public function it_can_set_a_costum_validator()
    {
        $customValidator = new SegmentValidator;
        $segment = Segment::fromAttributes('A');

        Segment::setValidator($customValidator);

        $this->assertEquals($customValidator, $segment->getValidator());
    }

    /** @test */
    public function it_gives_a_standard_validator_if_none_was_set()
    {
        $segment = Segment::fromAttributes('A');

        $this->assertInstanceOf(SegmentValidator::class, $segment->getValidator());
    }

    /** @test */
    public function it_gives_its_segment_name()
    {
        $segment = Segment::fromSegLine('A');

        $this->assertEquals('A', $segment->name());
    }

    /** @test */
    public function it_validates_itself()
    {
        $customValidator = m::mock(SegValidatorInterface::class, function($customValidator) {
            $customValidator->shouldReceive('validate')->once();
        });
        $segment = Segment::fromSegLine('A');

        Segment::setValidator($customValidator);

        $segment->validate();
    }

    /** @test */
    public function it_can_cast_to_a_string()
    {
        $givenString = 'A+B+1:2:3:4:5+D+E';
        $expectedString = $givenString;

        $segment = Segment::fromSegLine($givenString);

        $this->assertEquals($expectedString, (string)$segment);
    }

    /** @test */
    public function it_removes_his_loose_ends_when_it_is_castet_to_a_string()
    {
        $givenString = 'A+B+1:2:::+D++';
        $expectedString = 'A+B+1:2+D';

        $segment = Segment::fromSegLine($givenString);

        $this->assertEquals($expectedString, (string)$segment);
    }

}
