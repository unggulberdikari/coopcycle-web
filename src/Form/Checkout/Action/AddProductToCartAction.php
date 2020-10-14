<?php

namespace AppBundle\Form\Checkout\Action;

use AppBundle\Entity\HubRepository;
use AppBundle\Entity\Sylius\OrderTarget;
use AppBundle\Form\Checkout\Action\Validator\AddProductToCart as AssertAddProductToCart;

/**
 * @AssertAddProductToCart
 */
class AddProductToCartAction
{
    public $restaurant;
    public $product;
    public $cart;
    public $clear = false;

    public function handleTarget(HubRepository $hubRepository)
    {
        $isSingle = $this->cart->getTarget()->getRestaurant() !== null;

        if ($isSingle && $this->cart->getTarget()->getRestaurant() !== $this->restaurant) {
            $hub = $hubRepository->findOneByRestaurant($this->restaurant);
            if ($hub) {
                $target = new OrderTarget();
                $target->setHub($hub);

                $this->cart->setTarget($target);
            }
        }
    }
}
