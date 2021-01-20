# Ikarus Raspberry Pi Pinout

This package ships with all information about the pinout of Raspberry Pi Devices.  
It helps you to assign and prepare pins and also clean them up.

### Installation
```bin
$ composer require ikarus/raspberry-pinout
```

### Usage
There is a singleton class named RaspberryPiDevice which holds all information about the pinout and its device.
```php
<?php
use Ikarus\Raspberry\RaspberryPiDevice;

$dev = RaspberryPiDevice::getDevice();
echo $dev->getModelName();
```

Once the device is created you can define custom pinouts:
````php
<?php
use Ikarus\Raspberry\Pinout\Revision_1\AbstractBoardPinout;
use Ikarus\Raspberry\Pinout\Revision_2\AbstractBCMPinout;
use Ikarus\Raspberry\Pinout\Revision_3\AbstractWpiPinout;
// Take the abstract pinout class you need (device specific like rev 1-3) and you want to declare the pinout (bcm, wpi or board).

class MyPinout extends AbstractWpiPinout {
    const MOTOR_A = 0;
    const MOTOR_B = 1;
    const CONTACT_A = 2;
    const CONTACT_B = 3;
    
    const ASPIRATION = 4;
    
    protected $inputPins = [
        self::CONTACT_A => self::INPUT_RESISTOR_UP,
        self::CONTACT_B => self::INPUT_RESISTOR_DOWN
    ];
    
    protected $outputPins = [
        self::MOTOR_A => false,
        self::MOTOR_B => false,
        self::ASPIRATION => true    // Use PWM
    ];
    
    protected $activeLowPins = [
        self::CONTACT_A     // Inverts its value => if the pin is high, the value is 0.
    ];
}
````
Now you are able to work with the pi:
```php
<?php
require "vendor/autoload.php";
use Ikarus\Raspberry\RaspberryPiDevice;
$dev = RaspberryPiDevice::getDevice();

$dev->requirePinout( new MyPinout() );

// Drive forward
$dev->getOutputPin( MyPinout::MOTOR_A )->setValue(1);
while ( $dev->getInputPin( MyPinout::CONTACT_A )->getValue() )
    usleep(10000);

// Motor stop
$dev->getOutputPin( MyPinout::MOTOR_A )->setValue(0);
sleep(2);

// Drive backwards
$dev->getOutputPin( MyPinout::MOTOR_B )->setValue(1);
while ( $dev->getInputPin( MyPinout::CONTACT_B )->getValue() == 0 )
    usleep(10000);

// Motor stop
$dev->getOutputPin( MyPinout::MOTOR_B )->setValue(0);

// Releases all pins and brings them into a secure state (mode = input, value = 0 and resistor = none).
$dev->cleanup();
```
Or even saver:

```php
<?php

try {
    ...
} catch (WhatEver $e) {
    ...
} finally {
    $dev->cleanup();
}

```