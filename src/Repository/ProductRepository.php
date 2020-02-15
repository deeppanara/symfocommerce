<?php

namespace App\Repository;

use AppBundle\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\Product;
use App\Entity\User;

/**
 * ProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * Repository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @param string $slug
     * @return Product|null
     * @throws NonUniqueResultException
     */
    public function findBySlug(string $slug): ?Product
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb->select('p')
            ->from(Product::class, 'p')
            ->where('p.slug = :slug')
            ->andWhere($qb->expr()->neq('p.deleted', 1))
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Category $category
     * @param User $user
     * @return QueryBuilder
     */
    public function findByCategoryQB(Category $category, ?User $user): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(['p', 'pi', 'pm', 'pfa', 'pfe'])
            ->from(Product::class, 'p')
            ->innerJoin('p.category', 'ca')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.measure', 'pm')
            ->leftJoin('p.favourites', 'pfa', 'WITH', 'pfa.user = :user')//if liked
            ->leftJoin('p.featured', 'pfe')
            ->where('ca = :category')
            ->andWhere('p.quantity <> 0')
            ->andWhere($qb->expr()->neq('p.deleted', 1))
            ->setParameter('category', $category)
            ->setParameter('user', $user);

        return $qb;
    }

    /**
     * @param Manufacturer $manufacturer
     * @param User $user
     * @return QueryBuilder
     */
    public function findByManufacturerQB(Manufacturer $manufacturer, ?User $user): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(['p', 'pi', 'pm', 'pfa', 'pfe'])
            ->from(Product::class, 'p')
            ->innerJoin('p.manufacturer', 'ma')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.measure', 'pm')
            ->leftJoin('p.favourites', 'pfa', 'WITH', 'pfa.user = :user')//if liked
            ->leftJoin('p.featured', 'pfe')
            ->where('ma.id = :manufacturer')
            ->andWhere('p.quantity <> 0')
            ->andWhere($qb->expr()->neq('p.deleted', 1))
            ->setParameter('manufacturer', $manufacturer)
            ->setParameter('user', $user);

        return $qb;
    }

    /**
     * @param User $user
     * @return QueryBuilder
     */
    public function getFavouritesQB(?User $user): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(['p', 'pi', 'pm', 'pfa', 'pfe'])
            ->from(Product::class, 'p')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.measure', 'pm')
            ->innerJoin('p.favourites', 'pfa', 'WITH', 'pfa.user = :user')//only liked
            ->leftJoin('p.featured', 'pfe')
            ->andWhere('p.quantity <> 0')
            ->andWhere($qb->expr()->neq('p.deleted', 1))
            ->setParameter('user', $user);

        return $qb;
    }

    /**
     * @param array $searchWords
     * @param User $user
     * @return QueryBuilder
     */
    public function getSearchQB(array $searchWords, ?User $user): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(['p', 'pi', 'pm', 'pfa', 'pfe'])
            ->from(Product::class, 'p')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.measure', 'pm')
            ->leftJoin('p.favourites', 'pfa', 'WITH', 'pfa.user = :user')//if liked
            ->leftJoin('p.featured', 'pfe')
            ->where('p.quantity <> 0')
            ->andWhere($qb->expr()->neq('p.deleted', 1))
            ->setParameter('user', $user);

        $cqbORX = [];
        foreach ($searchWords as $searchWord) {
            $cqbORX[] = $qb->expr()->like('p.name', $qb->expr()->literal('%' . $searchWord . '%'));
            $cqbORX[] = $qb->expr()->like('p.description', $qb->expr()->literal('%' . $searchWord . '%'));
        }

        $qb->andWhere(\call_user_func_array([$qb->expr(), 'orx'], $cqbORX));

        return $qb;
    }

    /**
     * @param int $quantity
     * @param User $user
     * @return Product[]|null
     */
    public function getLatest(int $quantity = 1, ?User $user): ?array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(['p', 'pi', 'pm', 'pfa', 'pfe'])
            ->from(Product::class, 'p')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.measure', 'pm')
            ->leftJoin('p.favourites', 'pfa', 'WITH', 'pfa.user = :user')//if liked
            ->leftJoin('p.featured', 'pfe')
            ->where('p.quantity <> 0')
            ->andWhere($qb->expr()->neq('p.deleted', 1))
            ->setMaxResults($quantity)
            ->setParameter('user', $user)
            ->addOrderBy('p.dateCreated', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $quantity
     * @param User $user
     * @return Product[]|null
     */
    public function getFeatured(int $quantity = 1, ?User $user): ?array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(['p', 'pi', 'pm', 'pfa', 'pfe'])
            ->from(Product::class, 'p')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.measure', 'pm')
            ->leftJoin('p.favourites', 'pfa', 'WITH', 'pfa.user = :user')//if liked
            ->innerJoin('p.featured', 'pfe')
            ->where('p.quantity <> 0')
            ->andWhere($qb->expr()->neq('p.deleted', 1))
            ->setMaxResults($quantity)
            ->setParameter('user', $user)
            ->addOrderBy('pfe.productOrder', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $productIdsArray
     * @param User|null $user
     * @param int $quantity
     * @return Product[]|null
     */
    public function getLastSeen(array $productIdsArray, ?User $user, int $quantity = 1): ?array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(['p', 'pi', 'pm', 'pfe'])
            ->from(Product::class, 'p')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.measure', 'pm')
            ->leftJoin('p.featured', 'pfe')
            ->where('p.quantity <> 0')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $productIdsArray)
            ->setMaxResults($quantity);

        if ($user) {
            $qb->addSelect('pfa')
                ->leftJoin('p.favourites', 'pfa', 'WITH', 'pfa.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * return products for admin
     *
     * @return QueryBuilder
     */
    public function getAllProductsAdminQB(): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(['p', 'pi', 'pm', 'pc', 'pfe'])
            ->from(Product::class, 'p')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.manufacturer', 'pm')
            ->leftJoin('p.category', 'pc')
            ->leftJoin('p.featured', 'pfe')
            ->where($qb->expr()->neq('p.deleted', 1));

        return $qb;
    }

    /**
     * return products for admin
     *
     * @param string $searchPhrase
     * @return QueryBuilder
     */
    public function searchProductsAdminQB(string $searchPhrase): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p', 'pi', 'pm', 'pc', 'pfe')
            ->from(Product::class, 'p')
            ->leftJoin('p.images', 'pi')
            ->leftJoin('p.manufacturer', 'pm')
            ->leftJoin('p.category', 'pc')
            ->leftJoin('p.featured', 'pfe')
            ->where($qb->expr()->neq('p.deleted', 1));

        $searchWords = explode(' ', $searchPhrase);
        $cqbORX = [];

        foreach ($searchWords as $searchWord) {
            $cqbORX[] = $qb->expr()->like('p.name', $qb->expr()->literal('%' . $searchWord . '%'));
            $cqbORX[] = $qb->expr()->like('p.description', $qb->expr()->literal('%' . $searchWord . '%'));
        }

        $qb->andWhere(\call_user_func_array([$qb->expr(), 'orx'], $cqbORX));

        return $qb;
    }

    /**
     * @return array
     */
    public function getArrayForSitemap(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.slug, p.dateUpdated')
            ->getQuery()
            ->getArrayResult();
    }
}
