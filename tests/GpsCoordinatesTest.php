<?php

namespace Goosfraba\Gps;

use PHPUnit\Framework\TestCase;

class GpsCoordinatesTest extends TestCase
{
    /**
     * @test
     * @dataProvider validCoordinatesInDegrees
     */
    public function itCanBeCreateWithValidLatitudeAndLongitudeInDegrees(float $latitude, float $longitude)
    {
        $coordinates = GpsCoordinates::fromDegrees($latitude, $longitude);

        $this->assertInstanceOf(GpsCoordinates::class, $coordinates);
        $this->assertEquals($latitude, $coordinates->latitudeInDegrees());
        $this->assertEquals($longitude, $coordinates->longitudeInDegrees());
    }

    public function validCoordinatesInDegrees()
    {
        return [
            [90, 180],
            [90, -180],
            [-90, 180],
            [-90, -180],
            [0, 0]
        ];
    }

    /**
     * @test
     * @dataProvider validCoordinatesInRadians
     */
    public function itCanBeCreateWithValidLatitudeAndLongitudeInRadians(float $latitude, float $longitude)
    {
        $coordinates = GpsCoordinates::fromRadians($latitude, $longitude);

        $this->assertInstanceOf(GpsCoordinates::class, $coordinates);
        $this->assertEquals($latitude, $coordinates->latitudeInRadians());
        $this->assertEquals($longitude, $coordinates->longitudeInRadians());
    }

    public function validCoordinatesInRadians()
    {
        return [
            [pi()/2, pi()],
            [pi()/2, -pi()],
            [-pi()/2, pi()],
            [-pi()/2, -pi()],
            [0, 0]
        ];
    }

    /**
     * @test
     * @dataProvider invalidCoordinatesInDegrees
     */
    public function itCannotBeCreatedWithInvalidLatitudeAndLongitudeInDegrees(float $latitude, float $longitude)
    {
        $this->expectException(\OutOfRangeException::class);
        GpsCoordinates::fromDegrees($latitude, $longitude);
    }

    public function invalidCoordinatesInDegrees()
    {
        return [
            '> latitude' => [92, 120],
            '< latitude' => [-92, -120],
            '> longitude' => [80, 181],
            '< longitude' => [-23, -182]
        ];
    }

    /**
     * @test
     * @dataProvider invalidCoordinatesInRadians
     */
    public function itCannotBeCreatedWithInvalidLatitudeAndLongitudeInRadians(float $latitude, float $longitude)
    {
        $this->expectException(\OutOfRangeException::class);
        GpsCoordinates::fromRadians($latitude, $longitude);
    }

    public function invalidCoordinatesInRadians()
    {
        return [
            '> latitude' => [pi()/2*1.1, pi()*.9],
            '< latitude' => [-pi()/2*1.1, pi()*.9],
            '> longitude' => [pi()/2*.9, pi()*1.1],
            '< longitude' => [-pi()/2*.9, -pi()*1.1]
        ];
    }

    /**
     * @test
     * @dataProvider antipodesExamples
     */
    public function itCalculatesAntipodesCoordinates(GpsCoordinates $coordinates, GpsCoordinates $expectedAntipodes)
    {
        $this->assertEquals($expectedAntipodes, $coordinates->antipodes());
    }

    public function antipodesExamples()
    {
        return [
            [GpsCoordinates::fromDegrees(-10, 20), GpsCoordinates::fromDegrees(10, -160)],
            [GpsCoordinates::fromDegrees(23.44, -23.44), GpsCoordinates::fromDegrees(-23.44, 156.56)],
            [GpsCoordinates::fromDegrees(0, 0), GpsCoordinates::fromDegrees(0, 180)]
        ];
    }

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
            'max distance' => [
                $c1 = GpsCoordinates::fromDegrees(52.18025, 20.8079),
                $c2 = GpsCoordinates::fromDegrees(52.1803, 20.80862),
                $c1->distanceTo($c2),
                $c2,
            ],
            '0 distance' => [
                $c1 = GpsCoordinates::fromDegrees(52.18025, 20.8079),
                GpsCoordinates::fromDegrees(52.1803, 20.80862),
                0,
                $c1
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

    /**
     * @test
     */
    public function itThrowsExceptionOnAntipodalPointsInterpolation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $c = GpsCoordinates::fromDegrees(52.18025, 20.8079);

        $c->pointBetween($c->antipodes());
    }
}
