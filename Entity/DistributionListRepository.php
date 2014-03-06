<?php

namespace CanalTP\MttBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * DistributionListRepositoryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DistributionListRepository extends EntityRepository
{
    public function sortSchedules($schedules, $timetable)
    {
        $distributionList = $this->findOneByTimetable($timetable);
        $sortedSchedules = array();
        $sortedSchedules['included'] = array();
        $sortedSchedules['excluded'] = array();
        if (empty($distributionList)) {
            $sortedSchedules['included'] = $schedules;
        } else {
            $includedStops = $distributionList->getIncludedStops();
            foreach ($includedStops as $scheduleId) {
                while (list($key, $schedule) = each($schedules)) {
                    if ($schedule->stop_point->id == $scheduleId) {
                        $sortedSchedules['included'][] = $schedule;
                        unset($schedules[$key]);
                        reset($schedules);
                        break;
                    }
                }
            }
            $sortedSchedules['excluded'] = $schedules;
        }

        return $sortedSchedules;
    }
}
