<?php
define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME', 'map');

$db = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($db->connect_error){
	die('ERROR: Could not connect. ' . $db->connect_error);
}

require_once 'map_render.php';

session_start();

if (isset($_COOKIE['token'])) setcookie('token', $_COOKIE['token'], time() + (10 * 365 * 24 * 60 * 60));

$token = (isset($_COOKIE['token'])) ? htmlentities(trim($_COOKIE['token'])) : ''; 
$token = ($token == '' && isset($_SESSION['token'])) ? htmlentities(trim($_SESSION['token'])) : $token;

$op = (empty($_POST['op'])) ? 'noop' : $_POST['op'];

$size = (isset($_POST['size'])) ? intval($_POST['size']) : 7;
$dir = (isset($_POST['dir'])) ? intval($_POST['dir']) : 0;
$hidden = (isset($_POST['hide'])) ? boolval($_POST['hide']) : false;
$radius = (isset($_POST['rad'])) ? intval($_POST['rad']) : 2;
$scale = (isset($_POST['scale'])) ? intval($_POST['scale']) : 5;
$toct = (isset($_POST['toct'])) ? intval($_POST['toct']) : 2;
$roct = (isset($_POST['roct'])) ? intval($_POST['roct']) : 1;
$tpow = (isset($_POST['tpow'])) ? floatval($_POST['tpow']) : 1.5;
$rpow = (isset($_POST['rpow'])) ? floatval($_POST['rpow']) : 0.9;
$temp = (isset($_POST['temp'])) ? boolval($_POST['temp']) : false;
$rain = (isset($_POST['rain'])) ? boolval($_POST['rain']) : false;
$biomes = (isset($_POST['biomes'])) ? boolval($_POST['biomes']) : true;
$height = (isset($_POST['height'])) ? boolval($_POST['height']) : true;
$grid = (isset($_POST['grid'])) ? boolval($_POST['grid']) : false;
$water = (isset($_POST['water'])) ? boolval($_POST['water']) : false;
$wcut = (isset($_POST['wcut'])) ? floatval($_POST['wcut']) : 0.2;

$seed = (isset($_POST['seed'])) ? intval($_POST['seed']) : 0;
$name = (isset($_POST['name'])) ? substr(htmlentities(trim($_POST['name'])), 0, 64) : '';

$id = (isset($_POST['id'])) ? htmlentities(trim($_POST['id'])) : '';
$map = (isset($_POST['map'])) ? htmlentities(trim($_POST['map'])) : '';

$cookie = (isset($_POST['cookie'])) ? boolval($_POST['cookie']) : false;

if ($name == '') $name = 'Unnamed Map ' . substr(md5(uniqid('')), 0, 5);
if ($id == '') $id = $token;
if (empty($token)) newUser();
$_SESSION['token'] = $token;

$outArray = array();
$outArray['token'] = $token;
$outArray['cookie'] = isset($_COOKIE['token']);

switch ($op) {
	case 'params':
		getParams();
		break;
	case 'move':
		move();
		break;
	case 'clear':
		clearMap();
		break;
	case 'create':
		newMap();
		break;
	case 'cookie':
		cookie();
		break;
	case 'load':
		loadMapList();
		break;
	case 'change':
		changeId();
		break;
	case 'delmap':
		deleteMap();
	default:
		break;
}

echo json_encode($outArray);

