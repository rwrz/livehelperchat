<?php

class erLhcoreClassSpeech {
	
	public static function getSession() {
		if (! isset ( self::$persistentSession )) {
			self::$persistentSession = new ezcPersistentSession ( ezcDbInstance::get (), new ezcPersistentCodeManager ( './pos/lhspeech' ) );
		}
		return self::$persistentSession;
	}
	
	public static function getSpeechInstance(erLhcoreClassModelChat $chat)
	{
	    $chatLanguageSession = null;
	    $chatSpeech = erLhcoreClassModelSpeechChatLanguage::getList(array('filter' => array('chat_id' => $chat->id)));
	    	
	    if (empty($chatSpeech)) {
	        $chatLanguageSession = new erLhcoreClassModelSpeechChatLanguage();
	        $chatLanguageSession->chat_id = $chat->id;
	        
	        $speechUserLanguage = erLhcoreClassModelUserSetting::getSetting('speech_language','');
	        $speechUserDialect = erLhcoreClassModelUserSetting::getSetting('speech_dialect','');
	        
	        if ($speechUserLanguage != '' && $speechUserDialect != '') {
	            $chatLanguageSession->dialect = $speechUserDialect;
	            $chatLanguageSession->language_id = $speechUserLanguage;
	        } else {
	            $speechData = erLhcoreClassModelChatConfig::fetch('speech_data');
	            $data = (array)$speechData->data;	            
	            $chatLanguageSession->language_id = $data['language'];
	            $chatLanguageSession->dialect = $data['dialect'];
	        }
	        
	    } else {
	        $chatLanguageSession = array_pop($chatSpeech);
	    }
	
	    return $chatLanguageSession;
	}

    public static function validateLanguage(& $item)
    {
        $definition = array(
            'name' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
            )
        );

        $form = new ezcInputForm( INPUT_POST, $definition );
        $Errors = array();

        if ( $form->hasValidData( 'name' ) )
        {
            $item->name = $form->name;
        } else {
            $Errors[] =  erTranslationClassLhTranslation::getInstance()->getTranslation('chat/cannedmsg','Please enter language name!');
        }

