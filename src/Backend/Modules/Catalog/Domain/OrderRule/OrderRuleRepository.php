<?php

namespace Backend\Modules\Catalog\Domain\OrderRule;

use Backend\Modules\Catalog\Domain\OrderRule\Exception\OrderRuleNotFound;
use Doctrine\ORM\EntityRepository;

class OrderRuleRepository extends EntityRepository
{
    public function add(OrderRule $orderVat): void
    {
        // We don't flush here, see http://disq.us/p/okjc6b
        $this->getEntityManager()->persist($orderVat);
    }

    /**
     * @param int|null $id
     *
     * @return OrderRule|null
     * @throws OrderRuleNotFound
     */
    public function findOneById(?int $id): ?OrderRule
    {
        if ($id === null) {
            throw OrderRuleNotFound::forEmptyId();
        }

        /** @var OrderRule $orderRule */
        $orderRule = $this->findOneBy(['id' => $id]);

        if ($orderRule === null) {
            throw OrderRuleNotFound::forId($id);
        }

        return $orderRule;
    }
}
