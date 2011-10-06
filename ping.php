<?php
    define('PORT_CHECK_TIMEOUT', 3);
    
    function check_port($ip, $port)
    {
        $sock = @fsockopen($ip, $port, $errno, $errstr, PORT_CHECK_TIMEOUT);
        if (!$sock)
            return false;
        fclose($sock);
        return true;
    }

    // make sure everything required is actually set.
    if (!isset( $_REQUEST['port'] )) exit();
    if (!isset( $_REQUEST['name'] )) exit();
    if (!isset( $_REQUEST['state'] )) exit();
    if (!isset( $_REQUEST['map'] )) exit();
    if (!isset( $_REQUEST['mods'] )) exit();
    if (!isset( $_REQUEST['players'] )) exit();

    header( 'Content-type: text/plain' );
    try 
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    
        $db = new PDO('sqlite:db/openra.db');

        $select = $db->prepare("SELECT COUNT(address) FROM servers WHERE address LIKE '".$ip.":%'");

        $select->execute();
        $count = (int)$select->fetchColumn();
        if ( $count > 20 )
        {
            $stale = 60 * 5;
            //  If someone really spams master server using GET requests, he is restricted by 20 records per 300 seconds
            //  At the same time: remove old entries (why to keep them?)
            $delete = $db->prepare('DELETE FROM servers WHERE (' . time() . ' - ts > ' . $stale . ')');
            $delete->execute();
            exit();
        }
        
        $port = $_REQUEST['port'];
        $addr = $ip . ':' . $port;
        $name = urldecode( $_REQUEST['name'] );
        
        if (isset( $_REQUEST['new']))
        {
            $connectable = check_port($ip, $port);
            if (!$connectable)
                $name = '[down]' . $name;
        }
        
        $insert = $db->prepare('INSERT OR REPLACE INTO servers 
            (name, address, players, state, ts, map, mods) 
            VALUES (:name, :addr, :players, :state, :time, :map, :mods)');
        $insert->bindValue(':name', $name, PDO::PARAM_STR);
        $insert->bindValue(':addr', $addr, PDO::PARAM_STR);
        $insert->bindValue(':players', $_REQUEST['players'], PDO::PARAM_INT);
        $insert->bindValue(':state', $_REQUEST['state'], PDO::PARAM_INT);
        $insert->bindValue(':time', time(), PDO::PARAM_INT);
        $insert->bindValue(':map', $_REQUEST['map'], PDO::PARAM_STR);
        $insert->bindValue(':mods', $_REQUEST['mods'], PDO::PARAM_STR);
        
        $insert->execute();

        if (isset( $_REQUEST['new']))
        {
            $select = $db->prepare('SELECT id FROM servers WHERE address = :addr');
            $select->bindValue(':addr', $addr, PDO::PARAM_STR);

            $select->execute();

            echo (int)$select->fetchColumn();
    
            $games = file_get_contents("games.txt");
            file_put_contents("games.txt", $games + 1);
        }

        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
