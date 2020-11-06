<?php

namespace Postroyka\ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Postroyka\AccountBundle\Entity\ExtendedUser;
use Postroyka\AccountBundle\Provider\ExtendedUserProvider;
use Postroyka\ApiBundle\Provider\ApiProvider;
use Postroyka\AppBundle\Provider\CartProvider;
use Postroyka\AppBundle\Provider\CatalogProvider;
use Postroyka\AppBundle\Provider\OrdersProvider;
use Postroyka\AppBundle\Service\OrderCalculator;
use Submarine\CartBundle\Provider\SessionCartProvider;
use Submarine\CoreBundle\Entity\Options\Option;
use Submarine\CoreBundle\Options\OptionsProvider;
use Submarine\OrdersBundle\Entity\Order;
use Submarine\OrdersBundle\Entity\OrderItem;
use Submarine\PagesBundle\Entity\Page;
use Submarine\PagesBundle\Entity\Relations;
use Submarine\PagesBundle\Entity\Type\PageValue;
use Submarine\PropertiesBundle\Entity\PropertyValue;
use Submarine\PropertiesBundle\Provider\PropertyValuesProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class ApiController extends Controller
{
    const BATCH_SIZE = 50;

    /**
     * @return CatalogProvider
     */
    private function catalogProvider()
    {
        return $this->get('postroyka_app.catalog_provider');
    }

    /**
     * @return OrdersProvider
     */
    private function ordersProvider()
    {
        return $this->get('postroyka_app.orders_provider');
    }

    /**
     * @return ExtendedUserProvider
     */
    private function extendedUsersProvider()
    {
        return $this->get('postroyka_account.extended_user_provider');
    }

    /**
     * @return ApiProvider
     */
    private function apiProvider()
    {
        return $this->get('postroyka_api.api_provider');
    }

    /**
     * Экспорт пользователей
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     resource=true,
     *     parameters={
     *          {"name"="since", "dataType"="DateTime", "required"=true, "description"="dd.mm.yyyy hh:mm:ss"}
     *     }
     * )
     */
    public function exportUsersAction(Request $request)
    {
        $since = new \DateTime($request->get('since'));
        $currentDate = new \DateTime();
        $users = $this->extendedUsersProvider()->getUpdatedQuery($since)->getResult();
        $prepared = [];

        /** @var ExtendedUser $user */
        foreach ($users as $user) {
            $prepared[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'discount' => $user->getDiscount(),
                'discount_card' => $user->getDiscountCard() ? $user->getDiscountCard()->getName() : null,
                'discount_card_number' => $user->getDiscountCardNumber(),
                'enabled' => $user->getUser()->isEnabled(),
                'confirmed' => $user->getUser()->isConfirmed(),
                'description' => $user->getUser()->getDescription(),
            ];
        }

        $data = [
            'since' => $since->format('d.m.Y H:i:s'),
            'server_time' => $currentDate->format('d.m.Y H:i:s'),
            'users' => $prepared,
        ];

        return new JsonResponse($data);
    }

    /**
     * Экспорт групп продуктов
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     resource=true,
     * )
     */
    public function exportGroupsAction(Request $request)
    {
        $currentDate = new \DateTime();
        $groups = $this->catalogProvider()->getGroupsQuery()->getResult();
        $prepared = [];

        /** @var Page $group */
        foreach ($groups as $group) {
            $prepared[] = [
                'id' => $group->getId(),
                'parent_id' => $group->getParents()->first()->getParent()->getId(),
                'url' => $request->getSchemeAndHttpHost() . $group->getUrl(),
                'title' => $group->getTitle(),
                'description' => $group->getDescription(),
                'deleted' => $group->isDeleted(),
                'enabled' => $group->isEnabled(),
                'position' => $group->getParents()->first()->getPosition(),
            ];
        }

        $data = [
            'server_time' => $currentDate->format('d.m.Y H:i:s'),
            'groups' => $prepared,
        ];

        return new JsonResponse($data);
    }

    /**
     * Экспорт продуктов, измененых после указанной даты
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     resource=true,
     *     parameters={
     *          {"name"="since", "dataType"="DateTime", "required"=true, "description"="dd.mm.yyyy hh:mm:ss"}
     *     }
     * )
     */
    public function exportProductsAction(Request $request)
    {
        $currentDate = new \DateTime();
        $since = new \DateTime($request->get('since'));
        $products = $this->catalogProvider()->getUpdatedProductsQuery($since)->getResult();
        $prepared = [];

        /** @var Page $product */
        foreach ($products as $product) {
            $groupId = null;

            if ($product->getParents()->count()) {
                $relation = $product->getParents()->first();

                if ($relation instanceof Relations) {
                    $groupId = $relation->getParent() ? $relation->getParent()->getId() : null;
                }
            }

            $prepared[] = [
                'id' => $product->getId(),
                'group_id' => $groupId,
                'url' => $request->getSchemeAndHttpHost() . $product->getUrl(),
                'title' => $product->getTitle(),
                'description' => $product->getDescription(),
                'deleted' => $product->isDeleted(),
                'enabled' => $product->isEnabled(),
                'image' => $request->getSchemeAndHttpHost() . '/' . $product->getImage(),
                'price' => (float)$product->getPrice(),
                'article' => $product->getProductId(),
                'stock' => (bool)$product->getValue('stock'),
                'wholesale_discount' => $this->castFloat($product->getValue('wholesale_discount')),
                'wholesale_quantity' => (int)$product->getValue('wholesale_quantity'),
                'weight' => $this->castFloat($product->getValue('weight')),
                'updated_at' => $product->getUpdatedAt()->format('d.m.Y H:i:s'),
            ];
        }

        $data = [
            'since' => $since->format('d.m.Y H:i:s'),
            'server_time' => $currentDate->format('d.m.Y H:i:s'),
            'products' => $prepared,
        ];

        return new JsonResponse($data);
    }

    /**
     * Экспорт заказов, совершенных после указанной даты
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     resource=true,
     *     parameters={
     *          {"name"="since", "dataType"="DateTime", "required"=true, "description"="dd.mm.yyyy hh:mm:ss"}
     *     }
     * )
     */
    public function exportOrdersAction(Request $request)
    {

        $currentDate = new \DateTime();
        $since = new \DateTime($request->get('since'));
        $orders = $this->ordersProvider()->getUpdatedOrdersQuery($since)->getResult();
        $prepared = [];

        /** @var Order $order */
        foreach ($orders as $order) {
            $items = [];

            /** @var OrderItem $item */
            foreach ($order->getItems() as $item) {
                $items[] = [
                    'id' => $item->getId(),
                    'article' => $item->getProductId(),
                    'product_id' => $item->getEntityId(),
                    'title' => $item->getTitle(),
                    'quantity' => $item->getQuantity(),
                    'unit_price' => (float)$item->getUnitPrice(),
                    'base_price' => $item->getOptions()->get('base_price') ? $this->castFloat($item->getOptions()->get('base_price')->getValue()) : 0,
                    'url' => $request->getSchemeAndHttpHost() . $item->getUrl(),
                ];
            }


            if ($order->getUser()) {
                /** @var ExtendedUser $user */
                $user = $this->extendedUsersProvider()->getByUser($order->getUser());

                $userData = [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'discount' => $user->getDiscount(),
                    'discount_card' => $user->getDiscountCard() ? $user->getDiscountCard()->getName() : null,
                    'discount_card_number' => $user->getDiscountCardNumber(),
                    'enabled' => $user->getUser()->isEnabled(),
                    'confirmed' => $user->getUser()->isConfirmed(),
                    'description' => $user->getUser()->getDescription(),
                ];
            } else {
                $userData = null;
            }


            $prepared[] = [
                'id' => $order->getId(),
                'total' => (float)$order->getTotal(),
                'total_discount' => (float)$order->getTotalDiscount(),
                'user' => $userData,
                'discount_card' => $order->getOptions()->get('discount_card') ? $this->castFloat($order->getOptions()->get('discount_card')->getValue()) : 0,
                'total_weight' => $order->getOptions()->get('total_weight') ? $this->castFloat($order->getOptions()->get('total_weight')->getValue()) : 0,
                'created_at' => $order->getCreatedAt()->format('d.m.Y H:i:s'),
                'updated_at' => $order->getUpdatedAt()->format('d.m.Y H:i:s'),
                'delivery_method_price' => (float)$order->getDeliveryMethod(),
                'payment_method_price' => (float)$order->getPaymentMethodPrice(),
                'purchase' => $order->getOptions()->get('purchase') ? $order->getOptions()->get('purchase')->getValue() : '',
                'delivery_address' => $order->getOptions()->get('address') ? $order->getOptions()->get('address')->getValue() : '',
                'delivery_date' => $order->getOptions()->get('date') ? $order->getOptions()->get('date')->getValue() : '',
                'delivery_time' => $order->getOptions()->get('time') ? $order->getOptions()->get('time')->getValue() : '',
                'paid' => $order->isPaid(),
                'status' => [
                    'id' => $order->getStatus()->getId(),
                    'title' => $order->getStatus()->getTitle(),
                    'closed' => $order->getStatus()->isClosed(),
                    'success' => $order->getStatus()->isSuccess(),
                ],
                'items' => $items,
                'comment' => ($order->getOptions()->get('comment') ? $order->getOptions()->get('comment')->getValue() : ''),
                'delivery_price' => ($order->getOptions()->get('delivery_price') ? $this->castFloat($order->getOptions()->get('delivery_price')->getValue()) : 0),
                'unloading_price' => ($order->getOptions()->get('unloading_price') ? $this->castFloat($order->getOptions()->get('unloading_price')->getValue()) : 0),
                'phone' => ($order->getOptions()->get('phone') ? $order->getOptions()->get('phone')->getValue() : 0),
                'email' => ($order->getOptions()->get('email') ? $order->getOptions()->get('email')->getValue() : 0),
                'zona' => ($order->getOptions()->get('delivery') ? $order->getOptions()->get('delivery')->getValue() : 0),
                'elevator' => ($order->getOptions()->get('elevator') ? $order->getOptions()->get('elevator')->getValue() : 0),
                'extraFloor' => ($order->getOptions()->get('extraFloor') ? $order->getOptions()->get('extraFloor')->getValue() : 0),
                'floor' => ($order->getOptions()->get('floor') ? $order->getOptions()->get('floor')->getValue() : 0),
            ];
        }

        $data = [
            'since' => $since->format('d.m.Y H:i:s'),
            'server_time' => $currentDate->format('d.m.Y H:i:s'),
            'orders' => $prepared,
        ];

        return new JsonResponse($data);
    }

    /**
     * Импорт цен
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     * @ApiDoc(
     *     resource=true,
     *     parameters={
     *          {"name"="price", "dataType"="string", "required"=true, "description"="Price in json"}
     *     }
     * )
     */
    public function importPriceAction(Request $request)
    {
        $price = json_decode($request->get('price'), true);

        $report['updatedCount'] = 0;
        $report['notFoundCount'] = 0;
        $report['notFound'] = [];

        if (!count($price)) {
            return new JsonResponse($report, 500);
        }

        // Токен авторизации для изменения настроек Субмарины
        $token = new UsernamePasswordToken('fake', 'fake', 'fake', ['ROLE_ADMIN']);
        $this->get('security.token_storage')->setToken($token);

        // Обновляем время последнего импорта. Нужно для пересчета корзины
        $since = new \DateTime();
        $this->get('submarine.options.manager')->setOptionValue(CartProvider::PRICE_UPDATED_AT, $since->format('d.m.Y H:i:s'));
        $this->get('core.options_cache')->clearCache();

        // Уничтожаем токен авторизации для изменения настроек Субмарины
        $this->get('security.token_storage')->setToken(null);

        // Отключаем автообновление времени доступа, чтобы не экспоритровать потом все продукты
        $this->getDoctrine()->getManager()->getClassMetadata(Page::class)->setLifecycleCallbacks([]);

        $groupedProducts = array_chunk(array_keys($price), self::BATCH_SIZE);

        foreach ($groupedProducts as $ids) {
            $products = $this->catalogProvider()->getProductsQuery($ids)->getResult();
            $notFound = array_combine(array_values($ids), array_values($ids));

            /** @var Page $product */
            foreach ($products as $product) {
                $currentPrice = (float)$product->getPrice();
                $product->setPrice($price[$product->getId()]['price']);

                // Особенность реализации
                $values = $product->getValues();
                $values['wholesale_discount'] = $price[$product->getId()]['wholesale_discount'];
                $values['wholesale_quantity'] = $price[$product->getId()]['wholesale_quantity'];
                $values['old_price'] = ($currentPrice === $product->getPrice()) ? $values['old_price'] : $currentPrice;
                $product->setValues($values);

                unset($notFound[$product->getId()]);
            }

            try {
                $this->getDoctrine()->getManager()->flush();
                $this->getDoctrine()->getManager()->clear();
            } catch (\Exception $e) {
                throw new \LogicException('Ошибка записи в базу данных');
            }

            $report['updatedCount'] += count($products);
            $report['notFoundCount'] += count($notFound);
            $report['notFound'] = array_merge($report['notFound'], $notFound);
        }

        return new JsonResponse($report);
    }


    /**
     * Экспорт всех товаров со всеми полями (включая доп свойства)
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     resource=true,
     * )
     */
    public function exportAllProductsAction(Request $request)
    {
        // Требуется повышенное выделение памяти
        ini_set('memory_limit', '768M');

        // Получить все продукты
        $products = $this->apiProvider()->getProductsQuery()->getResult();
        $data = [];

        foreach ($products as $product) {
            $data[] = $this->formatProductData($product);
        }

        return new JsonResponse($data);
    }

    /**
     * Экспорт одного товара по id со всеми полями (включая доп свойства)
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     resource=true,
     *     parameters={
     *          {"name"="id", "dataType"="integer", "required"=true, "description"="ID of entity"}
     *     }
     * )
     */
    public function exportProductAction(Request $request)
    {
        // Получить продукт по id
        $product = $this->apiProvider()->getProductsQuery($request->get('id'))->getOneOrNullResult();

        if (!$product) {
            return new JsonResponse('Entity not found', 404);
        }

        $data = $this->formatProductData($product);

        return new JsonResponse($data);
    }

    private function formatProductData(Page $product)
    {
        // Вернуть ID категории с наименьшим ID
        $minParentId = min(array_map(function (Page $page) {
            return $page->getId();
        }, $product->getParentPages()));

        // Получить дополнительные поля продукта
        $fieldKeys = $product->getFields()->getKeys();

        $fieldValues = array_map(function (PageValue $value) {
            return $value->getValue();
        }, $product->getFields()->getValues());

        // Получить "Свойства" продукта
        /** @var PropertyValuesProvider $propertyValuesProvider */
        $propertyValuesProvider = $this->get('submarine_properties.property_values_provider');
        $properties = $propertyValuesProvider->getEntityValuesQuery($product)->getResult();

        $propKeys = array_map(function (PropertyValue $value) {
            return $value->getProperty()->getTitle();
        }, $properties);

        $propValues = array_map(function (PropertyValue $value) {
            return $value->getValue();
        }, $properties);

        // Подготовка json ответа
        $prepared = [
            'id' => $product->getId(),
            'title' => $product->getTitle(),
            'url' => $product->getUrl(),
            'price' => $product->getPrice(),
            'available' => $product->isAvailable(),
            'productId' => $product->getProductId(),
            'parentId' => $minParentId,
            'fields' => array_combine(
                $fieldKeys, $fieldValues
            ),
            'properties' => array_combine(
                $propKeys, $propValues
            ),
        ];

        return $prepared;
    }

    /**
     * Экспорт опций из "Настройки" в Субмарине
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     resource=true,
     * )
     */
    public function exportOptionsAction(Request $request)
    {
        // Опции "Панели менеджера"
        /** @var OptionsProvider $optionsProvider */
        $optionsProvider = $this->get('core.options');
        $options = $optionsProvider->getModuleOptions('postroyka_manager');

        $data = array_map(function (Option $value) {
            return $value->getValue();
        }, $options);

        // Карта из "Submarine.Front"
        $map_data = $optionsProvider->getOption('submarine_front.map_data');
        $data[$map_data->getName()] = $map_data->getValue();

        return new JsonResponse($data);
    }

    /**
     * Экспорт категорий товаров
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     resource=true,
     * )
     */
    public function exportCategoriesAction(Request $request)
    {
        $categories = $this->apiProvider()->getCategoriesQuery()->getResult();

        $data = [];

        foreach ($categories as $category) {
            /** @var Page $category */
            // Вернуть ID родительской категории с наименьшим ID
            $minParentId = min(array_map(function (Page $page) {
                return $page->getId();
            }, $category->getParentPages()));

            // Позиция категории с наименьшим ID
            $minParentPos = null;
            foreach ($category->getParents() as $rel) {
                if($rel->getParent()->getId() == $minParentId){
                    $minParentPos = $rel->getPosition();
                }
            }

            $data[] = [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
                'parentId' => $minParentId,
                'position' => $minParentPos,
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * Расчет стоимости разгрузки
     * Пример JSON POST BODY:
     * {
     *     "products": [
     *         {
     *             "id": "27",
     *             "quantity": "7"
     *         },
     *         {
     *             "id": "105",
     *             "quantity": "9"
     *         }
     *     ],
     *     "delivery": "postroyka_manager.delivery_zone_7",
     *     "unloading": "order.unloading.room",
     *     "floor": "3",
     *     "elevator": "1",
     *     "extraFloor": "1"
     * }
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @ApiDoc(
     *     resource=true
     * )
     */
    public function calculateUnloadingAction(Request $request)
    {
        // Валидация корректности BODY json запроса
        $params = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('Invalid json body: ' . json_last_error_msg());
        }

        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'products' => new Assert\All([
                new Assert\Collection([
                    'id' => new Assert\NotBlank(),
                    'quantity' => new Assert\NotBlank(),
                ]),
            ]),
            'delivery' => new Assert\NotBlank(),
            'unloading' => new Assert\NotBlank(),
            'floor' => new Assert\NotBlank(),
            'elevator' => new Assert\NotBlank(),
            'extraFloor' => new Assert\NotBlank(),
        ]);

        if ($validator->validate($params, $constraint)->count()) {
            throw new BadRequestHttpException('Not all required keys exist');
        }

        // Очистка сессионной корзины
        /** @var CartProvider $cartProvider */
        $cartProvider = $this->get('postroyka_app.cart_provider');
        $cartProvider->clearCart();

        // Добавление продуктов в корзину
        foreach ($params['products'] as $productParam) {
            /** @var Page $product */
            $product = $this->catalogProvider()->getProductsQuery([$productParam['id']])->getOneOrNullResult();

            if ($product) {
                $cartProvider->addToCart(null, $product, $productParam['quantity']);
            }
        }

        unset($params['products']);

        // Расчет стоимости разгрузки
        /** @var SessionCartProvider $sessionProvider */
        $sessionProvider = $this->get('submarine_cart.session_provider');

        /** @var OrderCalculator $orderCalculator */
        $orderCalculator = $this->get('postroyka_app.order_calculator');
        $result = $orderCalculator->getUnloadingPrice($sessionProvider->getCart(), $params);

        return new JsonResponse($result);
    }

    /**
     * Приведение строки к float
     * @param string $number
     * @return float
     */
    private function castFloat($number)
    {
        $number = str_replace([' ', ','], ['', '.'], $number);

        return (float)$number;
    }
}