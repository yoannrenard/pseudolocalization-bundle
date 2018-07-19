<?php

namespace YoannRenard\PseudolocalizationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

final class GenerateCommand extends Command
{
    /** @var TranslationWriterInterface */
    private $writer;

    /** @var string */
    private $defaultTransPath;

    /** @var array */
    private $defaultLocale;

    public function __construct(
        TranslationWriterInterface $writer,
        $defaultLocale,
        $defaultTransPath = null
    ) {
        parent::__construct();

        $this->writer           = $writer;
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

        $io->success('Your translation files were successfully updated.');

        return 0;
    }
}
