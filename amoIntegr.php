<?php
//require_once dirname(__FILE__) . '/RestClient.class.php';

class AmoException extends Exception
{
	const UNKNOWN = 0;
	const AUTH_INVALID = 1;
	const USER_NOT_SET = 2;
	const HASH_NOT_SET = 3;
	const SUBDOMAIN_NOT_SET = 4;

	const REQUEST_INVALID = 10;
	const REQUEST_NOT_FOUND = 11;

	const RESPONSE_INVALID = 20;

	const DATA_EMPTY = 30;
	const DATA_EMPTY_EMAIL = 31;
	const DATA_EMPTY_PHONE = 32;

	const NO_RESPONSIBLE_USERS = 56;

	public function __construct($code = 0, Exception $previous = null) {
		// TBA @ v2
		switch($code) {
			case self::USER_NOT_SET: $message = 'Username not set'; break;
			case self::HASH_NOT_SET: $message = 'Password not set'; break;
			case self::SUBDOMAIN_NOT_SET: $message = 'Subdomain not set'; break;
			case self::REQUEST_INVALID: $message = 'Invalid request'; break;
			case self::RESPONSE_INVALID: $message = 'Invalid response'; break;
			case self::REQUEST_NOT_FOUND: $message = 'Request URL not found'; break;
			case self::AUTH_INVALID: $message = 'Username or password invalid'; break;
			default: $message = 'Unknown error ('.$code.')';
		}
		parent::__construct($message, $code, $previous);
	}
}

class AmoAPI
{

	protected $user;
	protected $hash;
	protected $subdomain;

	private $currentCache;
	private $contactFieldCache;
	private $leadFieldCache;
	
	const REP_PIPELINE = 234889;
	const REP_STATUS = 11572309;
	
	const WORK_EMAIL_ID = 15042;
	const WORK_PHONE_ID = 15030;

	public function __construct($subdomain, $user, $hash) {
		$this->subdomain = $subdomain;
		$this->user = $user;
		$this->hash = $hash;
	}

