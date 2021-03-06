<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/* crmv@105600 */
 
// TODO: deprecate this class in favor of the new RCache class

/**
 * Class to handle Caching Mechanism and re-use information during the current request only
 */
class VTCacheUtils {
	
	/** Tab information caching */
	static $_tabidinfo_cache = array();
	static function lookupTabid($module) {
		$flip_cache = array_flip(self::$_tabidinfo_cache);
		
		if(isset($flip_cache[$module])) {
			return $flip_cache[$module];
		}
		return false;
	}
	
	static function lookupModulename($tabid) {
		if(isset(self::$_tabidinfo_cache[$tabid])) {
			return self::$_tabidinfo_cache[$tabid];
		}
		return false;
	}
	
	static function updateTabidInfo($tabid, $module) {
		if(!empty($tabid) && !empty($module)) {
			self::$_tabidinfo_cache[$tabid] = $module;
		}
	}
	
	/** All tab information caching */
	static $_alltabrows_cache = false;
	static function lookupAllTabsInfo() {
		return self::$_alltabrows_cache; 
	}
	static function updateAllTabsInfo($tabrows) {
		self::$_alltabrows_cache = $tabrows;
	}
	
	/** Field information caching */
	static $_fieldinfo_cache = array();
	static function updateFieldInfo($tabid, $fieldname, $fieldid, $fieldlabel, 
		$columnname, $tablename, $uitype, $typeofdata, $presence) {
			
		self::$_fieldinfo_cache[$tabid][$fieldname] = array(
			'tabid'     => $tabid,
			'fieldid'   => $fieldid,
			'fieldname' => $fieldname,
			'fieldlabel'=> $fieldlabel,
			'columnname'=> $columnname,
			'tablename' => $tablename,
			'uitype'    => $uitype,
			'typeofdata'=> $typeofdata,
			'presence'  => $presence,
		);
	}
	static function lookupFieldInfo($tabid, $fieldname) {
		if(isset(self::$_fieldinfo_cache[$tabid]) && isset(self::$_fieldinfo_cache[$tabid][$fieldname])) {
			return self::$_fieldinfo_cache[$tabid][$fieldname];
		// crmv@193294
		} else {
			return FieldUtils::getField($tabid, $fieldname);
		}
		// crmv@193294e
		return false;
	}	
	static function lookupFieldInfo_Module($module, $presencein = array('0', '2')) {
		$tabid = getTabid($module);
		
		// fix for autoupdater to 20.04
		global $is_vte_updating;
		if ($is_vte_updating) {
			$list = array('Emails' => array('subject', 'description'));
			return $list[$module];
		}
		
		$modulefields = false;
		if(isset(self::$_fieldinfo_cache[$tabid])) {
			$modulefields = array();
			
			$fldcache = self::$_fieldinfo_cache[$tabid];
			foreach($fldcache as $fieldname=>$fieldinfo) {
				if(in_array($fieldinfo['presence'], $presencein)) {
					$modulefields[] = $fieldinfo;
				}
			}
		// crmv@193294
		} else {
			// user the new field global cache
			$modulefields = FieldUtils::getFields($module, function($row) use ($presencein) {
				return in_array($row['presence'], $presencein);
			});
			if (is_array($modulefields)) $modulefields = array_values($modulefields);
		}
		// crmv@193294e
		
		return $modulefields;
	}
	
	static function lookupFieldInfoByColumn($tabid, $columnname) {
		if(isset(self::$_fieldinfo_cache[$tabid])) {
			foreach(self::$_fieldinfo_cache[$tabid] as $fieldname=>$fieldinfo) {
				if($fieldinfo['columnname'] == $columnname) {
					return $fieldinfo;
				}
			}
		// crmv@193294
		} else {
			// user the new field global cache
			$modulefields = FieldUtils::getFields($tabid, function($row) use ($columnname) {
				return ($row['columnname'] == $columnname);
			});
			if (count($modulefields) > 0) {
				reset($modulefields);
				return current($modulefields);
			}
		}
		// crmv@193294e
		return false;
	}
	
	/** User currency id caching */
	static $_usercurrencyid_cache = array();
	static function lookupUserCurrenyId($userid) {
		global $current_user;		
		if(isset($current_user) && $current_user->id == $userid) {
			return array(
				'currencyid' => $current_user->column_fields['currency_id']
			);
		}
		
		if(isset(self::$_usercurrencyid_cache[$userid])) {
			return self::$_usercurrencyid_cache[$userid];
		}
		
		return false;
	}
	static function updateUserCurrencyId($userid, $currencyid) {
		self::$_usercurrencyid_cache[$userid] = array(
			'currencyid' => $currencyid
		);
	}
	
	/** Currency information caching */
	static $_currencyinfo_cache = array();
	static function lookupCurrencyInfo($currencyid) {
		if(isset(self::$_currencyinfo_cache[$currencyid])) {
			return self::$_currencyinfo_cache[$currencyid];
		}
		return false;
	}
	static function updateCurrencyInfo($currencyid, $name, $code, $symbol, $rate) {
		self::$_currencyinfo_cache[$currencyid] = array(
			'currencyid' => $currencyid,
			'name'       => $name,
			'code'       => $code,
			'symbol'     => $symbol,
			'rate'       => $rate
		);
	}
	
	
	/** ProfileId information caching */
	static $_userprofileid_cache = array();
	static function updateUserProfileId($userid, $profileid) {
		self::$_userprofileid_cache[$userid] = $profileid;
	}
	static function lookupUserProfileId($userid) {
		if(isset(self::$_userprofileid_cache[$userid])) {
			return self::$_userprofileid_cache[$userid];
		}
		return false;
	}
	
	/** Profile2Field information caching */
	static $_profile2fieldpermissionlist_cache = array();
	static function lookupProfile2FieldPermissionList($module, $profileid) {
		$pro2fld_perm = self::$_profile2fieldpermissionlist_cache;
		if(isset($pro2fld_perm[$module]) && isset($pro2fld_perm[$module][$profileid])) {
			return $pro2fld_perm[$module][$profileid];
		}
		return false;
	}
	static function updateProfile2FieldPermissionList($module, $profileid, $value) {
		self::$_profile2fieldpermissionlist_cache[$module][$profileid] = $value;
	}
	
	/** Role information */
	static $_subroles_roleid_cache = array();
	static function lookupRoleSubordinates($roleid) {
		if(isset(self::$_subroles_roleid_cache[$roleid])) {
			return self::$_subroles_roleid_cache[$roleid];
		}
		return false;
	}
	static function updateRoleSubordinates($roleid, $roles) {
		self::$_subroles_roleid_cache[$roleid] = $roles;
	}
	static function clearRoleSubordinates($roleid = false) {
		if($roleid === false) {
			self::$_subroles_roleid_cache = array();
		} else if(isset(self::$_subroles_roleid_cache[$roleid])) {
			unset(self::$_subroles_roleid_cache[$roleid]);
		}
	}
	
}
