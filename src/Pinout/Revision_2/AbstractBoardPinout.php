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

abstract class AbstractBoardPinout extends AbstractMappedPinout
{
	const BRD_3 = 3;
	const BRD_5 = 5;
	const BRD_7 = 7;
	const BRD_8 = 8;
	const BRD_10 = 10;
	const BRD_11 = 11;
	const BRD_12 = 12;
	const BRD_13 = 13;
	const BRD_15 = 15;
	const BRD_16 = 16;
	const BRD_18 = 18;
	const BRD_19 = 19;
	const BRD_21 = 21;
	const BRD_22 = 22;
	const BRD_23 = 23;
	const BRD_24 = 24;
	const BRD_26 = 26;

    protected $pinMap = [
        3 => 2,
        5 => 3,
        7 => 4,
        8 => 14,
        10 => 15,
        11 => 17,
        12 => 18,
        13 => 27,
        15 => 22,
        16 => 23,
        18 => 24,
        19 => 10,
        21 => 9,
        22 => 25,
        23 => 11,
        24 => 8,
        26 => 7
    ];
}