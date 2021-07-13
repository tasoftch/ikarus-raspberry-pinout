<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Ikarus\Raspberry;


use Ikarus\Raspberry\Edge\EdgeInterface;
use Ikarus\Raspberry\Exception\OccupiedPinException;
use Ikarus\Raspberry\Exception\RaspberryPiException;
use Ikarus\Raspberry\Pin\InputPinInterface;
use Ikarus\Raspberry\Pin\OutputPinInterface;
use Ikarus\Raspberry\Pin\PinInterface;
use Ikarus\Raspberry\Pinout\PinoutInterface;

interface RaspberryPiDeviceInterface
{
	/** @var int The physical pin numbers on board */
	const GPIO_NS_BOARD      = 0;

	/** @var int The Broadcom SOC bord numbers */
	const GPIO_NS_BCM        = 1;

	/** @var int The wired board numbers */
	const GPIO_NS_WIRED      = 2;


	/** @var int Pin is available as GPIO */
	const MODE_GPIO         = 1<<0;

	/** @var int Pin is ground 0v */
	const MODE_GROUND       = 1<<1;

	/** @var int Power pin 3.3v */
	const MODE_33V          = 1<<2;

	/** @var int Power pin 5v */
	const MODE_5V           = 1<<3;

	/** @var int SPI Pin */
	const MODE_SPI          = 1<<4;

	/** @var int I2C Pin */
	const MODE_I2C          = 1<<5;

	/** @var int UART Pin */
	const MODE_UART         = 1<<6;

	/**
	 * Gets a singleton instance of the raspberry pi device.
	 * Please only create a board instance using this method!
	 *
	 * @return RaspberryPiDeviceInterface
	 * @throws RaspberryPiException
	 */
	public static function getDevice(): RaspberryPiDeviceInterface;

	/**
	 * The Device model as board value
	 *
	 * @return string
	 */
	public function getModel(): string ;

	/**
	 * The device model as string value
	 *
	 * @return string
	 */
	public function getModelName(): string;

	/**
	 * The hardware
	 *
	 * @return string
	 */
	public function getHardware(): string;

	/**
	 * The serial number
	 *
	 * @return string
	 */
	public function getSerial(): string;

	/**
	 * Reads the CPU temperature of the pi
	 *
	 * @return float
	 */
	public function getCpuTemperature(): float;
	/**
	 * Reads the current frequency of the CPU
	 *
	 * @return int
	 */
	public function getCpuFrequency(): int;

	/**
	 * Performs a measure of cpu current usage. This method blocks the current process during measure!
	 * Result is returned in percent
	 *
	 * @param float|int $measureTime
	 * @return float
	 */
	public function getCpuUsage(float $measureTime = 1): float;

	/**
	 * Converts any pin number from a given number system into another
	 *
	 * @param int $pinNumber
	 * @param int|NULL $from
	 * @param int $to
	 * @return int
	 *
	 * @see RaspberryPiBoardInterface::GPIO_NS_* constants
	 */
	public function convertPinNumber(int $pinNumber, int $from = self::GPIO_NS_BCM, int $to = self::GPIO_NS_BOARD): int;

	/**
	 * Reads from revision what modes (functions) a given pin has
	 *
	 * @param int $pinNumber
	 * @param int|NULL $ns      Number system or default
	 * @return int
	 *
	 * @see RaspberryPiDeviceInterface::MODE_* constants
	 */
	public function getModesForPin(int $pinNumber, int $ns = self::GPIO_NS_BCM): int;

	/**
	 * Gets the device name of the passed pin
	 *
	 * @param int $pin
	 * @param int $ns
	 * @return string|null
	 */
	public function getNameOfPin(int $pin, int $ns = self::GPIO_NS_BCM): ?string;


	/**
	 * Tries to get access to all desired pins declared by the pinout instance.
	 *
	 * @param PinoutInterface $pinout
	 * @return PinoutInterface[]
	 * @throws OccupiedPinException
	 */
	public function requirePinout(PinoutInterface $pinout): array;

	/**
	 * Releases access of pins declared by the pinout instance
	 *
	 * @param PinoutInterface $pinout
	 */
	public function releasePinout(PinoutInterface $pinout);

	/**
	 * Releases access of all pins used in this process.
	 */
	public function cleanup();

	/**
	 * Gets the registered pin if available.
	 *
	 * @param int $bcmPin
	 * @return PinInterface|null
	 */
	public function getPin(int $bcmPin): ?PinInterface;

	/**
	 * Gets the input pin if available
	 *
	 * @param int $bcmPin
	 * @return InputPinInterface|null
	 */
	public function getInputPin(int $bcmPin): ?InputPinInterface;

	/**
	 * Gets the output pin if available
	 *
	 * @param int $bcmPin
	 * @return InputPinInterface|null
	 */
	public function getOutputPin(int $bcmPin): ?OutputPinInterface;

	/**
	 * Maintains a safe endless loop with interval.
	 * The callback may return a numeric value to change the next interval or false to exit the loop..
	 *
	 * @param float $interval
	 * @param callable $callback
	 */
	public function loop(float $interval, callable $callback);

	/**
	 * This method blocks the current thread until one of the passed pins change its value.
	 * The edge flag defines, if the watching ends on raising (from 0 to 1) or falling (from 1 to 0) or one of them.
	 *
	 * @param $timeout
	 * @param EdgeInterface ...$edges
	 * @return EdgeInterface|null
	 */
	public function watchEdge($timeout, EdgeInterface ...$edges): ?EdgeInterface;

	/**
	 * This function gets called always on terminating the process from a watchEdge or loop call.
	 * @param callable $function
	 */
	public function registerCleanupFunction(callable $function);
}