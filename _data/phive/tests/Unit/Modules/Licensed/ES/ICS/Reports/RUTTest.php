<?php

namespace Tests\Unit\Modules\Licensed\ES\ICS\Reports;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\RUT;
use ES\ICS\Reports\Info;
use Tests\Unit\TestPhiveBase;

class RUTTest extends TestPhiveBase
{
    /** @var \Phive|object */
    private $licensed;

    public function setUp(): void
    {
        parent::setUp();
        $this->createScenario();
    }

    /**
     * @dataProvider shouldSelectVersionProperlyDataProvider
     */
    public function testShouldSelectVersionProperly(
        string $expectedVersion,
        string $endDate
    ): void {

        $report = new RUT(
            ICSConstants::COUNTRY,
            $this->licensed->getAllLicSettings(),
            [
                'period_start' => '2000-01-01',
                'period_end' => $endDate,
                'frequency' => array_rand(array_keys(ICSConstants::FREQUENCY_VALUES)),
                'game_types' => [],
            ]
        );
        $this->assertEquals($expectedVersion, $report->getXmlVersion());
    }

    public function shouldSelectVersionProperlyDataProvider(): array
    {
        return [
            [Info::VERSIONS[2]['xmlVersion'], Info::VERSIONS[2]['endDateTime']],
            [Info::VERSIONS[3]['xmlVersion'], (new \DateTimeImmutable(Info::VERSIONS[2]['endDateTime']))
                ->modify('+1 day')->format('Y-m-d')],
        ];
    }

    public function testShouldThrowWhenNoVersionMatch(): void
    {
        $versions = Info::VERSIONS;
        $lastVersion = array_pop($versions);

        if (empty($lastVersion['endDateTime'])) {
            $this->markTestSkipped('Last version has no end date so it will be selected as fallback.');
        }

        $endDate = (new \DateTime($lastVersion['endDateTime']))
            ->modify('+1 day')
            ->format('Y-m-d');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not find a proper report version for date: '.$endDate);

        new RUT(
            ICSConstants::COUNTRY,
            $this->licensed->getAllLicSettings(),
            [
                'period_start' => '2000-01-01',
                'period_end' => $endDate,
                'frequency' => array_rand(array_keys(ICSConstants::FREQUENCY_VALUES)),
                'game_types' => [],
            ]
        );
    }

    private function createScenario(): void
    {
        $this->licensed = phive('Licensed/ES/ES');
    }
}
