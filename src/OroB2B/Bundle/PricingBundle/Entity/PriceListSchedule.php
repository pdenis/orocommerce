<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_price_list_schedule")
 * @ORM\Entity
 */
class PriceListSchedule
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $priceList;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="active_at", type="datetime", nullable=true)
     */
    protected $activeAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="deactivate_at", type="datetime", nullable=true)
     */
    protected $deactivateAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param PriceList $priceList
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveAt()
    {
        return $this->activeAt;
    }

    /**
     * @param \DateTime|null $activeAt
     */
    public function setActiveAt(\DateTime $activeAt = null)
    {
        $this->activeAt = $activeAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getDeactivateAt()
    {
        return $this->deactivateAt;
    }

    /**
     * @param \DateTime|null $deactivateAt
     */
    public function setDeactivateAt(\DateTime $deactivateAt = null)
    {
        $this->deactivateAt = $deactivateAt;
    }
}
