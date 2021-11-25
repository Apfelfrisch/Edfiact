<?php

namespace Apfelfrisch\Edifact\Test\Message;

use Apfelfrisch\Edifact\Segments\Fallback;
use Apfelfrisch\Edifact\Test\TestCase;
use Apfelfrisch\Edifact\Message;
use Apfelfrisch\Edifact\EdifactFile;
use Apfelfrisch\Edifact\Exceptions\SegValidationException;
use Apfelfrisch\Edifact\SegmentFactory;

class MessageTest extends TestCase
{
    private $messageCore;

    public function setUp(): void
    {
        $file = new EdifactFile(__DIR__ . '/../data/edifact.txt');
        $this->messageCore = new Message($file);
    }

    /** @test */
    public function it_instanciates_with_file_and_validator()
    {
        $this->assertInstanceOf(Message::class, $this->messageCore);
    }

    /** @test */
    public function it_instanciates_from_a_string()
    {
        $messageCore = Message::fromString("UNH");
        $this->assertInstanceOf(Message::class, $messageCore);
    }

    /** @test */
    public function it_instanciates_from_a_filepath()
    {
        $messageCore = Message::fromFilepath(__DIR__ . '/../data/edifact.txt');
        $this->assertInstanceOf(Message::class, $messageCore);
    }

    /** @test */
    public function it_can_cast_the_edifact_content_to_a_string()
    {
        $this->assertEquals("UNH+O160482A7C2+ORDERS:D:09B:UN:1.1e'RFF+Z13:17103'", (string)$this->messageCore);
    }

    /** @test */
    public function it_fetch_the_current_segement_from_stream()
    {
        $messageCore = Message::fromString("UNH'UNB");

        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unh::class, $messageCore->getNextSegment());
        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unh::class, $messageCore->getCurrentSegment());
    }

    /** @test */
    public function it_parses_to_a_generic_segment_if_a_segment_is_unkown()
    {
        $messageCore = Message::fromString("UKN");

        $this->assertInstanceOf(Fallback::class, $messageCore->getNextSegment());
    }

    /** @test */
    public function it_throw_an_exception_if_no_generic_segment_is_set_and_a_segment_is_uknown()
    {
        $messageCore = Message::fromString("UKN", SegmentFactory::withDefaultDegments(withFallback: false));

        $this->expectException(SegValidationException::class);
        $this->assertInstanceOf('Apfelfrisch\Edifact\Test\Fixtures\Segments\Unh', $messageCore->getNextSegment());
    }

    /** @test */
    public function it_fetch_the_next_segement_from_stream()
    {
        $messageCore = Message::fromString("UNH'UNB");
        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unh::class, $messageCore->getNextSegment());
        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unb::class, $messageCore->getNextSegment());
    }

    /** @test */
    public function it_pinns_and_jumps_to_the_pointer_position()
    {
        $messageCore = Message::fromString("UNH'UNB");
        $messageCore->pinPointer();
        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unh::class, $messageCore->getNextSegment());
        $messageCore->jumpToPinnedPointer();
        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unh::class, $messageCore->getNextSegment());
    }

    /** @test */
    public function it_jumps_to_the_actual_position_if_no_pointer_was_pinned()
    {
        $messageCore = Message::fromString("UNH'UNB");
        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unh::class, $messageCore->getNextSegment());
        $messageCore->jumpToPinnedPointer();
        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unb::class, $messageCore->getNextSegment());
    }

    /** @test */
    public function it_iterates_over_the_stream()
    {
        $messageCore = Message::fromString("UNH'UNB'");
        $message = "";
        foreach ($messageCore as $segment) {
            $message .= $segment->toString($messageCore->getDelimiter()) . $messageCore->getDelimiter()->getSegment();
        }
        $this->assertEquals($message, (string)$messageCore);
    }

    /** @test */
    public function it_fetch_a_specific_segement_from_stream()
    {
        $messageCore = Message::fromString("UNH+O160482A7C2+ORDERS:D:09B:UN:1.1e'UNB'UKN'UNT");

        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unb::class, $messageCore->findNextSegment('UNB'));
        $this->assertInstanceOf(Fallback::class, $messageCore->findNextSegment('UKN'));
        $this->assertFalse($messageCore->findNextSegment('UNH'));

    }

    /** @test */
    public function it_fetch_a_specific_segement_from_start_of_the_stream()
    {
        $messageCore = Message::fromString("UNH+O160482A7C2+ORDERS:D:09B:UN:1.1e'UNB'UNT");
        $messageCore->findSegmentFromBeginn('UNH');

        $this->assertInstanceOf(\Apfelfrisch\Edifact\Segments\Unh::class, $messageCore->findSegmentFromBeginn('UNH'));
        $this->assertInstanceOf(
            \Apfelfrisch\Edifact\Segments\Unh::class,
            $messageCore->findSegmentFromBeginn('UNH', function($segment) {
                return $segment->referenz() == 'O160482A7C2';
            }
        ));
        $this->assertInstanceOf(
            \Apfelfrisch\Edifact\Segments\Unh::class,
            $messageCore->findSegmentFromBeginn('UNH', ['referenz' => 'O160482A7C2'])
        );
        $this->assertFalse(
            $messageCore->findSegmentFromBeginn('UNH', function($segment) {
                return $segment->referenz() == 'UNKNOW';
            })
        );
    }

    /** @test */
    public function it_uses_the_filters_from_configuration_class()
    {
        $messageCore = Message::fromString("FOO BAR");
        $messageCore->addStreamFilter('string.tolower');

        $this->assertEquals("foo bar", (string)$messageCore);
    }

    /** @test */
    public function it_can_return_the_delimter()
    {
        $unaValues = [":+.? '", "abcdef"];
        foreach ($unaValues as $unaValue) {
            $messageCore = Message::fromString("UNA" . $unaValue . "UNH+'");
            $delimiter = $messageCore->getDelimiter();
            $this->assertEquals($unaValue,
                 $delimiter->getData()
               . $delimiter->getDataGroup()
               . $delimiter->getDecimal()
               . $delimiter->getTerminator()
               . $delimiter->getEmpty()
               . $delimiter->getSegment()
           );
        }
    }

    /** @test */
    public function it_converts_decimal_seperator()
    {
        $message = Message::fromString("UNA:+_? 'MOA+QUL:20_00'");

        /** @var \Apfelfrisch\Edifact\Segments\Moa */
        $moa = $message->findSegmentFromBeginn('MOA');

        $this->assertSame('20.00', $moa->amount());
    }
}
