<?php

namespace Tests\Unit\Modules\Licensed\ES\ICS\Reports\v2;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\Info;
use ES\ICS\Reports\v2\RUT;
use Tests\Unit\TestPhiveBase;

class RUTTest extends TestPhiveBase
{
    private RUT $report;
    /** @var \Phive|object */
    private $licensed;

    public function setUp(): void
    {
        parent::setUp();
        $this->createScenario();
        $this->createSut();
    }

    public function testReportWasInstantiatedOk(): void
    {
        $this->assertInstanceOf(RUT::class, $this->report);
    }

    private function createScenario(): void
    {
        $this->licensed = phive('Licensed/ES/ES');
    }

    private function createSut(): void
    {
        $this->report = new RUT(
            ICSConstants::COUNTRY,
            $this->licensed->getAllLicSettings(),
            [
                'period_start' => '2000-01-01',
                'period_end' => Info::VERSIONS['2']['endDateTime'],
                'frequency' => ICSConstants::MONTHLY_FREQUENCY,
                'game_types' => [],
            ]
        );
    }
}
