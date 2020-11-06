<?php


namespace Postroyka\ApiBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Postroyka\AppBundle\Provider\CatalogProvider;
use Submarine\PagesBundle\Entity\Page;

class ApiProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * ApiProvider constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $id
     *
     * @return \Doctrine\ORM\Query
     */
    public function getProductsQuery($id = null)
    {
        $qb = $this
            ->entityManager
            ->createQueryBuilder()
            ->select('page', 'parents', 'fields')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.parents', 'parents')
            ->leftJoin('page.fields', 'fields')
            ->where('page.type = :type')
            ->setParameter('type', CatalogProvider::PRODUCT_TYPE);

        if ($id) {
            $qb
                ->andWhere('page.id = :id')
                ->setParameter('id', $id);
        }

        return $qb->getQuery();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getCategoriesQuery()
    {
        $qb = $this
            ->entityManager
            ->createQueryBuilder()
            ->select('page', 'parents')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.parents', 'parents')
            ->where('page.type = :type')
            ->setParameter('type', CatalogProvider::GROUP_TYPE);

        return $qb->getQuery();
    }
}