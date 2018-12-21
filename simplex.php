<?php

class Simplex {

	const STRETCH = -1 / 6;
	const SQUISH = 1 / 3;
	const NORM = 103;
	const GRADIENTS = array(
		-11,  4,  4,     -4,  11,  4,    -4,  4,  11,
		 11,  4,  4,      4,  11,  4,     4,  4,  11,
		-11, -4,  4,     -4, -11,  4,    -4, -4,  11,
		 11, -4,  4,      4, -11,  4,     4, -4,  11,
		-11,  4, -4,     -4,  11, -4,    -4,  4, -11,
		 11,  4, -4,      4,  11, -4,     4,  4, -11,
		-11, -4, -4,     -4, -11, -4,    -4, -4, -11,
		 11, -4, -4,      4, -11, -4,     4, -4, -11
	);

	const RANGE_BOUND = 0.86602540378443864676372317075294; // sqrt(3) / 2

	var $seed;
	var $p;
	var $pGradIndex;

	function __construct($seed = NULL) {
		$this->seed = ($seed === NULL) ? time() : $seed;

		$this->p = $this->fy_shuffle(range(0,255));
		$this->generateGradIndex();
	}

	function fy_shuffle($items) {
	    srand($this->seed);
	    for ($i = count($items) - 1; $i > 0; $i--) {
	        $j = @rand(0, $i);
	        $tmp = $items[$i];
	        $items[$i] = $items[$j];
	        $items[$j] = $tmp;
	    }

	    return $items;
	}

	function generateGradIndex() {
		$this->pGradIndex = array();

		for ($i = 0; $i < count($this->p); $i++) {
			$this->pGradIndex[$i] = $this->p[$i] % (count(self::GRADIENTS) / 3) * 3;
		}
	}

	function extrapolate($xsb, $ysb, $zsb, $dx, $dy, $dz) {
		$index = $this->pGradIndex[($this->p[($this->p[$xsb & 0xFF] + $ysb) & 0xFF] + $zsb) & 0xFF];
		return self::GRADIENTS[$index] * $dx + self::GRADIENTS[$index + 1] * $dy + self::GRADIENTS[$index + 2] * $dz;
	}

	function noise($x, $y, $z, $scale = 1, $overtones = 1, $exponent = 1) {
		if ($overtones < 1) $overtones = 1;
		$value = 0;

		while ($overtones > 0) {
			$overtones--;
			$frequency = 2 ** $overtones;
			$amplitude = 1 / $frequency;
			$value += $amplitude * $this->eval($x, $y, $z, $scale / $frequency);
		}

		return min(1, max(0, ($value / self::RANGE_BOUND + 1) / 2)) ** $exponent;
	}

