<?php 
/**
 * php cron.php -s site_admin -e lhctelegram -c cron/update_structure
 * */

$contentData = file_get_contents('extension/lhctelegram/doc/structure.json');

$tables = erLhcoreClassUpdate::getTablesStatus(json_decode($contentData,true));

$queries = array();
foreach ($tables as $table => $status) {
    $queries = array_merge($queries,$status['queries']);
}

if (empty($queries)){
    echo "No queries to execute found\n";
} else {
    echo "The following queries will be executed\nYou have 10 seconds to stop executing these quries\n";
    echo implode("\n", $queries)."\n";
    sleep(10);
    
    erLhcoreClassUpdate::doTablesUpdate(json_decode($contentData,true));
    echo "Tables were updated";
}


?>