<?php

namespace YoannRenard\PseudolocalizationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriter;
use YoannRenard\Pseudolocalization\TranslatorFactory;
use YoannRenard\PseudolocalizationBundle\Catalogue\MergeOperationAdapter;

class GenerateCommand28 extends Command
{
    /** @var TranslationWriter */
    private $translationWriter;

    /** @var TranslationLoader */
    private $translationLoader;

    /** @var KernelInterface */
    private $kernel;

    /** @var string */
    private $defaultLocale;

    /**
     * @param TranslationWriter  $translationWriter
     * @param TranslationLoader  $translationLoader
     * @param KernelInterface    $kernel
     * @param string             $defaultLocale
     */
    public function __construct(
        TranslationWriter $translationWriter,
        TranslationLoader $translationLoader,
        KernelInterface $kernel,
        $defaultLocale
    ) {
        $this->translationWriter = $translationWriter;
        $this->translationLoader = $translationLoader;
        $this->kernel            = $kernel;
        $this->defaultLocale     = $defaultLocale;

        parent::__construct();
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
            ))
            ->setDescription('Generate pseudolocalized translations')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command generates pseudolocalized translation strings from templates
of a given bundle or the app folder.

Example running against a Bundle (AcmeBundle)
  <info>php %command.full_name% AcmeBundle</info>
  <info>php %command.full_name% --force AcmeBundle</info>

Example running against app messages (app/Resources folder)
  <info>php %command.full_name%</info>
  <info>php %command.full_name% --force</info>
EOF
            )
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);


        // Define Root Path to App folder
        $transPaths = array($this->kernel->getRootDir().'/Resources/');

        $io->title('Translation Messages Extractor and Dumper');
        $io->comment('Generating translation files');


        // load any existing messages from the translation files
        $pseudolocaziedCatalogue = new MessageCatalogue(/*$input->getArgument('locale')*/'__');
        foreach ($transPaths as $path) {
            $path .= 'translations';
            if (is_dir($path)) {
                $this->translationLoader->loadMessages($path, $pseudolocaziedCatalogue);
            }
        }

        // load any existing messages from the translation files
        $defaultCatalogue = new MessageCatalogue($input->getArgument('locale'));
        foreach ($transPaths as $path) {
            $path .= 'translations';
            if (is_dir($path)) {
                $this->translationLoader->loadMessages($path, $defaultCatalogue);
            }
        }







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
            $io->warning('No translation messages were found.');

            return;
        }

        $resultMessage = 'Translation files were successfully updated';



        // save the files
        $io->comment('Writing files...');

        $bundleTransPath = false;
        foreach ($transPaths as $path) {
            $path .= 'translations';
            if (is_dir($path)) {
                $bundleTransPath = $path;
            }
        }

        if (!$bundleTransPath) {
            $bundleTransPath = end($transPaths).'translations';
        }

        $this->translationWriter->writeTranslations(
            $operation->getResult(),
            'php'/*$input->getOption('output-format')*/,
            [
                'path' => $bundleTransPath,
                'default_locale' => '__'
            ]
        );

        $resultMessage .= ' and translation files were updated';

        $io->success($resultMessage.'.');
    }
}
