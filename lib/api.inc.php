<?php

function getDJ(&$out){
    global $db;
    $sql = "SELECT * FROM streamer WHERE status = 'STREAMING' LIMIT 1;";
    $dbres = $db->query($sql);
    if($dbres) {
        $row = $db->fetch($dbres);
        $out['dj'] = $row['username'];
        $out['djid'] = $row['streamer'];
    }
}

/**
 * get the streamer
 */
function getDJInfo(&$out){
    global $db;
    if(isset($_GET['djname'])) {
        $djname = $db->escape($_GET['djname']);
        $sql = "SELECT * FROM streamer WHERE username = '" . $djname . "' LIMIT 1;";
    }
    elseif(isset($_GET['djid'])) {
        $djid = $db->escape($_GET['djid']);
        $sql = "SELECT * FROM streamer WHERE streamer = '" . $djid . "' LIMIT 1;";
    }
    else {
        throw_error(0, 'no djname or djid given');
    }
    $dbres = $db->query($sql);
    if($dbres) {
        $row = $db->fetch($dbres);
        $out['dj'] = $row['username'];
        $out['djid'] = $row['streamer'];
    }
}

function kickDJ(&$out){
    require_once(dirname(__FILE__).'/LiquidInterface.php');
    global $db;
    $timestamp = time() + (2 * 60);
    $timestamp = date('Y-m-d H:i:s', $timestamp);
    $sql = "UPDATE streamer SET ban = '". $timestamp . "' WHERE streamer = '". $out['djid'] ."';";
    $dbres = $db->query($sql);
    $liquid = new LiquidInterface();
    $liquid->connect();
    $liquid->kickHarbor($liquid->getHarborSource());
    $out['status'] = 0;
}

function getCurrShow(&$out){
    global $db;
    $sql = 'SELECT `show`, UNIX_TIMESTAMP(begin) as b,UNIX_TIMESTAMP(end) as e,name, description,type, username, streamer, status, thread
            FROM shows
            JOIN streamer USING (streamer)
            WHERE end IS NULL
            OR NOW() between begin AND end;';
    $dbres = $db->query($sql);
    if($dbres && $db->num_rows($dbres) > 0) {
        while($row = $db->fetch($dbres)) {
            if($db->num_rows($dbres) > 1 && $row['type'] == 'PLANNED') {
                $key = 'ushow';
                $out['status'] = 'OVERLAP';
            }else {
                $key = 'show';
                if($out['status'] != 'OVERLAP')
                {
                    $out['status'] = $row['status'];
                }
            }
            $out[$key.'begin'] = (int)$row['b'];
            $out[$key.'end'] = (int)$row['e'];
            $out[$key.'type'] = $row['type'];
            $out[$key.'name'] = $row['name'];
            $out[$key.'description'] = $row['description'];
            $out[$key.'id'] = $row['show'];
            $out[$key.'thread'] = $row['thread'];
            $out[$key.'dj'] = $row['username'];
            $out['showdjid'] = $row['streamer'];
        }
    }
}

function getNextShows(&$out,$count = false){
    global $db;
    if(isset($_GET['c']) && $_GET['c'] > 1){
        $limit = $_GET['c'];
    }else{
        $limit = 1;
    }

    if($count && $count > 1){
        $limit = $count;
    }
    $sql  = 'SELECT `show`, thread,UNIX_TIMESTAMP(begin) as b,UNIX_TIMESTAMP(end) as e, name, description, type, username, streamer
                FROM shows
                JOIN streamer USING (streamer)
                WHERE begin > NOW() ';

    if(isset($_GET['djname']) && strlen($_GET['djname']) > 0) {
        $sql .= 'AND username = "' . $db->escape($_GET['djname']) . '" ';
    }

    $sql .= 'ORDER BY begin ASC
                LIMIT 0,'.$limit;

    $dbres = $db->query($sql);
    if($dbres) {

        while($row = $db->fetch($dbres)) {
            $tmp = array();
            $tmp['showbegin'] = (int)$row['b'];
            $tmp['showend'] = (int)$row['e'];
            $tmp['showtype'] = $row['type'];
            $tmp['showname'] = $row['name'];
            $tmp['showdescription'] = $row['description'];
            $tmp['showid'] = $row['show'];
            $tmp['showdj'] = $row['username'];
            $tmp['showdjid'] = $row['streamer'];
            $tmp['showthread'] = (int)$row['thread'];
            $out['shows'][] = $tmp;
        }
    }
    if(!isset($out['shows'])){
        $out['shows'] = array();
    }

}

