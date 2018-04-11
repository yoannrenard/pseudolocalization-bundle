<?php

namespace YoannRenard\PseudolocalizationBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\Extractor\PhpExtractor;
use Symfony\Component\Translation\Reader\TranslationReader;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;
use YoannRenard\PseudolocalizationBundle\Command\GenerateCommand;

class GenerateCommandTest extends TestCase
{
//    /** @var TranslationWriter|ObjectProphecy */
//    private $translationWriter;
//
//    /** @var TranslationLoader|ObjectProphecy */
//    private $translationLoader;
//
//    /** @var KernelInterface|ObjectProphecy */
//    private $kernelMock;
    /** @var TranslationWriterInterface|ObjectProphecy */
    private $writerMock;

    /** @var TranslationReaderInterface|ObjectProphecy */
    private $readerMock;

    /** @var ExtractorInterface|ObjectProphecy */
    private $extractorMock;

    /** @var string */
    private $defaultLocale;

    /** @var string */
    private $defaultTransPath;

    /** @var string */
    private $defaultViewsPath;

    /** @var KernelInterface|ObjectProphecy*/
    private $kernelMock;

    /** @var CommandTester */
    private $tester;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->writerMock       = $this->prophesize(TranslationWriter::class);
        $this->readerMock       = $this->prophesize(TranslationReader::class);
        $this->extractorMock    = $this->prophesize(PhpExtractor::class);
        $this->defaultLocale    = 'fr';
        $this->defaultTransPath = __DIR__.'/../dummy/Resources/translations';
        $this->defaultViewsPath = '/home/yoann/workspace/demo/templates';

//        $this->writer->addDumper('php', new PhpFileDumper()
//);
//        $this->translationWriter = new TranslationWriter();
//        $this->translationWriter->addDumper('php', new PhpFileDumper());
//
//        $this->translationLoader = new TranslationLoader();
//        $this->translationLoader->addLoader('php', new PhpFileLoader());
//
//        $this->kernelMock        = $this->prophesize(KernelInterface::class);
//        $this->kernelMock->getRootDir()->willReturn(__DIR__.'/../dummy');

        $containerMock = $this->prophesize(\Symfony\Component\DependencyInjection\ContainerInterface::class);

        $this->kernelMock = $this->prophesize(KernelInterface::class);
        $this->kernelMock->getBundles()->willReturn([]);
        $this->kernelMock->getEnvironment()->willReturn('test');
        $this->kernelMock->boot()->shouldBeCalled();
        $this->kernelMock->getContainer()->willReturn($containerMock->reveal());
        $this->kernelMock->getRootDir()->willReturn(__DIR__.'/../dummy');


        $application = new Application($this->kernelMock->reveal());
        $application->add(new GenerateCommand(
            $this->writerMock->reveal(),
            $this->readerMock->reveal(),
            $this->extractorMock->reveal(),
            $this->defaultLocale,
            $this->defaultTransPath,
            $this->defaultViewsPath
        ));

        $command = $application->find('translation:pseudolocalization:generate');
        $command->setApplication($application);

        $this->writerMock->getFormats()->willReturn(['yml', 'php']);

        $this->tester = new CommandTester($command);
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
    public function it_does_not_find_any_translation()
    {
        $this->tester->execute([
            'command' => 'translation:pseudolocalization:generate',
            'locale' => 'en',
            '--output-format' => 'php',
        ]);

        $this->assertRegExp('/No translation messages were found. /', $this->tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_translates()
    {
        $this->tester->execute([
            'command' => 'translation:pseudolocalization:generate',
            'locale' => 'en',
            '--output-format' => 'php',
        ]);

        $this->assertRegExp('/No translation messages were found. /', $this->tester->getDisplay());
    }
}
