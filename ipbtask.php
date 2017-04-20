<?php
require_once dirname(__FILE__) . '/amoIntegr.php';

$runAmo = new AmoAPI('icgr', '5179907@mail.ru', '1036ac2a6f0b3ca803e3dd2ab7e549ea');
foreach ($_POST['leads']['status'] as $lead) {
    $leadData = $runAmo->getLeadByID($lead['id']);
    file_put_contents(dirname(__FILE__).'/past.txt', print_r($leadData, true));
    
    foreach ($leadData->leads as $l) {
        foreach ($l->custom_fields as $field) {
            if ($field->id == 498277) {
                foreach($field->values as $value) {
                    if ($value->value == 'Да')
                    {
                        $runAmo->generateTask($lead->responsible_user_id, $lead['id'],
                            2, 1, 'Получить документы для ИПБ', strtotime("+5 days"));
                        exit();
                    }
                }
            }
        }
    }
}

?>