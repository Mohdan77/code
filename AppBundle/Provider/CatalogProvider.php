<?php

namespace Postroyka\AppBundle\Provider;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Submarine\PagesBundle\Entity\Page;
use Submarine\PagesBundle\Entity\Relations;
use Submarine\PagesBundle\Filter\PagesFilter;
use Submarine\PagesBundle\Provider\PagesProvider;
use Symfony\Component\HttpFoundation\Request;

class CatalogProvider extends PagesProvider
{
    const PRODUCT_TYPE = 'product';
    const GROUP_TYPE = 'catalogGroup';
    const SEO_GROUP_TYPE = 'catalogSeoGroup';
    const SEO_GROUP_DOWN_TYPE = 'catalogSeoGroupDown';
    const STOCK_FIELD = 'stock';
    const NOVELTY_FIELD = 'novelty';
    const MARKDOWN_FIELD = 'markdown';
    const SUPERSHARE_FIELD = 'supershare';
    const MANUFACTURER_FIELD = 'manufacturer';
    const SALES_LEADER = 'sales_leader';
    const MAYNEED = 'mayneed';
    const CATALOG_TAG = 'catalog';
    const PAGE_TYPE = 'page';

    /**
     * @param Page $page
     * @param PagesFilter $filter
     *
     * @return Query
     * @throws \Exception
     */
    public function getChildPagesQuery(Page $page = null, PagesFilter $filter = null)
    {
        $builder = $this->createBuilderFromFilter($filter);
        $builder
            ->select('page', 'relation', 'parent')
            ->leftJoin('relation.parent', 'parent')
            ->andWhere('relation.parent = :page')
            ->setParameter('page', $page);

        return $builder->getQuery();
    }

