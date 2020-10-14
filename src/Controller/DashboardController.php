<?php

namespace AppBundle\Controller;

use AppBundle\Controller\Utils\AccessControlTrait;
use AppBundle\Controller\Utils\DeliveryTrait;
use AppBundle\Controller\Utils\RestaurantTrait;
use AppBundle\Controller\Utils\StoreTrait;
use AppBundle\Entity\LocalBusiness;
use AppBundle\Sylius\Order\OrderFactory;
use AppBundle\Utils\OrderTimeHelper;
use Cocur\Slugify\SlugifyInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractController
{
    use AccessControlTrait;
    use DeliveryTrait;
    use RestaurantTrait;
    use StoreTrait;

    protected function getRestaurantRoutes()
    {
        return [
            'restaurants' => 'profile_restaurants',
            'restaurant' => 'profile_restaurant',
            'menu_taxons' => 'profile_restaurant_menu_taxons',
            'menu_taxon' => 'profile_restaurant_menu_taxon',
            'products' => 'profile_restaurant_products',
            'product_options' => 'profile_restaurant_product_options',
            'product_new' => 'profile_restaurant_product_new',
            'dashboard' => 'profile_restaurant_dashboard',
            'planning' => 'profile_restaurant_planning',
            'stripe_oauth_redirect' => 'profile_restaurant_stripe_oauth_redirect',
            'preparation_time' => 'profile_restaurant_preparation_time',
            'stats' => 'profile_restaurant_stats',
            'deposit_refund' => 'profile_restaurant_deposit_refund',
            'promotions' => 'profile_restaurant_promotions',
            'promotion_new' => 'profile_restaurant_new_promotion',
            'promotion' => 'profile_restaurant_promotion',
            'product_option_preview' => 'profile_restaurant_product_option_preview',
            'reusable_packaging_new' => 'profile_restaurant_new_reusable_packaging',
        ];
    }

    protected function getDeliveryRoutes()
    {
        return [
            'list'      => 'profile_tasks',
            'pick'      => 'profile_delivery_pick',
            'deliver'   => 'profile_delivery_deliver',
            'view'      => 'profile_delivery',
            'store_new' => 'profile_store_delivery_new'
        ];
    }

    protected function getRestaurantList(Request $request)
    {
        return [ $this->getUser()->getRestaurants(), 1, 1 ];
    }

    protected function getStoreList(Request $request)
    {
        return [ $this->getUser()->getStores(), 1, 1 ];
    }

    private function handleSwitchRequest(Request $request, $items, $queryKey, $sessionKey)
    {
        if ($request->query->has($queryKey)) {
            foreach ($items as $item) {
                if ($item->getId() === $request->query->getInt($queryKey)) {
                    $request->getSession()->set($sessionKey, $item->getId());

                    return $this->redirectToRoute('fos_user_profile_show');
                }
            }

            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Route("/dashboard", name="customer_dashboard", methods={"GET"})
     */
    public function indexAction(Request $request,
        SlugifyInterface $slugify,
        TranslatorInterface $translator,
        PaginatorInterface $paginator)
    {
        $user = $this->getUser();

        if ($user->hasRole('ROLE_STORE') && $request->attributes->has('_store')) {

            if ($response = $this->handleSwitchRequest($request, $user->getStores(), 'store', '_store')) {

                return $response;
            }

            $store = $request->attributes->get('_store');

            $routes = $request->attributes->has('routes') ? $request->attributes->get('routes') : [];
            $routes['import_success'] = 'fos_user_profile_show';
            $routes['stores'] = 'fos_user_profile_show';
            $routes['store'] = 'profile_store';

            $request->attributes->set('layout', 'dashboard.html.twig');
            $request->attributes->set('routes', $routes);

            return $this->storeDeliveriesAction($store->getId(), $request, $translator, $paginator);

            // FIXME Forward doesn't copy request attributes
            // return $this->forward('AppBundle\Controller\ProfileController::storeDeliveriesAction', [
            //     'id'  => $store->getId(),
            // ]);
        }

        if ($user->hasRole('ROLE_RESTAURANT') && $request->attributes->has('_restaurant')) {

            if ($response = $this->handleSwitchRequest($request, $user->getRestaurants(), 'restaurant', '_restaurant')) {

                return $response;
            }

            $restaurant = $request->attributes->get('_restaurant');

            return $this->statsAction($restaurant->getId(), $request, $slugify, $translator);
        }

        return $this->redirectToRoute('fos_user_profile_show');
    }
}
