<?php
require_once dirname(__FILE__) . '/amoIntegr.php';
$runAmo = new AmoAPI('icgr', '5179907@mail.ru', '1036ac2a6f0b3ca803e3dd2ab7e549ea');

parse_str(file_get_contents("php://input"), $data);             
$data = json_decode(json_encode($data));

$number = $data->caller_number;

$response = $runAmo->request('GET', 'v2/json/contacts/list', 
    array('query' => substr($number, -7), 'limit_rows' => 1));

$numberToTransfer = $_GET['default'];
if(!empty($response->contacts[0])) 
{
    $userId = $response->contacts[0]->responsible_user_id;
    $accountData = $runAmo->current();
    foreach($accountData->users as $user)
    {
        if ($user->id == $userId)
        {
            $numberToTransfer = $user->phone_number;
        }
    }
    
    if ($data->caller_name == "74993504654")
    {
        $mainp = 504604;
        $mains = 14093680;
        $repp = 504622;
        $reps = 14107147;
    }
    elseif ($data->caller_name == "74993504673")
    {
        $mainp = 525508;
        $mains = 14268634;
        $repp = 525508;
        $reps = 14268634;
    }
    
    if ($data->caller_name == "74993504654" ||
        $data->caller_name == "74993504673")
    {
        $runAmo->setMainPipeline($mainp, $mains);
        $runAmo->setRepPipeline($repp, $reps);
        $runAmo->setGenerateTasksForRec(false);
        $runAmo->processData(
            array(
                'NAME' => 'Входящий звонок '.$number
            ), 
            array(
                'PHONE' => array('WORK' => $number), 
                'NAME' => 'Входящий звонок '.$number, 
                'Источник добавления' => 'Телефония'
            ), 
            "Заяка из телефонии", 
            $userId
        );
    }
}

echo ('transfer: "' . $numberToTransfer . '"');
// echo ('transfer: "' . '79162859615' . '"');
