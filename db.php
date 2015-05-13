<?php
// error_reporting(E_ALL);
// ini_set('display_errors', True);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

define('MAX_LENGTH', 4294967295);

if (!array_key_exists('option', $_POST) ||
    !array_key_exists('app', $_POST) ||
    !array_key_exists('password', $_POST)) {
    echo "{\"code\": 500, \"status\": \"error\", \"error\": \"Bad parameters.\"}";
    exit();
}

$opt = strtoupper($_POST['option']);
$app = $_POST['app'];
$password = md5($_POST['password']);
if (array_key_exists('version', $_POST)) {
    $version = $_POST['version'];
} else {
    $version = 0;
}
$version = $version . '';

$response = '';

$db = new mysqli("31.22.4.32","feifeiha_public","p0OnMM722iqZ","feifeiha_big_db");

// Check connection
if ($db->connect_error) {
    $response = "{\"code\": 500, \"status\": \"error\", \"error\": \"Failed to connect to MySQL: " . $db->connect_error . "\"}";
}
else {
    try {
        switch ($opt) {
            case 'UPGRADE':
            $response = newVersion($app, $password, $db);
            break;

            case 'TOP':
            $response = listVersions($app, $password, $db);
            break;

            case 'PULL':
            $response = fetchDB($app, $password, $version, $db);
            break;

            case 'PULL KEY':
            if (!array_key_exists('key', $_POST)) {
                $response = "{\"code\": 500, \"status\": \"error\", \"error\": \"Missing 'key' parameter.\"}";
            } else {
                $response = fetchKeyDB($app, $password, $version, $_POST['key'], $db);
            }

            break;

            case 'WATCH':
            if (!array_key_exists('key', $_POST)) {
                $response = "{\"code\": 500, \"status\": \"error\", \"error\": \"Missing 'key' parameter.\"}";
            } else {
                watchKeyDB($app, $password, $version, $_POST['key'], $db);
            }

            break;

            case 'PUSH':
            if (!array_key_exists('json', $_POST)) {
                $response = "{\"code\": 500, \"status\": \"error\", \"error\": \"Missing 'JSON' parameter.\"}";
            } else {
                $response = pushDB($app, $password, $version, $_POST['json'], $db);
            }
            break;

            case 'UPDATE':
            if (!array_key_exists('key', $_POST) ||
                !array_key_exists('json', $_POST)) {
                $response = "{\"code\": 500, \"status\": \"error\", \"error\": \"Missing 'key' or 'JSON' parameter.\"}";
            } else {
                $response = updateKeyDB($app, $password, $version, $_POST['key'], $_POST['json'], $db);
            }
            break;

            case 'REMOVE':
            if (!array_key_exists('key', $_POST)) {
                $response = "{\"code\": 500, \"status\": \"error\", \"error\": \"Missing 'key' parameter.\"}";
            } else {
                $response = removeKeyDB($app, $password, $version, $_POST['key'], $db);
            }
            break;

            case 'CREATE':
            $response = createDB($app, $password, $db);
            break;

            default:
            $response = "{\"code\": 400, \"status\": \"error\", \"error\": \"Bad request.\"}";
            break;
        }
    } catch (Exception $e) {
        mysqli_close($db);

        echo "{\"code\": 0, \"status\": \"unknown error\", \"error\": \"".$e->getMessage()."\"}";
    }
}

function newVersion($app, $password, $db) {

    $stmt = $db->prepare('SELECT password FROM Basement WHERE AppName = ?');
    $stmt->bind_param('s', $app);
    $stmt->execute();

    $stmt->store_result();

    $stmt->bind_result($pass);
    while ($stmt->fetch()) {
        if ($pass !== $password) {
            $stmt->close();
            return "{\"status\": \"error\", \"error\": \"wrong password.\", \"code\": 403}";
        }
    }
    $rows = $stmt->num_rows;

    $stmt->close();

    $time = time();
    $json = '{}';
    $version = $rows;

    $stmt = $db->prepare('INSERT INTO Basement (AppName, Password, LastModified, JSON, Version) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $app, $password, $time, $json, $version);


    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return "{\"code\": 500, \"status\": \"error\", \"error\": \"Storage creation failed: ".$error."\"}";
    }
    else {
        $stmt->close();
        return "{\"code\": 200, \"status\": \"ok\", \"version\": " .$version. "}";
    }

}

