<?php

namespace app\util;


use TiBeN\CrontabManager\CrontabJob;
use TiBeN\CrontabManager\CrontabRepository;
use TiBeN\CrontabManager\CrontabAdapter;

/**
 * This class describes a crontab.
 */
class Crontab
{
    public $crontabRepository;

    function __construct()
    {
        $this->crontabRepository = new CrontabRepository(new CrontabAdapter());
    }


    /**
     * Adds an autocache job.
     *
     * @param      <type>  $hours  The hours
     */
    public function addAutocacheJob($hours)
    {
        $this->crontabRepository = new CrontabRepository(new CrontabAdapter());
        $crontabJob = new CrontabJob();
        $crontabJob
            ->setEnabled(true)
            ->setMinutes(1)
            ->setHours('*/' . $hours)
            ->setDayOfMonth('*')
            ->setMonths('*')
            ->setDayOfWeek('*')
            ->setTaskCommandLine('php ~/www/cli.php -m autocache')
            ->setComments('Auto caching sites');

            $this->crontabRepository->addJob($crontabJob);
            $this->crontabRepository->persist();
    }


    /**
     * { function_description }
     */
    public function delAutocacheJob()
    {

    }


    /**
     * { function_description }
     *
     * @param      <type>  $hours  The hours
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function updateAutocacheJob($hours)
    {
        $results = $this->crontabRepository->findJobByRegex('/autocache/');

        if (count($results) > 0)
        {
            $crontabJob = $results[0];
            $crontabJob->setHours('*/' . $hours);
            $res = $this->crontabRepository->persist();
        }
        else
        {
            $res = $this->addAutocacheJob($hours);
        }

        return $res;
    }
}