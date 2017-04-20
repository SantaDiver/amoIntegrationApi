<?php

// print_r($_GET);
if (!isset($_GET['uid'])) exit();

require_once dirname(__FILE__) . '/amoIntegr.php';
try {

    $runAmo = new AmoAPI('icgr', '5179907@mail.ru', '1036ac2a6f0b3ca803e3dd2ab7e549ea');
    $sender = explode("<", $_POST['from']);
    $name = $sender[0];
    $email = trim($sender[1], ">");
    
    if (ctype_space($email) || empty($email) || $email == 'support@amocrm.ru' || 
        $name == 'support@amocrm.ru' || strpos($email, '@jivosite.com') !== false
        || strpos($email, '@bisnescafe.ru') !== false)
    {
    	exit();
    }

    if (strpos($email, '@icgr.ru') !== false || strpos($email, '@amocrm.ru') !== false || 
        $email == 'icgr@mail.ru' || $email == '5179907@mail.ru' || $email == 'nay82@mail.ru')
    {
        exit();
    }

    if (ctype_space($name) || empty($name))
    {
    	$name = $email;
    }

    $id = $_GET['uid'];//1018116

    if (isset($_GET['mainp']) && isset($_GET['mains']));
        $runAmo->setMainPipeline($_GET['mainp'], $_GET['mains']);
    if (isset($_GET['repp']) && isset($_GET['reps']))
        $runAmo->setRepPipeline($_GET['repp'], $_GET['reps']);
    if (isset($_GET['did']))
        $runAmo->setDepartmentID($_GET['did']);
    
    $runAmo->setGenerateTasksForRec(false);
    
    $runAmo->processData(
        array('NAME' => 'E-mail от клиента '.$name), 
        array('EMAIL' => array('OTHER' => $email), 'NAME' => $name, 'Источник добавления' => 'e-mail'), 
        "Заяка из email", 
        $id
    );

} catch(AmoException $e) {
	echo 'AMO_EXCEPTION: '.$e->getMessage().PHP_EOL;
} catch(Exception $e) {
	echo 'EXCEPTION: '.$e->getMessage().PHP_EOL;
}


// $res = print_r($_POST, true);
// file_put_contents(dirname(__FILE__).'/past.txt', $res);

?>