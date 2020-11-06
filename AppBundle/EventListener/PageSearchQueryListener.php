<?php

declare(strict_types=1);

namespace Postroyka\AppBundle\EventListener;

use Submarine\PagesBundle\Event\PageSearchQueryEvent;
use Submarine\PagesBundle\Manager\PageSearchQueryManager;
use Submarine\PagesBundle\Entity\PageSearchQuery;
use Psr\Log\LoggerInterface;

/**
 * Save search queries
 */
class PageSearchQueryListener
{
    /**
     * @var PageSearchQueryManager
     */
    private $searchQueryManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(PageSearchQueryManager $searchQueryManager, LoggerInterface $logger)
    {
        $this->searchQueryManager = $searchQueryManager;
        $this->logger = $logger;
    }

    /**
     * @param PageSearchQueryEvent $event
     */
    public function onSearchQueryCreate(PageSearchQueryEvent $event): void
    {
        try {
            $user = $event->getUser();

            $searchQuery = new PageSearchQuery(
                trim($event->getQuery()),
                $user ? $user->getId() : null
            );

            $this->searchQueryManager->create($searchQuery);
        } catch (\Throwable $e) {
            $this->logger->error("Ошибка записи запросов поиска: {message} \n{trace}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}