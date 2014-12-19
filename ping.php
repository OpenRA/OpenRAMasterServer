<?php
    date_default_timezone_set('UTC');

    // === configuration ===

    define('DEBUG', 0);
    define('PORT_CHECK_TIMEOUT', 3);
    ini_set('display_errors', DEBUG);
    error_reporting(DEBUG ? E_ALL : 0);

    header('Content-type: text/plain');

    // === functions ===

    function check_port($ip, $port)
    {
        return @fsockopen($ip, $port, $errno, $errstr, PORT_CHECK_TIMEOUT);
    }

    function updatedbinfo($gameinfo) {
        global $db;

        $fields = array_keys($gameinfo);
        $query  = $db->prepare('INSERT OR ABORT INTO `servers` ('.implode(', ', $fields).')
                                VALUES (:'.implode(', :', $fields).')
        ');
        $result = $query->execute($gameinfo);
        if (!$result) {
            $query  = $db->prepare('UPDATE OR FAIL `servers` SET '.implode('=?, ', $fields).'=? WHERE address = :address');
            $result = $query->execute(array_merge(array_values($gameinfo), array(':address' => $gameinfo['address'])));
        }

        if (DEBUG) $query->debugDumpParams();

        if (!isset($_REQUEST['clients']) || $_REQUEST['clients'] == "")
            return true;

        $query = $db->prepare('DELETE FROM clients WHERE address = :addr');
        $query->bindValue(':addr', $gameinfo['address'], PDO::PARAM_STR);
        $query->execute();

        $clients = explode(",", $_REQUEST['clients']);
        foreach ($clients as $client)
        {
            $query = $db->prepare("INSERT INTO clients ('address','client','ts')
                                VALUES (:addr, :client, :ts)");
            $query->bindValue(':addr', $gameinfo['address'], PDO::PARAM_STR);
            $query->bindValue(':client', base64_decode($client), PDO::PARAM_STR);
            $query->bindValue(':ts', time(), PDO::PARAM_INT);
            $query->execute();
        }
        return true;
    }

    // === body ===

    // make sure everything required is actually set.
    foreach(array('port', 'name', 'state', 'map', 'mods', 'players') as $key)
        if(!isset($_REQUEST[$key]))
            die('field "'.$key.'" is not set');

    try
    {
        $ip   = $_SERVER['REMOTE_ADDR'];
        $port = $_REQUEST['port'];
        $addr = $ip.':'.$port;

        // don't get spammed so easily
        if (!check_port($ip, $port))
            die('[001] game server "'.$addr.'" does not respond');

        $name = urldecode($_REQUEST['name']);
        $started = '';

        $version_arr = explode('@', $_REQUEST['mods']);
        $game_mod = array_shift($version_arr);
        $version = implode('@', $version_arr);

        $db = new PDO('sqlite:db/openra.db');

        if ($_REQUEST['state'] == 2)
        {
            $query = $db->prepare('SELECT * FROM servers WHERE address = :addr');
            $query->bindValue(':addr', $addr, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetchAll();
            foreach ($result as $row)
            {
                if ($row['state'] == 1)
                {
                    $started = date('Y-m-d H:i:s');

                    $insert = $db->prepare("INSERT INTO started ('game_id','name','address','map','game_mod','version','protected','started','players','spectators','bots')
                            VALUES (:game_id, :name, :address, :map, :game_mod, :version, :protected, :started, :players, :spectators, :bots)"
                    );
                    $insert->bindValue(':game_id', $row['id'], PDO::PARAM_INT);
                    $insert->bindValue(':name', htmlspecialchars($_REQUEST['name']), PDO::PARAM_STR);
                    $insert->bindValue(':address', $addr, PDO::PARAM_STR);
                    $insert->bindVAlue(':map', $_REQUEST['map'], PDO::PARAM_STR);
                    $insert->bindValue(':game_mod', htmlspecialchars($game_mod), PDO::PARAM_STR);
                    $insert->bindValue(':version', htmlspecialchars($version), PDO::PARAM_STR);
                    $insert->bindValue(':protected', isset($_REQUEST['protected']) ? $_REQUEST['protected'] : 0, PDO::PARAM_STR);
                    $insert->bindValue(':started', $started, PDO::PARAM_STR);
                    $insert->bindValue(':players', htmlspecialchars($_REQUEST['players']), PDO::PARAM_INT);
                    $insert->bindValue(':spectators', isset($_REQUEST['spectators']) ? htmlspecialchars($_REQUEST['spectators']) : 0, PDO::PARAM_INT);
                    $insert->bindValue(':bots', isset($_REQUEST['bots']) ? htmlspecialchars($_REQUEST['bots']) : 0, PDO::PARAM_INT);
                    $insert->execute();
                    if (DEBUG) $insert->debugDumpParams();
                }
                else
                    $started = $row['started'];
                break;
            }
        }

        if ($_REQUEST['state'] == 3)
        {
            $query = $db->prepare('SELECT id,started FROM servers WHERE address = :addr');
            $query->bindValue(':addr', $addr, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetchAll();
            foreach ($result as $row)
            {
                if ($row['started'] == '')
                    break;
                $insert = $db->prepare("INSERT INTO finished ('game_id','name','address','map','game_mod','version','protected','started','finished')
                            VALUES (:game_id, :name, :address, :map, :game_mod, :version, :protected, :started, :finished)"
                );
                $insert->bindValue(':game_id', $row['id'], PDO::PARAM_INT);
                $insert->bindValue(':name', htmlspecialchars($_REQUEST['name']), PDO::PARAM_STR);
                $insert->bindValue(':address', $addr, PDO::PARAM_STR);
                $insert->bindVAlue(':map', $_REQUEST['map'], PDO::PARAM_STR);
                $insert->bindValue(':game_mod', $game_mod, PDO::PARAM_STR);
                $insert->bindValue(':version', $version, PDO::PARAM_STR);
                $insert->bindValue(':protected', isset($_REQUEST['protected']) ? $_REQUEST['protected'] : 0, PDO::PARAM_STR);
                $insert->bindValue(':started', $row['started'], PDO::PARAM_STR);
                $insert->bindValue(':finished', date('Y-m-d H:i:s'), PDO::PARAM_STR);
                $insert->execute();
                if (DEBUG) $insert->debugDumpParams();

                $played_counter = 1;
                $query = $db->prepare('SELECT played_counter FROM map_stats WHERE map = :map');
                $query->bindValue(':map', $_REQUEST['map'], PDO::PARAM_STR);
                $query->execute();
                $result = $query->fetchAll();
                if ($result)
                {
                    foreach ($result as $row)
                    {
                        $played_counter = $row['played_counter'] + 1;
                        break;
                    }
                    $insert = $db->prepare('UPDATE map_stats SET played_counter = :played_counter, last_change = :last_change WHERE map = :map');
                }
                else
                {
                    $insert = $db->prepare("INSERT INTO map_stats ('map','played_counter','last_change')
                            VALUES (:map, :played_counter, :last_change)"
                    );
                }
                $insert->bindValue(':map', $_REQUEST['map'], PDO::PARAM_STR);
                $insert->bindValue(':played_counter', $played_counter, PDO::PARAM_INT);
                $insert->bindValue(':last_change', date('Y-m-d H:i:s'), PDO::PARAM_STR);
                $insert->execute();
                if (DEBUG) $insert->debugDumpParams();
                break;
            }
            $remove = $db->prepare('DELETE FROM `servers` WHERE address = :addr');
            $remove->bindValue(':addr', $addr, PDO::PARAM_STR);
            $remove->execute();
            $db->query('DELETE FROM servers WHERE (' . time() . ' - ts > 300)');

            if (isset($_REQUEST['clients']))
            {
                $remove = $db->prepare('DELETE FROM clients WHERE address = :addr OR (' . time() . ' - ts > 300)');
                $remove->bindValue(':addr', $addr, PDO::PARAM_STR);
                $remove->execute();
            }
            unset($db);
            exit;
        }

        if (isset($_REQUEST['new']))
        {
            $games = file_get_contents("games.txt");
            file_put_contents("games.txt", $games + 1);
        }

        updatedbinfo(
            array(
                'name'      => htmlspecialchars($name),
                'address'   => $addr,
                'players'   => htmlspecialchars($_REQUEST['players']),
                'state'     => $_REQUEST['state'],
                'ts'        => time(),
                'map'       => $_REQUEST['map'], 
                'mods'      => htmlspecialchars($_REQUEST['mods']),
                'bots'      => isset($_REQUEST['bots']) ? htmlspecialchars($_REQUEST['bots']) : 0,
                'spectators'=> isset($_REQUEST['spectators']) ? htmlspecialchars($_REQUEST['spectators']) : 0,
                'maxplayers'=> isset($_REQUEST['maxplayers']) ? htmlspecialchars($_REQUEST['maxplayers']) : 0,
                'protected' => isset($_REQUEST['protected']) ? $_REQUEST['protected'] : 0,
                'started'   => $started,
            )
        );
        unset($db);
    }
    catch (PDOException $e)
    {
        die($e->getMessage());
    }

?>