function listVersions($app, $password, $db) {

    $stmt = $db->prepare('SELECT password, JSON FROM Basement WHERE AppName = ?');
    $stmt->bind_param('s', $app);
    $stmt->execute();

    $stmt->store_result();

    $stmt->bind_result($pass, $json);
    $sizes = array();
    $count = 0;
    while ($stmt->fetch()) {
        if ($pass !== $password) {
            $stmt->close();
            return "{\"status\": \"error\", \"error\": \"wrong password.\", \"code\": 403}";
        }

        $sizes[$count . ''] = floatval(number_format(strlen($json) / MAX_LENGTH, 3));
        ++$count;
    }
    $rows = $stmt->num_rows - 1;
    $total = $rows + 1;

    $stmt->close();
    return "{\"code\": 200, \"status\": \"ok\", \"total\": ". $total . ", \"top\": " . $rows .", \"used\": " .json_encode($sizes). "}" ;

}

function createDB($app, $password, $db) {

    $stmt = $db->prepare("SELECT * FROM Basement WHERE AppName = ?");
    $stmt->bind_param('s', $app);
    $stmt->execute();

    $stmt->store_result();

    while($stmt->fetch()) {
        $stmt->close();
        return "{\"code\": 500, \"status\": \"error\", \"error\": \"Storage name already existed.\"}";
    }

    $time = time();
    $json = '{}';
    $version = '0';

    $stmt = $db->prepare('INSERT INTO Basement (AppName, Password, LastModified, JSON, Version) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $app, $password, $time, $json, $version);


    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return "{\"code\": 500, \"status\": \"error\", \"error\": \"Storage creation failed: ".$error."\"}";
    }
    else {
        $stmt->close();
        return "{\"code\": 200, \"status\": \"ok\"}";
    }
}

function fetchDB($app, $password, $version, $db) {
    $res = "{\"status\": \"error\", \"error\": \"no records found.\", \"code\": 404}";

    $stmt = $db->prepare('SELECT * FROM Basement WHERE AppName = ?');
    $stmt->bind_param('s', $app);
    $stmt->execute();

    $stmt->store_result();
    $stmt->bind_result($id, $name, $modified, $json, $pass, $ver);
    while ($stmt->fetch()) {
        if ($pass !== $password) {
            $stmt->close();
            return "{\"status\": \"error\", \"error\": \"wrong password.\", \"code\": 403}";
        }

        if ($version !== $ver) {
            continue;
        } else {
            $res = "{\"code\": 200, \"status\": \"ok\", \"LastModified\": \"".$modified."\", \"version\":".$version.", \"json\": \"".$json."\"}";
        }
    }

    $stmt->close();
    return $res;
}

function fetchKeyDB($app, $password, $version, $key, $db) {
    $res = fetchDB($app, $password, $version, $db);
    $old = json_decode($res, true);
    $json = json_decode($res, true);
    if ($json['code'] !== 200) {
        return $res;
    } else {
        // retrieve range from $key.
        $showAll = true;
        $rangeFrom = 0;
        $rangeTo = 0;

        $rangeTks = explode("::", $key);
        $key = trim($rangeTks[0]);
        if (count($rangeTks) > 1) {
            $showAll = false;

            $rangeTks = explode("-", trim($rangeTks[1]));
            if (count($rangeTks) == 1) {
                $rangeTo = intval(trim($rangeTks[0]));
            } else {
                $rangeFrom = intval(trim($rangeTks[0]));
                $rangeTo = intval(trim($rangeTks[1]));
            }
        }

        $json = json_decode(urldecode($json['json']), true);
        $keys = explode(">", $key);
        $target = $json;
        foreach ($keys as $k) {
            $k = trim($k);
            if (array_key_exists($k, $target)) {
                $target = $target[$k];
            }
            else {
                $target = null;
            }
        }

        // if don't need show all records, fetch the required ones within range.
        if ($target !== null &&
            $showAll == false &&
            (is_object($target) || is_array($target))) {

            if ($rangeTo >= count($target)) {
                $rangeTo = $target;
            }

            $temp = array();
            $count = 0;

            foreach ($target as $key => $value) {
                if ($count >= $rangeFrom) {
                    $temp[$key] = $value;
                }

                if ($count == $rangeTo) {
                    break;
                }
                ++$count;
            }

            $target = $temp;
        }

        $target = json_encode($target);
        $target = str_replace(array('%'), "%25", $target);
        $target = str_replace("'", "%27", $target);
        $target = str_replace("\"", "%22", $target);

        return "{\"code\": 200, \"status\": \"ok\", \"LastModified\": \"".$old['LastModified']."\", \"version\":".$version.", \"json\": \"{%22data%22: ".$target."}\"}";
    }
}

