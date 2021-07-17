<?php


namespace Ikarus\Raspberry\Pinout;

/**
 * Trait _DynamicPinoutTrait
 * @package Ikarus\Raspberry\Pinout
 *
 * @property array $inputPins
 * @property array $outputPins
 * @property array $activeLowPins
 */
trait _DynamicPinoutTrait
{
	public function addInputPin(int $pinNumber, int $resistor = 1, bool $activeLow = false) {
		$this->inputPins[ $pinNumber ] = $resistor;
		if($activeLow)
			$this->activeLowPins[] = $pinNumber;
		return $this;
	}

	public function addOutputPin(int $pin, bool $activeLow = false) {
		$this->outputPins[$pin] = false;
		if($activeLow)
			$this->activeLowPins[] = $pin;
		return $this;
	}

	public function addOutputPWMPin(int $pin) {
		$this->outputPins[$pin] = true;
		return $this;
	}
}