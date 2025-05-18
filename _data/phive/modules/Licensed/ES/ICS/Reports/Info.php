<?php

declare(strict_types=1);

namespace ES\ICS\Reports;

use Exception;
use Carbon\Carbon;

class Info
{
    public const VERSIONS = [
        2 => [
            'endDateTime' => '2025-03-21 23:59:59',
            'xmlVersion' => '2.15'
        ],
        3 => [
            'xmlVersion' => '3.3'
        ]
    ];

    /** @throws Exception */
    public static function getVersion(string $endDate): int
    {
        foreach (self::VERSIONS as $version => $versionInfo)
        {
            if (empty($versionInfo['endDateTime']) || $endDate <= $versionInfo['endDateTime']) {
                return $version;
            }
        }

        throw new Exception('Could not find a proper report version for date: '.$endDate);
    }

    /** @throws Exception */
    public static function getXmlVersion(string $endDate): string
    {
        foreach (self::VERSIONS as $version => $versionInfo)
        {
            if (empty($versionInfo['endDateTime']) || $endDate <= $versionInfo['endDateTime']) {
                return $versionInfo['xmlVersion'];
            }
        }

        throw new Exception('Could not find a proper report version for date: '.$endDate);
    }

    public static function getDailyReportClasses(string $date): array
    {
        $version = self::getVersion($date);
        if ($version === 2) {
            // v2
            return [RUD::class, RUT::class, CJD::class, CJT::class];
        } else {
            // v3
            return [RUD::class, CJD::class, CJT::class];
        }
    }

    public static function getMonthlyReportClasses(): array
    {
        return [RUD::class, RUT::class, CJD::class, CJT::class, OPT::class];
    }

    public static function getRealTimeReportClasses(string $date): array
    {
        $version = self::getVersion($date);
        if ($version === 2) {
            // v2
            return [JUD::class, JUT::class];
        } else {
            // v3
            return [JUC::class];
        }
    }

    public static function getUsersSessionsDates(Carbon $date, string $country): array
    {
        $version = self::getVersion($date->format('Y-m-d'));

        if ($version === 2) {
            $className = v2\JUT::class;
        } else {
            $className = v3\JUC::class;
        }

        return $className::getUsersSessionsDates($date, $country);
    }
}
