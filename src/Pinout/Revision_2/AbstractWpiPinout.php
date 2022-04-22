<?php
/**
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

namespace Ikarus\Raspberry\Pinout\Revision_2;


use Ikarus\Raspberry\Pinout\AbstractMappedPinout;

abstract class AbstractWpiPinout extends AbstractMappedPinout
{
	const WPI_0 = 0;
	const WPI_1 = 1;
	const WPI_2 = 2;
	const WPI_3 = 3;
	const WPI_4 = 4;
	const WPI_5 = 5;
	const WPI_6 = 6;
	const WPI_7 = 7;
	const WPI_8 = 8;
	const WPI_9 = 9;
	const WPI_10 = 10;
	const WPI_11 = 11;
	const WPI_12 = 12;
	const WPI_13 = 13;
	const WPI_14 = 14;
	const WPI_15 = 15;
	const WPI_16 = 16;

	protected $pinMap = [
        0 => 17,
        1 => 18,
        2 => 27,
        3 => 22,
        4 => 23,
        5 => 24,
        6 => 25,
        7 => 4,
        8 => 2,
        9 => 3,
        10 => 8,
        11 => 7,
        12 => 10,
        13 => 9,
        14 => 11,
        15 => 14,
        16 => 15
    ];
}