function getLastShows(&$out){
    global $db;
    if(isset($_GET['c']) && $_GET['c'] > 1){
        $limit = $_GET['c'];
    }else{
        $limit = 1;
    }
    $sql  = 'SELECT `show`, thread,UNIX_TIMESTAMP(begin) as b,UNIX_TIMESTAMP(end) as e, name, description, type, username, streamer
                FROM shows
                JOIN streamer USING (streamer)
                WHERE end < NOW() ';

    if(isset($_GET['djname']) && strlen($_GET['djname']) > 0) {
        $sql .= 'AND username = "' . $db->escape($_GET['djname']) . '" ';
    }

    $sql .= 'ORDER BY end DESC
                LIMIT 0,'.$limit;

    $dbres = $db->query($sql);
    if($dbres) {

        while($row = $db->fetch($dbres)) {
            $tmp = array();
            $tmp['showbegin'] = (int)$row['b'];
            $tmp['showend'] = (int)$row['e'];
            $tmp['showtype'] = $row['type'];
            $tmp['showname'] = $row['name'];
            $tmp['showdescription'] = $row['description'];
            $tmp['showid'] = $row['show'];
            $tmp['showdj'] = $row['username'];
            $tmp['showdjid'] = $row['streamer'];
            $tmp['showthread'] = (int)$row['thread'];
            $out['shows'][] = $tmp;
        }
    }
    if(!isset($out['shows'])){
        $out['shows'] = array();
    }

}

function getCurrTrack(&$out) {
    global $db;
    $lasttrack = 0;
    if(isset($_GET['ltid']) && $_GET['ltid'] > 0){
        $lasttrack = $db->escape($_GET['ltid']);
    }
    $sql = "SELECT *
            FROM songhistory
            WHERE end IS NULL
            AND song > ".$lasttrack.";";
    $dbres = $db->query($sql);
    if($dbres) {
        if($row = $db->fetch($dbres)) {
            $out['trackid'] = $row['song'];
            $out['title'] = $row['title'];
            $out['artist'] = $row['artist'];
        }
    }
}

function getListenerPerRelay(&$out) {
    global $db;
    $sql = "SELECT relay, COUNT(*) as c, hostname
            FROM listenerhistory
            JOIN relays USING (relay)
            WHERE disconnected IS NULL
            GROUP BY relay;";
    $dbres = $db->query($sql);
    $tmp = array();
        if($dbres) {
            while($row = $db->fetch($dbres)) {
                $tmp[] = array('relay' => $row['relay'],
                'hostname' => $row['hostname'],
                'c' => $row['c']);
            }
        }
    $out['relay'] = $tmp;
}

function getListener(&$out){
    global $db;
    $sql = "SELECT name, IF(c IS NULL, 0, c) as c, description
            FROM (SELECT COUNT(*) as c, mount
                  FROM listenerhistory
                  WHERE disconnected IS NULL
                  GROUP BY mount) as c
            RIGHT JOIN mounts USING (mount);";
    $dbres = $db->query($sql);
    $tmp = array();
    if($dbres) {
        while($row = $db->fetch($dbres)) {
            $tmp[] = array('name' => $row['name'],
                'description' => $row['description'],
                'c' => $row['c']);
        }
    }
    $out['listener'] = $tmp;
}

function getListenerData(&$out) {
    global $db;
    $sql = "SELECT ip, country, city FROM listenerhistory WHERE disconnected IS NULL;";
    $dbres = $db->query($sql);
    $tmp = array();
    if($dbres) {
        while($row = $db->fetch($dbres)) {
            $location = getLocation(long2ip($row['ip']));
            $tmp[] = array('ip' => '0000000000',
                           'country' => $row['country'],
                           'city' => $row['city'],
                           'latitude' => (string)$location['latitude'],
                           'longitude' => (string)$location['longitude']);
        }
    }
    $out['listener'] = $tmp;
}

