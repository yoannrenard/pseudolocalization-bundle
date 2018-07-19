<?php

namespace YoannRenard\PseudolocalizationBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Translation\Writer\TranslationWriter;
use YoannRenard\PseudolocalizationBundle\Command\GenerateCommand;

class GenerateCommandTest extends TestCase
{
    /** @var CommandTester */
    private $tester;

    /** @var TranslationWriter|ObjectProphecy */
    private $writerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->writerMock = $this->prophesize(TranslationWriter::class);

        $application = new Application();
        $application->add(new GenerateCommand(
            $this->writerMock->reveal(),
            'fr',
            __DIR__.'/../dummy/Resources/translations'
        ));
        $command = $application->find('translation:pseudolocalization:generate');
        $command->setApplication($application);

        $this->tester = new CommandTester($command);

        $this->writerMock->getFormats()->willReturn(['yml', 'php']);
    }

    /**
     * @test
     */
    public function it_returns_an_error_as_the_output_format_is_not_supported()
    {
        $this->tester->execute([
            'command' => 'translation:pseudolocalization:generate',
            'locale' => 'fr',
            '--output-format' => 'yaml',
        ]);
        $this->assertRegExp('/Supported formats are: /', $this->tester->getDisplay());
        $this->assertEquals(1, $this->tester->getStatusCode());
    }

    /**
     * @test
     */
    public function it_displays_a_success_message()
    {
        $this->tester->execute([
            'command' => 'translation:pseudolocalization:generate',
        ]);

        $this->assertRegExp('/Your translation files were successfully updated./', $this->tester->getDisplay());
        $this->assertEquals(0, $this->tester->getStatusCode());
    }
}
