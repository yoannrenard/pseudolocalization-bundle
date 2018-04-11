<?php

namespace YoannRenard\PseudolocalizationBundle\Translation\Reader;

use Symfony\Component\Translation\MessageCatalogue;

interface MultipleTranslationReaderInterface
{
    /**
     * Reads translation messages from a directory list to the catalogue.
     *
     * @param string[]         $directory
     * @param MessageCatalogue $catalogue
     */
    public function readMultiple(array $directory, MessageCatalogue $catalogue);
}