function getTracks(&$out){
    global $db;
    if(isset($_GET['c']) && $_GET['c'] > 1){
        $limit = $_GET['c'];
    }else{
        $limit = 1;
    }
    $sql = 'SELECT title, artist
            FROM songhistory
            ORDER BY song DESC
            LIMIT 0,'.$limit.';';
    $dbres = $db->query($sql);
    $tmp = array();
    if($dbres) {
        while($row = $db->fetch($dbres)) {
            $tmp[] = array('title' => $row['title'],
                           'artist' => $row['artist']);
        }
    }
    $out['history'] = $tmp;
}

function getTraffic(&$out) {
    global $db;
    
    $sql = "SELECT relay, hostname, tx FROM relays WHERE status <> 'OFFLINE';";
    $dbres = $db->query($sql);
    $tmp = array();
    if($dbres) {
        while($row = $db->fetch($dbres)) {
            $tmp[] = array('relay' => $row['relay'],
            'hostname' => $row['hostname'],
            'out' => $row['tx']);
        }
    }
    $out['traffic'] = $tmp;
}

function getCountries(&$out) {
    global $db;

    $sql =  'SELECT c, country
            FROM (SELECT COUNT(*) as c, country
            FROM listenerhistory
            WHERE disconnected IS NULL
            GROUP BY country) as c
            ORDER BY c DESC;';

    $dbres = $db->query($sql);
    $tmp = array();
    if($dbres) {
        while($row = $db->fetch($dbres)) {
            $tmp[] = array('country' => $row['country'],
                           'count' => (int)$row['c']);
        }
    }
    $out['countries'] = $tmp;
}

function authTest(&$out) {
    global $db;
    if(isset($_GET['hostmask']) && strlen($_GET['hostmask']) > 0) {
        $hostmask = explode('!', $_GET['hostmask']);
        $sql = "SELECT streamer, username
                FROM streamersettings
                JOIN streamer using(streamer)
                WHERE `key` = 'hostmask'
                AND `value` REGEXP '[A-z0-9]+!". $db->escape($hostmask[1]) . "' ;";
        $dbres = $db->query($sql);
        if($dbres) {
            if($row = $db->fetch($dbres)) {
                $out['auth']['nick'] = $row['username'];
                $out['auth']['id'] = $row['streamer'];
                $out['auth']['status'] = 0;
                return;
            }
        }
    }
    $out['auth']['status'] = 1;
}

function authAdd(&$out) {
    global $db;

    if((isset($_GET['hostmask']) && strlen($_GET['hostmask']) > 0) && (isset($_GET['user']) && strlen($_GET['user']) > 0) && (isset($_GET['pass']) && strlen($_GET['pass']) > 0)) {
        $sql = "SELECT * FROM streamer WHERE username = '" . $db->escape($_GET['user']) . "' AND streampassword = '" . $db->escape($_GET['pass']) . "';";
        $dbres = $db->query($sql);
        if($dbres && $db->num_rows($dbres) > 0) {
            if($row = $db->fetch($dbres)) {
                $sql = "INSERT INTO streamersettings (streamer,`key`,value)
                        VALUES (" . $row['streamer'] . ",'hostmask','" . $db->escape($_GET['hostmask']) ."')
                        ON DUPLICATE KEY UPDATE value = '" . $db->escape($_GET['hostmask']) . "';";
                if($db->execute($sql)) {
                    $out['auth']['nick'] = $row['username'];
                    $out['auth']['id'] = $row['streamer'];
                    $out['auth']['status'] = 0;
                    return;
                }
            }
        }
    }
    $out['auth']['status'] = 1;
}

function authJoin(&$out) {
    global $db;

    if(isset($_GET['hostmask']) && strlen($_GET['hostmask']) > 0) {
        $hostmask = explode('!', $_GET['hostmask']);
        $sql = "SELECT * FROM streamersettings JOIN streamer using(streamer) WHERE `key` = 'hostmask' AND `value` REGEXP '[A-z0-9]+!" . $db->escape($hostmask[1])  . "';";
        $dbres = $db->query($sql);
	if($dbres && $db->num_rows($dbres) > 0) {
            if($row = $db->fetch($dbres)) {
                if($row['value'] != $_GET['hostmask']) {
                    $sql = "INSERT INTO streamersettings (streamer,`key`,value)
                            VALUES (" . $row['streamer'] . ",'hostmask', '" . $db->escape($_GET['hostmask']) . "')
                            ON DUPLICATE KEY UPDATE value = '" . $db->escape($_GET['hostmask']) . "';";
                    $db->execute($sql);
                }
                $sql = "INSERT INTO streamersettings (streamer,`key`,value)
                        VALUES (" . $row['streamer'] . ",'isIRC', 1)
                        ON DUPLICATE KEY UPDATE value = 1;";
                if($db->execute($sql)) {
                    $out['auth']['nick'] = $row['username'];
                    $out['auth']['id'] = $row['streamer'];
                    $out['auth']['status'] = 0;
                    return;
                }
            }
        }
    }
    $out['auth']['status'] = 1;
}

