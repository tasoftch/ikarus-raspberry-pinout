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

namespace Ikarus\Raspberry\Edge;


use Ikarus\Raspberry\Pin\InputPinInterface;

abstract class AbstractEdge implements EdgeInterface
{
	/** @var InputPinInterface */
	private $pin;
	/** @var float */
	private $pollSpeed;

	/**
	 * AbstractEdge constructor.
	 * @param InputPinInterface $pin
	 * @param float $pollSpeed
	 */
	public function __construct(InputPinInterface $pin, float $pollSpeed = 0.01)
	{
		$this->pin = $pin;
		$this->pollSpeed = $pollSpeed;
	}

	/**
	 * @inheritDoc
	 */
	public function wait()
	{
		$last = $this->getPin()->getValue();
		$iv = $this->getPollSpeed() * 1e6;

		declare(ticks=1) {
			usleep($iv);
		}

		while (1) {
			$n = $this->getPin()->getValue();
			if($n != $last && $this->breakEdge($last, $n))
				break;
			$last = $n;
			declare(ticks=1) {
				usleep($iv);
			}
		}
	}

	/**
	 * Decide if the edge will break the wait method.
	 *
	 * @param $old
	 * @param $new
	 * @return bool
	 */
	abstract protected function breakEdge($old, $new): bool;

	/**
	 * @return InputPinInterface
	 */
	public function getPin(): InputPinInterface
	{
		return $this->pin;
	}

	/**
	 * @return float
	 */
	public function getPollSpeed(): float
	{
		return $this->pollSpeed;
	}
}