function checkStatus($buffer) {
    $json = json_decode($buffer, true);
    $result = false;
    if ($json['code'] == 200) {
        $result = $json['json'];
    }
    return $result;
}

function watchKeyDB($app, $password, $version, $key, $db) {
    $buffer = fetchKeyDB($app, $password, $version, $key, $db);
    $status = checkStatus($buffer);
    if (!$status) {
        echo $buffer;
        exit();
    } else {
        $buffer = $status;
    }

    $isChanged = false;

    while (!connection_aborted() && !$isChanged) {
        sleep(2);

        $temp = fetchKeyDB($app, $password, $version, $key, $db);
        $copy = $temp;
        $status = checkStatus($temp);
        if (!$status) {
            echo $temp;
            exit();
        }

        $temp = $status;
        if ($temp !== $buffer) {
            echo $copy;
            $isChanged = true;
        }
    }

    exit();
}

function pushDB($app, $password, $version, $json, $db) {

    $stmt = $db->prepare('SELECT Password FROM Basement WHERE AppName = ?');
    $stmt->bind_param('s', $app);
    $stmt->execute();

    $stmt->store_result();
    $stmt->bind_result($pass);

    while($stmt->fetch())
    {
        if ($pass !== $password) {
            $stmt->close();
            return "{\"status\": \"error\", \"error\": \"wrong password.\", \"code\": 403}";
        }
    }

    $stmt->close();

    $json = str_replace(array('%'), "%25", $json);
    $json = str_replace("'", "%27", $json);
    $json = str_replace("\"", "%22", $json);

    $time = time();
    $stmt = $db->prepare('UPDATE Basement SET JSON = ?, LastModified = ?
                   WHERE AppName = ? AND Version = ?');
    $stmt->bind_param('ssss', $json, $time, $app, $version);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return "{\"code\": 500, \"status\": \"error\", \"error\": \"Record update failed: ".$error."\"}";

    } else {
        $stmt->close();
        return "{\"code\": 200, \"status\": \"ok\", \"version\":".$version."}";
    }
}

function updateDB($app, $password, $version, $jsonStr, $db) {
    $res = fetchDB($app, $password, $version, $db);
    $old = json_decode($res, true);
    $json = json_decode($res, true);
    if ($json['code'] !== 200) {
        return $res;
    } else {
        $json = json_decode(urldecode($json['json']), true);
        $new = json_decode($jsonStr, true);
        $json = json_encode(array_merge($json, $new));
        return pushDB($app, $password, $version, $json, $db);
    }
}

function updateKeyDB($app, $password, $version, $key, $value, $db) {
    $res = fetchDB($app, $password, $version, $db);
    $json = json_decode($res, true);
    if ($json['code'] !== 200) {
        return $res;
    } else {
        $json = urldecode($json['json']);
        $json = json_decode($json, true);

        $key = preg_replace('/\s+/', '', $key);
        $tks = explode('>', $key);
        $key = '';
        foreach ($tks as $token) {
            $key = $key . '["'. $token .'"]';
        }

        $value = json_decode($value, true);
        eval('$json'.$key.' = $value;');

        $json = json_encode($json);
        return pushDB($app, $password, $version, $json, $db);
    }
}

function removeKeyDB($app, $password, $version, $key, $db) {
    $res = fetchDB($app, $password, $version, $db);
    $json = json_decode($res, true);
    if ($json['code'] !== 200) {
        return $res;
    } else {
        $json = urldecode($json['json']);
        $json = json_decode($json, true);

        $key = preg_replace('/\s+/', '', $key);
        $tks = explode('>', $key);
        $key = '';
        foreach ($tks as $token) {
            $key = $key . '["'. $token .'"]';
        }

        eval('unset($json'.$key.');');

        $json = json_encode($json);
        return pushDB($app, $password, $version, $json, $db);
    }
}

mysqli_close($db);

function echobig($string, $bufferSize = 8192) {
    header('Content-Length: ' . strlen($string));

    $chuncks = str_split($string, $bufferSize);
    foreach ($chuncks as $chunck) {
        echo $chunck;
    }
}

echobig($response);
?>
