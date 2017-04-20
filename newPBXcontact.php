<?php

function contains($needle, $haystack)
{
    return strpos($haystack, $needle) !== false;
}

function isEmpty($data){
    if (ctype_space($data) || empty($data) ) {
        return true;
    }
    return false;
}

require_once dirname(__FILE__) . '/amoIntegr.php';
$runAmo = new AmoAPI('icgr', '5179907@mail.ru', '1036ac2a6f0b3ca803e3dd2ab7e549ea');

// file_put_contents(dirname(__FILE__).'/past.txt', print_r($_POST, true));
// exit();

if (!isset($_POST['contacts']['add'])) exit();

foreach ($_POST['contacts']['add'] as $contact)
{
    if (!contains('Пропущенный', $contact['name']) && 
        !contains('Входящий', $contact['name']))
    {
        continue;
    }
    
    // Update Contact
	$contactNewData = array(
	    'custom_fields' => $runAmo->processFields(
	        array('Источник добавления' => 'Телефония'), 
	        AmoAPI::CONTACT_DATA_TYPE
        ), 
        'id' => $contact['id'], 
        'last_modified' => time()
    );
    
    if ( contains('Издательство', $contact['name']) || 
        contains('74993504654', $contact['name']) )
    {
        $runAmo->setDepartmentID(0);
        $mainp = 504604;
        $mains = 14093680;
        $repp = 504622;
        $reps = 14107147;
    }
    elseif (contains('Семинары', $contact['name']) ||
        contains('74993504673', $contact['name']))
    {
        $runAmo->setDepartmentID(101569);
        $mainp = 525508;
        $mains = 14268634;
        $repp = 525508;
        $reps = 14268634;
    }
    
    if (contains('Пропущенный', $contact['name']))
    {    
        $userId = $runAmo->rotateUser()->id;
        $contactNewData['responsible_user_id'] = $userId;
    }
    
	$updateRequest = array('request' => array('contacts' => array('update' => array($contactNewData))));
	$response = $runAmo->request('POST','v2/json/contacts/set', $updateRequest); 
	
	if (
	    contains('Издательство', $contact['name']) || 
	    contains('74993504654', $contact['name']) ||
	    contains('Семинары', $contact['name']) ||
        contains('74993504673', $contact['name'])
	    )
    {
        $runAmo->setMainPipeline($mainp, $mains);
        $runAmo->setRepPipeline($repp, $reps);
        
        foreach ($contact['custom_fields'] as $field)
        {
            if ($field['name'] == 'Телефон')
            {
                $number = $field['values'][0]['value'];
                break;
            }
        }
        $runAmo->setGenerateTasksForRec(false);
        if (!isEmpty($number))
        {
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

            $counter = 0;
            do {
                usleep(2000);
                $resp = $runAmo->request('GET', 'v2/json/tasks/list', array('element_id' => $contact['id']));   
                $counter++;
                if ($counter > 4) exit();
            } while (empty($resp->tasks[0]));
            
            foreach ($resp->tasks as $task) {
                if (contains('пропущенный', $task->text)) {
                    $t = array(
                        'id' => $task->id,
                        'last_modified' => time(),
                        'element_id' => $task->element_id,
                        'element_type' => $task->element_type,
                        'text' => $task->text,
                        'responsible_user_id' => $userId,
                        'complete_till' => time() + 15*60
                    );

                    $updateRequest = array('request' => array('tasks' => array('update' => array($t))));
                    $response = $runAmo->request('POST', 'v2/json/tasks/set', $updateRequest);
                }
            }
        }
	}
}