function authPart(&$out) {
    global $db;

    if(isset($_GET['hostmask']) && strlen($_GET['hostmask']) > 0) {
        $hostmask = explode('!', $_GET['hostmask']);
        $sql = "SELECT * FROM streamersettings JOIN streamer using(streamer) WHERE `key` = 'hostmask' AND `value` REGEXP '[A-z0-9]+!" . $db->escape($hostmask[1])  . "';";
        $dbres = $db->query($sql);
        if($dbres && $db->num_rows($dbres) > 0) {
            if($row = $db->fetch($dbres)) {
                $sql = "INSERT INTO streamersettings (streamer,`key`,value)
                        VALUES (" . $row['streamer'] . ",'isIRC', 0)
                        ON DUPLICATE KEY UPDATE value = 0;";
                if($db->execute($sql)) {
                    $out['auth']['nick'] = $row['username'];
                    $out['auth']['id'] = $row['streamer'];
                    $out['auth']['status'] = 0;
                    return;
                }
            }
        }
    }
    $out['auth']['status'] = 1;
}

function authUpdate(&$out) {
    global $db;

    if((isset($_GET['hostmask']) && strlen($_GET['hostmask']) > 0) && (isset($_GET['nick']) && strlen($_GET['nick']) > 0)) {
        $hostmask = explode('!', $_GET['hostmask']);
        $sql = "SELECT * FROM streamersettings JOIN streamer using(streamer) WHERE `key` = 'hostmask' AND `value` REGEXP '[A-z0-9]+!" . $db->escape($hostmask[1])  . "';";
        $dbres = $db->query($sql);
        if($dbres && $db->num_rows($dbres) > 0) {
            if($row = $db->fetch($dbres)) {
                $sql = "INSERT INTO streamersettings (streamer,`key`,value)
                        VALUES (" . $row['streamer'] . ",'hostmask', '" . $db->escape($_GET['nick'] . "!" . $hostmask[1]) . "')
                        ON DUPLICATE KEY UPDATE value = '" . $db->escape($_GET['nick'] . "!" . $hostmask[1]) . "';";
                if($db->execute($sql)) {
                    $out['auth']['nick'] = $row['username'];
                    $out['auth']['id'] = $row['streamer'];
                    $out['auth']['status'] = 0;
                    return;
                }
            }
        }
    }
    $out['auth']['status'] = 1;
}

function isIRC(&$out) {
    global $db;

    if(isset($_GET['djid']) && strlen($_GET['djid']) > 0) {
        $sql = "SELECT * FROM streamersettings JOIN streamer using(streamer) WHERE `key` = 'isIRC' AND value = 1 AND streamer = '" . $db->escape($_GET['djid']) . "';";
        $dbres = $db->query($sql);
        if($dbres && $db->num_rows($dbres) > 0) {
            if($row = $db->fetch($dbres)) {
                $out['auth']['hostmask'] = getHostmask($row['streamer']);
                $out['auth']['nick'] = $row['username'];
                $out['auth']['id'] = $row['streamer'];
                $out['auth']['status'] = 0;
                return;
            }
        }
    }
    $out['auth']['status'] = 1;
}

function areIRC(&$out) {
    global $db;

    $sql = "SELECT * FROM streamersettings JOIN (SELECT streamer FROM streamersettings WHERE `key` = 'isIRC' AND value = 1) as a USING(streamer) WHERE `key` = 'hostmask';";
    $dbres = $db->query($sql);
    $tmp = array();
    if($dbres) {
        while($row = $db->fetch($dbres)) {
            $tmp[] = $row['value'];
        }
    $out['hostmasks'] = $tmp;
    }
}

function getHostmask($djid) {
    global $db;
    if(isset($djid)) {
        $sql = "SELECT * FROM streamersettings JOIN streamer using(streamer) WHERE `key` = 'hostmask' AND streamer = '" . $db->escape($djid) . "';";
        $dbres = $db->query($sql);
        if($dbres && $db->num_rows($dbres) > 0) {
            if($row = $db->fetch($dbres)) {
                return $row['value'];
            }
        }
    }
}

