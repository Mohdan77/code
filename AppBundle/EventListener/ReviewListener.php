<?php

namespace Postroyka\AppBundle\EventListener;

use Postroyka\AppBundle\Provider\CatalogProvider;
use Submarine\PagesBundle\Entity\Page;
use Submarine\PagesBundle\Manager\PagesManager;
use Submarine\ReviewsBundle\Events\ReviewEvent;
use Submarine\ReviewsBundle\Events\ReviewEvents;
use Submarine\ReviewsBundle\Provider\ReviewsProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReviewListener implements EventSubscriberInterface
{
    const PRODUCT_VALUE_RATING = 'rating';
    const PRODUCT_VALUE_REVIEWS_COUNT = 'reviews_count';

    /**
     * @var ReviewsProvider
     */
    private $reviewsProvider;

    /**
     * @var PagesManager
     */
    private $pagesManager;

    /**
     * @param ReviewsProvider $reviewsProvider
     * @param PagesManager $pagesManager
     */
    public function __construct(ReviewsProvider $reviewsProvider, PagesManager $pagesManager)
    {
        $this->reviewsProvider = $reviewsProvider;
        $this->pagesManager = $pagesManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            ReviewEvents::ON_CREATE => 'onReviewEvent',
            ReviewEvents::ON_CHANGE => 'onReviewEvent',
            ReviewEvents::ON_REMOVE => 'onReviewEvent',
        ];
    }

    public function onReviewEvent(ReviewEvent $event)
    {
        $review = $event->getReview();

        if ($review->getEntityName() === Page::class) {
            try {
                $product = $this->pagesManager->get($review->getEntityId());

                if ($product->getId() && $product->getType()->getId() === CatalogProvider::PRODUCT_TYPE) {
                    // Рейтинг товара: 1.0-5.0
                    $rating = $this->reviewsProvider->getEntityRating($product);
                    $rating = number_format($rating, 1);
                    $product->setValue(self::PRODUCT_VALUE_RATING, $rating);

                    // Кол-во активных отзывов
                    $count = $this->reviewsProvider->getEntityReviewsCount($product);
                    $product->setValue(self::PRODUCT_VALUE_REVIEWS_COUNT, $count);

                    $this->pagesManager->save($product);
                }
            } catch (\Exception $e) {
            }
        }
    }
}