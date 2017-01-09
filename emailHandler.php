<?php

require_once dirname(__FILE__) . '/amoIntegr.php';
try {
    $runAmo = new AmoAPI('amopresto', 'tim@presto-ps.com.ua', 'c0f0a68bfdc7dfe1c4412b0551637c6e');
    $sender = explode("<", $_POST['from']);
    $name = $sender[0];
    $email = trim($sender[1], ">");
    
    $runAmo->setGenerateTasksForRec(false);
    $runAmo->processData(array('NAME' => 'E-mail от клиента '.$name), array('EMAIL' => array('OTHER' => $email), 'NAME' => $name), "Заяка из email", 1018116);

} catch(AmoException $e) {
	echo 'AMO_EXCEPTION: '.$e->getMessage().PHP_EOL;
} catch(Exception $e) {
	echo 'EXCEPTION: '.$e->getMessage().PHP_EOL;
}


// $res = print_r($_POST, true);
// file_put_contents(dirname(__FILE__).'/past.txt', $res);

?>