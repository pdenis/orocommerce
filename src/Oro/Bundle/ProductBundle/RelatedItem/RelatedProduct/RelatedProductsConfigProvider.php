<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;

/**
 * @codeCoverageIgnore There is no point to test these getters
 */
class RelatedProductsConfigProvider extends AbstractRelatedItemConfigProvider
{
    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_ENABLED));
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MAX_NUMBER_OF_RELATED_PRODUCTS));
    }

    /**
     * @return bool
     */
    public function isBidirectional()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_BIDIRECTIONAL));
    }
}