	public function request($method, $request, $parameters = array(), $isPublicApi = false) 
	{
		if(empty($this->subdomain)) throw new AmoException(AmoException::SUBDOMAIN_NOT_SET);

		$url = 'https://' . $this->subdomain . '.amocrm.ru/';
		if($isPublicApi) 
		{
			if(empty($this->user)) throw new Exception(AmoException::USER_NOT_SET);
			if(empty($this->hash)) throw new Exception(AmoException::HASH_NOT_SET);
			$url .= 'api/' . $request . '?login=' . $this->user . '&api_key=' . $this->hash;
		} else 
		{
			$url .= 'private/api/' . $request . '?type=json';
		}
		if($method === 'GET') 
		{
			$url .= '&'.http_build_query($parameters);
		}

		$curl=curl_init();

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST,$method);
		if($method === 'POST') 
		{
			curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($parameters));
		}
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$out = curl_exec($curl);
		$code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		if(($code === 401 || $code === 403) && self::auth()) 
		{
			if($this->auth()) 
			{
				$out = curl_exec($curl);
				$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			}
		}
		if($code === 200 || $code === 204) 
		{
			if(is_null($response = json_decode($out))) 
			{
				return null;
			} else 
			{
				return $response->response;
			}
		} else 
		{ 
			return $code; 
		}
	}

	public function auth() 
	{
		if(empty($this->user)) throw new AmoException(AmoException::USER_NOT_SET);
		if(empty($this->hash)) throw new AmoException(AmoException::HASH_NOT_SET);

		$response = $this->request('POST', 'auth.php', array(
			'USER_LOGIN' => $this->user,
			'USER_HASH' => $this->hash,
			'type' => 'json'
			));
		if(!is_int($response)) 
		{
			if($response->auth === false) 
			{
				throw new AmoException(AmoException::AUTH_INVALID);
			} else 
			{
				return true;
			}
		} else 
		{
			switch ($response) 
			{
			case 404: throw new AmoException(AmoException::REQUEST_NOT_FOUND); break;
			case 401: throw new AmoException(AmoException::AUTH_INVALID); break;
			default: throw new AmoException(AmoException::REQUEST_INVALID); break;
			}
		}
	}

	public function current() 
	{
		return ($this->currentCache ? : $this->currentCache = $this->request('GET', 'v2/json/accounts/current')->account);
	}
	
	public function printCurrentCache()
	{
		$results = print_r($this->current(), true);
		file_put_contents(dirname(__FILE__).'/past.txt', $results);
	}

	public function getLeadUsers() 
	{
		$users = $this->current()->users;
		$userIds = array();
		foreach ($users as $key => $user) 
		{
			if($user->rights_lead_view === 'M' || $user->rights_lead_view === 'A') 
			{
				// if ($user->login <> 'info@fabrika-tt.ru') {
					$userIds[$user->id] = $user;
				// }
			}
		}
		return $userIds;
	}
	
	public function getLeadUser($userId) 
	{
		$users = $this->getLeadUsers();
		return (empty($users[$userId]) ? null : $users[$userId]);
	}
	
	public function rotateUser() 
	{
		if(file_exists(dirname(__FILE__) . '/last.txt')) 
		{
			$user = file_get_contents(dirname(__FILE__) . '/last.txt');
			$user = $this->getLeadUser($user);
		}
		$users = $this->getLeadUsers();
		if(empty($user)) 
		{
			$user = array_shift($users);
		} else 
		{
			$found = false;
			foreach($users as $id => $select_user) 
			{
				if($found === true) 
				{
					$user = $select_user;
					$found = false;
					break;
				}
				if($id == $user->id) 
				{ 
					$found = true;
				}
			}
			if($found === true) 
			{
				$user = array_shift($users);
			}
		}
		// file_put_contents(dirname(__FILE__).'/last.txt', $user->id);
		// return $user;
	}

	public function cacheFields() 
	{
		$this->contactFieldCache = array();
		foreach($this->current()->custom_fields->contacts as $field) 
		{
			if(!empty($field->code)) 
			{
				$this->contactFieldCache[$field->code] = $field;
			}
			$this->contactFieldCache[$field->name] = $field; // will break duplicate checks
			$this->contactFieldCache[$field->id] = $field;
		}
		$this->leadFieldCache = array();
		foreach($this->current()->custom_fields->leads as $field) 
		{
			if(!empty($field->code)) 
			{
				$this->leadFieldCache[$field->code] = $field;
			}
			$this->leadFieldCache[$field->name] = $field;
			$this->leadFieldCache[$field->id] = $field;
		}
	}
	
	public function getContactFields() 
	{
		if(empty($this->contactFieldCache)) $this->cacheFields();
		return $this->contactFieldCache;
	}
	
	
	public function getContactField($fieldName) 
	{
		$fields = $this->getContactFields();
		return (empty($fields[$fieldName]) ? null : $fields[$fieldName]);
	}
	
	public function processContactFields($contactData) 
	{
		$cfields = array();
		foreach($contactData as $code => $value) {
			if(empty($value)) throw new AmoException(-2);
			$field = $this->getContactField($code);
			if(is_null($field)) continue;

			if(!is_array($value)) 
			{ 
				$value = array($value); 
			}
			if($field->multiple === 'N' && count($value) > 1) throw new AmoException(-4);

			$cf = array('id' => $field->id,'values' => array());

			foreach($value as $enum => $subvalue) 
			{
				if(is_string($enum)) 
				{
					$cf['values'][] = array('value' => $subvalue, 'enum' => $enum);
				} else 
				{
					$cf['values'][] = array('value' => $subvalue);
				}
			}
			$cfields[] = $cf;
		}
		return $cfields;
	}

	public function getLeadFields() 
	{
		if(empty($this->leadFieldCache)) $this->cacheFields();
		return $this->leadFieldCache;
	}
	
	public function getLeadField($fieldName)
	{
		$fields = $this->getLeadFields();
		return (empty($fields[$fieldName]) ? null : $fields[$fieldName]);
	}
	
	public function processLeadFields($leadData)
	{
		$cfields = array();
		foreach($leadData as $code => $value) {
			if(empty($value)) throw new AmoException(-2);
			$field = $this->getLeadField($code);
			if(is_null($field)){echo ($field); continue;}

			if(!is_array($value)) { $value = array($value); }
			if($field->multiple === 'N' && count($value) > 1) throw new AmoException(-4);

			$cf = array('id' => $field->id,'values' => array());

			foreach($value as $enum => $subvalue)
			{
				if(is_string($enum)) 
				{
					$cf['values'][] = array('value' => $subvalue, 'enum' => $enum);
				} else 
				{
					$cf['values'][] = array('value' => $subvalue);
				}
			}
			$cfields[] = $cf;
		}
		return $cfields;
	}
	
	public function leadRequest($respUserID, $name, $status, $pipelineID, $customFields, $tags) 
	{
	    
		// Leads Request
		$lead = array(
			'responsible_user_id' => $respUserID,
			'name' => $name,
			'status_id' => $status,
			'pipeline_id' => $pipelineID,
			'custom_fields' => $customFields,
			'tags' => $tags
		);
		if(!empty($leadData['PRICE']) && is_numeric($leadData['PRICE']))
		{
			$lead['PRICE'] = $leadData['PRICE'];
		}
		$leadsRequest = array('request' => array('leads' => array('add' => array($lead))));
		$response = $this->request('POST','v2/json/leads/set', $leadsRequest);
		if(!isset($response->leads->add[0]->id)) throw new AmoException(-8);
		return $response->leads->add[0]->id;
	}
	
	public function contactRequest($respUserID, $name, $customFields, $linkedLeads) 
	{
		// Contacts Request
		$contact_fields = $this->getContactFields();
		$contact = array(
			'responsible_user_id' => $respUserID,
			'name' => $name,
			'custom_fields' => $customFields,
			'linked_leads_id' => $linkedLeads
			);
		$contactsRequest = array('request' => array('contacts' => array('add' => array($contact))));
		$response = $this->request('POST','v2/json/contacts/set', $contactsRequest);
		if(!isset($response->contacts->add[0]->id)) throw new AmoException(-8);
		return $contactId = $response->contacts->add[0]->id;
	}
	
	public function generateTask($respUserID, $leadId, $elemType, $taskType, $text, $completeTill) 
	{
		// Task generation
		$task = array(
			'responsible_user_id' => $respUserID,
			'element_id' => $leadId,
			'element_type' => $elemType,
			'task_type' => $tasktype,
			'text' => $text,
			'complete_till' => $completeTill
		);
		$tasksRequest = array('request' => array('tasks' => array('add' => array($task))));
		$response = $this->request('POST','v2/json/tasks/set', $tasksRequest);
		if(!isset($response->tasks->add[0]->id)) throw new AmoException(-8);
	}

	public function processData($leadData, $contactData, $leadTags, $respUserSend) {
		if(empty($contactData)) throw new AmoException(AmoException::DATA_EMPTY);
		if(empty($contactData['EMAIL']) and empty($contactData['PHONE'])) throw new AmoException(AmoException::DATA_EMPTY_EMAIL);
		if(empty($leadData['NAME'])) throw new AmoException(-5);

		// Double Prevention
		$phoneIds = array();
		if (!empty($contactData['PHONE']))
		{
			foreach((is_array($contactData['PHONE']) ? $contactData['PHONE'] : array($contactData['PHONE'])) as $phone) 
			{
				$response = $this->request('GET', 'v2/json/contacts/list', array('query' => $phone, 'limit_rows' => 1));
				if(!empty($response->contacts[0])) 
				{
					$phoneIds[] = $response->contacts[0]->id;
					$responsibleUserID = $response->contacts[0]->responsible_user_id;
					$cacheContactData = $response->contacts[0]->custom_fields;
					$linkedLeads = $response->contacts[0]->linked_leads_id;
					break;
				}
			}
		}
		
		$emailIds = array();
		if (!empty($contactData['EMAIL']) && empty($phoneIds))
		{
			foreach((is_array($contactData['EMAIL']) ? $contactData['EMAIL'] : array($contactData['EMAIL'])) as $email) 
			{
				$response = $this->request('GET', 'v2/json/contacts/list', array('query' => $email, 'limit_rows' => 1));
				if(!empty($response->contacts[0])) 
				{
					$emailIds[] = $response->contacts[0]->id;
					$responsibleUserID = $response->contacts[0]->responsible_user_id;
					$cacheContactData = $response->contacts[0]->custom_fields;
					$linkedLeads = $response->contacts[0]->linked_leads_id;
					break;
				}
			}
		}
		
		$intersectId = -1;
		if (!empty($contactData['EMAIL']) and !empty($emailIds))
		{
			$intersectId = $emailIds[0];
		}
		if (!empty($contactData['PHONE']) and !empty($phoneIds))
		{
			$intersectId = $phoneIds[0];
		}
		
		if($intersectId != -1) 
		{
			$isNewPhone = true;
			$isNewEmail = true;
			foreach ($cacheContactData as $field)
			{
				foreach ($field->values as $val)
				{
					foreach ((is_array($contactData['PHONE']) ? $contactData['PHONE'] : array($contactData['PHONE'])) as $phone)
					{
						if ($phone == $val->value) $isNewPhone = false;
					}
					
					foreach ((is_array($contactData['EMAIL']) ? $contactData['EMAIL'] : array($contactData['EMAIL'])) as $email)
					{
						if ($email == $val->value) $isNewEmail = false;
					}
				}
			}
			
			foreach ($cacheContactData as $field)
			{
				if ($field->code == 'EMAIL' and $isNewEmail)
				{
					$values = $this->processContactFields(array('EMAIL' => $contactData['EMAIL']))[0]['values'];
					foreach ($values as $value) 
					{
						$value['enum'] = self::WORK_EMAIL_ID;
						$field->values[] = (object)$value;
					}
				}
				if ($field->code == 'PHONE' and $isNewPhone)
				{
					$values = $this->processContactFields(array('PHONE' => $contactData['PHONE']))[0]['values'];
					foreach ($values as $value) 
					{
						$value['enum'] = self::WORK_PHONE_ID;
						$field->values[] = (object)$value;
					}
				}
			}
			
			// Update Contact
			$contact = array('custom_fields' => $cacheContactData, 'id' => $intersectId, 'last_modified' => time());
			$updateRequest = array('request' => array('contacts' => array('update' => array($contact))));
			$response = $this->request('POST','v2/json/contacts/set', $updateRequest);
			if(!isset($response->contacts->update[0]->id)) throw new AmoException(-8);
			
			$leadId = -1;
			if (!empty($linkedLeads))
			{
				$response = $this->request('GET', 'v2/json/leads/list', array('id' => $linkedLeads, 'limit_rows' => 1));
				foreach ($response->leads as $lead) 
				{
					if ($lead->date_close == 0)
					{
						$leadId = $lead->id;
						break;
					}
				}
			}
			
			if($leadId == -1)
			{
				$leadId = $this->leadRequest($responsibleUserID, $leadData['NAME'], self::REP_STATUS, self::REP_PIPELINE, $this->processLeadFields($leadData), $leadTags);
				
				$linkedLeads[] = $leadId;
				$contact = array('linked_leads_id' => $linkedLeads, 'id' => $intersectId, 'last_modified' => time());
				$updateRequest = array('request' => array('contacts' => array('update' => array($contact))));
				$response = $this->request('POST','v2/json/contacts/set', $updateRequest);
				if(!isset($response->contacts->update[0]->id)) throw new AmoException(-8);
			}
			else 
			{
				$this->generateTask($responsibleUserID, $leadId, 2, 1, 'Клиент оставил заявку повторно. Связаться', strtotime("+15 minutes"));
			}
		}
		else
		{
			// Responsible user
			if ($respUserSend == -1)
			{
				$responsibleUser = $this->rotateUser();
			}
			else
			{
				$responsibleUser = $respUserSend;
			}
			
			// $results = print_r($this->processLeadFields($leadData), true);
			// file_put_contents(dirname(__FILE__).'/past.txt', $results);
			
			// return 0;
	
			// Leads Request
			$leadId = $this->leadRequest($responsibleUser->id, $leadData['NAME'], 1, 1, $this->processLeadFields($leadData), $leadTags);
	
			// Contacts Request
			$contactId = $this->contactRequest($responsibleUser->id, (empty($contactData['NAME']) ? 'Untitled' : $contactData['NAME']),
				$this->processContactFields($contactData), array($leadId));
		}

		return $leadId;
	}
	
	
	public function addResourceToContactByEmail($email, $resource)
	{
		$response = $this->request('GET', 'v2/json/contacts/list', array('query' => $email, 'limit_rows' => 1));
		$emailId = $response->contacts[0]->id;
		//if (empty($emailId)) file_put_contents(dirname(__FILE__).'/past.txt', '123');
		
		$res = array(
			'element_id' => $emailId,
			'element_type' => 1,
			'note_type' => 4,
			'text' => $resource
		);
		$resRequest = array('request' => array('notes' => array('add' => array($res))));
		$response = $this->request('POST','v2/json/notes/set', $resRequest);
		//if(!isset($response->notes->add[0]->id));
	}
	
	public function getContactByLead($leadID)
	{

		$response = $this->request('GET', 'v2/json/contacts/links', array('deals_link' => array($leadID), 'limit_rows' => 1));
		if(!empty($response->links)) 
		{
			$contactID = $response->links[0]->contact_id;	
			$contact = $this->request('GET', 'v2/json/contacts/list', array('id' => $contactID));
			
			return $contact;
		}
		else {
			return null;
		}
	
	}
	
	public function getLeadByID($leadID)
	{
		$lead = $this->request('GET', 'v2/json/leads/list', array('id' => $leadID));
		
		if(empty($lead->leads))
		{
			throw new AmoException(-8);
		}
		return $lead;
	}
	
	public function getAccountData()
	{
		return $this->request('GET', 'v2/json/accounts/current');
	}
}

?>
