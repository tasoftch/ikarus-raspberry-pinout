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

class Edge implements EdgeInterface
{
	/** @var InputPinInterface */
	private $inputPin;
	/** @var int */
	private $watchedEdge;
	/** @var int */
	private $debounce;
	/** @var int  */
	private $value = self::VALUE_NONE;

	/**
	 * Edge constructor.
	 * @param InputPinInterface $inputPin
	 * @param int $watchedEdge
	 * @param int $debounce
	 */
	public function __construct(InputPinInterface $inputPin, int $watchedEdge = self::EDGE_BOTH, int $debounce = 0)
	{
		$this->inputPin = $inputPin;
		$this->watchedEdge = $watchedEdge;
		$this->debounce = $debounce;
	}

	/**
	 * @return int
	 */
	public function getWatchedEdge(): int
	{
		return $this->watchedEdge;
	}

	/**
	 * @return InputPinInterface
	 */
	public function getInputPin(): InputPinInterface
	{
		return $this->inputPin;
	}

	/**
	 * @return int
	 */
	public function getDebounce(): int
	{
		return $this->debounce;
	}

	/**
	 * @return int
	 */
	public function getValue(): int
	{
		return $this->value;
	}

	/**
	 * @param int $value
	 */
	public function setValue(int $value)
	{
		$this->value = $value;
	}
}