# Goosgraba GPS

A simple wrapper for GPS coordinates.
Provides basic functionalities like distance calculation between two givens points and interpolation of the coordinates between two givens points.

## Installation

Use `composer`

  ```sh
  composer require goosfraba/gps
  ```

## Usage

### Coordinates instantiation and basic usage

```php

use Goosfraba\Gps\GpsCoordinates;
$coordinates = GpsCoordinates::createFromDegrees(38.8, -77.1);
// or
$coordinates = GpsCoordinates::createFromRadians(0.677187749773799875846392, -1.3456488532876281038081655);

// accessing coordinates in radians
$latitudeInRadians = $coordinates->latitudeInRadians();
$longitudeInRadians = $coordinates->longitudeInRadians();

// accessing coordinates in degrees
$latitudeInDegrees = $coordinates->latitudeInDegrees();
$longitudeInDegrees = $coordinates->longitudeInDegrees();

```

### Calculating distance between two givens points

```php

use Goosfraba\Gps\GpsCoordinates;

$coordinate1 = GpsCoordinates::createFromDegrees(51.5, 0);
$coordinate2 = GpsCoordinates::createFromDegrees(38.8, -77.1);

$distanceInMeters = $coordinate1->disntaceTo($coordinate2);

```


### Coordinates interpolation between two givens points at given distance

```php

use Goosfraba\Gps\GpsCoordinates;

$coordinate1 = GpsCoordinates::createFromDegrees(51.5, 0);
$coordinate2 = GpsCoordinates::createFromDegrees(38.8, -77.1);

$midPointCoordinates = $coordinate1->pointBetween($coordinate2);
$coordinatesAt10Percent = $coordinate1->pointBetween($coordinate2, $coordinate1->distanceTo($coordinate2) / 10);

```

### Tests

```bash
./vendor/bin/phpunit
```
