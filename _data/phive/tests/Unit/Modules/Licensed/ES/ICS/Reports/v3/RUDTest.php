<?php

namespace Tests\Unit\Modules\Licensed\ES\ICS\Reports\v3;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\Info;
use ES\ICS\Reports\v3\RUD;
use Tests\Unit\TestPhiveBase;

class RUDTest extends TestPhiveBase
{
    private RUD $report;
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
        $this->assertInstanceOf(RUD::class, $this->report);
    }

    private function createScenario(): void
    {
        $this->licensed = phive('Licensed/ES/ES');
    }

    private function createSut(): void
    {
        $this->report = new RUD(
            ICSConstants::COUNTRY,
            $this->licensed->getAllLicSettings(),
            [
                'period_start' => '2000-01-01',
                'period_end' => (new \DateTimeImmutable(Info::VERSIONS[2]['endDateTime']))
                    ->modify('+1 day')->format('Y-m-d'),
                'frequency' => ICSConstants::MONTHLY_FREQUENCY,
                'game_types' => [],
            ]
        );
    }
}
