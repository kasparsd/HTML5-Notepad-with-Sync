<?php

error_reporting(E_ERROR);

define('DATA_DIR', 'entries'); // use this to protect files from being publicly viewable
define('USERNAME', 'demo');
define('PASSWORD', 'demo');

// Parse incoming data
if (isset($_POST['clientTime'])) {
    $time_delta = time() - bigintval($_POST['clientTime']);
    unset($_POST['clientTime']);}
else {
    $time_delta = 0;}
    
if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    list($name, $password) = explode(':', base64_decode($matches[1]));
    $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
    $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
}
    
//set http auth headers for apache+php-cgi work around if variable gets renamed by apache
if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
    list($name, $password) = explode(':', base64_decode($matches[1]));
    $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
    $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
}

if (($_SERVER['PHP_AUTH_USER'] !== USERNAME) || ($_SERVER['PHP_AUTH_PW'] !== PASSWORD)) {
    header('WWW-Authenticate: Basic realm="Cloud Notes"');
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

header('Content-type: application/json; charset=utf-8');
	
$remote_index = (array)json_decode(stripslashes($_POST['index']), true);
unset($_POST['index']);
$remote_entries = $_POST;

// Process

$local_index = get_entry('_index');

$diff_index = array_diff_key($local_index, $remote_index);

$debug = array();

$debug['new entries'] = $remote_entries;
$debug['old entries'] = get_entries();


foreach ($remote_index as $id => $item) {
	if (array_key_exists($id, $local_index)) {
		if ($item['timestamp'] == 0) {
			// Remote entry has been deleted, remove the local too
			$local_index[$id] = $item;
			delete_entry($id);
			unset($remote_index[$id]);
			unset($remote_entries[$id]);
		} elseif ($local_index[$id]['timestamp'] == 0) {
			// Local entry has been deleted, remove local entry too
			$remote_index[$id] = $local_index[$id];
			delete_entry($id);
			unset($remote_index[$id]);
			unset($remote_entries[$id]);
		} elseif (strval(bigintval($item['timestamp'])) - $time_delta > $local_index[$id]['timestamp']) {
			// Remote entry is newer, replace it and don't send it back
			$local_index[$id] = $item;
			$local_index[$id]['timestamp'] = strval(bigintval($local_index[$id]['timestamp']) - $time_delta); 
			store_entry($id, $_POST[$id]);
			unset($remote_entries[$id]);
			unset($local_entries[$id]);
		} elseif (strval(bigintval($item['timestamp'])) - $time_delta == $local_index[$id]['timestamp']) {
			// Local entry is already the latest, don't send it back
			unset($remote_entries[$id]);
			unset($local_entries[$id]);
		} else {
			// Local entry is newer, send it back
			$remote_index[$id] = $local_index[$id];
			$remote_index[$id]['timestamp'] = strval(bigintval($remote_index[$id]['timestamp']) + $time_delta); 
			$remote_entries[$id] = get_entry($id);
			
		}
	} else {
		$local_index[$id] = $remote_index[$id];
		$local_index[$id]['timestamp'] = strval(bigintval($local_index[$id]['timestamp']) - $time_delta); 
		store_entry($id, $_POST[$id]);
		unset($remote_entries[$id]);
	}
}

foreach ($diff_index as $id => $data) {
	//echo $data['timestamp'];
	if ($local_index[$id]['timestamp'] !== 0) {
		$remote_entries[$id] = get_entry($id);
		$remote_index[$id] = $local_index[$id];
		$remote_index[$id]['timestamp'] = strval(bigintval($remote_index[$id]['timestamp']) + $time_delta); 
	} else {
		unset($remote_entries[$id]);
		unset($remote_index[$id]);
	}
}

store_entry('_index', $local_index);

$return = array('index' => $remote_index, 'entries' => $remote_entries, 'debug' => $debug);
echo json_encode($return);


// Helpers

function bigintval($value) {
  $value = trim($value);
  if (ctype_digit($value)) {
    return $value;
  }
  $value = preg_replace("/[^0-9](.*)$/", '', $value);
  if (ctype_digit($value)) {
    return $value;
  }
  return 0;
}

function get_entry($id) {
	$file = DATA_DIR . '/' . $id;
	if (file_exists($file)) {
		if ($le = json_decode(file_get_contents($file), true)) {
			return $le; // return addslashes($le);
		}
	}
	return array();
}

function store_entry($id, $data) {
	$file = DATA_DIR . '/' . $id;
	if (empty($data))
		return;
	if (is_array($data))
		$data = json_encode($data);
	
	// $data = stripslashes($data);

	file_put_contents($file, $data) 
		or die_json("Can't read the file!");
}

function delete_entry($id) {
	$file = DATA_DIR . '/' . $id;
	if (file_exists($file))
		unlink($file);
}

function die_json($message) {
	echo json_encode(array('error' => $message));
	exit;
}

function get_entries() {
	$entries = array();
	if ($handle = opendir(DATA_DIR)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$entry = json_decode(file_get_contents(DATA_DIR . '/' . $file), true);
				$entries[$file] = $entry;
			}
		}
		closedir($handle);
	}
	if (!empty($entries))
		return json_encode($entries);
}

?>
