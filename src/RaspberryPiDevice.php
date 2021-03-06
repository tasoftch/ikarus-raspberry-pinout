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
	private $cleanUpFuncs=[];

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
	 * Performs a measure of cpu current usage. This method blocks the current process during measure!
	 * Result is returned in percent
	 *
	 * @param float|int $measureTime
	 * @return float
	 */
	public function getCpuUsage(float $measureTime = 1): float {
		if(preg_match("/^cpu\s+([0-9\s]+)$/im", file_get_contents("/proc/stat"), $ms)) {
			list($usr,/* Not used */, $sys, $idle) = preg_split("/\s+/", $ms[1]);
			$usage = [$usr+$sys, $idle];
		}

		declare(ticks=1) {
			usleep($measureTime * 1e6);
		}

		if(preg_match("/^cpu\s+([0-9\s]+)$/im", file_get_contents("/proc/stat"), $ms)) {
			list($usr,/* Not used */, $sys, $idle) = preg_split("/\s+/", $ms[1]);
			list($ou, $oi) = $usage;

			$du = $usr+$sys-$ou;
			$di = $idle-$oi;

			return $du / ($du+$di) * 100;
		}
		return 0;
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

		$resistor = 0;
		$activeLow = false;

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
		array_walk($this->cleanUpFuncs, function($cb) { $cb(); });
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

	/**
	 * @inheritDoc
	 */
	public function loop(float $interval, callable $callback) {
		if(function_exists("pcntl_signal")) {
			$handler = function() {
				$this->cleanup();
				echo PHP_EOL;
				exit();
			};

			pcntl_signal(SIGTERM, $handler);
			pcntl_signal(SIGINT, $handler);
		}
		while (1) {
			$int = $interval;
			$res = $callback();
			if($res === false)
				break;
			if(is_numeric($res))
				$int = $res;

			declare(ticks=1) {
				usleep($int * 1e6);
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function watchEdge($timeout, EdgeInterface ...$edges): ?EdgeInterface {
		$seconds = floor($timeout);
		$micro = ($timeout - $seconds) * 1e6;
		$read = $write = $streams = $pins = $states = $debounces = [];

		if(function_exists("pcntl_signal")) {
			$handler = function() use (&$streams) {
				array_walk($streams, function($v) { @fclose($v); });
				$this->cleanup();
				echo PHP_EOL;
				exit();
			};

			pcntl_signal(SIGTERM, $handler);
			pcntl_signal(SIGINT, $handler);
		}

		foreach($edges as $edge) {
			$p = $edge->getInputPin()->getPinNumber();
			$pins[$p] = $edge;
			
			if($s = @fopen("/sys/class/gpio/gpio$p/value", 'r')) {
				file_put_contents("/sys/class/gpio/gpio$p/edge", 'both');
				stream_set_blocking($s, false);
				$states[$p] = fread($s, 1) * 1;
				@rewind($s);
				$streams[$p] = $s;
				$debounces[$p] = 0;
			}
		}

		repeatWatch:

		$_STREAMS = $streams;
		declare(ticks=1) {
			$result = @stream_select($read, $write, $_STREAMS, $seconds, $micro);
		}

		if (!$result) {
			return NULL;
		}
		$result = NULL;
		foreach ($_STREAMS as $pin => $stream) {
			$value = fread($stream, 1);
			@rewind($stream);

			if ($value !== false && $value != $states[$pin]) {
				if(microtime(true)>$debounces[$pin] + $pins[$pin]->getDebounce()/1000) {
					$debounces[$pin] = microtime(true);

					if($value>0) {
						// Rising edge
						if($pins[$pin]->getWatchedEdge() & EdgeInterface::EDGE_RISING) {
							$result = $pins[$pin];
							$result->setValue(EdgeInterface::VALUE_DID_RISE);
							break;
						}
					} else {
						// Falling edge
						if($pins[$pin]->getWatchedEdge() & EdgeInterface::EDGE_FALLING) {
							$result = $pins[$pin];
							$result->setValue(EdgeInterface::VALUE_DID_FALL);
							break;
						}
					}
				}
			}
			$states[$pin] = $value;
		}

		if(!$result)
			goto repeatWatch;

		array_walk($streams, function($v) { @fclose($v); });
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function registerCleanupFunction(callable $function)
	{
		$this->cleanUpFuncs[] = $function;
	}


	public function __destruct()
	{
		$this->cleanup();
	}
}