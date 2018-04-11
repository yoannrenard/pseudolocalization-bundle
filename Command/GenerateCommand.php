<?php

namespace YoannRenard\PseudolocalizationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;
use YoannRenard\Pseudolocalization\TranslatorFactory;
use YoannRenard\PseudolocalizationBundle\Translation\Catalogue\MergeOperationAdapter;
use YoannRenard\PseudolocalizationBundle\Translation\Reader\MultipleTranslationReaderInterface;

class GenerateCommand extends Command
{
    /** @var TranslationWriterInterface */
    private $writer;

    /** @var MultipleTranslationReaderInterface */
    private $reader;

    /** @var ExtractorInterface */
    private $extractor;

    /** @var string */
    private $defaultLocale;

    /** @var string */
    private $defaultTransPath;

    /** @var KernelInterface */
    private $kernel;

    /** @var array */
    private $transPaths;

    /**
     * @param TranslationWriterInterface $writer
     * @param MultipleTranslationReaderInterface $reader
     * @param ExtractorInterface         $extractor
     * @param string                     $defaultLocale
     * @param string                     $defaultTransPath
     */
    public function __construct(
        TranslationWriterInterface $writer,
        MultipleTranslationReaderInterface $reader,
        ExtractorInterface $extractor,
        $defaultLocale,
        $defaultTransPath = null
    ) {
        parent::__construct();

        $this->writer           = $writer;
        $this->reader           = $reader;
        $this->extractor        = $extractor;
        $this->defaultLocale    = $defaultLocale;
        $this->defaultTransPath = $defaultTransPath;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('translation:pseudolocalization:generate')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::OPTIONAL, 'The locale', $this->defaultLocale),
                new InputArgument('bundle', InputArgument::OPTIONAL, 'The bundle name or directory where to load the messages, defaults to app/Resources folder'),
                new InputOption('output-format', null, InputOption::VALUE_OPTIONAL, 'Override the default output format', 'yml'),
            ))
            ->setDescription('Generate pseudolocalized translations')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command generates pseudolocalized translation strings from templates
of a given bundle or the app folder.

Example running against a Bundle (AcmeBundle)
  <info>php %command.full_name% AcmeBundle</info>

Example running against app messages (app/Resources folder)
  <info>php %command.full_name%</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->kernel = $this->getApplication()->getKernel();

        // Define Root Paths
        $this->transPaths = [
            $this->kernel->getRootDir().'/Resources/translations',
            $this->defaultTransPath,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $errorIo = $io->getErrorStyle();

        // check format
        $supportedFormats = $this->writer->getFormats();
        if (!in_array($input->getOption('output-format'), $supportedFormats)) {
            $errorIo->error(array('Wrong output format', 'Supported formats are: '.implode(', ', $supportedFormats).'.'));

            return 1;
        }


        $currentName = 'app folder';

        // Override with provided Bundle info
        if (null !== $input->getArgument('bundle')) {
            throw new \Exception('The argument \'bundle\' is yet not supported');
//            try {
//                $foundBundle = $this->kernel->getBundle($input->getArgument('bundle'));
//                $this->transPaths = array($foundBundle->getPath().'/Resources/translations');
//                if ($this->defaultTransPath) {
//                    $this->transPaths[] = $this->defaultTransPath.'/'.$foundBundle->getName();
//                }
//                $this->transPaths[] = sprintf('%s/Resources/%s/translations', $this->kernel->getRootDir(), $foundBundle->getName());
//                $viewsPaths = array($foundBundle->getPath().'/Resources/views');
//                if ($this->defaultViewsPath) {
//                    $viewsPaths[] = $this->defaultViewsPath.'/bundles/'.$foundBundle->getName();
//                }
//                $viewsPaths[] = sprintf('%s/Resources/%s/views', $this->kernel->getRootDir(), $foundBundle->getName());
//                $currentName = $foundBundle->getName();
//            } catch (\InvalidArgumentException $e) {
//                // such a bundle does not exist, so treat the argument as path
//                $this->transPaths = array($input->getArgument('bundle').'/Resources/translations');
//                $viewsPaths = array($input->getArgument('bundle').'/Resources/views');
//                $currentName = $this->transPaths[0];
//
//                if (!is_dir($this->transPaths[0])) {
//                    throw new \InvalidArgumentException(sprintf('<error>"%s" is neither an enabled bundle nor a directory.</error>', $this->transPaths[0]));
//                }
//            }
        }

        $errorIo->title('Translation Messages Extractor and Dumper');
        $errorIo->comment(sprintf('Generating pseudolocalized translation files for "<info>%s</info>"', $currentName));


        // Filter $this->transPaths : only keep dir path
        $this->transPaths = array_filter($this->transPaths, function ($path) {
            return is_dir($path);
        });








        // load any existing messages from the translation files
        $pseudolocaziedCatalogue = $this->reader->readMultiple($this->transPaths, new MessageCatalogue('__'));
        $defaultCatalogue        = $this->reader->readMultiple($this->transPaths, new MessageCatalogue($input->getArgument('locale')));


        // Translate into pseudo language
        $translator = TranslatorFactory::create();
        $newPseudolocaziedCatalogue = new MessageCatalogue($input->getArgument('locale'));
        foreach ($defaultCatalogue->getDomains() as $domain) {
            $messages = array_map(
                function ($translation) use ($translator) {
                    return $translator->trans($translation);
                },
                $defaultCatalogue->all($domain)
            );

            $newPseudolocaziedCatalogue->add($messages, $domain = 'messages');
        }

        // process catalogues
        $operation = new MergeOperationAdapter($pseudolocaziedCatalogue, $newPseudolocaziedCatalogue);

        // Exit if no messages found.
        if (!count($operation->getDomains())) {
            $errorIo->warning('No translation messages were found.');

            return;
        }


        // save the files
        $errorIo->comment('Writing files...');

        $bundleTransPath = false;
        foreach ($this->transPaths as $path) {
            $bundleTransPath = $path;
        }

        if (!$bundleTransPath) {
            $bundleTransPath = end($this->transPaths);
        }

        $this->writer->disableBackup();
        $this->writer->write(
            $operation->getResult(),
            $input->getOption('output-format'),
            [
                'path' => $bundleTransPath,
                'default_locale' => $this->defaultLocale
            ]
        );

        $errorIo->success('Translation files were successfully updated.');

        return 0;
    }
}