    /**
     * Товары на акции
     * @return Query
     */
    public function getStockAndMarkdownQuery()
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page', 'relation', 'parent')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.fields', 'fields')
            ->leftJoin('fields.field', 'field_types')
            ->leftJoin('page.parents', 'relation')
            ->leftJoin('relation.parent', 'parent')
            ->where('page.enabled = true')
            ->andWhere('page.deleted = false')
            ->andWhere('page.type = :type')
            ->andWhere('field_types.id = :stock OR field_types.id = :markdown OR field_types.id = :supershare')
            ->andWhere('fields.value = true')
            ->setParameter('type', self::PRODUCT_TYPE)
            ->setParameter('stock', self::STOCK_FIELD)
            ->setParameter('markdown', self::MARKDOWN_FIELD)
            ->setParameter('supershare', self::SUPERSHARE_FIELD)
            ->groupBy('page.id')
            ->orderBy('page.title')
            ->getQuery();
    }

    /**
     * Новинки
     * @return Query
     */
    public function getNoveltyQuery()
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page', 'relation', 'parent')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.fields', 'fields')
            ->leftJoin('fields.field', 'field_types')
            ->leftJoin('page.parents', 'relation')
            ->leftJoin('relation.parent', 'parent')
            ->where('page.enabled = true')
            ->andWhere('page.deleted = false')
            ->andWhere('page.type = :type')
            ->andWhere('field_types.id = :novelty')
            ->andWhere('fields.value = true')
            ->setParameter('type', self::PRODUCT_TYPE)
            ->setParameter('novelty', self::NOVELTY_FIELD)
            ->groupBy('page.id')
            ->orderBy('page.title')
            ->getQuery();
    }

    /**
     * Best sellers
     * @return Query
     */
    public function getSalesLeadersQuery()
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page', 'relation', 'parent')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.fields', 'fields')
            ->leftJoin('fields.field', 'field_types')
            ->leftJoin('page.parents', 'relation')
            ->leftJoin('relation.parent', 'parent')
            ->where('page.enabled = true')
            ->andWhere('page.deleted = false')
            ->andWhere('page.type = :type')
            ->andWhere('field_types.id = :sales_leader')
            ->andWhere('fields.value = true')
            ->setParameter('type', self::PRODUCT_TYPE)
            ->setParameter('sales_leader', self::SALES_LEADER)
            ->groupBy('page.id')
            ->orderBy('page.title')
            ->getQuery();
    }

    /**
     * Best sellers
     * @return Query
     */
    public function getMayneedQuery()
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page', 'relation', 'parent')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.fields', 'fields')
            ->leftJoin('fields.field', 'field_types')
            ->leftJoin('page.parents', 'relation')
            ->leftJoin('relation.parent', 'parent')
            ->where('page.enabled = true')
            ->andWhere('page.deleted = false')
            ->andWhere('page.type = :type')
            ->andWhere('field_types.id = :mayneed')
            ->andWhere('fields.value = true')
            ->setParameter('type', self::PRODUCT_TYPE)
            ->setParameter('mayneed', self::MAYNEED)
            ->groupBy('page.id')
            ->orderBy('page.title')
            ->getQuery();
    }

    /**
     * Продукты, обновленные после указанной даты
     * @param \DateTime $since
     * @return Query
     */
    public function getUpdatedProductsQuery(\DateTime $since)
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page', 'relation', 'parent')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.parents', 'relation')
            ->leftJoin('relation.parent', 'parent')
            ->where('page.type = :type')
            ->andWhere('page.updatedAt > :since')
            ->setParameter('since', $since)
            ->setParameter('type', self::PRODUCT_TYPE)
            ->groupBy('page.id')
            ->getQuery();
    }

    /**
     * Все активные продукты
     * @return Query
     */
    public function getActiveProductsQuery()
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page', 'relation', 'parent')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.parents', 'relation')
            ->leftJoin('relation.parent', 'parent')
            ->where('page.type = :type')
            ->andWhere('page.enabled = true')
            ->andWhere('page.deleted = false')
            ->setParameter('type', self::PRODUCT_TYPE)
            ->groupBy('page.id')
            ->getQuery();
    }

    /**
     * Продукты с указанными ID
     * @param array $ids
     * @return Query
     */
    public function getProductsQuery(array $ids)
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page')
            ->from(Page::class, 'page', 'page.id')
            ->where('page.type = :type')
            ->andWhere('page.id IN (:ids)')
            ->setParameter('type', self::PRODUCT_TYPE)
            ->setParameter('ids', $ids)
            ->groupBy('page.id')
            ->getQuery();
    }

    /**
     * Группы товаров
     * @return Query
     */
    public function getGroupsQuery()
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page', 'relation', 'parent')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.parents', 'relation')
            ->leftJoin('relation.parent', 'parent')
            ->where('page.type = :type')
            ->setParameter('type', self::GROUP_TYPE)
            ->groupBy('page.id')
            ->getQuery();
    }

    /**
     * Дерево групп каталога
     * @return array
     */
    public function getGroupsTree()
    {
        $filter = new PagesFilter(self::GROUP_TYPE);
        $rootGroups = $this->getChildPagesByTagQuery(self::CATALOG_TAG, $filter)->getResult();
        $groups = [];

        /**
         * @var Page $group
         */
        foreach ($rootGroups as $group) {
            $groups[] = [
                'group' => $group,
                'subgroups' => $this->getChildPagesByIdQuery($group->getId(), $filter)->getResult(),
            ];
        }

        return $groups;
    }

    /**
     * Дерево групп каталога (массивы)
     * @return array
     */
    public function getGroupsTreeArray()
    {
        $filter = new PagesFilter([self::GROUP_TYPE, self::PAGE_TYPE]);
        $rootGroups = $this->getChildPagesByTagQuery(self::CATALOG_TAG, $filter)->getArrayResult();
        $groups = [];

        foreach ($rootGroups as $group) {
            $levels = [
                'group' => $group,
                'subgroups' => $this->getChildPagesByIdQuery($group['id'], $filter)->getArrayResult(),
                'subsubgroups' => [],
            ];

            foreach ($levels['subgroups'] as $subgroup) {
                $subsubgroups = $this->getChildPagesByIdQuery($subgroup['id'], $filter)->getArrayResult();

                if ($subsubgroups) {
                    $levels['subsubgroups'][$subgroup['id']] = $subsubgroups;
                }
            }

            $groups[] = $levels;
        }

        return $groups;
    }

    /**
     * Продукты по группам
     * @return array
     */
    public function getGroupedProducts()
    {
        $result = [];
        $filter = new PagesFilter(self::PRODUCT_TYPE);
        $groups = $this->getGroupsTree();

        foreach ($groups as $group) {
            $products = $this->getChildPages($group['group'], $filter);

            if ($products) {
                $result[] = [
                    'group' => $group['group'],
                    'products' => $products,
                ];
            }

            foreach ($group['subgroups'] as $subgroup) {
                $products = $this->getChildPages($subgroup, $filter);

                if ($products) {
                    $result[] = [
                        'group' => $subgroup,
                        'products' => $products,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Получение страницы по URL, чувствительное к регистру
     * Требуется beberlei/DoctrineExtensions для функции BINARY
     * @param string $url URL
     * @param bool $allowDisabled Разрешить отключенные и удаленные страницы
     * @return Page
     */
    public function getByUrl($url, $allowDisabled = false)
    {
        if (!$url) {
            return new Page();
        }

        if (strpos($url, '/') !== 0) {
            $url = '/' . $url;
        }

        try {
            /** @var $page Page */
            $page = $this
                ->entityManager()
                ->createQueryBuilder()
                ->select('page')
                ->from(Page::class, 'page')
                ->where('BINARY(page.url) = :url')
                ->setParameter('url', $url)
                ->getQuery()
                ->getSingleResult();

            if (!$allowDisabled && ($page->isDeleted() || !$page->isEnabled())) {
                throw new NoResultException;
            }

            $page->setFilesProvider($this->files);

            return $page;
        } catch (\Exception $exc) {
            return new Page();
        }
    }

    /**
     * Поиск страниц: AND по всем полям
     * Требуется beberlei/DoctrineExtensions для функции CONCAT_WS
     * @param string $query Фраза поиска
     * @param array $fields Поля поиска
     * @param PagesFilter $filter
     * @return Query
     * @throws \Exception
     */
    public function searchQuery($query, array $fields = ['title'], PagesFilter $filter = null, Request $request = null)
    {
        $queryBuilder = $this->searchQueryBuilder($query, $fields, $filter);
        $queryBuilder->select('page', 'relation', 'parent');

//        $get = $request->query->all();
//        dd($get);

//        $queryBuilder
//            ->innerJoin('page.fields', 'fields')
//            ->innerJoin('fields.field', 'type_field');

//        if (isset($get['group']) && (int)$get['group'] > 0) {
//            $queryBuilder
//                ->andWhere('relation.parent = :group')
//                ->setParameter('group', $get['group']);
//        }

//        if (isset($get['brand']) && (int)$get['brand'] > 0) {
//            $brand = $this->get($get['brand']);
//            $queryBuilder
//                ->andWhere('type_field.id = :field_id AND fields.value = :field_value')
//                ->andWhere('page.enabled = true')
//                ->andWhere('page.deleted = false')
//                ->andWhere('page.type = :type')
//                ->andWhere('parent.type = :parentType')
//                ->setParameter('type', self::PRODUCT_TYPE)
//                ->setParameter('parentType', self::GROUP_TYPE)
//                ->setParameter('field_id', self::MANUFACTURER_FIELD)
//                ->setParameter('field_value', $brand->getId());
//
////            $queryBuilder->innerJoin('fields.field', 'type_field')->andWhere('type_field.id = :field_id AND fields.value = :field_value')->setParameter('field_value', $brand->getId());
//        }

//        if(isset($get['stock'])){
//            $queryBuilder
//                ->andWhere('type_field.id = :field_id')
//                ->setParameter('field_id', self::STOCK_FIELD);
//        }
//        if(isset($get['sales_leader'])){
//            $queryBuilder
//                ->andWhere('type_field.id = :field_id')
//                ->setParameter('field_id', self::SALES_LEADER);
//        }
//        if(isset($get['novelty'])){
//            $queryBuilder
//                ->andWhere('type_field.id = :field_id')
//                ->setParameter('field_id', self::NOVELTY_FIELD);
//        }
//        dd($queryBuilder->getDQL());
        return $queryBuilder->getQuery();
    }

    /**
     * Количество найденных страниц: AND по всем полям
     * Требуется beberlei/DoctrineExtensions для функции CONCAT_WS
     * @param string $query Фраза поиска
     * @param array $fields Поля поиска
     * @param PagesFilter $filter
     * @return int
     * @throws \Exception
     */
    public function searchCount($query, array $fields = ['title'], PagesFilter $filter = null)
    {
        $queryBuilder = $this->searchQueryBuilder($query, $fields, $filter);
        $queryBuilder->select('page.id');

        return count($queryBuilder->getQuery()->getScalarResult());
    }

    /**
     * Поиск страниц: AND по всем полям
     * Требуется beberlei/DoctrineExtensions для функции CONCAT_WS
     * @param string $query Фраза поиска
     * @param array $fields Поля поиска
     * @param PagesFilter $filter
     * @return QueryBuilder
     * @throws \Exception
     */
    private function searchQueryBuilder($query, array $fields = ['title'], PagesFilter $filter = null)
    {
        $queryArray = explode(' ', $query);
        $words = [];

        // Массив слов для поиска
        foreach ($queryArray as $item) {
            if (mb_strlen($item) >= 2) {
                $words[] = $item;
            }
        }

        $countWords = count($words);
        $dqlWhere = '';

        // Список полей поиска
        foreach ($fields as &$field) {
            $field = 'page.' . $field;
        }

        unset($field);


        // Условие поиска: AND по всем полям
        for ($i = 0; $i < $countWords; $i++) {
            $dqlWhere .= '(';

            $keyWord = 'word_' . $i;
            $dqlWhere .= "CONCAT_WS(''," . implode(', ', $fields) . ') LIKE :' . $keyWord;

            $dqlWhere .= ')';

            if ($i + 1 !== $countWords) {
                $dqlWhere .= ' AND ';
            }
        }


        $builder = $this->createBuilderFromFilter($filter);
        $builder
            ->leftJoin('relation.parent', 'parent')
            ->andWhere($dqlWhere)
            ->groupBy('page.id');

        foreach ($words as $key => $word) {
            $builder->setParameter('word_' . $key, '%' . $word . '%');
        }

        return $builder;
    }

    public function getGroupsProductsManufacturerQuery(Page $page)
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('rel as relation', 'parent', 'COUNT(page) as cnt')
            ->from(Relations::class, 'rel')
            ->innerJoin('rel.page', 'page')
            ->innerJoin('rel.parent', 'parent')
            ->innerJoin('page.fields', 'fields')
            ->innerJoin('fields.field', 'type_field')
            ->andWhere('type_field.id = :field_id AND fields.value = :field_value')
            ->andWhere('page.enabled = true')
            ->andWhere('page.deleted = false')
            ->andWhere('page.type = :type')
            ->andWhere('parent.type = :parentType')
            ->setParameter('type', self::PRODUCT_TYPE)
            ->setParameter('parentType', self::GROUP_TYPE)
            ->setParameter('field_id', self::MANUFACTURER_FIELD)
            ->setParameter('field_value', $page->getId())
            ->groupBy('rel.parent')
            ->getQuery();
    }

    //adam
    public function getChildGroup()
    {
        $group = $this->getGroupsTreeArray();
        $data = [];
        foreach ($group as $item){
            if (empty($item['subgroups'])){
                $data[$item['group']['id']] = $item['group'];
            }
            if (!empty($item['subgroups'])) {
                foreach ($item['subgroups'] as $subgroup){
                    $data[$subgroup['id']] = $subgroup;
                }
            }
        }

        return $data;
    }


    /**
     * Поиск страниц: AND по всем полям
     * Требуется beberlei/DoctrineExtensions для функции CONCAT_WS
     * @param string $query Фраза поиска
     * @param array $fields Поля поиска
     * @param PagesFilter $filter
     * @return Query
     * @throws \Exception
     */
    public function searchQueryFilter($query, array $fields = ['title'], PagesFilter $filter = null, Request $request = null)
    {
        $queryBuilder = $this->searchQueryBuilderFilter($query, $fields, $filter);
        $queryBuilder->select('page', 'relation', 'parent');

        $get = $request->query->all();
//dd($get);
        if ($get['group']) $queryBuilder->andWhere('relation.parent = :group')->setParameter('group', $get['group']);
//        dd($request->query->all(), $queryBuilder->getDQL());
        return $queryBuilder->getQuery();
    }

    /**
     * Поиск страниц: AND по всем полям
     * Требуется beberlei/DoctrineExtensions для функции CONCAT_WS
     * @param string $query Фраза поиска
     * @param array $fields Поля поиска
     * @param PagesFilter $filter
     * @return QueryBuilder
     * @throws \Exception
     */
    private function searchQueryBuilderFilter($query, array $fields = ['title'], PagesFilter $filter = null)
    {
        $queryArray = explode(' ', $query);
        $words = [];

        // Массив слов для поиска
        foreach ($queryArray as $item) {
            if (mb_strlen($item) >= 2) {
                $words[] = $item;
            }
        }

        $countWords = count($words);
        $dqlWhere = '';

        // Список полей поиска
        foreach ($fields as &$field) {
            $field = 'page.' . $field;
        }

        unset($field);


        // Условие поиска: AND по всем полям
        for ($i = 0; $i < $countWords; $i++) {
            $dqlWhere .= '(';

            $keyWord = 'word_' . $i;
            $dqlWhere .= "CONCAT_WS(''," . implode(', ', $fields) . ') LIKE :' . $keyWord;

            $dqlWhere .= ')';

            if ($i + 1 !== $countWords) {
                $dqlWhere .= ' AND ';
            }
        }


        $builder = $this->createBuilderFromFilter($filter);
        $builder
            ->leftJoin('relation.parent', 'parent')
            ->andWhere($dqlWhere)
            ->groupBy('page.id');

        foreach ($words as $key => $word) {
            $builder->setParameter('word_' . $key, '%' . $word . '%');
        }

        return $builder;
    }


    /**
     * Товары на акции
     * @return Query
     */
    public function getAddFilterQuery()
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('page', 'relation', 'parent')
            ->from(Page::class, 'page', 'page.id')
            ->leftJoin('page.fields', 'fields')
            ->leftJoin('fields.field', 'field_types')
            ->leftJoin('page.parents', 'relation')
            ->leftJoin('relation.parent', 'parent')
            ->where('page.enabled = true')
            ->andWhere('page.deleted = false')
            ->andWhere('page.type = :type')
            ->andWhere('field_types.id = :stock OR field_types.id = :markdown OR field_types.id = :supershare')
            ->andWhere('fields.value = true')
            ->setParameter('type', self::PRODUCT_TYPE)
            ->setParameter('stock', self::STOCK_FIELD)
            ->setParameter('markdown', self::MARKDOWN_FIELD)
            ->setParameter('supershare', self::SUPERSHARE_FIELD)
            ->groupBy('page.id')
            ->orderBy('page.title')
            ->getQuery();
    }

}
