<?php

namespace AppBundle\Domain\Order\Command;

use AppBundle\Sylius\Order\OrderInterface;

class Checkout
{
    private $order;
    private $data;

    public function __construct(OrderInterface $order, $data = null)
    {
        $this->order = $order;
        $this->data = $data;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getStripeToken()
    {
        if (is_string($this->data)) {

            return $this->data;
        }

        return $this->data['stripeToken'];
    }

    public function getData()
    {
        return $this->data;
    }
}

