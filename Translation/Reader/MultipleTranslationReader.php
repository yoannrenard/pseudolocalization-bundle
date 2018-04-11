<?php

namespace YoannRenard\PseudolocalizationBundle\Translation\Reader;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;

class MultipleTranslationReader implements MultipleTranslationReaderInterface
{
    /** @var TranslationReaderInterface */
    private $reader;

    public function __construct(TranslationReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @inheritdoc
     */
    public function readMultiple(array $directory, MessageCatalogue $catalogue)
    {
        array_map(function ($path) use ($catalogue) {
            $this->reader->read($path, $catalogue);
        }, $directory);
    }
}
