<?php

defined('JPATH_BASE') or die();
jimport('joomla.form.formfield');

class JFormFieldProfilelist extends JFormField{
	    protected     $type = 'profilelist';

        protected function getInput(){
				$db = JFactory::getDBO();
				$key = 'id';
				$val = 'profile';

				//$db->debug(1);
				$db->setQuery("	SELECT $key,$val
								FROM #__excel2js
								ORDER BY id");
				$rows = $db->loadAssocList();
                
				if (count($rows)>0)
				foreach ($rows as $row)
					$options[]=array($key=>$row[$key],$val=>$row[$val]);

                if($options){
                        return JHTML::_('select.genericlist',$options, $this->name, ' size="1"', $key, $val,$this->value);
                }
        }
}