	function eval($x, $y, $z, $scale = 1) {
		$x = $x / $scale;
		$y = $y / $scale;
		$z = $z / $scale;

		$stretchOffset = ($x + $y + $z) * self::STRETCH;

		$xs = $x + $stretchOffset;
		$ys = $y + $stretchOffset;
		$zs = $z + $stretchOffset;

		$xsb = (int)floor($xs);
		$ysb = (int)floor($ys);
		$zsb = (int)floor($zs);

		$squishOffset = ($xsb + $ysb + $zsb) * self::SQUISH;

		$xb = $xsb + $squishOffset;
		$yb = $ysb + $squishOffset;
		$zb = $zsb + $squishOffset;

		$xins = $xs - $xsb;
		$yins = $ys - $ysb;
		$zins = $zs - $zsb;

		$inSum = $xins + $yins + $zins;

		$dx0 = $x - $xb;
		$dy0 = $y - $yb;
		$dz0 = $z - $zb;

		$dx_ext0 = $dy_ext0 = $dz_ext0 = 0;
		$dx_ext1 = $dy_ext1 = $dz_ext1 = 0;
		$xsv_ext0 = $ysv_ext0 = $zsv_ext0 = 0;
		$xsv_ext1 = $ysv_ext1 = $zsv_ext1 = 0;

		$value = 0;
		if ($inSum <= 1) {
			$aPoint = 0x01;
			$aScore = $xins;
			$bPoint = 0x02;
			$bScore = $yins;

			if ($aScore >= $bScore && $zins > $bScore) {
				$bScore = $zins;
				$bPoint = 0x04;
			} else if ($aScore < $bScore && $zins > $aScore) {
				$aScore = $zins;
				$aPoint = 0x04;
			}

			$wins = 1 - $inSum;
			if ($wins > $aScore || $wins > $bScore) {
				$c = ($bScore > $aScore) ? $bPoint : $aPoint;

				if (($c & 0x01) == 0) {
					$xsv_ext0 = $xsb - 1;
					$xsv_ext1 = $xsb;
					$dx_ext0 = $dx0 + 1;
					$dx_ext1 = $dx0;
				} else {
					$xsv_ext0 = $xsv_ext1 = $xsb + 1;
					$dx_ext0 = $dx_ext1 = $dx0 - 1;
				}

				if (($c & 0x02) == 0) {
					$ysv_ext0 = $ysv_ext1 = $ysb;
					$y_ext0 = $dy_ext1 = $dy0;
					if (($c & 0x01) == 0) {
						$ysv_ext1 -= 1;
						$dy_ext1 += 1;
					} else {
						$ysv_ext0 -= 1;
						$dy_ext0 += 1;
					}
				} else {
					$ysv_ext0 = $ysv_ext1 = $ysb + 1;
					$dy_ext0 = $dy_ext1 = $dy0 - 1;
				}

				if (($c & 0x04) == 0) {
					$zsv_ext0 = $zsb;
					$zsv_ext1 = $zsb - 1;
					$dz_ext0 = $dz0;
					$dz_ext1 = $dz0 + 1;
				} else {
					$zsv_ext0 = $zsv_ext1 = $zsb + 1;
					$dz_ext0 = $dz_ext1 = $dz0 - 1;
				}
			} else {
				$c = $aPoint | $bPoint;
				
				if (($c & 0x01) == 0) {
					$xsv_ext0 = $xsb;
					$xsv_ext1 = $xsb - 1;
					$dx_ext0 = $dx0 - 2 * self::SQUISH;
					$dx_ext1 = $dx0 + 1 - self::SQUISH;
				} else {
					$xsv_ext0 = $xsv_ext1 = $xsb + 1;
					$dx_ext0 = $dx0 - 1 - 2 * self::SQUISH;
					$dx_ext1 = $dx0 - 1 - self::SQUISH;
				}

				if (($c & 0x02) == 0) {
					$ysv_ext0 = $ysb;
					$ysv_ext1 = $ysb - 1;
					$dy_ext0 = $dy0 - 2 * self::SQUISH;
					$dy_ext1 = $dy0 + 1 - self::SQUISH;
				} else {
					$ysv_ext0 = $ysv_ext1 = $ysb + 1;
					$dy_ext0 = $dy0 - 1 - 2 * self::SQUISH;
					$dy_ext1 = $dy0 - 1 - self::SQUISH;
				}

				if (($c & 0x04) == 0) {
					$zsv_ext0 = $zsb;
					$zsv_ext1 = $zsb - 1;
					$dz_ext0 = $dz0 - 2 * self::SQUISH;
					$dz_ext1 = $dz0 + 1 - self::SQUISH;
				} else {
					$zsv_ext0 = $zsv_ext1 = $zsb + 1;
					$dz_ext0 = $dz0 - 1 - 2 * self::SQUISH;
					$dz_ext1 = $dz0 - 1 - self::SQUISH;
				}
			}

			$attn0 = 2 - $dx0 * $dx0 - $dy0 * $dy0 - $dz0 * $dz0;
			if ($attn0 > 0) {
				$attn0 *= $attn0;
				$value += $attn0 * $attn0 * $this->extrapolate($xsb + 0, $ysb + 0, $zsb + 0, $dx0, $dy0, $dz0);
			}

			$dx1 = $dx0 - 1 - self::SQUISH;
			$dy1 = $dy0 - 0 - self::SQUISH;
			$dz1 = $dz0 - 0 - self::SQUISH;
			$attn1 = 2 - $dx1 * $dx1 - $dy1 * $dy1 - $dz1 * $dz1;
			if ($attn1 > 0) {
				$attn1 *= $attn1;
				$value += $attn1 * $attn1 * $this->extrapolate($xsb + 1, $ysb + 0, $zsb + 0, $dx1, $dy1, $dz1);
			}

			$dx2 = $dx0 - 0 - self::SQUISH;
			$dy2 = $dy0 - 1 - self::SQUISH;
			$dz2 = $dz1;
			$attn2 = 2 - $dx2 * $dx2 - $dy2 * $dy2 - $dz2 * $dz2;
			if ($attn2 > 0) {
				$attn2 *= $attn2;
				$value += $attn2 * $attn2 * $this->extrapolate($xsb + 0, $ysb + 1, $zsb + 0, $dx2, $dy2, $dz2);
			}

			$dx3 = $dx2;
			$dy3 = $dy1;
			$dz3 = $dz0 - 1 - self::SQUISH;
			$attn3 = 2 - $dx3 * $dx3 - $dy3 * $dy3 - $dz3 * $dz3;
			if ($attn3 > 0) {
				$attn3 *= $attn3;
				$value += $attn3 * $attn3 * $this->extrapolate($xsb + 0, $ysb + 0, $zsb + 1, $dx3, $dy3, $dz3);
			}
		} else if ($inSum >= 2) {
			$aPoint = 0x06;
			$aScore = $xins;
			$bPoint = 0x05;
			$bScore = $yins;

			if ($aScore <= $bScore && $zins < $bScore) {
				$bScore = $zins;
				$bPoint = 0x03;
			} else if ($aScore > $bScore && $zins < $aScore) {
				$aScore = $zins;
				$aPoint = 0x03;
			}
			
			$wins = 3 - $inSum;
			if ($wins < $aScore || $wins < $bScore) {
				$c = ($bScore < $aScore) ? $bPoint : $aPoint; 
				
				if (($c & 0x01) != 0) {
					$xsv_ext0 = $xsb + 2;
					$xsv_ext1 = $xsb + 1;
					$dx_ext0 = $dx0 - 2 - 3 * self::SQUISH;
					$dx_ext1 = $dx0 - 1 - 3 * self::SQUISH;
				} else {
					$xsv_ext0 = $xsv_ext1 = $xsb;
					$dx_ext0 = $dx_ext1 = $dx0 - 3 * self::SQUISH;
				}

				if (($c & 0x02) != 0) {
					$ysv_ext0 = $ysv_ext1 = $ysb + 1;
					$dy_ext0 = $dy_ext1 = $dy0 - 1 - 3 * self::SQUISH;
					if (($c & 0x01) != 0) {
						$ysv_ext1 += 1;
						$dy_ext1 -= 1;
					} else {
						$ysv_ext0 += 1;
						$dy_ext0 -= 1;
					}
				} else {
					$ysv_ext0 = $ysv_ext1 = $ysb;
					$dy_ext0 = $dy_ext1 = $dy0 - 3 * self::SQUISH;
				}

				if (($c & 0x04) != 0) {
					$zsv_ext0 = $zsb + 1;
					$zsv_ext1 = $zsb + 2;
					$dz_ext0 = $dz0 - 1 - 3 * self::SQUISH;
					$dz_ext1 = $dz0 - 2 - 3 * self::SQUISH;
				} else {
					$zsv_ext0 = $zsv_ext1 = $zsb;
					$dz_ext0 = $dz_ext1 = $dz0 - 3 * self::SQUISH;
				}
			} else {
				$c = $aPoint & $bPoint;
				
				if (($c & 0x01) != 0) {
					$xsv_ext0 = $xsb + 1;
					$xsv_ext1 = $xsb + 2;
					$dx_ext0 = $dx0 - 1 - self::SQUISH;
					$dx_ext1 = $dx0 - 2 - 2 * self::SQUISH;
				} else {
					$xsv_ext0 = $xsv_ext1 = $xsb;
					$dx_ext0 = $dx0 - self::SQUISH;
					$dx_ext1 = $dx0 - 2 * self::SQUISH;
				}

				if (($c & 0x02) != 0) {
					$ysv_ext0 = $ysb + 1;
					$ysv_ext1 = $ysb + 2;
					$dy_ext0 = $dy0 - 1 - self::SQUISH;
					$dy_ext1 = $dy0 - 2 - 2 * self::SQUISH;
				} else {
					$ysv_ext0 = $ysv_ext1 = $ysb;
					$dy_ext0 = $dy0 - self::SQUISH;
					$dy_ext1 = $dy0 - 2 * self::SQUISH;
				}

				if (($c & 0x04) != 0) {
					$zsv_ext0 = $zsb + 1;
					$zsv_ext1 = $zsb + 2;
					$dz_ext0 = $dz0 - 1 - self::SQUISH;
					$dz_ext1 = $dz0 - 2 - 2 * self::SQUISH;
				} else {
					$zsv_ext0 = $zsv_ext1 = $zsb;
					$dz_ext0 = $dz0 - self::SQUISH;
					$dz_ext1 = $dz0 - 2 * self::SQUISH;
				}
			}
			
			$dx3 = $dx0 - 1 - 2 * self::SQUISH;
			$dy3 = $dy0 - 1 - 2 * self::SQUISH;
			$dz3 = $dz0 - 0 - 2 * self::SQUISH;
			$attn3 = 2 - $dx3 * $dx3 - $dy3 * $dy3 - $dz3 * $dz3;
			if ($attn3 > 0) {
				$attn3 *= $attn3;
				$value += $attn3 * $attn3 * $this->extrapolate($xsb + 1, $ysb + 1, $zsb + 0, $dx3, $dy3, $dz3);
			}

			$dx2 = $dx3;
			$dy2 = $dy0 - 0 - 2 * self::SQUISH;
			$dz2 = $dz0 - 1 - 2 * self::SQUISH;
			$attn2 = 2 - $dx2 * $dx2 - $dy2 * $dy2 - $dz2 * $dz2;
			if ($attn2 > 0) {
				$attn2 *= $attn2;
				$value += $attn2 * $attn2 * $this->extrapolate($xsb + 1, $ysb + 0, $zsb + 1, $dx2, $dy2, $dz2);
			}

			$dx1 = $dx0 - 0 - 2 * self::SQUISH;
			$dy1 = $dy3;
			$dz1 = $dz2;
			$attn1 = 2 - $dx1 * $dx1 - $dy1 * $dy1 - $dz1 * $dz1;
			if ($attn1 > 0) {
				$attn1 *= $attn1;
				$value += $attn1 * $attn1 * $this->extrapolate($xsb + 0, $ysb + 1, $zsb + 1, $dx1, $dy1, $dz1);
			}

			$dx0 = $dx0 - 1 - 3 * self::SQUISH;
			$dy0 = $dy0 - 1 - 3 * self::SQUISH;
			$dz0 = $dz0 - 1 - 3 * self::SQUISH;
			$attn0 = 2 - $dx0 * $dx0 - $dy0 * $dy0 - $dz0 * $dz0;
			if ($attn0 > 0) {
				$attn0 *= $attn0;
				$value += $attn0 * $attn0 * $this->extrapolate($xsb + 1, $ysb + 1, $zsb + 1, $dx0, $dy0, $dz0);
			}
		} else { 
			$aScore;
			$aPoint;
			$aIsFurtherSide;
			$bScore;
			$bPoint;
			$bIsFurtherSide;

			$p1 = $xins + $yins;
			if ($p1 > 1) {
				$aScore = $p1 - 1;
				$aPoint = 0x03;
				$aIsFurtherSide = true;
			} else {
				$aScore = 1 - $p1;
				$aPoint = 0x04;
				$aIsFurtherSide = false;
			}

			$p2 = $xins + $zins;
			if ($p2 > 1) {
				$bScore = $p2 - 1;
				$bPoint = 0x05;
				$bIsFurtherSide = true;
			} else {
				$bScore = 1 - $p2;
				$bPoint = 0x02;
				$bIsFurtherSide = false;
			}
			
			$p3 = $yins + $zins;
			if ($p3 > 1) {
				$score = $p3 - 1;
				if ($aScore <= $bScore && $aScore < $score) {
					$aScore = $score;
					$aPoint = 0x06;
					$aIsFurtherSide = true;
				} else if ($aScore > $bScore && $bScore < $score) {
					$bScore = $score;
					$bPoint = 0x06;
					$bIsFurtherSide = true;
				}
			} else {
				$score = 1 - $p3;
				if ($aScore <= $bScore && $aScore < $score) {
					$aScore = $score;
					$aPoint = 0x01;
					$aIsFurtherSide = false;
				} else if ($aScore > $bScore && $bScore < $score) {
					$bScore = $score;
					$bPoint = 0x01;
					$bIsFurtherSide = false;
				}
			}
			
			if ($aIsFurtherSide == $bIsFurtherSide) {
				if ($aIsFurtherSide) { 

					$dx_ext0 = $dx0 - 1 - 3 * self::SQUISH;
					$dy_ext0 = $dy0 - 1 - 3 * self::SQUISH;
					$dz_ext0 = $dz0 - 1 - 3 * self::SQUISH;
					$xsv_ext0 = $xsb + 1;
					$ysv_ext0 = $ysb + 1;
					$zsv_ext0 = $zsb + 1;

					$c = $aPoint & $bPoint;
					if (($c & 0x01) != 0) {
						$dx_ext1 = $dx0 - 2 - 2 * self::SQUISH;
						$dy_ext1 = $dy0 - 2 * self::SQUISH;
						$dz_ext1 = $dz0 - 2 * self::SQUISH;
						$xsv_ext1 = $xsb + 2;
						$ysv_ext1 = $ysb;
						$zsv_ext1 = $zsb;
					} else if (($c & 0x02) != 0) {
						$dx_ext1 = $dx0 - 2 * self::SQUISH;
						$dy_ext1 = $dy0 - 2 - 2 * self::SQUISH;
						$dz_ext1 = $dz0 - 2 * self::SQUISH;
						$xsv_ext1 = $xsb;
						$ysv_ext1 = $ysb + 2;
						$zsv_ext1 = $zsb;
					} else {
						$dx_ext1 = $dx0 - 2 * self::SQUISH;
						$dy_ext1 = $dy0 - 2 * self::SQUISH;
						$dz_ext1 = $dz0 - 2 - 2 * self::SQUISH;
						$xsv_ext1 = $xsb;
						$ysv_ext1 = $ysb;
						$zsv_ext1 = $zsb + 2;
					}
				} else {
					$dx_ext0 = $dx0;
					$dy_ext0 = $dy0;
					$dz_ext0 = $dz0;
					$xsv_ext0 = $xsb;
					$ysv_ext0 = $ysb;
					$zsv_ext0 = $zsb;

					$c = $aPoint | $bPoint;
					if (($c & 0x01) == 0) {
						$dx_ext1 = $dx0 + 1 - self::SQUISH;
						$dy_ext1 = $dy0 - 1 - self::SQUISH;
						$dz_ext1 = $dz0 - 1 - self::SQUISH;
						$xsv_ext1 = $xsb - 1;
						$ysv_ext1 = $ysb + 1;
						$zsv_ext1 = $zsb + 1;
					} else if (($c & 0x02) == 0) {
						$dx_ext1 = $dx0 - 1 - self::SQUISH;
						$dy_ext1 = $dy0 + 1 - self::SQUISH;
						$dz_ext1 = $dz0 - 1 - self::SQUISH;
						$xsv_ext1 = $xsb + 1;
						$ysv_ext1 = $ysb - 1;
						$zsv_ext1 = $zsb + 1;
					} else {
						$dx_ext1 = $dx0 - 1 - self::SQUISH;
						$dy_ext1 = $dy0 - 1 - self::SQUISH;
						$dz_ext1 = $dz0 + 1 - self::SQUISH;
						$xsv_ext1 = $xsb + 1;
						$ysv_ext1 = $ysb + 1;
						$zsv_ext1 = $zsb - 1;
					}
				}
			} else {
				$c1 = $c2 = 0;
				if ($aIsFurtherSide) {
					$c1 = $aPoint;
					$c2 = $bPoint;
				} else {
					$c1 = $bPoint;
					$c2 = $aPoint;
				}

				if (($c1 & 0x01) == 0) {
					$dx_ext0 = $dx0 + 1 - self::SQUISH;
					$dy_ext0 = $dy0 - 1 - self::SQUISH;
					$dz_ext0 = $dz0 - 1 - self::SQUISH;
					$xsv_ext0 = $xsb - 1;
					$ysv_ext0 = $ysb + 1;
					$zsv_ext0 = $zsb + 1;
				} else if (($c1 & 0x02) == 0) {
					$dx_ext0 = $dx0 - 1 - self::SQUISH;
					$dy_ext0 = $dy0 + 1 - self::SQUISH;
					$dz_ext0 = $dz0 - 1 - self::SQUISH;
					$xsv_ext0 = $xsb + 1;
					$ysv_ext0 = $ysb - 1;
					$zsv_ext0 = $zsb + 1;
				} else {
					$dx_ext0 = $dx0 - 1 - self::SQUISH;
					$dy_ext0 = $dy0 - 1 - self::SQUISH;
					$dz_ext0 = $dz0 + 1 - self::SQUISH;
					$xsv_ext0 = $xsb + 1;
					$ysv_ext0 = $ysb + 1;
					$zsv_ext0 = $zsb - 1;
				}

				$dx_ext1 = $dx0 - 2 * self::SQUISH;
				$dy_ext1 = $dy0 - 2 * self::SQUISH;
				$dz_ext1 = $dz0 - 2 * self::SQUISH;
				$xsv_ext1 = $xsb;
				$ysv_ext1 = $ysb;
				$zsv_ext1 = $zsb;
				if (($c2 & 0x01) != 0) {
					$dx_ext1 -= 2;
					$xsv_ext1 += 2;
				} else if (($c2 & 0x02) != 0) {
					$dy_ext1 -= 2;
					$ysv_ext1 += 2;
				} else {
					$dz_ext1 -= 2;
					$zsv_ext1 += 2;
				}
			}

			$dx1 = $dx0 - 1 - self::SQUISH;
			$dy1 = $dy0 - 0 - self::SQUISH;
			$dz1 = $dz0 - 0 - self::SQUISH;
			$attn1 = 2 - $dx1 * $dx1 - $dy1 * $dy1 - $dz1 * $dz1;
			if ($attn1 > 0) {
				$attn1 *= $attn1;
				$value += $attn1 * $attn1 * $this->extrapolate($xsb + 1, $ysb + 0, $zsb + 0, $dx1, $dy1, $dz1);
			}

			$dx2 = $dx0 - 0 - self::SQUISH;
			$dy2 = $dy0 - 1 - self::SQUISH;
			$dz2 = $dz1;
			$attn2 = 2 - $dx2 * $dx2 - $dy2 * $dy2 - $dz2 * $dz2;
			if ($attn2 > 0) {
				$attn2 *= $attn2;
				$value += $attn2 * $attn2 * $this->extrapolate($xsb + 0, $ysb + 1, $zsb + 0, $dx2, $dy2, $dz2);
			}

			$dx3 = $dx2;
			$dy3 = $dy1;
			$dz3 = $dz0 - 1 - self::SQUISH;
			$attn3 = 2 - $dx3 * $dx3 - $dy3 * $dy3 - $dz3 * $dz3;
			if ($attn3 > 0) {
				$attn3 *= $attn3;
				$value += $attn3 * $attn3 * $this->extrapolate($xsb + 0, $ysb + 0, $zsb + 1, $dx3, $dy3, $dz3);
			}

			$dx4 = $dx0 - 1 - 2 * self::SQUISH;
			$dy4 = $dy0 - 1 - 2 * self::SQUISH;
			$dz4 = $dz0 - 0 - 2 * self::SQUISH;
			$attn4 = 2 - $dx4 * $dx4 - $dy4 * $dy4 - $dz4 * $dz4;
			if ($attn4 > 0) {
				$attn4 *= $attn4;
				$value += $attn4 * $attn4 * $this->extrapolate($xsb + 1, $ysb + 1, $zsb + 0, $dx4, $dy4, $dz4);
			}

			$dx5 = $dx4;
			$dy5 = $dy0 - 0 - 2 * self::SQUISH;
			$dz5 = $dz0 - 1 - 2 * self::SQUISH;
			$attn5 = 2 - $dx5 * $dx5 - $dy5 * $dy5 - $dz5 * $dz5;
			if ($attn5 > 0) {
				$attn5 *= $attn5;
				$value += $attn5 * $attn5 * $this->extrapolate($xsb + 1, $ysb + 0, $zsb + 1, $dx5, $dy5, $dz5);
			}

			$dx6 = $dx0 - 0 - 2 * self::SQUISH;
			$dy6 = $dy4;
			$dz6 = $dz5;
			$attn6 = 2 - $dx6 * $dx6 - $dy6 * $dy6 - $dz6 * $dz6;
			if ($attn6 > 0) {
				$attn6 *= $attn6;
				$value += $attn6 * $attn6 * $this->extrapolate($xsb + 0, $ysb + 1, $zsb + 1, $dx6, $dy6, $dz6);
			}
		}

		$attn_ext0 = 2 - $dx_ext0 * $dx_ext0 - $dy_ext0 * $dy_ext0 - $dz_ext0 * $dz_ext0;
		if ($attn_ext0 > 0)
		{
			$attn_ext0 *= $attn_ext0;
			$value += $attn_ext0 * $attn_ext0 * $this->extrapolate($xsv_ext0, $ysv_ext0, $zsv_ext0, $dx_ext0, $dy_ext0, $dz_ext0);
		}

		$attn_ext1 = 2 - $dx_ext1 * $dx_ext1 - $dy_ext1 * $dy_ext1 - $dz_ext1 * $dz_ext1;
		if ($attn_ext1 > 0)
		{
			$attn_ext1 *= $attn_ext1;
			$value += $attn_ext1 * $attn_ext1 * $this->extrapolate($xsv_ext1, $ysv_ext1, $zsv_ext1, $dx_ext1, $dy_ext1, $dz_ext1);
		}
		
		return $value / self::NORM;
	}

}

?>