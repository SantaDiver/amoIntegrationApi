<?php

require_once dirname(__FILE__)."/log/log.php";
require_once dirname(__FILE__).'/amoIntegr.php';

$data = json_decode(file_get_contents('php://input'), true);
$results = print_r($data, true);

function getRespUserByName($name) {
    $runAmo = new AmoAPI('icgr', '5179907@mail.ru', '1036ac2a6f0b3ca803e3dd2ab7e549ea');
    $users = $runAmo->current()->users;

    foreach($users as $user) {
        if ($user->name == $name) {
            return $user->id;
        }
    }
    return -1;
}

function isEmpty($data){
    if (ctype_space($data) || empty($data) ) {
        return true;
    }
    return false;
}
try {
    log_info($data);
    if ($data['event_name'] == 'chat_finished' || $data['event_name'] == 'chat_accepted' ||
        $data['event_name'] == 'offline_message' || $data['event_name'] == 'chat_updated')
    {
        if (isEmpty($data['visitor']['email']) && isEmpty($data['visitor']['phone'])) {
            $response = array(
                'result' => 'ok',
                'error_message' => 'email or phone is required to find or create lead'
            );
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        $string = isset($data['session']['utm']) ? $data['session']['utm'] : ' ';
        $string = str_replace("|", "&", $string);
        parse_str($string, $utm);

        $leadData['NAME'] = 'Заявка из JivoSite '.$data['visitor']['name'];
        if (!isEmpty($utm["source"]))       { $leadData['utm_source']   = $utm["utm_source"]; }
        if (!isEmpty($utm["term"]))         { $leadData['utm_term']     = $utm["term"]; }
        if (!isEmpty($utm["medium"]))       { $leadData['utm_medium']   = $utm["medium"]; }
        if (!isEmpty($utm["campaign"]))     { $leadData['utm_campaign'] = $utm["campaign"]; }
        if (!isEmpty($utm["content"]))      { $leadData['utm_content']  = $utm["content"]; }
        if (!isEmpty($data['page']['url'])) { $leadData['url'] = $data['page']['url']; }

        $contactData['Источник добавления'] = 'Jivosite';
        if (!isEmpty($data['visitor']['name']))  { $contactData['NAME']  = $data['visitor']['name']; }
            else { $contactData['NAME'] = "Клиент из jivosite"; }
        if (!isEmpty($data['visitor']['email']))         { $contactData['EMAIL'] = array('OTHER' => $data['visitor']['email']); }
        if (!isEmpty($data['visitor']['phone']))         { $contactData['PHONE'] = array('WORK'  => $data['visitor']['phone']); }
        if (!isEmpty($data['session']['geoip']['city'])) { $contactData['Город'] = $data['session']['geoip']['city']; }

        $agent = (!isEmpty($data['agent']['name'])) ? $data['agent']['name'] : $data['agents'][0]['name'];

        $respUser = -1;
        $agentWasFound = true;
        if ($data['event_name'] == 'offline_message'){
             $respUser = -1;
        }
        else {
            $respUser = getRespUserByName($agent);
            if ($respUser == -1) {
                $respUser = getRespUserByName("Виктория Щербина"); // ВППП
                $agentWasFound = false;
            }
        }


        $runAmo = new AmoAPI('icgr', '5179907@mail.ru', '1036ac2a6f0b3ca803e3dd2ab7e549ea');
        $runAmo->setDepartmentID(101569);
        $runAmo->setMainPipeline(525508, 14268634);
        $runAmo->setRepPipeline(525508, 14268634);

        if ($data['event_name'] == 'offline_message') {
            $runAmo->setGenerateTasksForRec(true);
        }
        else {
            $runAmo->setGenerateTasksForRec(false);
        }

        $leadId = $runAmo->processData(
            $leadData, $contactData, "Заяка из jivosite", $respUser);

        $message = '';
        if ($data['event_name'] == 'offline_message')
        {
            $message = 'Сообщение от клиента: '.$data['message'];
        }
        if ($data['event_name'] == 'chat_finished')
        {
            foreach ($data['chat']['messages'] as $m)
            {
                if ($m['type'] == 'visitor') $message = $message.'Клиент: ';
                else $message = $message.$agent.": ";
                $message = $message.$m['message'];
                $message = $message.PHP_EOL;
            }
        }

        if ($data['event_name'] != 'chat_accepted' &&
            $data['event_name'] != 'chat_updated')
        {
            $runAmo->addResourceToLeadById($leadId, $message);
        }

        if (!$agentWasFound) {
            $runAmo->addResourceToLeadById($leadId, 'Обращение обработал(а) '.$agent.
                 '. Пользователь не был найден в интеграции. Сделка создана на Викторию Щербину.');
        }

        $response = array(
            'result' => 'ok',
            'enable_assign' => true,
            'crm_link' => 'https://icgr.amocrm.ru/leads/detail/'.$leadId
        );
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    else
    {
        $response = array(
            'result' => 'ok',
        );
        header('Content-Type: application/json');
        echo json_encode($response);
    }


} catch(AmoException $e) {
    echo "Ошибка отправки данных, повторите пожалуйста отправку позже";
} catch(Exception $e) {
    echo "Ошибка отправки данных, повторите пожалуйста отправку позже";
}
?>
