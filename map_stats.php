<?php
    date_default_timezone_set('UTC');

    if (!isset($_REQUEST['hash']))
        die('hash argument is required.');

    include('./config.php');

    header('Content-Type: application/javascript');
    header('Access-Control-Allow-Origin: *');

    try
    {
        $db = new PDO(DATABASE);
        $query = $db->prepare('SELECT played_counter FROM map_stats WHERE map = :map');
        $query->bindValue(':map', $_REQUEST['hash'], PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll();
        $json_result_array = array();
        foreach ($result as $row)
        {
            $json_result_array['map'] = $_REQUEST['hash'];
            $json_result_array['played'] = $row['played_counter'];
        }
        print(json_encode($json_result_array));
        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }

?>
