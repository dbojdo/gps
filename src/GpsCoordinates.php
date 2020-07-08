<?php

namespace Goosfraba\Gps;

use Webit\Wrapper\BcMath\BcMathNumber;

final class GpsCoordinates
{
    /**
     * Earth's radius in meters
     * @var int
     */
    private const EARTH_RADIUS = 6371000;

    /**
     * PI maths constant
     * @var string
     */
    private const PI = '3.1415926535897932384626433';

    /**
     * bcmath scale used in calculations
     * @var int
     */
    private const BCMATH_SCALE = 25;

    /** @var BcMathNumber */
    private $latitude;

    /** @var BcMathNumber */
    private $longitude;

    /**
     * @param BcMathNumber $latitude
     * @param BcMathNumber $longitude
     */
    private function __construct(BcMathNumber $latitude, BcMathNumber $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Creates a new instance of GpsCoordinates from latitude and longitude given in degrees
     *
     * @param float $latitude
     * @param float $longitude
     * @return GpsCoordinates
     */
    public static function fromDegrees(float $latitude, float $longitude): GpsCoordinates
    {
        return new self(
            self::degreesToRadians(BcMathNumber::create($latitude)),
            self::degreesToRadians(BcMathNumber::create($longitude))
        );
    }

    /**
     * Creates a new instance of GpsCoordinates from latitude and longitude given in radians
     *
     * @param float $latitude
     * @param float $longitude
     * @return GpsCoordinates
     */
    public static function fromRadians(float $latitude, float $longitude): GpsCoordinates
    {
        return new self(BcMathNumber::create($latitude), BcMathNumber::create($longitude));
    }

    /**
     * Gets the latitude in radians
     *
     * @return float
     */
    public function latitudeInRadians(): float
    {
        return $this->latitude->toFloat();
    }

    /**
     * Gets the longitude in radians
     *
     * @return float
     */
    public function longitudeInRadians(): float
    {
        return $this->longitude->toFloat();
    }

    /**
     * Gets the latitude in degrees
     *
     * @return float
     */
    public function latitudeInDegrees(): float
    {
        return self::radiansToDegrees($this->latitude)->toFloat();
    }

    /**
     * Gets the longitude in degrees
     *
     * @return float
     */
    public function longitudeInDegrees(): float
    {
        return self::radiansToDegrees($this->longitude)->toFloat();
    }

    /**
     * Calculates distance between points in meters
     *
     * @param GpsCoordinates $coordinate
     * @return float
     */
    public function distanceTo(GpsCoordinates $coordinate)
    {
        $deltaLatitude = $coordinate->latitude->sub($this->latitude, self::BCMATH_SCALE);
        $deltaLongitude = $coordinate->longitude->sub($this->longitude, self::BCMATH_SCALE);

//      sin(dLat/2) * sin(dLat/2) + sin(dLon/2) * sin(dLon/2) * cos(lat1) * cos(lat2);
        $a = BcMathNumber::create(sin($deltaLatitude->div(2, self::BCMATH_SCALE)->toFloat()))->pow(2, self::BCMATH_SCALE)->add(
            BcMathNumber::create(sin($deltaLongitude->div(2, self::BCMATH_SCALE)->toFloat()))->pow(2, self::BCMATH_SCALE)->mul(cos($this->latitudeInRadians()), self::BCMATH_SCALE)->mul(cos($coordinate->latitudeInRadians()), self::BCMATH_SCALE),
            self::BCMATH_SCALE
        );

//      2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        $c = BcMathNumber::create(2)->mul(atan2($a->sqrt(self::BCMATH_SCALE)->toFloat(), BcMathNumber::create(1)->sub($a, self::BCMATH_SCALE)->sqrt(self::BCMATH_SCALE)->toFloat()), self::BCMATH_SCALE);

        return BcMathNumber::create(self::EARTH_RADIUS)->mul($c, self::BCMATH_SCALE)->toFloat();
    }

    /**
     * Interpolates the point offset be the given distance from this coordinates towards the given coordinates
     * @see http://www.edwilliams.org/avform.htm#Intermediate
     *
     * @param GpsCoordinates $coordinates
     * @param float|null $distance in meters, interpolates mid point if null
     * @return GpsCoordinates
     */
    public function pointBetween(GpsCoordinates $coordinates, ?float $distance = null): GpsCoordinates
    {
        $maxDistance = $this->distanceTo($coordinates);
        $distance = $distance === null ? $maxDistance / 2 : $distance;

        if ($distance < 0 || $distance > $maxDistance) {
            throw new \OutOfRangeException(
                sprintf('Distance must be a number between 0 and %s', $maxDistance)
            );
        }

        if ($distance == 0) {
            return $this;
        }

        if ($distance == $maxDistance) {
            return $coordinates;
        }

        $d = BcMathNumber::create($maxDistance)->div(self::EARTH_RADIUS, self::BCMATH_SCALE);
        $f = $maxDistance > 0 ? BcMathNumber::create($distance)->div($maxDistance, self::BCMATH_SCALE) : 0;

        $a = BcMathNumber::create(
            sin(
                BcMathNumber::create(1)
                    ->sub($f, self::BCMATH_SCALE)
                    ->mul($d, self::BCMATH_SCALE)->toFloat()
            )
        )->div(sin($d->toFloat()), self::BCMATH_SCALE); // A=sin((1-f)*d)/sin(d)

        $b = BcMathNumber::create(
            sin($f->mul($d->toFloat(), self::BCMATH_SCALE)->toFloat())
        )->div(sin($d->toFloat()), self::BCMATH_SCALE); // B=sin(f*d)/sin(d)

        $x = $a
            ->mul(cos($this->latitudeInRadians()), self::BCMATH_SCALE)
            ->mul(cos($this->longitudeInRadians()), self::BCMATH_SCALE)
            ->add(
                $b
                    ->mul(cos($coordinates->latitudeInRadians()), self::BCMATH_SCALE)
                    ->mul(cos($coordinates->longitudeInRadians()), self::BCMATH_SCALE),
                self::BCMATH_SCALE
            ); // A*cos(lat1)*cos(lon1) + B*cos(lat2)*cos(lon2)

        $y = $a
            ->mul(cos($this->latitude->toFloat()), self::BCMATH_SCALE)
            ->mul(sin($this->longitude->toFloat()), self::BCMATH_SCALE)
            ->add(
                $b
                    ->mul(cos($coordinates->latitudeInRadians()), self::BCMATH_SCALE)
                    ->mul(sin($coordinates->longitudeInRadians()), self::BCMATH_SCALE),
                self::BCMATH_SCALE
            ); // A*cos(lat1)*sin(lon1) +  B*cos(lat2)*sin(lon2)

        $z = $a
            ->mul(sin($this->latitudeInRadians()), self::BCMATH_SCALE)
            ->add(
                $b->mul(sin($coordinates->latitudeInRadians()), self::BCMATH_SCALE),
                self::BCMATH_SCALE
            ); // A*sin(lat1) + B*sin(lat2)

        return new GpsCoordinates(
            BcMathNumber::create(
                atan2(
                    $z->toFloat(),
                    $x
                        ->pow(2, self::BCMATH_SCALE)
                        ->add($y->pow(2, self::BCMATH_SCALE), self::BCMATH_SCALE)
                        ->sqrt(self::BCMATH_SCALE)
                        ->toFloat()
                )
            ), // atan2(z,sqrt(x^2+y^2))
            BcMathNumber::create(
                atan2($y->toFloat(), $x->toFloat())
            ) // atan2(y,x)
        );
    }

    /**
     * @param BcMathNumber $degrees
     * @return BcMathNumber
     */
    private static function degreesToRadians(BcMathNumber $degrees): BcMathNumber
    {
        return $degrees->mul(self::PI, self::BCMATH_SCALE)->div(180, self::BCMATH_SCALE);
    }

    /**
     * @param BcMathNumber $radians
     * @return BcMathNumber
     */
    private static function radiansToDegrees(BcMathNumber $radians): BcMathNumber
    {
        return $radians->mul(180,self::BCMATH_SCALE)->div(self::PI, self::BCMATH_SCALE);
    }
}
