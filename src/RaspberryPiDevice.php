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


use Ikarus\Raspberry\Exception\OccupiedPinException;
use Ikarus\Raspberry\Exception\RaspberryPiException;
use Ikarus\Raspberry\Pin\InputPin;
use Ikarus\Raspberry\Pin\InputPinInterface;
use Ikarus\Raspberry\Pin\OutputPin;
use Ikarus\Raspberry\Pin\OutputPinInterface;
use Ikarus\Raspberry\Pin\PinInterface;
use Ikarus\Raspberry\Pin\PullDownInputPin;
use Ikarus\Raspberry\Pin\PullUpInputPin;
use Ikarus\Raspberry\Pin\PulseWithModulationPin;
use Ikarus\Raspberry\Pin\PulseWithModulationPinInterface;
use Ikarus\Raspberry\Pinout\PinoutInterface;

class RaspberryPiDevice implements RaspberryPiDeviceInterface
{
	const GPIO_PREFIX = '/sys/class/gpio';
	const GPIO_EXPORT = self::GPIO_PREFIX . "/export";
	const GPIO_UNEXPORT = self::GPIO_PREFIX . "/unexport";
	const GPIO_EXPORTED_PIN = self::GPIO_PREFIX . "/gpio%d";



	private $model;
	private $modelName;
	private $pinout;
	private $hardware;
	private $serial;

	protected static $device;

	protected $usedPins = [];

	private function __construct() {
		$info = file_get_contents('/proc/cpuinfo');
		if(preg_match_all("/^revision\s*:\s*(\S+)\s*$/im", $info, $ms)) {
			$this->model = $ms[1][0];
			$revisions = require __DIR__ . "/../lib/revisions.php";
			$this->modelName = $revisions["revisions"][$this->model] ?? 'Unknown';

			if(is_callable( $po = $revisions["pinout"] ?? NULL)) {
				if(is_file($file = __DIR__ . "/../lib/pinout-" . $po($this->model) . ".php")) {
					$this->pinout = require $file;
				}
			}
			if(!$this->pinout)
				throw new RaspberryPiException("Unknow Raspberry Pi device, can not detect pinout");
		}
		if(preg_match_all("/^hardware\s*:\s*(\S+)\s*$/im", $info, $ms)) {
			$this->hardware = $ms[1][0];
		}
		if(preg_match_all("/^serial\s*:\s*(\S+)\s*$/im", $info, $ms)) {
			$this->serial = $ms[1][0];
		}
	}


	/**
	 * @inheritDoc
	 */
	public static function getDevice(): RaspberryPiDeviceInterface
	{
		if(!static::$device)
			static::$device = new static();
		return static::$device;
	}

	/**
	 * @inheritDoc
	 */
	public function getModel(): string {
		return $this->model;
	}
	/**
	 * @inheritDoc
	 */
	public function getModelName(): string {
		return $this->modelName;
	}
	/**
	 * @inheritDoc
	 */
	public function getHardware(): string {
		return $this->hardware;
	}
	/**
	 * @inheritDoc
	 */
	public function getSerial(): string {
		return $this->serial;
	}

	/**
	 * @inheritDoc
	 */
	public function getCpuTemperature(): float
	{
		return floatval(file_get_contents('/sys/class/thermal/thermal_zone0/temp'))/1000;
	}

	/**
	 * @inheritDoc
	 */
	public function getCpuFrequency(): int
	{
		return floatval(file_get_contents('/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq'))/1000;
	}

	/**
	 * @inheritDoc
	 */
	public function convertPinNumber(int $pinNumber, int $from = self::GPIO_NS_BCM, int $to = self::GPIO_NS_BOARD): int
	{
		switch ($from) {
			case self::GPIO_NS_BCM:
				$src = $this->pinout['bcm2brd'];
				break;
			case self::GPIO_NS_WIRED:
				$src = $this->pinout['wpi2brd'];
				break;
			default:
		}

		switch ($to) {
			case self::GPIO_NS_BCM:
				$dst = $this->pinout['bcm2brd'];
				break;
			case self::GPIO_NS_WIRED:
				$dst = $this->pinout['wpi2brd'];
				break;
			default:
		}

		if(isset($src))
			$pinNumber = $src[$pinNumber] ?? -1;

		if(isset($dst)) {
			if(($idx = array_search($pinNumber, $dst)) !== false)
				return $idx;
			return -1;
		}

		if(isset($this->pinout["name"][$pinNumber]))
			return $pinNumber;
		else
			return -1;
	}

	/**
	 * @inheritDoc
	 */
	public function getModesForPin(int $pinNumber, int $ns = self::GPIO_NS_BCM): int
	{
		$pin = $this->convertPinNumber($pinNumber, $ns);
		$modes = 0;
		foreach($this->pinout["funcs"] as $mode => $pins) {
			if(in_array($pin, $pins))
				$modes|=$mode;
		}
		return $modes;
	}

