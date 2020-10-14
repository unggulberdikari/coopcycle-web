<?php

namespace Tests\AppBundle\Form\Checkout\Action;

use AppBundle\Entity\Hub;
use AppBundle\Entity\HubRepository;
use AppBundle\Entity\LocalBusiness;
use AppBundle\Entity\Sylius\Order;
use AppBundle\Entity\Sylius\OrderTarget;
use AppBundle\Form\Checkout\Action\AddProductToCartAction;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class AddProductToCartActionTest extends TestCase
{
    use ProphecyTrait;

    public function setUp(): void
    {
        $this->hubRepository = $this->prophesize(HubRepository::class);
    }

    public function testHandleTargetWithHub()
    {
        $restaurant = $this->prophesize(LocalBusiness::class);
        $otherRestaurant = $this->prophesize(LocalBusiness::class);

        $target = new OrderTarget();
        $target->setRestaurant($otherRestaurant->reveal());

        $cart = $this->prophesize(Order::class);
        $cart->getTarget()->willReturn($target);

        $hub = new Hub();
        $this->hubRepository->findOneByRestaurant($restaurant->reveal())->willReturn($hub);

        $action = new AddProductToCartAction();
        $action->restaurant = $restaurant->reveal();
        $action->cart = $cart->reveal();

        $action->handleTarget($this->hubRepository->reveal());

        $cart->setTarget(Argument::that(function (OrderTarget $target) use ($hub) {
            return $target->getHub() === $hub;
        }))->shouldHaveBeenCalled();
    }
}
