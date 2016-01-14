<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;

class CombinedPriceListGarbageCollector
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $combinedPriceListClass;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListsRepository;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function cleanCombinedPriceLists()
    {
        $configCombinedPriceList = $this->configManager->get(Configuration::getConfigKeyToPriceList());
        $exceptPriceLists = [];
        if ($configCombinedPriceList) {
            $exceptPriceLists[] = $configCombinedPriceList;
        }
        $this->getCombinedPriceListsRepository()->deleteUnusedPriceLists($exceptPriceLists);
    }

    /**
     * @param string $combinedPriceListClass
     */
    public function setCombinedPriceListClass($combinedPriceListClass)
    {
        $this->combinedPriceListClass = $combinedPriceListClass;
        $this->combinedPriceListsRepository = null;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListsRepository()
    {
        if (!$this->combinedPriceListsRepository) {
            $this->combinedPriceListsRepository = $this->registry->getManagerForClass($this->combinedPriceListClass)
                ->getRepository($this->combinedPriceListClass);
        }

        return $this->combinedPriceListsRepository;
    }
}
