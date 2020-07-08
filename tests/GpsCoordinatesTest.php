<?php

namespace Goosfraba\Gps;

use PHPUnit\Framework\TestCase;

class GpsCoordinatesTest extends TestCase
{
    /**
     * @test
     * @dataProvider distanceCoordinates
     */
    public function itCalculatesDistance(GpsCoordinates $coordinates1, GpsCoordinates $coordinates2, float $expectedDistance)
    {
        $this->assertEquals($expectedDistance, $coordinates1->distanceTo($coordinates2));
    }

    public function distanceCoordinates()
    {
        return [
            'London -> Arlington' => [
                GpsCoordinates::fromDegrees(51.5, 0),
                GpsCoordinates::fromDegrees(38.8, -77.1),
                5918185.064088776
            ],
            'points close together' => [
                GpsCoordinates::fromDegrees(52.18025, 20.8079),
                GpsCoordinates::fromDegrees(52.1803, 20.80862),
                49.405153139987
            ]
        ];
    }

    /**
     * @test
     * @dataProvider pointBetweenExamples
     */
    public function itInterpolatesPointBetween(GpsCoordinates $coordinates1, GpsCoordinates $coordinates2, ?float $distance, GpsCoordinates $expectedCoordinates)
    {
        $this->assertEquals($expectedCoordinates, $coordinates1->pointBetween($coordinates2, $distance));
    }

    public function pointBetweenExamples()
    {
        return [
            'mid point' => [
                GpsCoordinates::fromDegrees(52.18025, 20.8079),
                GpsCoordinates::fromDegrees(52.1803, 20.80862),
                null,
                GpsCoordinates::fromRadians(0.91071760335565, 0.3631726486075),
            ],
            '10% distance' => [
                $c1 = GpsCoordinates::fromDegrees(52.18025, 20.8079),
                $c2 = GpsCoordinates::fromDegrees(52.1803, 20.80862),
                $c1->distanceTo($c2) / 10,
                GpsCoordinates::fromRadians(0.91071725428367, 0.36316762206151),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider pointBetweenInvalidExamples
     */
    public function itThrowsExceptionOnInvalidDistanceForInterpolation(GpsCoordinates $coordinates1, GpsCoordinates $coordinates2, float $distance)
    {
        $this->expectException(\OutOfRangeException::class);
        $coordinates1->pointBetween($coordinates2, $distance);
    }

    public function pointBetweenInvalidExamples()
    {
        return [
            'distance greater than c2' => [
                $c1 = GpsCoordinates::fromDegrees(52.18025, 20.8079),
                $c2 = GpsCoordinates::fromDegrees(52.1803, 20.80862),
                65
            ],
            'negative distance' => [
                GpsCoordinates::fromDegrees(52.18025, 20.8079),
                GpsCoordinates::fromDegrees(52.1803, 20.80862),
                -60
            ],
        ];
    }
}
