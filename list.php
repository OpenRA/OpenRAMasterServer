<?php
    header( 'Content-type: text/plain' );

    try
    {
        $db = new PDO('sqlite:db/openra.db');
        $stale = 60 * 5;
        $result = $db->query('SELECT * FROM servers WHERE (' . time() . ' - ts < ' . $stale . ') ORDER BY name');
	$n = 0;
        foreach ( $result as $row )
        {
            echo "Game@" . $n++ . ":\n";
            echo "\tId: " . $row['id'] . "\n";
            echo "\tName: " . $row['name'] . "\n";
            echo "\tAddress: " . $row['address'] . "\n";
            echo "\tState: " . $row['state'] . "\n";
            echo "\tPlayers: " . $row['players'] . "\n";
            echo "\tMap: " . $row['map'] . "\n";
            echo "\tMods: " . $row['mods'] . "\n";
            echo "\tTTL: " . ($stale - (time() - $row['ts'])) . "\n";
	    if (isset( $_REQUEST['location'] ))
	    {
		$ip_addr = split(":", $row['address']);
		array_pop($ip_addr);
		$ip_addr = implode(":", $ip_addr);
		$content = file_get_contents('http://api.hostip.info/country.php?ip=' . $ip_addr);
		if( $content !== FALSE )
		    echo "\tLocation: " . $content . "\n";
	    }
        }
        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
