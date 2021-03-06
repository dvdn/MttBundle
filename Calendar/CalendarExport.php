<?php

namespace CanalTP\MttBundle\Calendar;

use CanalTP\MttBundle\Calendar as CalendarCsv;
use CanalTP\MttBundle\Entity\Calendar;
use Guzzle\Http\Message\Response;
use Guzzle\Http\ClientInterface;

class CalendarExport
{
    const EXPORT_API = '/grid_calendar';
    const ENTRY_POINT = '/coverages';
    const EXPORT_FILENAME = 'export_calendars.zip';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    private $archiveFolder;

    public function __construct(ClientInterface $httpClient, $archiveFolder = null)
    {
        $this->httpClient = $httpClient;

        if (null === $archiveFolder) {
            $archiveFolder = sys_get_temp_dir();
        }

        $this->archiveFolder = $archiveFolder;
    }

    /**
     * @param string $externalCoverageId External Coverage Id
     * @param Calendar[] $calendars Array of Calendar Entities
     *
     * @return Response
     */
    public function export($externalCoverageId, array $calendars)
    {
        if (!is_dir($folder = $this->archiveFolder.'/'.$externalCoverageId)) {
            mkdir($folder, 0777, true);
        }

        $location = $folder.'/'.static::EXPORT_FILENAME;

        $calendarArchiveGenerator = new CalendarArchiveGenerator($location);
        $calendarArchiveGenerator->addCsv(new CalendarCsv\GridCalendarsCsv($calendars));
        $calendarArchiveGenerator->addCsv(new CalendarCsv\GridPeriodsCsv($calendars));
        $calendarArchiveGenerator->addCsv(new CalendarCsv\GridNetworksAndLinesCsv($calendars));
        $calendarArchiveGenerator->getArchive()->close();

        $response = $this->sendCalendar($externalCoverageId, $location);

        unlink($location);

        return $response;
    }

    /**
     * Post a calendar to tartare
     *
     * @param string $externalCoverageId External coverage Id
     * @param string $location Calendar location
     * @return Response Guzzle Response
     */
    public function sendCalendar($externalCoverageId, $location)
    {
        $client  = $this->httpClient;

        $request = $client->post(
            [self::ENTRY_POINT.'/{coverage}/'.self::EXPORT_API, ['coverage' => $externalCoverageId]],
            ['Content-Type => multipart/form-data'],
            null,
            ['exceptions' => false]
        );

        $request->addPostFile('file', $location);

        return $client->send($request);
    }
}
