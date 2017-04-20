<?php
    require_once dirname(__FILE__) . '/log/log.php';


    function url_path_encode($url) {
        $path = parse_url($url, PHP_URL_PATH);
        if (strpos($path,'%') !== false) return $url; //avoid double encoding
        else {
            $encoded_path = array_map('urlencode', explode('/', $path));
            return str_replace('+', '%20', str_replace($path, implode('/', $encoded_path), $url));
        }
    }
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
    function isEmpty($data){
        if (ctype_space($data) || empty($data) ) {
            return true;
        }
        return false;
    }
    function handleForm($filename) {
        log_info(array("Прилетела заявка с сайта", $_POST, $filename), "sitehandler.log");
        try {
            require_once dirname(__FILE__) . '/amoIntegr.php';

            $filename = url_path_encode($filename);

            $ipaddress = getClientIP();
            function ip_details($ip) {
                $json = file_get_contents("http://ipinfo.io/{$ip}/geo");
                $details = json_decode($json, true);
                return $details;
            }
            $details = ip_details($ipaddress);
            $city = $details['city'];
            $utm_source = !isEmpty($_COOKIE["utm_source"]) ? $_COOKIE["utm_source"] : ' ';
            $utm_medium = !isEmpty($_COOKIE["utm_medium"]) ? $_COOKIE["utm_medium"] : ' ';
            $utm_campaign = !isEmpty($_COOKIE["utm_campaign"]) ? $_COOKIE["utm_campaign"] : ' ';
            $utm_term = !isEmpty($_COOKIE["utm_term"]) ? $_COOKIE["utm_term"] : ' ';
            $utm_content = !isEmpty($_COOKIE["utm_content"]) ? $_COOKIE["utm_content"] : ' ';
            $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $referer = !isEmpty($_COOKIE["ref"]) ? $_COOKIE["ref"] : ' ';
            $gautm = !isEmpty($_COOKIE["snmga"]) ? $_COOKIE["snmga"] : ' ';
            $seminar = $_POST['seminar'];

            $contactData = array();
            $name = "";
            if (!isEmpty($_POST['name'])) { $name = $contactData['NAME'] = $_POST['name']; }
                else { $name = $contactData['NAME'] = "Имя не указано"; }
            if (!isEmpty($_POST['email'])) { $contactData['EMAIL'] = array('OTHER' => $_POST['email']); }
            if (!isEmpty($_POST['tel'])) { $contactData['PHONE'] = array('WORK' => $_POST['tel']); }
            if (!isEmpty($_POST['org'])) { $contactData['Организация'] = $_POST['org']; }
            if (!isEmpty($_POST['card'])) { $contactData['Дисконтная карта'] = $_POST['card']; }
            if (!isEmpty($_POST['card-number'])) { $contactData['Номер карты'] = $_POST['card-number']; }
            if (!isEmpty($city)) { $contactData['Город'] = $city; }

            $contactData['Источник добавления'] = 'Форма на сайте';

            if (!isset($_POST['promo-code']))
            {
                $_POST['promo-code'] = 'Промокод отсутствует';
            }

            if (isEmpty($_POST['promo-code']))
            {
                $_POST['promo-code'] = 'Промокод отсутствует';
            }


            try {
            	$runAmo = new AmoAPI('icgr', '5179907@mail.ru', '1036ac2a6f0b3ca803e3dd2ab7e549ea');
                $runAmo->setDepartmentID(101569);
                $runAmo->setMainPipeline(525508, 14268634);
                $runAmo->setRepPipeline(525508, 14268634);

                $lead_id = $runAmo->processData(
                	array(
                        'NAME' => $name,
                        'utm_source' => $utm_source,
                        'utm_term' => $utm_term,
                        'utm_medium' => $utm_medium,
                		'utm_campaign' => $utm_campaign,
                        'utm_content' => $utm_content,
                        'url' => $url,
                        'referer' => $referer,
                        'GA UTM' => $gautm,
                        'Форма' => "Заявка на семинар",
                        'Семинар' => $seminar,
                        'Промо код' => $_POST['promo-code'],
                    ),
                	$contactData,
                	"Заявка с сайта", 1347646);

                if (!isEmpty($_POST['body'])) {
                    $note_request = array(
                        "request" => array(
                            "notes" => array(
                                "add" =>  array(
                                    array(
                                        "element_id" =>  $lead_id,
                                        "element_type" =>  2,
                                        "note_type" =>  4,
                                        "text" => "Сообщение из формы обратной связи: \n\n".$_POST['body'],
                                    )
                                )
                            )
                        )
                    );
                    $runAmo->request("POST", "v2/json/notes/set", $note_request);
                }
                if (!isEmpty($filename)) {
                    $note_request = array(
                        "request" => array(
                            "notes" => array(
                                "add" =>  array(
                                    array(
                                        "element_id" =>  $lead_id,
                                        "element_type" =>  2,
                                        "note_type" =>  4,
                                        "text" => "Файл из формы обратной связи $filename",
                                    )
                                )
                            )
                        )
                    );
                    $runAmo->request("POST", "v2/json/notes/set", $note_request);
                }
                log_info(array("Заявка с сайта обработана", $_POST, $filename), "sitehandler.log");
            } catch(AmoException $e) {
                log_error($e, "sitehandler.log", "sitehandler.log");
                log_info("Ошибка AmoException при отправке данных", "sitehandler.log");
            } catch(Exception $e) {
                log_error($e, "sitehandler.log", "sitehandler.log");
                log_info("Неизвестная ошибка при отправке данных", "sitehandler.log");
            }
        } catch(Exception $e) {
            log_error($e, "sitehandler.log");
            log_info("Неизвестная ошибка при отправке данных (функция)", "sitehandler.log");
        }
    }

?>
