<?php

error_reporting(E_ERROR);

define('DATA_DIR', 'entries'); // use this to protect files from being publicly viewable
define("USERNAME", 'demo');
define('PASSWORD', 'demo');

if (($_SERVER['PHP_AUTH_USER'] !== USERNAME) || ($_SERVER['PHP_AUTH_PW'] !== PASSWORD)) {
    header('WWW-Authenticate: Basic realm="Cloud Notes"');
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

header('Content-type: application/json; charset=utf-8');
	
// Parse incoming data

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
		} elseif ($item['timestamp'] > $local_index[$id]['timestamp']) {
			// Remote entry is newer, replace it and don't send it back
			$local_index[$id] = $item;
			store_entry($id, $_POST[$id]);
			unset($remote_entries[$id]);
			unset($local_entries[$id]);
		} elseif ($item['timestamp'] == $local_index[$id]['timestamp']) {
			// Local entry is already the latest, don't send it back
			unset($remote_entries[$id]);
			unset($local_entries[$id]);
		} else {
			// Local entry is newer, send it back
			$remote_index[$id] = $local_index[$id];
			$remote_entries[$id] = get_entry($id);
		}
	} else {
		$local_index[$id] = $remote_index[$id];
		store_entry($id, $_POST[$id]);
		unset($remote_entries[$id]);
	}
}

foreach ($diff_index as $id => $data) {
	//echo $data['timestamp'];
	if ($local_index[$id]['timestamp'] !== 0) {
		$remote_entries[$id] = get_entry($id);
		$remote_index[$id] = $local_index[$id];
	} else {
		unset($remote_entries[$id]);
		unset($remote_index[$id]);
	}
}

store_entry('_index', $local_index);

$return = array('index' => $remote_index, 'entries' => $remote_entries, 'debug' => $debug);
echo json_encode($return);


// Helpers

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