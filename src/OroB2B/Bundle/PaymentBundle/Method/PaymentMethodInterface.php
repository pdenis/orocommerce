<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface PaymentMethodInterface
{
    const AUTHORIZE = 'authorize';
    const CAPTURE = 'capture';
    const CHARGE = 'charge';

    /**
     * Action to wrap action combination - charge, authorize, authorize and capture
     */
    const PURCHASE = 'purchase';

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function execute(PaymentTransaction $paymentTransaction);

    /**
     * @return string
     */
    public function getType();

    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = []);
}
