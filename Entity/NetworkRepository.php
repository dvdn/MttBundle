<?php

namespace CanalTP\MttBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * NetworkRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NetworkRepository extends EntityRepository
{
    public function findNetworksByUserId($userId)
    {
        $sql = 'SELECT n.external_coverage_id, n.external_id';
        $sql .= ' FROM mtt.users_networks un, mtt.network n';
        $sql .= ' WHERE un.network_id = n.id AND un.user_id = ' . $userId;
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        $stmt->execute();

        return ($stmt->fetchAll());
    }
}
