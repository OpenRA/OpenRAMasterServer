<?php
    header( 'Content-type: text/plain' );
    try
    {
        $db = new PDO('sqlite:db/openra.db');
        echo "Connection to DB established.\n";
        $schema = 'CREATE TABLE IF NOT EXISTS servers (id INTEGER PRIMARY KEY AUTOINCREMENT, name varchar(255), 
            address varchar(255) UNIQUE, players integer, state integer, ts integer, map varchar(255), mods varchar(255))';
        $db->query($schema);
        echo "Connection to DB closed.\n";
        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
