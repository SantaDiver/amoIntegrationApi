<?php
require_once dirname(__FILE__) . '/amoIntegr.php';
try {
    $runAmo = new AmoAPI('snmsoft', 'nik7101@yandex.ru', 'dbf1a2144301ac365925d1b1f0d3d10d');

    $runAmo->processData(array('NAME' => 'Test integr', 'что-то' => '123', 'GA UTM' => 'utmutmtu'), 
        array('EMAIL' => array('PRIV' => 'bmc'), 'PHONE' => array('WORK' => '914'), 'NAME' => 'Andrew'), "Заявка с сайта");

} catch(AmoException $e) {
	echo 'AMO_EXCEPTION: '.$e->getMessage().PHP_EOL;
} catch(Exception $e) {
	echo 'EXCEPTION: '.$e->getMessage().PHP_EOL;
}

// $results = print_r($this->leadFieldCache, true);
// file_put_contents(dirname(__FILE__).'/past.txt', $results);
?>

