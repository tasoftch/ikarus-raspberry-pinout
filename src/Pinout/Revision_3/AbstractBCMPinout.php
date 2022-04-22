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

namespace Ikarus\Raspberry\Pinout\Revision_3;


use Ikarus\Raspberry\Pinout\AbstractMappedPinout;

abstract class AbstractBCMPinout extends AbstractMappedPinout
{
	const BCM_0 = 0;
	const BCM_1 = 1;
	const BCM_2 = 2;
	const BCM_3 = 3;
	const BCM_4 = 4;
	const BCM_5 = 5;
	const BCM_6 = 6;
	const BCM_7 = 7;
	const BCM_8 = 8;
	const BCM_9 = 9;
	const BCM_10 = 10;
	const BCM_11 = 11;
	const BCM_12 = 12;
	const BCM_13 = 13;
	const BCM_14 = 14;
	const BCM_15 = 15;
	const BCM_16 = 16;
	const BCM_17 = 17;
	const BCM_18 = 18;
	const BCM_19 = 19;
	const BCM_20 = 20;
	const BCM_21 = 21;
	const BCM_22 = 22;
	const BCM_23 = 23;
	const BCM_24 = 24;
	const BCM_25 = 25;
	const BCM_26 = 26;
	const BCM_27 = 27;

	protected $pinMap = [
        2 => 2,
        3 => 3,
        4 => 4,
        14 => 14,
        15 => 15,
        17 => 17,
        18 => 18,
        27 => 27,
        22 => 22,
        23 => 23,
        24 => 24,
        10 => 10,
        9 => 9,
        25 => 25,
        11 => 11,
        8 => 8,
        7 => 7,
        0 => 0,
        1 => 1,
        5 => 5,
        6 => 6,
        12 => 12,
        13 => 13,
        19 => 19,
        16 => 16,
        26 => 26,
        20 => 20,
        21 => 21
    ];
}