	/**
	 * @inheritDoc
	 */
	public function getNameOfPin(int $pin, int $ns = self::GPIO_NS_BCM): ?string
	{
		$pin = $this->convertPinNumber($pin, $ns);
		return $this->pinout["name"][$pin] ?? '??';
	}

	private function exec($cmd) {
		static $ok = NULL;
		if($ok === NULL) {
			$ok = exec("gpio readall") ? true : false;
		}

		if(!$ok)
			throw new RaspberryPiException("Can not apply feature because wiringpi is not installed on this device. Install it with $ sudo apt-get install wiringpi");

		exec("gpio -g $cmd");
	}

	/**
	 * @inheritDoc
	 */
	public function requirePinout(PinoutInterface $pinout): array
	{
		$pins = [];
		$usePin = function(PinInterface $pin) use (&$pins) {
			$pins[ $pin->getPinNumber() ] = $pin;
			$this->usedPins[ $pin->getPinNumber() ] = $pin;
		};

		foreach($pinout->yieldInputPin($resistor, $activeLow) as $pin) {
			if(isset($this->usedPins[$pin]))
				throw (new OccupiedPinException("Pin $pin is already in use", 401))->setPinNumber($pin);

			if(!file_exists( sprintf(static::GPIO_EXPORTED_PIN, $pin) )) {
				file_put_contents( static::GPIO_EXPORT, $pin );
			}
			file_put_contents(sprintf(static::GPIO_EXPORTED_PIN . "/direction", $pin), 'in');

			if($resistor == $pinout::INPUT_RESISTOR_UP) {
				$usePin(new PullUpInputPin($pin, $activeLow));
				$this->exec("mode $pin up");
			}
			elseif($resistor == $pinout::INPUT_RESISTOR_DOWN) {
				$usePin(new PullDownInputPin($pin, $activeLow));
				$this->exec("mode $pin down");
			} else
				$usePin(new InputPin($pin, $activeLow));
			$resistor = 0;
			$activeLow = false;
		}

		$pwm = false;
		foreach($pinout->yieldOutputPin($pwm, $activeLow) as $pin) {
			if(isset($this->usedPins[$pin]))
				throw (new OccupiedPinException("Pin $pin is already in use", 401))->setPinNumber($pin);

			if($pwm) {
				$usePin(new PulseWithModulationPin($pin, $activeLow));
				$this->exec("mode $pin pwm");
				$pwm = false;
			} else {
				if(!file_exists( sprintf(self::GPIO_EXPORTED_PIN, $pin) )) {
					file_put_contents( self::GPIO_EXPORT, $pin );
				}
				file_put_contents(sprintf(self::GPIO_EXPORTED_PIN . "/direction", $pin), 'out');
				$usePin(new OutputPin($pin, $activeLow));
			}
		}
		return $pins;
	}

	/**
	 * @param PinInterface $pin
	 */
	private function releasePin(PinInterface $pin) {
		$pinNr = $pin->getPinNumber();

		if ($pin instanceof OutputPinInterface) {
			if($pin instanceof PulseWithModulationPinInterface) {
				$pin->setValue(0.0);
				$this->exec("mode $pinNr in");
			} else {
				$pin->setValue(0);
				$this->exec("write $pinNr 0");
				$this->exec("mode $pinNr in");
				$this->exec("mode $pinNr tri");
			}
		} elseif($pin instanceof InputPinInterface) {
			if($pin instanceof PullUpInputPin || $pin instanceof PullDownInputPin)
				$this->exec("mode $pinNr tri");
		}

		if(file_exists( sprintf(self::GPIO_EXPORTED_PIN, $pin->getPinNumber()) )) {
			file_put_contents( self::GPIO_UNEXPORT, $pin->getPinNumber() );
		}
		unset($this->usedPins[$pinNr]);
	}

	/**
	 * @inheritDoc
	 */
	public function releasePinout(PinoutInterface $pinout)
	{
		foreach($pinout->yieldInputPin($r, $a) as $pin) {
			if($p = $this->usedPins[$pin] ?? NULL)
				$this->releasePin($p);
		}
		foreach($pinout->yieldOutputPin($p, $a) as $pin) {
			if($p = $this->usedPins[$pin] ?? NULL)
				$this->releasePin($p);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function cleanup()
	{
		foreach(array_values($this->usedPins) as $pin)
			$this->releasePin($pin);
	}

	/**
	 * @inheritDoc
	 */
	public function getPin(int $bcmPin): ?PinInterface
	{
		return $this->usedPins[$bcmPin] ?? NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function getInputPin(int $bcmPin): ?InputPinInterface
	{
		return ($p = $this->getPin($bcmPin)) instanceof InputPinInterface ? $p : NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function getOutputPin(int $bcmPin): ?OutputPinInterface
	{
		return ($p = $this->getPin($bcmPin)) instanceof OutputPinInterface ? $p : NULL;
	}
}