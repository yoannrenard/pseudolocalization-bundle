<?php

namespace YoannRenard\PseudolocalizationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriter;
use YoannRenard\Pseudolocalization\TranslatorFactory;
use YoannRenard\PseudolocalizationBundle\Translation\Catalogue\MergeOperationAdapter;

final class GenerateCommand extends Command
{
    /** @var TranslationWriter */
    private $writer;

    /** @var TranslationReaderInterface */
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

    public function __construct(
        TranslationWriter $writer,
        TranslationReaderInterface $reader,
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
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('translation:pseudolocalization:generate')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::OPTIONAL, 'The locale', $this->defaultLocale),
                new InputOption('output-format', null, InputOption::VALUE_OPTIONAL, 'Override the default output format', 'php'),
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

        $errorIo->title('Translation Messages Extractor and Dumper');

        // Filter $this->transPaths : only keep dir path
        $this->transPaths = array_filter($this->transPaths, function ($path) {
            return is_dir($path);
        });

        // load any existing messages from the translation files
        $pseudolocaziedCatalogue = $this->readMultiple($this->transPaths, new MessageCatalogue('__'));
        $defaultCatalogue        = $this->readMultiple($this->transPaths, new MessageCatalogue($input->getArgument('locale')));


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

        $operation = new MergeOperationAdapter($pseudolocaziedCatalogue, $newPseudolocaziedCatalogue);

        // Exit if no messages found.
        if (!count($operation->getDomains())) {
            $errorIo->warning('No translation messages were found.');

            return 1;
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



        if (!version_compare(Kernel::VERSION, '4.1.0', '>=')) {
            $this->writer->disableBackup();
        }

        $this->writer->write(
            $operation->getResult(),
            $input->getOption('output-format'),
            [
                'path' => $bundleTransPath,
                'default_locale' => $this->defaultLocale
            ]
        );

        $io->success('Your translation files were successfully updated.');

        return 0;
    }

    private function readMultiple(array $directory, MessageCatalogue $catalogue)
    {
        array_map(function ($path) use ($catalogue) {
            $this->reader->read($path, $catalogue);
        }, $directory);

        return $catalogue;
    }
}
