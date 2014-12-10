<?php

namespace lib\Medals;

/**
 * medal to be awarded when use complete specified geopath
 *
 * @author Łza
 */
class MedalGeopathCompleted extends medal implements \lib\Medals\MedalInterface
{

    protected $conditions;

    public function checkConditionsForUser(\lib\User\User $user)
    {

        if ($this->conditions['geoPath']['ocNodeId'] !== $this->config->getOcNodeId()) { /* this medal is not available in current node */
            return;
        }

        $query = 'SELECT count(`id`) as `completedLogCount` FROM `PowerTrail_comments` WHERE `deleted` = 0 AND `userId` = :1 AND `PowerTrailId` = :2 ';
        /* @var $db \dataBase */
        $db = \lib\Database\DataBaseSingleton::Instance();
        $db->multiVariableQuery($query, $user->getUserId(), $this->conditions['geoPath']['geoPathId']);
        $cacheCountArr = $db->dbResultFetchOneRowOnly();
        if ($cacheCountArr['completedLogCount'] == 1) {
            $this->prizedTime = date($this->config->getDbDateTimeFormat());
        } else {
            $this->prizedTime = false;
        }
        $this->storeMedalStatus($user);
    }

}