function getParams() {
	global $db, $outArray, $token, $seed, $name, $size, $hidden, $radius, $scale, $toct, $roct, $tpow, $rpow, $temp, $rain, $biomes, $height, $grid, $water, $wcut;

	$stmt = $db->prepare("SELECT id,cur_map FROM user WHERE token=?");
	$stmt->bind_param("s", $token);
	$stmt->execute();
	$stmt->bind_result($userId, $mapId);
	$stmt->fetch();
	$stmt->close();

	$stmt = $db->prepare("SELECT 
		seed,name,
		is_hidden,show_temp,show_rain,show_biome,show_height,show_grid,show_water,
		size,radius,
		scale,temp_octaves,rain_octaves,temp_power,rain_power,water_level 
		FROM map WHERE id=? AND made_by=?");
	$stmt->bind_param("ii", $mapId, $userId);
	$stmt->execute();
	$stmt->bind_result($seed, $name, $hidden, $temp, $rain, $biomes, $height, $grid, $water, $size, $radius, $scale, $toct, $roct, $tpow, $rpow, $wcut);
	$stmt->fetch();
	$stmt->close();

	$result = array('seed'=>$seed,'name'=>$name,
		'hide'=>$hidden,'temp'=>$temp,'rain'=>$rain,'biomes'=>$biomes,'height'=>$height,'grid'=>$grid,'water'=>$water,
		'size'=>$size,'rad'=>$radius,
		'scale'=>$scale,'toct'=>$toct,'roct'=>$roct,'tpow'=>$tpow,'rpow'=>$rpow,'wcut'=>$wcut);

	$outArray = array_merge($outArray, $result);
}

function move() {
	global $db, $outArray, $token, $size, $dir, $hidden, $radius, $scale, $toct, $roct, $tpow, $rpow, $temp, $rain, $biomes, $height, $grid, $water, $wcut;

	$mapRender = new MapRender($db, $token, $size, $hidden, $radius, $scale, $toct, $roct, $tpow, $rpow, $water, $wcut);

	$stmt = $db->prepare("SELECT id,cur_map FROM user WHERE token=?");
	$stmt->bind_param("s", $token);
	$stmt->execute();
	$stmt->bind_result($userId, $mapId);
	$stmt->fetch();
	$stmt->close();

	$tint = (int)$temp;
	$rint = (int)$rain;
	$bint = (int)$biomes;
	$hint = (int)$height;
	$gint = (int)$grid;
	$wint = (int)$water;

	$stmt = $db->prepare('UPDATE map SET
		is_hidden=?,show_temp=?,show_rain=?,show_biome=?,show_height=?,show_grid=?,show_water=?,
		size=?,radius=?,
		scale=?,temp_octaves=?,rain_octaves=?,temp_power=?,rain_power=?,water_level=? 
		WHERE id=? AND made_by=?');
	$stmt->bind_param("iiiiiiiiiiiidddii",
		$hidden,$tint,$rint,$bint,$hint,$gint,$wint,
		$size,$radius,
		$scale,$toct,$roct,$tpow,$rpow,$wcut,
		$mapId, $userId);
	$stmt->execute();
	$stmt->close();	

	$outArray = array_merge($outArray, $mapRender->updatePosition($dir));
}

function clearMap() {
	global $db, $token;

	$stmt = $db->prepare("SELECT id,cur_map FROM user WHERE token=?");
	$stmt->bind_param("s", $token);
	$stmt->execute();
	$stmt->bind_result($userId, $mapId);
	$stmt->fetch();
	$stmt->close();

	$stmt = $db->prepare("DELETE FROM position_discovery WHERE map_id=? AND user_id=?");
	$stmt->bind_param("ii", $mapId, $userId);
	$stmt->execute();
	$stmt->close();
}

function newUser() {
	global $db, $token;

	$token = md5(uniqid('', true));
	$_SESSION['token'] = $token;
	$stmt = $db->prepare('INSERT INTO user (token) VALUES (?)');
	$stmt->bind_param("s", $token);
	$stmt->execute();
	$stmt->close();

	newMap();

	return $token;
}

function newMap() {
	global $db, $token, $seed, $name;

	if ($seed == 0) $seed = rand(-9007199254740991, 9007199254740992);

	$stmt = $db->prepare("SELECT id FROM user WHERE token = ?");
	$stmt->bind_param("s", $token);
	$stmt->execute();
	$stmt->bind_result($userId);
	$stmt->fetch();
	$stmt->close();

	$stmt = $db->prepare('INSERT INTO map (made_by,seed,name) VALUES (?,?,?)');
	$stmt->bind_param("iis", $userId, $seed, $name);
	$stmt->execute();
	$mapId = $stmt->insert_id;
	$stmt->close();

	$stmt = $db->prepare('INSERT INTO position (map_id,user_id) VALUES (?,?)');
	$stmt->bind_param("ii", $mapId, $userId);
	$stmt->execute();
	$stmt->close();

	$stmt = $db->prepare('UPDATE user SET cur_map=? WHERE id=?');
	$stmt->bind_param("ii", $mapId, $userId);
	$stmt->execute();
	$stmt->close();	
}

function cookie() {
	global $token, $outArray, $cookie;

	if ($cookie) {
		setcookie('token', $token, time() + (10 * 365 * 24 * 60 * 60));
	} else {
		setcookie('token', $token, time() - (60 * 60));
		unset($_COOKIE['token']);
	}

	$outArray['cookie'] = $cookie;
}

function loadMapList() {
	global $db, $token, $outArray, $id;

	if ($id == '') $id = $token;

	$result = array();

	$stmt = $db->prepare("SELECT id FROM user WHERE token=?");
	$stmt->bind_param("s", $id);
	$stmt->execute();
	$stmt->bind_result($userId);
	$stmt->fetch();
	$stmt->close();

	$stmt = $db->prepare("SELECT id,name FROM map WHERE made_by=?");
	$stmt->bind_param("i", $userId);
	$stmt->execute();
	$stmt->bind_result($mapId, $mapName);
	while ($stmt->fetch()) {
		array_push($result, array('val'=>$mapId,'text'=>$mapName));
	}
	$stmt->close();

	$outArray['list'] = $result;
}

function changeId() {
	global $db, $outArray, $id, $map;

	$stmt = $db->prepare("SELECT id FROM user WHERE token=?");
	$stmt->bind_param("s", $id);
	$stmt->execute();
	$stmt->bind_result($userId);
	$stmt->fetch();
	$stmt->close();

	$stmt = $db->prepare("SELECT seed FROM map WHERE id=? AND made_by=?");
	$stmt->bind_param("ii", $map, $userId);
	$stmt->execute();
	$stmt->bind_result($seed);
	$stmt->fetch();
	$stmt->close();

	if (isset($seed)) {
		$_SESSION['token'] = $id;
		if (isset($_COOKIE['token'])) setcookie('token', $id, time() + (10 * 365 * 24 * 60 * 60));

		$stmt = $db->prepare("UPDATE user SET cur_map=? WHERE id=?");
		$stmt->bind_param("ii", $map, $userId);
		$stmt->execute();
		$stmt->close();
	}
}

function deleteMap() {
	global $db, $id, $map;

	$stmt = $db->prepare("SELECT id FROM user WHERE token=?");
	$stmt->bind_param("s", $id);
	$stmt->execute();
	$stmt->bind_result($userId);
	$stmt->fetch();
	$stmt->close();

	$stmt = $db->prepare("DELETE FROM map WHERE id=? AND made_by=?");
	$stmt->bind_param("ii", $map, $userId);
	$stmt->execute();
	$stmt->close();
}

?>