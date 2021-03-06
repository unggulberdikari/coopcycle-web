<?php

namespace AppBundle\OpeningHours;

use Doctrine\Common\Collections\Collection;

interface OpenCloseInterface
{
    /**
     * @return array
     */
    public function getOpeningHours($method = 'delivery');

    /**
     * @return bool
     */
    public function isOpen(\DateTime $now = null): bool;

    /**
     * @return \DateTime|null
     */
    public function getNextOpeningDate(\DateTime $now = null);

    /**
     * @return \DateTime|null
     */
    public function getNextClosingDate(\DateTime $now = null);

    /**
     * @return Collection
     */
    public function getClosingRules();

    /**
     * @param \DateTime|null $date
     * @param \DateTime|null $now
     * @return boolean
     */
    public function hasClosingRuleFor(\DateTime $date = null, \DateTime $now = null): bool;

    /**
     * @param int $shippingOptionsDays
     */
    public function setShippingOptionsDays(int $shippingOptionsDays);

    /**
     * @return int
     */
    public function getOrderingDelayMinutes();
}
