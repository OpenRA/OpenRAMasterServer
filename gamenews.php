<?php

function arg($var, $default = '')
{
	return isset($_REQUEST[$var]) ? $_REQUEST[$var] : $default;
}

if (isset($_REQUEST['id']))
{
    try
    {
        $db = new PDO('sqlite:db/openra.db');
    
        $insert = $db->prepare("INSERT OR REPLACE INTO sysinfo ('system_id','updated','platform','os','x64','runtime','gl','windowsize','windowscale','lang','version','mod','modversion','sysinfoversion')
            VALUES (:system_id, :updated, :platform, :os, :x64, :runtime, :gl, :windowsize, :windowscale, :lang, :version, :mod, :modversion, :sysinfoversion)"
        );

        // Anonymous user GUID. Added in protocol v1.
        $insert->bindValue(':system_id', arg('id'), PDO::PARAM_STR);

        // Time of the last ping (e.g. 2017-02-11 22:00:00). Added in protocol v1.
        $insert->bindValue(':updated', date('Y-m-d H:i:s'), PDO::PARAM_STR);

        // OS Type (Windows/OSX/Linux). Added in protocol v1.
        $insert->bindValue(':platform', arg('platform'), PDO::PARAM_STR);

        // OS Version string (e.g. Unix 4.4.0.31). Added in protocol v1.
        $insert->bindValue(':os', arg('os'), PDO::PARAM_STR);

        // OS is 64 bit. Added in protocol v2.
        $insert->bindValue(':x64', (arg('x64', 'true') == 'true' ? 1 : 0), PDO::PARAM_BOOL);

        // .NET runtime version (e.g. Mono 4.2.1). Added in protocol v1.
        $insert->bindValue(':runtime', arg('runtime'), PDO::PARAM_STR);

        // OpenGL driver version (e.g. 3.0 Mesa 2.0.6). Added in protocol v2.
        $insert->bindValue(':gl', arg('gl'), PDO::PARAM_STR);

        // OpenRA window size (e.g. 1024x768). Added in protocol v2.
        $insert->bindValue(':windowsize', arg('windowsize', '0x0'), PDO::PARAM_STR);

        // OpenRA window scale (> 1 for HiDPI). Added in protocol v2.
        $insert->bindValue(':windowscale', arg('windowscale', '1.00'), PDO::PARAM_STR);

        // Default system language (e.g. en). Added in protocol v1.
        $insert->bindValue(':lang', arg('lang'), PDO::PARAM_STR);

        // OpenRA engine version (e.g. release-20161019). Added in protocol v1.
        $insert->bindValue(':version', arg('version'), PDO::PARAM_STR);

        // Currently active mod (e.g. ra). Added in protocol v1.
        $insert->bindValue(':mod', arg('mod'), PDO::PARAM_STR);

        // Version of currently active mod (e.g. release-20161019). Added in protocol v1.
        $insert->bindValue(':modversion', arg('modversion'), PDO::PARAM_STR);

        // Protocol version (useful for easily filtering bogus columns). Added in protocol v2.
        $insert->bindValue(':sysinfoversion', arg('sysinfoversion', '1'), PDO::PARAM_INT);

        $insert->execute();
    }
    catch (PDOException $e)
    {
        // Eat the exception
    }
}

header('Location: http://www.openra.net/gamenews');

?>
