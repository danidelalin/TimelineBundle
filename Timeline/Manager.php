<?php

namespace Highco\TimelineBundle\Timeline;

use Highco\TimelineBundle\Model\TimelineAction;
use Highco\TimelineBundle\Timeline\Collection;
use Highco\TimelineBundle\Timeline\Provider\ProviderInterface;
use Highco\TimelineBundle\Model\TimelineActionManagerInterface;
use Highco\TimelineBundle\Timeline\Spread\Deployer;
use Highco\TimelineBundle\Timeline\Filter\FilterInterface;

/**
 * Manager timeline
 * Allow to push a timeline action or pull a list of timeline action
 *
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class Manager
{
    /**
     * @var TimelineActionManagerInterface
     */
    protected $timelineActionManager;

    /**
     * @var Deployer
     */
    protected $deployer;

    /**
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * @var array
     */
    protected $filters = array();

    /**
     * @param TimelineActionManagerInterface $timelineActionManager Manager to retrieve from local storage
     * @param Deployer                       $deployer              Deploy to notify on deploy on spreads
     * @param ProviderInterface              $provider              Provider to pull
     */
    public function __construct(TimelineActionManagerInterface $timelineActionManager, Deployer $deployer, ProviderInterface $provider)
    {
        $this->provider              = $provider;
        $this->deployer              = $deployer;
        $this->timelineActionManager = $timelineActionManager;
    }

    /**
     * @param TimelineAction $timelineAction
     *
     * @return boolean
     */
    public function push(TimelineAction $timelineAction)
    {
        $this->timelineActionManager->updateTimelineAction($timelineAction);

        if ($this->deployer->getDelivery() == Deployer::DELIVERY_IMMEDIATE) {
            $this->deployer->deploy($timelineAction);

            return true;
        }

        return false;
    }

    /**
     * @param string $subjectModel The class of the subject
     * @param string $subjectId    The oid of the subject
     * @param string $context      default GLOBAL
     * @param array  $options      An array of options
     *
     * @return array
     */
    public function getWall($subjectModel, $subjectId, $context = 'GLOBAL', $options = array())
    {
        $params = array(
            'subjectModel' => $subjectModel,
            'subjectId'    => $subjectId,
            'context'      => $context,
        );

        $results = new Collection($this->provider->getWall($params, $options));

        return $this->applyFilter($results);
    }

    /**
     * @param string $subjectModel The class of the subject
     * @param string $subjectId    The oid of the subject
     * @param array  $options      An array of options to give to puller
     *
     * @return array
     */
    public function getTimeline($subjectModel, $subjectId, $options = array())
    {
        $params = array(
            'subjectModel' => $subjectModel,
            'subjectId'    => $subjectId,
        );

        $results = new Collection($this->timelineActionManager->getTimeline($params, $options));

        return $this->applyFilter($results);
    }

    /**
     * This action will filters each results given in parameters
     * You have to return results
     *
     * @param array $results
     *
     * @return array
     */
    public function applyFilter($results)
    {
        foreach ($this->filters as $filter) {
            $results = $filter->filter($results);
        }

        return $results;
    }

    /**
     * @param FilterInterface $filter
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @param FilterInterface $filter
     */
    public function removeFilter(FilterInterface $filter)
    {
        foreach ($this->filters as $key => $filterExisting) {
            if ($filterExisting == $filter) {
                unset($this->filters[$key]);
            }
        }
    }

}