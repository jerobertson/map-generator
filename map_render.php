<?php

class MapRender {

	var $db;
	var $token;
	var $userId;
	var $size;
	var $midPoint;

	var $mapId;
	var $seed;
	var $x;
	var $y;

	var $tempGen;
	var $rainGen;

	var $discovery;
	var $discoveryRadius;

	var $scale;
	var $tempOctaves;
	var $rainOctaves;
	var $tempPower;
	var $rainPower;
	
	var $hasWater;
	var $waterCutoff;

	var $biomeMatrix = [
		[7,7,7,7,2,2,2,2,2,1,1],
		[7,7,7,2,2,2,2,2,2,1,1],
		[7,8,8,3,3,3,3,3,3,1,1],
		[7,8,8,3,3,3,5,4,4,1,1],
		[7,8,8,3,5,5,5,4,4,1,1],
		[7,8,8,5,5,5,5,4,4,1,1],
		[7,9,8,5,5,5,5,4,1,1,1],
		[7,9,9,5,5,5,4,4,1,1,1],
		[8,9,9,6,5,5,4,4,1,1,1],
		[8,9,9,6,6,6,6,4,1,1,1],
		[8,9,9,6,6,6,4,4,1,1,1],
	];

	function __construct($db, $token, $size = 7, 
						$hidden = true, $radius = 2, 
						$scale = 5, $tempOctaves = 2, $rainOctaves = 1, $tempPower = 1.5, $rainPower = 0.9, 
						$water = false, $waterCutoff = 0.2) {
		require_once 'simplex.php';

		$this->db = $db;
		$this->token = $token;
		$this->size = max(3, min(203, $size + 2));
		$this->midPoint = intdiv($this->size, 2);
		$this->discovery = boolval($hidden);
		$this->discoveryRadius = max(0, min($this->size, $radius));
		$this->scale = max(1, $scale);
		$this->tempOctaves = max(1, min(11, $tempOctaves));
		$this->rainOctaves = max(1, min(11, $rainOctaves));
		$this->tempPower = max(0, min(999, $tempPower));
		$this->rainPower = max(0, min(999, $rainPower));
		$this->hasWater = boolval($water);
		$this->waterCutoff = max(0, min(1, $waterCutoff));

		$stmt = $this->db->prepare("SELECT id,cur_map FROM user WHERE token = ?");
		$stmt->bind_param("s", $this->token);
		$stmt->execute();
		$stmt->bind_result($this->userId, $this->mapId);
		$stmt->fetch();
		$stmt->close();

		$stmt = $this->db->prepare("SELECT seed FROM map WHERE id = ?");
		$stmt->bind_param("i", $this->mapId);
		$stmt->execute();
		$stmt->bind_result($this->seed);
		$stmt->fetch();
		$stmt->close();

		$stmt = $this->db->prepare("SELECT x,y FROM position WHERE map_id = ?");
		$stmt->bind_param("i", $this->mapId);
		$stmt->execute();
		$stmt->bind_result($this->x, $this->y);
		$stmt->fetch();
		$stmt->close();

		$this->tempGen = new Simplex($this->seed);
		$this->rainGen = new Simplex($this->seed - 1);
	}

	function discovery($hidden, $radius) {
		$this->discovery = $hidden;
		$this->discoveryRadius = $radius;
	}

	function getBiomeId($temp, $rain) {
		if ($this->hasWater) {
			if ($temp <= $this->waterCutoff) return 10;
			if ($temp <= $this->waterCutoff + 0.1) return 7;
		}

		$tempRound = intval((round($temp * 100) / 10) % 10);
		$rainRound = intval((round($rain * 100) / 10) % 10);
		return $this->biomeMatrix[$tempRound][$rainRound];
	}

	function getTemp($x, $y) {
		//x y z scale octaves exponent
		return $this->tempGen->noise($x, $y, 0, $this->scale, $this->tempOctaves, $this->tempPower);
	}

	function getRain($x, $y) {
		//x y z scale octaves exponent
		return $this->rainGen->noise($x, $y, 0, $this->scale, $this->rainOctaves, $this->rainPower);
	}

	function checkDiscoverBounds($x, $y) {
		$rX = $x - $this->midPoint;
		$rY = $y - $this->midPoint;
		$dSq = $rX * $rX + $rY * $rY;
		
		if ($dSq <= $this->discoveryRadius * $this->discoveryRadius) return true;

		return false;
	}

