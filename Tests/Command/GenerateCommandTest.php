<?php

namespace YoannRenard\PseudolocalizationBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Dumper\PhpFileDumper;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Reader\TranslationReader;
use Symfony\Component\Translation\Writer\TranslationWriter;
use YoannRenard\PseudolocalizationBundle\Command\GenerateCommand;

class GenerateCommandTest extends TestCase
{
    private static $defaultTransPath = __DIR__.'/../dummy/translations';

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        @unlink(__DIR__.'/../dummy/translations/messages.__.php');
    }

    private function getCommandTester($path = null)
    {
        $writer = new TranslationWriter();
        $writer->addDumper('yml', $this->prophesize(DumperInterface::class)->reveal());
        $writer->addDumper('php', new PhpFileDumper());

        $reader = new TranslationReader();
        $reader->addLoader('php', new PhpFileLoader());

        $kernelMock = $this->prophesize(KernelInterface::class);
        $kernelMock->getBundles()->willReturn([]);
        $kernelMock->getEnvironment()->willReturn('test');
        $kernelMock->boot()->shouldBeCalled();
        $kernelMock->getContainer()->willReturn($this->prophesize(ContainerInterface::class)->reveal());
        $kernelMock->getRootDir()->willReturn(__DIR__.'/../dummy');


        $application = new Application($kernelMock->reveal());
        $application->add(new GenerateCommand(
            $writer,
            $reader,
            $this->prophesize(ExtractorInterface::class)->reveal(),
            'en',
            !empty($path) ? $path : self::$defaultTransPath
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
        $tester = $this->getCommandTester();
        $tester->execute([
            '--output-format' => 'unsupported format',
        ]);

        $this->assertRegExp('/Supported formats are: yml, php./', $tester->getDisplay());
        $this->assertEquals(1, $tester->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_an_error_as_no_messages_has_been_found()
    {
        $tester = $this->getCommandTester(__DIR__);
        $tester->execute([
            'locale' => 'fr',
            '--output-format' => 'php',
        ]);

        $this->assertRegExp('/No translation messages were found./', $tester->getDisplay());
        $this->assertEquals(1, $tester->getStatusCode());
    }

    /**
     * @test
     */
    public function it_generates_pseudolocalized_translation_file_in_php()
    {
        $tester = $this->getCommandTester();
        $tester->execute([
            'locale' => 'en',
            '--output-format' => 'php',
        ]);

        $this->assertFileEquals(
            __DIR__.'/../dummy/translations/expected_messages.__.php',
            __DIR__.'/../dummy/translations/messages.__.php'
        );
        $this->assertEquals(0, $tester->getStatusCode());
    }
}
