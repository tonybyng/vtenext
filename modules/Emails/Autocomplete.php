<?php
/* crmv@25356 crmv@55137 */
require_once('include/Zend/Json.php');
global $adb,$table_prefix;
$search = str_replace("'", '', $_REQUEST['term']);

//$result = $adb->pquery('select * from vtiger_field where tabid = ? and fieldname = ?',array(getTabid('Emails'),'parent_id'));
//$webserviceField = WebserviceField::fromQueryResult($adb,$result,0);
//$referenceList = $webserviceField->getReferenceList();

//crmv@25562
if ($_REQUEST['referenceModule'] != '') {
	$referenceList = array($_REQUEST['referenceModule']);
}
else {
	$referenceList = array('Contacts','Accounts','Vendors','Leads','Users');
}
//crmv@25562e

$return = array();
foreach ($referenceList as $module) {

	$select_fields = array();
	$search_fields = array();
	$search_conditions = array();
	$search_conditions_adv = array();
	$fl = array();

	if (vtlib_isModuleActive($module) && $moduleInstance = Vtiger_Module::getInstance($module)) {

		$moduleEntity = CRMEntity::getInstance($module);

		$result = $adb->pquery('select fieldid, columnname, tablename from '.$table_prefix.'_field where tabid = ? and uitype in (?,?) and presence in (0,2)',array($moduleInstance->id,13,104));
		if ($result && $adb->num_rows($result)>0) {
			while($row=$adb->fetchByAssoc($result)) {
				$select_fields[$row['fieldid']] = array('columnname'=>$row['columnname'],'tablename'=>$row['tablename']);
				$search_fields[] = $row['tablename'].'.'.$row['columnname'];
			}
		}

		$query = "select fieldname,tablename,entityidfield from ".$table_prefix."_entityname where modulename = ?";
		$result = $adb->pquery($query, array($module));
		$fieldsname = $adb->query_result($result,0,'fieldname');
		$tablename = $adb->query_result($result,0,'tablename');
		$entityidfield = $adb->query_result($result,0,'entityidfield');
		if(!(strpos($fieldsname,',') === false))
		{
			$fieldlists = explode(',',$fieldsname);
			foreach($fieldlists as $w => $c)
			{
				if (count($fl)) {
					$fl[] = "' '";
				}
				$wsfield = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname = ?',array($moduleInstance->id,$c)),0);
				$fl[] = "COALESCE(".$wsfield->getTableName().'.'.$wsfield->getColumnName().", cast('' as char))"; //crmv@179991
				$search_fields[] = $wsfield->getTableName().'.'.$wsfield->getColumnName();
			}
			$fieldsname = $adb->sql_concat($fl);
		} else {
			$wsfield = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname = ?',array($moduleInstance->id,$fieldsname)),0);
			$fieldsname = $wsfield->getTableName().'.'.$wsfield->getColumnName();
			$search_fields[] = $wsfield->getTableName().'.'.$wsfield->getColumnName();
		}
		if ($module == 'Users') {
			$search_fields[] = $table_prefix.'_users.user_name';
		}

		$query = "select $fieldsname entityname, $tablename.$entityidfield";
		if (!empty($select_fields)) {
			foreach($select_fields as $info) {
				$query .= ', '.$info['tablename'].'.'.$info['columnname'];
			}
		}
		$query .= " from $tablename";
		if ($module != 'Users') {
			$query .= " inner join ".$table_prefix."_crmentity on $tablename.$entityidfield = ".$table_prefix."_crmentity.crmid";
		}
		if (!empty($moduleEntity->customFieldTable)) {
			$query .= " inner join ".$moduleEntity->customFieldTable[0]." on $tablename.$entityidfield = ".$moduleEntity->customFieldTable[0].".".$moduleEntity->customFieldTable[1];
		}
		if ($module == 'Users') {
			$query .= " where status = 'Active'";
		//crmv@25708
		} elseif ($module == 'Leads') {
			$query .= " where deleted = 0 and converted = 0";
		//crmv@25708e
		} else {
			$query .= " where deleted = 0";
		}
		foreach($search_fields as $field) {
			$search_conditions[] = "$field like '%$search%'";
		}
		foreach($select_fields as $info) {
			$search_conditions_adv[] = $info['tablename'].'.'.$info['columnname']." <> '' or ".$info['tablename'].'.'.$info['columnname'].' is not null';
		}
		if (!empty($search_conditions)) {
			$query .= ' and ('.implode(' or ',$search_conditions).')';
			$query .= ' and ('.implode(' or ',$search_conditions_adv).')';
		}
		if ($module != 'Users') {
			$secQuery = getNonAdminAccessControlQuery($module, $current_user);
			if(strlen($secQuery) > 1) {
				$query = appendFromClauseToQuery($query, $secQuery);
			}
			$query = listQueryNonAdminChange($query, $module); // crmv@151974
		}
		$query .= " ORDER BY entityname,$tablename.$entityidfield ";	//crmv@25562

		$module_emails = array();
		$result = $adb->limitQuery($query,0,5);	//crmv@46030
		if ($result && $adb->num_rows($result)>0) {
			while($row=$adb->fetchByAssoc($result)) {
				foreach ($select_fields as $fieldid => $info) {
					if ($row[$info['columnname']] != '') {

						$emk = array_search($row[$info['columnname']], $module_emails);
						// filtra i duplicati e prende l'ultimo (crmid maggiore)
						if ($emk !== false && $emk < intval($row[$entityidfield])) {
							continue;
						} else {
							$module_emails[$row[$entityidfield]] = $row[$info['columnname']];
						}

						if (in_array($_REQUEST['field'],array('cc_name','bcc_name'))) {
							$return[] = array(	'label'=>getTranslatedString($module,$module).': '.$row['entityname'].' <'.$row[$info['columnname']].'>',
												'value'=>$row[$info['columnname']],
										);
						} else {
							//crmv@25852
							if ($module == 'Users') {
								$fieldid = '-'.$fieldid;
							}
							//crmv@25852e
							$return[] = array(	'id'=>$row[$entityidfield].'_'.$fieldid,	//crmv@25562
												'label'=>getTranslatedString($module,$module).': '.$row['entityname'].' <'.$row[$info['columnname']].'>',
												'value'=>$row['entityname'],
												'parent_id'=>$row[$entityidfield].'@'.$fieldid,
												'parent_name'=>$row['entityname'],
												'hidden_toid'=>$row[$info['columnname']],
												//crmv@25562
												'module'=>getTranslatedString($module,$module),
												'moduleName'=>$module,
												//crmv@25562e
										);
						}
					}
				}
			}
		}
	}
}

echo Zend_Json::encode($return);
if ($_REQUEST['dont_terminate'] != 'true') exit;  //crmv@42537
?>
