<?php

if (isset($_REQUEST['id']))
{
    try
    {
        $db = new PDO('sqlite:db/openra.db');
    
        $insert = $db->prepare("INSERT OR REPLACE INTO sysinfo ('system_id','updated','platform','os','x64','runtime','gl','windowsize','windowscale','lang','version','mod','modversion','sysinfoversion')
            VALUES (:system_id, :updated, :platform, :os, :x64, :runtime, :gl, :windowsize, :windowscale, :lang, :version, :mod, :modversion, :sysinfoversion)"
        );
    
        $insert->bindValue(':system_id', $_REQUEST['id'], PDO::PARAM_STR);
        $insert->bindValue(':updated', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $insert->bindValue(':platform', $_REQUEST['platform'], PDO::PARAM_STR);
        $insert->bindValue(':os', $_REQUEST['os'], PDO::PARAM_STR);
        $insert->bindValue(':x64', ($_REQUEST['x64'] == 'true' ? 1 : 0), PDO::PARAM_BOOL);
        $insert->bindValue(':runtime', $_REQUEST['runtime'], PDO::PARAM_STR);
        $insert->bindValue(':gl', $_REQUEST['gl'], PDO::PARAM_STR);
        $insert->bindValue(':windowsize', $_REQUEST['windowsize'], PDO::PARAM_STR);
        $insert->bindValue(':windowscale', $_REQUEST['windowscale'], PDO::PARAM_STR);
        $insert->bindValue(':lang', $_REQUEST['lang'], PDO::PARAM_STR);
        $insert->bindValue(':version', $_REQUEST['version'], PDO::PARAM_STR);
        $insert->bindValue(':mod', $_REQUEST['mod'], PDO::PARAM_STR);
        $insert->bindValue(':modversion', $_REQUEST['modversion'], PDO::PARAM_STR);
        $insert->bindValue(':sysinfoversion', $_REQUEST['sysinfoversion'], PDO::PARAM_INT);
    
        $insert->execute();
    }
    catch (PDOException $e)
    {
        // Eat the exception
    }
}

header('Location: http://www.openra.net/gamenews');

?>
