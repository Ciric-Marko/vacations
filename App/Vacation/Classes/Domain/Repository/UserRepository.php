<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 22.3.2019.
 * Time: 23.57
 */

namespace App\Vacation\Domain\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findAllOrderedByName()
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT p FROM AppBundle:Product p ORDER BY p.name ASC'
            )
            ->getResult();
    }
}