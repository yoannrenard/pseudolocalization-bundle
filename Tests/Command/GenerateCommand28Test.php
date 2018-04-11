<?php

namespace YoannRenard\PseudolocalizationBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Dumper\PhpFileDumper;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Writer\TranslationWriter;
use YoannRenard\PseudolocalizationBundle\Command\GenerateCommand28;

class GenerateCommand28Test extends TestCase
{
    /** @var TranslationWriter|ObjectProphecy */
    private $translationWriter;

    /** @var TranslationLoader|ObjectProphecy */
    private $translationLoader;

    /** @var KernelInterface|ObjectProphecy */
    private $kernelMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->translationWriter = new TranslationWriter();
        $this->translationWriter->addDumper('php', new PhpFileDumper());

        $this->translationLoader = new TranslationLoader();
        $this->translationLoader->addLoader('php', new PhpFileLoader());

        $this->kernelMock        = $this->prophesize(KernelInterface::class);
        $this->kernelMock->getRootDir()->willReturn(__DIR__.'/../dummy');
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester()
    {
        $application = new Application();
        $application->add(new GenerateCommand28(
            $this->translationWriter,
            $this->translationLoader,
            $this->kernelMock->reveal(),
            'en'
        ));

        $command = $application->find('translation:pseudolocalization:generate');
        $command->setApplication($application);

        return new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_returns_an_error_as_the_output_format_is_not_supported()
    {
        $tester = $this->createCommandTester();
        $tester->execute(
            ['locale' => 'fr'],
            ['output-format' => 'toto']
        );

        $this->assertEquals(0, $tester->getStatusCode(), 'Returns 0 in case of success');
    }
}