	function updatePosition($dir = 0) {
		switch ($dir) {
			case 37:
				$this->x--;
				break;
			case 38:
				$this->y++;
				break;
			case 39:
				$this->x++;
				break;
			case 40:
				$this->y--;
				break;
			default:
				break;
		}

		$stmt = $this->db->prepare("UPDATE position SET x=?,y=? WHERE map_id=? AND user_id=?");
	    $stmt->bind_param("iiii", $this->x, $this->y, $this->mapId, $this->userId);
	    $stmt->execute();
	    $stmt->close();

	    if ($this->discovery) {
	    	for ($y = $this->midPoint - $this->discoveryRadius; $y < $this->midPoint + $this->discoveryRadius + 1; $y++) {
				for ($x = $this->midPoint - $this->discoveryRadius; $x < $this->midPoint + $this->discoveryRadius + 1; $x++) {
					if ($this->checkDiscoverBounds($x, $y)) {
						$pX = ($this->x + $x - $this->midPoint);
						$pY = ($this->y + $y - $this->midPoint);
						$stmt = $this->db->prepare("INSERT IGNORE INTO position_discovery SET map_id=?,user_id=?,x=?,y=?");
					    $stmt->bind_param("iiii", $this->mapId, $this->userId, $pX, $pY);
					    $stmt->execute();
					    $stmt->close();
					}
				}
			} 	
	    }

	    $result = array();
	    $result = $this->renderBiome($dir);

	    return $result;
	}

	function renderBiome($dir = 0) {
		$outMap = array();
		$tempMap = array();
		$rainMap = array();
		$biomeMap = array();
		$discoveryMap = array();

		$minX = 0;
		$maxX = $this->size;
		$minY = 0;
		$maxY = $this->size;

		switch ($dir) {
			case 37:
				$maxX = $minX + 1;
				break;
			case 38:
				$minY = $maxY - 1;
				break;
			case 39:
				$minX = $maxX - 1;
				break;
			case 40:
				$maxY = $minY + 1;
				break;
			default:
				break;
		}

		if ($this->discovery) {
			$dMinX = $minX + $this->x - $this->midPoint;
			$dMaxX = $maxX + $this->x - $this->midPoint;
			$dMinY = $minY + $this->y - $this->midPoint;
			$dMaxY = $maxY + $this->y - $this->midPoint;

			$stmt = $this->db->prepare("SELECT x,y FROM position_discovery WHERE map_id=? AND user_id=? AND x>=? AND x<=? AND y>=? AND y<=?");
			$stmt->bind_param("iiiiii", $this->mapId, $this->userId, $dMinX, $dMaxX, $dMinY, $dMaxY);
			$stmt->execute();
			$stmt->bind_result($dX, $dY);
			while ($stmt->fetch()) {
				if (!array_key_exists($dX, $discoveryMap)) {
					$discoveryMap[$dX] = array();
				}
				array_push($discoveryMap[$dX], $dY);
			}
			$stmt->close();
		}

		for ($y = $minY; $y < $maxY; $y++) {
			for ($x = $minX; $x < $maxX; $x++) {
				if (!array_key_exists($x, $biomeMap)) {
					$tempMap[$x] = array();
					$rainMap[$x] = array();
					$biomeMap[$x] = array();
				}
				$pX = ($this->x + $x - $this->midPoint);
				$pY = ($this->y + $y - $this->midPoint);
				if (!$this->discovery || (array_key_exists($pX, $discoveryMap) && in_array($pY, $discoveryMap[$pX]))) {
					$temp = $this->getTemp($pX, $pY);
					$rain = $this->getRain($pX, $pY);
					$tempMap[$x][$y] = $temp;
					$rainMap[$x][$y] = $rain;
					$biomeMap[$x][$y] = $this->getBiomeId($temp, $rain);
				} else {
					$tempMap[$x][$y] = 0;
					$rainMap[$x][$y] = 0;
					$biomeMap[$x][$y] = 0;
				}
			}
		}

		if ($this->discovery) {
			for ($y = $this->midPoint - $this->discoveryRadius; $y < $this->midPoint + $this->discoveryRadius + 1; $y++) {
				for ($x = $this->midPoint - $this->discoveryRadius; $x < $this->midPoint + $this->discoveryRadius + 1; $x++) {
					if (!array_key_exists($x, $biomeMap)) {
						$biomeMap[$x] = array();
					}
					if ($this->checkDiscoverBounds($x, $y)) {
						$pX = ($this->x + $x - $this->midPoint);
						$pY = ($this->y + $y - $this->midPoint);
						$temp = $this->getTemp($pX, $pY);
						$rain = $this->getRain($pX, $pY);
						$tempMap[$x][$y] = $temp;
						$rainMap[$x][$y] = $rain;
						$biomeMap[$x][$y] = $this->getBiomeId($temp, $rain);
					}
				}
			}
		}

		$outMap['temp'] = $tempMap;
		$outMap['rain'] = $rainMap;
		$outMap['biome'] = $biomeMap;

		return $outMap;
	}
}

?>