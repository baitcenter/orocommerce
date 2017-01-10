<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;

interface AddressProviderInterface
{
    /**
     * @param Customer $account
     * @param string $type
     *
     * @return CustomerAddress[]
     * @throws \InvalidArgumentException
     */
    public function getAccountAddresses(Customer $account, $type);

    /**
     * @param CustomerUser $accountUser
     * @param string $type
     *
     * @return CustomerUserAddress[]
     * @throws \InvalidArgumentException
     */
    public function getAccountUserAddresses(CustomerUser $accountUser, $type);

    /**
     * @param string $type
     * @throws \InvalidArgumentException
     */
    public static function assertType($type);
}