        return $Errors;
    }

    public static function validateDialect(& $item) {
        $definition = array(
            'language_id' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'int', array('min_range' => 1)
            ),
            'lang_name' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
            ),
            'lang_code' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
            ),
            'short_code' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
            )
        );

        $form = new ezcInputForm( INPUT_POST, $definition );
        $Errors = array();

        if ( $form->hasValidData( 'language_id' ) )
        {
            $item->language_id = $form->language_id;
        } else {
            $Errors[] =  erTranslationClassLhTranslation::getInstance()->getTranslation('chat/cannedmsg','Please choose a language!');
        }

        if ( $form->hasValidData( 'lang_name' ) )
        {
            $item->lang_name = $form->lang_name;
        }

        if ( $form->hasValidData( 'lang_code' ) )
        {
            $item->lang_code = $form->lang_code;
        }

        if ( $form->hasValidData( 'short_code' ) )
        {
            $item->short_code = $form->short_code;
        }

        return $Errors;
    }

    public static function setUserLanguages($userId, $userLanguages)
    {
        $currentLanguages = erLhcoreClassModelSpeechUserLanguage::getList(array('limit' => false, 'filter' => array('user_id' => $userId)));

        $currentLanguagesLangs = array();

        foreach ($currentLanguages as $language) {
            $currentLanguagesLangs[$language->language] = $language;
        }

        $languagesNew = array();

        foreach ($userLanguages as $userLanguage) {
            $languagesNew[] = $userLanguage;
            if (!key_exists($userLanguage,$currentLanguagesLangs)) {
                $newLanguage = new erLhcoreClassModelSpeechUserLanguage();
                $newLanguage->language = $userLanguage;
                $newLanguage->user_id = $userId;
                $newLanguage->saveThis();
            }
        }

        $removedLanguages = array_diff(array_keys($currentLanguagesLangs),$languagesNew);

        foreach ($removedLanguages as $removedLanguage) {
            if (isset($currentLanguagesLangs[$removedLanguage])) {
                $currentLanguagesLangs[$removedLanguage]->removeThis();
            }
        }
    }

    public static function getUserLanguages($userId)
    {
        $currentLanguages = erLhcoreClassModelSpeechUserLanguage::getList(array('limit' => false, 'filter' => array('user_id' => $userId)));

        $currentLanguagesLangs = array();
        foreach ($currentLanguages as $currentLanguage) {
            $currentLanguagesLangs[$currentLanguage->language] = $currentLanguage;
        }

        return $currentLanguagesLangs;
    }

	public static function getList($paramsSearch = array(), $class = 'erLhcoreClassModelSpeechLanguage', $tableName = 'lh_speech_language')
	{
	    $paramsDefault = array('limit' => 500, 'offset' => 0);
	
	    $params = array_merge($paramsDefault,$paramsSearch);
	
	    $session = self::getSession();
	    $q = $session->createFindQuery( $class, isset($params['ignore_fields']) ? $params['ignore_fields'] : array() );
	
	    $conditions = array();
	
	    if (!isset($paramsSearch['smart_select'])) {
	        if (isset($params['filter']) && count($params['filter']) > 0)
	        {
	            foreach ($params['filter'] as $field => $fieldValue)
	            {
	                $conditions[] = $q->expr->eq( $field, $q->bindValue($fieldValue) );
	            }
	        }
	
	        if (isset($params['filterin']) && count($params['filterin']) > 0)
	        {
	            foreach ($params['filterin'] as $field => $fieldValue)
	            {
	                $conditions[] = $q->expr->in( $field, $fieldValue );
	            }
	        }
	
	        if (isset($params['filterlike']) && count($params['filterlike']) > 0)
	        {
	            foreach ($params['filterlike'] as $field => $fieldValue)
	            {
	                $conditions[] = $q->expr->like( $field, $q->bindValue('%'.$fieldValue.'%') );
	            }
	        }
	
	        if (isset($params['filterlt']) && count($params['filterlt']) > 0)
	        {
	            foreach ($params['filterlt'] as $field => $fieldValue)
	            {
	                $conditions[] = $q->expr->lt( $field, $q->bindValue($fieldValue) );
	            }
	        }
	
	        if (isset($params['filtergt']) && count($params['filtergt']) > 0)
	        {
	            foreach ($params['filtergt'] as $field => $fieldValue)
	            {
	                $conditions[] = $q->expr->gt( $field,$q->bindValue( $fieldValue ));
	            }
	        }
	
	        if (isset($params['filterlte']) && count($params['filterlte']) > 0)
	        {
	            foreach ($params['filterlte'] as $field => $fieldValue)
	            {
	                $conditions[] = $q->expr->lte( $field, $q->bindValue($fieldValue) );
	            }
	        }
	
	        if (isset($params['filtergte']) && count($params['filtergte']) > 0)
	        {
	            foreach ($params['filtergte'] as $field => $fieldValue)
	            {
	                $conditions[] = $q->expr->gte( $field,$q->bindValue( $fieldValue ));
	            }
	        }
	
	        if (isset($params['customfilter']) && count($params['customfilter']) > 0)
	        {
	            foreach ($params['customfilter'] as $fieldValue)
	            {
	                $conditions[] = $fieldValue;
	            }
	        }
	
	        if (count($conditions) > 0)
	        {
	            $q->where(
	                $conditions
	            );
	        }
	
	        if (isset($params['use_index'])) {
	            $q->useIndex( $params['use_index'] );
	        }
	
	        $q->limit($params['limit'],$params['offset']);
	
	        $q->orderBy(isset($params['sort']) ? $params['sort'] : 'id DESC' );
	    } else {
	
	        $q2 = $q->subSelect();
	        $q2->select( 'id' )->from( $tableName );
	
	        if (isset($params['filter']) && count($params['filter']) > 0)
	        {
	            foreach ($params['filter'] as $field => $fieldValue)
	            {
	                $conditions[] = $q2->expr->eq( $field, $q->bindValue($fieldValue) );
	            }
	        }
	
	        if (isset($params['filterlike']) && count($params['filterlike']) > 0)
	        {
	            foreach ($params['filterlike'] as $field => $fieldValue)
	            {
	                $conditions[] = $q->expr->like( $field, $q->bindValue('%'.$fieldValue.'%') );
	            }
	        }
	
	        if (isset($params['filterin']) && count($params['filterin']) > 0)
	        {
	            foreach ($params['filterin'] as $field => $fieldValue)
	            {
	                $conditions[] = $q2->expr->in( $field, $fieldValue );
	            }
	        }
	
	        if (isset($params['filterlt']) && count($params['filterlt']) > 0)
	        {
	            foreach ($params['filterlt'] as $field => $fieldValue)
	            {
	                $conditions[] = $q2->expr->lt( $field, $q->bindValue($fieldValue) );
	            }
	        }
	
	        if (isset($params['filterlte']) && count($params['filterlte']) > 0)
	        {
	            foreach ($params['filterlte'] as $field => $fieldValue)
	            {
	                $conditions[] = $q2->expr->lte( $field, $q->bindValue($fieldValue) );
	            }
	        }
	
	        if (isset($params['filtergt']) && count($params['filtergt']) > 0)
	        {
	            foreach ($params['filtergt'] as $field => $fieldValue)
	            {
	                $conditions[] = $q2->expr->gt( $field,$q->bindValue( $fieldValue) );
	            }
	        }
	
	        if (isset($params['filtergte']) && count($params['filtergte']) > 0)
	        {
	            foreach ($params['filtergte'] as $field => $fieldValue)
	            {
	                $conditions[] = $q2->expr->gte( $field,$q->bindValue( $fieldValue) );
	            }
	        }
	
	        if (isset($params['customfilter']) && count($params['customfilter']) > 0)
	        {
	            foreach ($params['customfilter'] as $fieldValue)
	            {
	                $conditions[] = $fieldValue;
	            }
	        }
	
	
	        if (count($conditions) > 0)
	        {
	            $q2->where(
	                $conditions
	            );
	        }
	
	        if (isset($params['use_index'])) {
	            $q2->useIndex( $params['use_index'] );
	        }
	
	        $q2->limit($params['limit'],$params['offset']);
	        $q2->orderBy(isset($params['sort']) ? $params['sort'] : 'id DESC');
	
	        $q->innerJoin( $q->alias( $q2, 'items' ), $tableName . '.id', 'items.id' );
	        $q->orderBy(isset($params['sort']) ? $params['sort'] : 'id DESC' );
	    }
	
	    $objects = $session->find( $q );
	
	    return $objects;
	}
	
	public static function getCount($params = array(), $table = 'lh_chat', $operation = 'COUNT(id)')
	{
	    $session = erLhcoreClassChat::getSession();
	    $q = $session->database->createSelectQuery();
	    $q->select( $operation )->from( $table );
	    $conditions = array();
	
	    if (isset($params['filter']) && count($params['filter']) > 0)
	    {
	        foreach ($params['filter'] as $field => $fieldValue)
	        {
	            $conditions[] = $q->expr->eq( $field, $q->bindValue($fieldValue) );
	        }
	    }
	
	    if (isset($params['filterin']) && count($params['filterin']) > 0)
	    {
	        foreach ($params['filterin'] as $field => $fieldValue)
	        {
	            $conditions[] = $q->expr->in( $field, $fieldValue );
	        }
	    }
	
	    if (isset($params['filterlt']) && count($params['filterlt']) > 0)
	    {
	        foreach ($params['filterlt'] as $field => $fieldValue)
	        {
	            $conditions[] = $q->expr->lt( $field, $q->bindValue($fieldValue) );
	        }
	    }
	
	    if (isset($params['filtergt']) && count($params['filtergt']) > 0)
	    {
	        foreach ($params['filtergt'] as $field => $fieldValue)
	        {
	            $conditions[] = $q->expr->gt( $field,$q->bindValue( $fieldValue ));
	        }
	    }
	
	    if (isset($params['filterlte']) && count($params['filterlte']) > 0)
	    {
	        foreach ($params['filterlte'] as $field => $fieldValue)
	        {
	            $conditions[] = $q->expr->lte( $field, $q->bindValue($fieldValue) );
	        }
	    }
	
	    if (isset($params['filtergte']) && count($params['filtergte']) > 0)
	    {
	        foreach ($params['filtergte'] as $field => $fieldValue)
	        {
	            $conditions[] = $q->expr->gte( $field,$q->bindValue( $fieldValue ));
	        }
	    }
	
	    if (isset($params['filterlike']) && count($params['filterlike']) > 0)
	    {
	        foreach ($params['filterlike'] as $field => $fieldValue)
	        {
	            $conditions[] = $q->expr->like( $field, $q->bindValue('%'.$fieldValue.'%') );
	        }
	    }
	
	    if (isset($params['customfilter']) && count($params['customfilter']) > 0)
	    {
	        foreach ($params['customfilter'] as $fieldValue)
	        {
	            $conditions[] = $fieldValue;
	        }
	    }
	
	    if (isset($params['leftjoin']) && count($params['leftjoin']) > 0) {
	        foreach ($params['leftjoin'] as $table => $joinOn) {
	            $q->leftJoin($table, $q->expr->eq($joinOn[0], $joinOn[1]));
	        }
	    }
	     
	    if (isset($params['innerjoin']) && count($params['innerjoin']) > 0) {
	        foreach ($params['innerjoin'] as $table => $joinOn) {
	            $q->innerJoin($table, $q->expr->eq($joinOn[0], $joinOn[1]));
	        }
	    }
	     
	    if ( count($conditions) > 0 )
	    {
	        $q->where( $conditions );
	    }
	
	     
	    if (isset($params['use_index'])) {
	        $q->useIndex( $params['use_index'] );
	    }
	
	    $stmt = $q->prepare();
	    $stmt->execute();
	    $result = $stmt->fetchColumn();
	
	    return $result;
	}
	
	private static $persistentSession;
}

?>