function setIRCCount(&$out) {
    if(isset($_GET['c']) && (is_int((int)$_GET['c']))) {
        if(file_put_contents('../../var/irccount', (int)$_GET['c']));
        $out['status'] = 0;
    }
    else {
        $out['status'] = 1;
    }
}

function getIRCCount() {
    return (int)file_get_contents('../../var/irccount');
}

function rconfig(&$out) {
    global $db;

    if($out['auth']['status'] != 0) {
        throw_error(21, 'auth failed');
        return;
    }
    if(isset($_GET['key']) && strlen($_GET['key']) > 0) {
        switch($_GET['key']) {
            case 'background':
                $key = 'background';
                break;
            case 'description':
                $key = 'defaultshowdescription';
                break;
            case 'name':
                $key = 'defaultshowname';
                break;
            default:
                throw_error(22, 'invalid input');
                return;
        }
    }
    else {
        throw_error(22, 'invalid input');
        return;
    }
    if(isset($_GET['value']) && strlen($_GET['value']) > 0) {
        //update
        $value = $_GET['value'];
        $sql = "INSERT INTO streamersettings (streamer,`key`,value)
                VALUES (" . $out['auth']['id'] . ",'" . $db->escape($key) ."', '" . $db->escape($value) . "')
                ON DUPLICATE KEY UPDATE value = '" . $db->escape($value) ."';";
        if($db->execute($sql)) {
            $out['status'] = 0;
            $out['key'] = $key;
            $out['value'] = $value;
        }
    }
    else {
        //output
        $sql = "SELECT * FROM streamersettings JOIN streamer using(streamer) WHERE `key` = '" . $db->escape($key) ."' AND streamer = '" . $db->escape($out['auth']['id']) . "';";
        $dbres = $db->query($sql);
        if($dbres && $db->num_rows($dbres) > 0) {
            if($row = $db->fetch($dbres)) {
                $out['status'] = 0;
                $out['key'] = $key;
                $out['value'] = $row['value'];
            }
        }
    }
}

function rthread(&$out) {
    global $db;

    if(isset($_GET['show']) && strlen($_GET['show']) > 0) {
        $show = $_GET['show'];
        if(isset($_GET['thread']) && (is_int((int)$_GET['thread']))) {
            //update
            $thread = (int)$_GET['thread'];
            if($out['auth']['status'] != 0) {
                throw_error(21, 'auth failed');
                return;
            }
            if($thread == 0) {
                $sql = "UPDATE shows SET thread = NULL WHERE `show` = '" . $db->escape($show) ."' AND streamer = '" . $db->escape($out['auth']['id']) ."';";
            }
            else {
                $sql = "UPDATE shows SET thread = '" . $db->escape($thread) . "' WHERE `show` = '" . $db->escape($show) ."' AND streamer = '" . $db->escape($out['auth']['id']) ."';";
            }
            if($db->execute($sql)) {
                if($db->getAffectedRows() > 0) {
                    $out['status'] = 0;
                    $out['show'] = $show;
                    $out['thread'] = $thread;
                }
                else {
                    throw_error(21, 'auth failed');
                    return;
                }
            }

        }
        else {
            //output
            $sql = "SELECT thread FROM shows WHERE `show` = '" . $db->escape($show) . "';";
            $dbres = $db->query($sql);
            if($dbres && $db->num_rows($dbres) > 0) {
                if($row = $db->fetch($dbres)) {
                    $out['status'] = 0;
                    $out['show'] = $show;
                    $out['thread'] = $row['thread'];
                }
            }
            else {
                throw_error(22, 'invalid input');
                return;
            }
        }
    }
    else {
        throw_error(22, 'invalid input');
        return;
    }
}

