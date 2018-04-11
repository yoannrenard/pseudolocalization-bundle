<?php

namespace YoannRenard\PseudolocalizationBundle\Tests\Reader;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use YoannRenard\PseudolocalizationBundle\Translation\Reader\MultipleTranslationReader;
use PHPUnit\Framework\TestCase;

class MultipleTranslationReaderTest extends TestCase
{
    /** @var MultipleTranslationReader */
    private $translationReader;

    /** @var TranslationReaderInterface|ObjectProphecy */
    private $readerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->readerMock = $this->prophesize(TranslationReaderInterface::class);

        $this->translationReader = new MultipleTranslationReader(
            $this->readerMock->reveal()
        );
    }

    /**
     * @test
     */
    public function it_reads_every_translation_items()
    {
        $catalogueMock = $this->prophesize(MessageCatalogue::class);

        $this->readerMock->read('1st dir', $catalogueMock)->shouldBeCalled();
        $this->readerMock->read('2nd dir', $catalogueMock)->shouldBeCalled();

        $this->translationReader->readMultiple(['1st dir', '2nd dir'], $catalogueMock->reveal());
    }
}
