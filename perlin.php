<?php

class Perlin {
	
	var $p, $permutation, $seed;
	var $defaultSize = 64;
	
	function __construct($seed = NULL) {
		$this->seed = ($seed === NULL) ? time() : $seed;
		
		$this->permutation = $this->fy_shuffle(range(0,255));
		$this->p = array_merge($this->permutation, $this->permutation);
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
	
	function noise($x,$y,$z,$size = NULL) {
		
		if ($size == NULL) $size = $this->defaultSize;
		
		//Set the initial value and initial size
		$value = 0.0;
		$initialSize = $size;
		
		//Add finer and finer hues of smoothed noise together
		while($size >= 1) {
	
			$value += $this->smoothNoise($x / $size, $y / $size, $z / $size) * $size;
			$size /= 2.0;

		}
		
		return (($value / $initialSize) + 1) / 2;
	
	}
	
	//This function determines what cube the point passed resides in
	//and determines its value.
	function smoothNoise($x, $y, $z) {
		
		$x += $this->seed;
		$y += $this->seed;
		$z += $this->seed;
		
		$orig_x = $x;
		$orig_y = $y;
		$orig_z = $z;
		
		$xi = (int)floor($x) & 255;
		$yi = (int)floor($y) & 255;
		$zi = (int)floor($z) & 255;
		$x -= floor($x);
		$y -= floor($y);
		$z -= floor($z);
		$u = $this->fade($x);
		$v = $this->fade($y);
		$w = $this->fade($z);
		
		$a  = $this->p[$xi]+$yi;
		$aa = $this->p[$a]+$zi;
		$ab = $this->p[$a+1]+$zi;
		$b  = $this->p[$xi+1]+$yi;
		$ba = $this->p[$b]+$zi;
		$bb = $this->p[$b+1]+$zi;
		
		$result = $this->lerp($w, $this->lerp($v, $this->lerp($u, $this->grad($this->p[$aa  ], $x  , $y  , $z   ),
																  $this->grad($this->p[$ba  ], $x-1, $y  , $z   )),
												  $this->lerp($u, $this->grad($this->p[$ab  ], $x  , $y-1, $z   ),
																  $this->grad($this->p[$bb  ], $x-1, $y-1, $z   ))),
								  $this->lerp($v, $this->lerp($u, $this->grad($this->p[$aa+1], $x  , $y  , $z-1 ),
																  $this->grad($this->p[$ba+1], $x-1, $y  , $z-1 )),
												  $this->lerp($u, $this->grad($this->p[$ab+1], $x  , $y-1, $z-1 ),
																  $this->grad($this->p[$bb+1], $x-1, $y-1, $z-1 ))));
		
		return $result;
	}

	function fade($t) { 
	    return $t * $t * $t * ($t * ($t * 6 - 15) + 10);
	}
	
	function lerp($t, $a, $b) { 
		return $a + $t * ($b - $a); 
	}

	function grad($hash, $x, $y, $z) {
	    $h = $hash & 0xF;

	    switch ($h) {
	        case 0x0: return $x + $y;
	        case 0x1: return 0 - $x + $y;
	        case 0x2: return $x - $y;
	        case 0x3: return 0 - $x - $y;
	        case 0x4: return $x + $z;
	        case 0x5: return 0 - $x + $z;
	        case 0x6: return $x - $z;
	        case 0x7: return 0 - $x - $z;
	        case 0x8: return $y + $z;
	        case 0x9: return 0 - $y + $z;
	        case 0xA: return $y - $z;
	        case 0xB: return 0 - $y - $z;
	        case 0xC: return $y + $x;
	        case 0xD: return 0 - $y + $z;
	        case 0xE: return $y - $x;
	        case 0xF: return 0 - $y - $z;
	        default: return 0;
	    }
	}
}

?>