function remindAdd(&$out) {
    global $db;
    
    if((isset($_GET['type']) && strlen($_GET['type']) > 0) && (isset($_GET['id']) && is_int((int)$_GET['id']))) {
        $type = $_GET['type'];
        $id = (int)$_GET['id'];
         
        if($out['auth']['status'] != 0) {
            throw_error(21, 'auth failed');
            return;
        }
        $streamer = $out['auth']['id'];
        
        if($type == 'dj' || $type == 'show') {
            $sql = "SELECT COUNT(*) as c FROM ircEvents WHERE streamer = '" . $db->escape($streamer) . "' and `" . $db->escape($type) . "` = '" . $db->escape($id) . "';";
            $dbres = $db->query($sql);
            if($dbres) {
                $row = $db->fetch($dbres);
                if($row['c'] == 0) {
                    $sql = "INSERT INTO ircEvents (streamer, `" . $db->escape($type) . "`, type) VALUES ('" . $db->escape($streamer) . "','" . $db->escape($id) . "','REMIND');";
                    if($db->execute($sql)) {
                        $out['remindadd']['status'] = 0;
                        return;
                    }
                }
                else {
                    $out['remindadd']['dupe'] = 1;
                }
            }
        }
        else {
            throw_error(22, 'invalid input');
        }
    }
    $out['remindadd']['status'] = 1;
}

function remindGet(&$out) {
    global $db;
    
    if((isset($_GET['showid']) && is_int((int)$_GET['showid'])) && (isset($_GET['djid']) && is_int((int)$_GET['djid']))) {
        $showid = (int)$_GET['showid'];
        $djid = (int)$_GET['djid'];
        
        $sql = "SELECT * FROM ircEvents JOIN streamersettings USING (streamer)
                WHERE (`show` = '" . $db->escape($showid) . "' OR `dj` = '" . $db->escape($djid) . "')
                AND streamersettings.key = 'isIRC' AND streamersettings.value = '1' AND type = 'REMIND';";
        $dbres = $db->query($sql);
        $tmp = array();
        if($dbres) {
            while($row = $db->fetch($dbres)) {
                if(! array_key_exists($row['streamer'], $tmp)) {
                    $sql = "SELECT * FROM streamersettings WHERE streamer = '" . $db->escape($row['streamer']) . "' and `key` = 'hostmask';";
                    $dbres2 = $db->query($sql);
                    if($dbres2) {
                        $row2 = $db->fetch($dbres2);
                        $nick = explode('!', $row2['value']);
                        $nick = $nick[0];
                        $tmp[$row['streamer']] = $nick;
                    }
                }
            }   
        }
        $out['remindGet'] = $tmp;
    }
}

function remindList(&$out) {
    global $db;
    
    if($out['auth']['status'] != 0) {
        throw_error(21, 'auth failed');
        return;
    }
     
    $streamer = $out['auth']['id'];
    $sql = "SELECT * FROM ircEvents WHERE streamer = '" . $db->escape($streamer) . "';";
    $dbres = $db->query($sql);
    $tmp = array();
    if($dbres) {
        while($row = $db->fetch($dbres)) {
            if($row['dj']) {
                $sql = "SELECT username FROM streamer WHERE streamer = '" . $db->escape($row['dj']) ."';";
                $dbres2 = $db->query($sql);
                if($dbres2) {
                    $row2 = $db->fetch($dbres2);
                    array_push($tmp, array('id' => $row['ircevent'], 'value' => $row2['username'], 'type' => 'dj'));
                }
            }
            elseif ($row['show']) {
                $sql = "SELECT COUNT(*) as c FROM shows WHERE `show` = '" . $db->escape($row['show']) . "' AND begin > NOW();";
                $dbres2 = $db->query($sql);
                if($dbres2) {
                    $row2 = $db->fetch($dbres2);
                    if($row2['c'] > 0) {
                        array_push($tmp, array('id' => $row['ircevent'], 'value' => $row['show'], 'type' => 'show'));
                    }
                }
            }
        }
    }
    $out['remindlist'] = $tmp;
}

function remindDelete(&$out) {
    global $db;
    
    
    if(isset($_GET['id']) && is_int((int)$_GET['id'])) {
        if($out['auth']['status'] != 0) {
            throw_error(21, 'auth failed');
            return;
        }

        $streamer = $out['auth']['id'];
        $id = (int)$_GET['id'];
        
        $sql = "DELETE FROM ircEvents WHERE ircevent = '" . $db->escape($id) . "' AND streamer = '" . $db->escape($streamer) ."';";
        if($db->execute($sql)) {
            if($db->getAffectedRows() > 0) {
                $out['reminddelete']['status'] = 0;
                return;
            }
        }
    }
    $out['reminddelete']['status'] = 1;
}

?>
