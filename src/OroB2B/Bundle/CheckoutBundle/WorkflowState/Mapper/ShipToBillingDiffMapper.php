<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class ShipToBillingDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shipToBillingAddress';

    /**
     * {@inheritdoc}
     */
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /**
     * @param Checkout $checkout
     * @return array
     */
    public function getCurrentState($checkout)
    {
        return [
            self::DATA_NAME => $checkout->isShipToBillingAddress(),
        ];
    }

    /**
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($checkout, array $savedState)
    {
        return
            isset($savedState[self::DATA_NAME]) &&
            $savedState[self::DATA_NAME] === $checkout->isShipToBillingAddress();
    }
}
