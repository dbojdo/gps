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

    /** Min / Max latitude and longitude values in degrees */
    private const MIN_LATITUDE_IN_DEGREES = -90;
    private const MAX_LATITUDE_IN_DEGREES = 90;
    private const MIN_LONGITUDE_IN_DEGREES = -180;
    private const MAX_LONGITUDE_IN_DEGREES = 180;

    /** Min / Max latitude and longitude values in radians */
    private const MIN_LATITUDE_IN_RADIANS = -self::PI/2;
    private const MAX_LATITUDE_IN_RADIANS = self::PI/2;
    private const MIN_LONGITUDE_IN_RADIANS = -self::PI;
    private const MAX_LONGITUDE_IN_RADIANS = self::PI;

    /**
     * bcmath scale used in calculations
     * @var int
     */
    private const BCMATH_CALCULATION_SCALE = 25;

    /**
     * bcmath scale used in comparision
     * @var int
     */
    private const BCMATH_COMPARISION_SCALE = 12;

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
        self::validateInDegrees($latitude, $longitude);

        $this->latitude = $latitude->mul(1, self::BCMATH_CALCULATION_SCALE);
        $this->longitude = $longitude->mul(1, self::BCMATH_CALCULATION_SCALE);
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
        return new self(BcMathNumber::create($latitude), BcMathNumber::create($longitude));
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
        self::validateInRadians(
            $latitude = BcMathNumber::create($latitude),
            $longitude = BcMathNumber::create($longitude)
        );

        return new self(
            self::radiansToDegrees(BcMathNumber::create($latitude))->mul(1, self::BCMATH_COMPARISION_SCALE),
            self::radiansToDegrees(BcMathNumber::create($longitude))->mul(1, self::BCMATH_COMPARISION_SCALE)
        );
    }

    /**
     * Validates given latitude and longitude in radians
     *
     * @param BcMathNumber $latitude
     * @param BcMathNumber $longitude
     * @throws \OutOfRangeException
     */
    private static function validateInRadians(BcMathNumber $latitude, BcMathNumber $longitude)
    {
        if ($latitude->isGreaterThan(self::MAX_LATITUDE_IN_RADIANS, self::BCMATH_COMPARISION_SCALE) || $latitude->isLessThan(self::MIN_LATITUDE_IN_RADIANS, self::BCMATH_COMPARISION_SCALE)) {
            throw new \OutOfRangeException(
                sprintf(
                    'Latitude must be between %f and %f radians.',
                    self::MIN_LATITUDE_IN_RADIANS,
                    self::MAX_LATITUDE_IN_RADIANS
                )
            );
        }

        if ($longitude->isGreaterThan(self::MAX_LONGITUDE_IN_RADIANS, self::BCMATH_COMPARISION_SCALE) || $longitude->isLessThan(self::MIN_LONGITUDE_IN_RADIANS, self::BCMATH_COMPARISION_SCALE)) {
            throw new \OutOfRangeException(
                sprintf(
                    'Longitude must be between %f and %f radians.',
                    self::MIN_LONGITUDE_IN_RADIANS,
                    self::MAX_LONGITUDE_IN_RADIANS
                )
            );
        }
    }

    private static function validateInDegrees(BcMathNumber $latitude, BcMathNumber $longitude)
    {
        if ($latitude->isGreaterThan(self::MAX_LATITUDE_IN_DEGREES, self::BCMATH_COMPARISION_SCALE) || $latitude->isLessThan(self::MIN_LATITUDE_IN_DEGREES, self::BCMATH_COMPARISION_SCALE)) {
            throw new \OutOfRangeException(
                sprintf(
                    'Latitude must be between %d and %d degrees.',
                    self::MIN_LATITUDE_IN_DEGREES,
                    self::MAX_LATITUDE_IN_DEGREES
                )
            );
        }

        if ($longitude->isGreaterThan(self::MAX_LONGITUDE_IN_DEGREES, self::BCMATH_COMPARISION_SCALE) || $longitude->isLessThan(self::MIN_LONGITUDE_IN_DEGREES, self::BCMATH_COMPARISION_SCALE)) {
            throw new \OutOfRangeException(
                sprintf(
                    'Longitude must be between %d and %d degrees.',
                    self::MIN_LONGITUDE_IN_DEGREES,
                    self::MAX_LONGITUDE_IN_DEGREES
                )
            );
        }
    }

    /**
     * Gets the latitude in radians
     *
     * @return float
     */
    public function latitudeInRadians(): float
    {
        return self::degreesToRadians($this->latitude)->toFloat();
    }

    /**
     * Gets the longitude in radians
     *
     * @return float
     */
    public function longitudeInRadians(): float
    {
        return self::degreesToRadians($this->longitude)->toFloat();
    }

    /**
     * Gets the latitude in degrees
     *
     * @return float
     */
    public function latitudeInDegrees(): float
    {
        return $this->latitude->toFloat();
    }

    /**
     * Gets the longitude in degrees
     *
     * @return float
     */
    public function longitudeInDegrees(): float
    {
        return $this->longitude->toFloat();
    }

    /**
     * Gets the antipodes coordinates
     *
     * @return GpsCoordinates
     */
    public function antipodes(): GpsCoordinates
    {
        return new self(
            $this->latitude->mul(-1, self::BCMATH_CALCULATION_SCALE),
            $this->longitude->add(
                $this->longitude->isLessOrEquals(0) ? self::MAX_LONGITUDE_IN_DEGREES : self::MIN_LONGITUDE_IN_DEGREES,
                self::BCMATH_CALCULATION_SCALE
            )
        );
    }

    /**
     * Calculates distance between points in meters
     * @see http://www.edwilliams.org/avform.htm#Dist
     *
     * @param GpsCoordinates $coordinate
     * @return float
     */
    public function distanceTo(GpsCoordinates $coordinate)
    {
        $deltaLatitude = self::degreesToRadians($coordinate->latitude->sub($this->latitude, self::BCMATH_CALCULATION_SCALE));
        $deltaLongitude = self::degreesToRadians($coordinate->longitude->sub($this->longitude, self::BCMATH_CALCULATION_SCALE));

//      sin(dLat/2) * sin(dLat/2) + sin(dLon/2) * sin(dLon/2) * cos(lat1) * cos(lat2);
        $a = BcMathNumber::create(sin($deltaLatitude->div(2, self::BCMATH_CALCULATION_SCALE)->toFloat()))->pow(2, self::BCMATH_CALCULATION_SCALE)->add(
            BcMathNumber::create(sin($deltaLongitude->div(2, self::BCMATH_CALCULATION_SCALE)->toFloat()))->pow(2, self::BCMATH_CALCULATION_SCALE)->mul(cos($this->latitudeInRadians()), self::BCMATH_CALCULATION_SCALE)->mul(cos($coordinate->latitudeInRadians()), self::BCMATH_CALCULATION_SCALE),
            self::BCMATH_CALCULATION_SCALE
        );

//      2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        $c = BcMathNumber::create(2)->mul(atan2($a->sqrt(self::BCMATH_CALCULATION_SCALE)->toFloat(), BcMathNumber::create(1)->sub($a, self::BCMATH_CALCULATION_SCALE)->sqrt(self::BCMATH_CALCULATION_SCALE)->toFloat()), self::BCMATH_CALCULATION_SCALE);

        return BcMathNumber::create(self::EARTH_RADIUS)->mul($c, self::BCMATH_CALCULATION_SCALE)->toFloat();
    }

    /**
     * Interpolates the point offset be the given distance from this coordinates towards the given one
     * @see http://www.edwilliams.org/avform.htm#Intermediate
     *
     * @param GpsCoordinates $coordinates
     * @param float|null $distance in meters from first (this) coordinates, interpolates mid point if null
     * @return GpsCoordinates
     */
    public function pointBetween(GpsCoordinates $coordinates, ?float $distance = null): GpsCoordinates
    {
        if ($coordinates == $this->antipodes()) {
            throw new \InvalidArgumentException('Cannot calculate distance between antipodal points.');
        }

        if ($distance === 0.) {
            return $this;
        }

        $maxDistance = $this->distanceTo($coordinates);
        $distance = $distance === null ? $maxDistance / 2 : $distance;

        if ($distance === $maxDistance) {
            return $coordinates;
        }

        if ($distance < 0 || $distance > $maxDistance) {
            throw new \OutOfRangeException(
                sprintf('Distance must be a number between 0 and %s', $maxDistance)
            );
        }

        $d = BcMathNumber::create($maxDistance)->div(self::EARTH_RADIUS, self::BCMATH_CALCULATION_SCALE);
        $f = $maxDistance > 0 ? BcMathNumber::create($distance)->div($maxDistance, self::BCMATH_CALCULATION_SCALE) : 0;

        $a = BcMathNumber::create(
            sin(
                BcMathNumber::create(1)
                    ->sub($f, self::BCMATH_CALCULATION_SCALE)
                    ->mul($d, self::BCMATH_CALCULATION_SCALE)->toFloat()
            )
        )->div(sin($d->toFloat()), self::BCMATH_CALCULATION_SCALE); // A=sin((1-f)*d)/sin(d)

        $b = BcMathNumber::create(
            sin($f->mul($d->toFloat(), self::BCMATH_CALCULATION_SCALE)->toFloat())
        )->div(sin($d->toFloat()), self::BCMATH_CALCULATION_SCALE); // B=sin(f*d)/sin(d)

        $x = $a
            ->mul(cos($this->latitudeInRadians()), self::BCMATH_CALCULATION_SCALE)
            ->mul(cos($this->longitudeInRadians()), self::BCMATH_CALCULATION_SCALE)
            ->add(
                $b
                    ->mul(cos($coordinates->latitudeInRadians()), self::BCMATH_CALCULATION_SCALE)
                    ->mul(cos($coordinates->longitudeInRadians()), self::BCMATH_CALCULATION_SCALE),
                self::BCMATH_CALCULATION_SCALE
            ); // A*cos(lat1)*cos(lon1) + B*cos(lat2)*cos(lon2)

        $y = $a
            ->mul(cos($this->latitudeInRadians()), self::BCMATH_CALCULATION_SCALE)
            ->mul(sin($this->longitudeInRadians()), self::BCMATH_CALCULATION_SCALE)
            ->add(
                $b
                    ->mul(cos($coordinates->latitudeInRadians()), self::BCMATH_CALCULATION_SCALE)
                    ->mul(sin($coordinates->longitudeInRadians()), self::BCMATH_CALCULATION_SCALE),
                self::BCMATH_CALCULATION_SCALE
            ); // A*cos(lat1)*sin(lon1) +  B*cos(lat2)*sin(lon2)

        $z = $a
            ->mul(sin($this->latitudeInRadians()), self::BCMATH_CALCULATION_SCALE)
            ->add(
                $b->mul(sin($coordinates->latitudeInRadians()), self::BCMATH_CALCULATION_SCALE),
                self::BCMATH_CALCULATION_SCALE
            ); // A*sin(lat1) + B*sin(lat2)

        return self::fromRadians(
            atan2(
                $z->toFloat(),
                $x
                    ->pow(2, self::BCMATH_CALCULATION_SCALE)
                    ->add($y->pow(2, self::BCMATH_CALCULATION_SCALE), self::BCMATH_CALCULATION_SCALE)
                    ->sqrt(self::BCMATH_CALCULATION_SCALE)
                    ->toFloat()
            ), // atan2(z,sqrt(x^2+y^2))
            atan2($y->toFloat(), $x->toFloat()) // atan2(y,x)
        );
    }

    /**
     * @param BcMathNumber $degrees
     * @return BcMathNumber
     */
    private static function degreesToRadians(BcMathNumber $degrees): BcMathNumber
    {
        return $degrees->mul(self::PI, self::BCMATH_CALCULATION_SCALE)->div(180, self::BCMATH_CALCULATION_SCALE);
    }

    /**
     * @param BcMathNumber $radians
     * @return BcMathNumber
     */
    private static function radiansToDegrees(BcMathNumber $radians): BcMathNumber
    {
        return $radians->mul(180,self::BCMATH_CALCULATION_SCALE)->div(self::PI, self::BCMATH_CALCULATION_SCALE);
    }
}
