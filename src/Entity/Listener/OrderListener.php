<?php

namespace AppBundle\Entity\Listener;

use AppBundle\Entity\Sylius\Order;
use AppBundle\Entity\Sylius\OrderTarget;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

class OrderListener
{
    public function preFlush(Order $order, PreFlushEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $target = $order->getTarget();

        if (null !== $target) {
            if (!$entityManager->contains($target)) {
                $existingTarget = $entityManager->getRepository(OrderTarget::class)
                    ->findOneBy(['restaurant' => $target->getRestaurant()]);
                if (null !== $existingTarget) {
                    $order->setTarget($existingTarget);
                } else {
                    $entityManager->persist($target);
                    $entityManager->flush();
                }
            }
        }
    }
}
