<?php

namespace YoannRenard\PseudolocalizationBundle\Translation\Catalogue;

use Symfony\Component\Translation\Catalogue\MergeOperation as MergeOperationBase;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;

class MergeOperationAdapter extends MergeOperationBase
{
    /**
     * @param MessageCatalogueInterface $source
     * @param MessageCatalogueInterface $target
     */
    public function __construct(MessageCatalogueInterface $source, MessageCatalogueInterface $target)
    {
        $this->source = $source;
        $this->target = $target;
        $this->result = new MessageCatalogue($source->getLocale());
        $this->messages = array();
    }
}
