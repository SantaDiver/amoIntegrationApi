<?php
//Отсюда начинается код по отправке данных в amo
require_once dirname(__FILE__) . '/amoIntegr.php';
function getClientIP(){
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

$ipaddress = getClientIP();

function ip_details($ip) {
    $json = file_get_contents("http://ipinfo.io/{$ip}/geo");
    $details = json_decode($json, true);
    return $details;
}

$details = ip_details($ipaddress);
$city = $details['city'];

$utm_source = isset($_COOKIE["utm_source"]) ? $_COOKIE["utm_source"] : ' ';
$utm_medium = isset($_COOKIE["utm_medium"]) ? $_COOKIE["utm_medium"] : ' ';
$utm_campaign = isset($_COOKIE["utm_campaign"]) ? $_COOKIE["utm_campaign"] : ' ';
$utm_term = isset($_COOKIE["utm_term"]) ? $_COOKIE["utm_term"] : ' ';
$utm_content = isset($_COOKIE["utm_content"]) ? $_COOKIE["utm_content"] : ' ';

$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$referer = isset($_COOKIE["ref"]) ? $_COOKIE["ref"] : ' ';

$name = (strlen(trim($_POST['name'])) != 0) ? $_POST['name'] : 'Имя не указано';
$phone = $_POST['phone'];
$email = $_POST['email']; 
if($_POST['form_name']=='get_cost'){
	$comment = $_POST['position'];
} 
elseif($_POST['form_name']=='get_call')
{
	$comment = ' Тема разговора: '.$_POST['theme'].'Комментарий:  '.$_POST['comment'];
}
$form_send_from = ($_POST['form_name']=='get_cost') ? "Узнать стоимость" : "Заказ звонка";

try {
	$runAmo = new AmoAPI('amopresto', 'tim@presto-ps.com.ua', 'c0f0a68bfdc7dfe1c4412b0551637c6e');
    $runAmo->processData(
    	array('NAME' => $name, 'utm_source' => $utm_source, 'utm_term' => $utm_term, 'utm_medium' => $utm_medium, 
    		'utm_campaign' => $utm_campaign, 'utm_content' => $utm_content, 'url' => $url, 'referer' => $referer, 'Форма' => $form_send_from), 
    	array('EMAIL' => array('OTHER' => $email), 'PHONE' => array('WORK' => $phone), 'NAME' => $name, 'Город по IP' => $city), 
    	"Заявка с сайта");

    header("Location: http://presto-ps.com.ua/thanks/");
} catch(AmoException $e) {
    echo "Ошибка отправки данных, повторите пожалуйста отправку позже";
} catch(Exception $e) {
    echo "Ошибка отправки данных, повторите пожалуйста отправку позже";
}

?>