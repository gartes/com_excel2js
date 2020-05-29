<?php

defined('JPATH_BASE') or die();
jimport('joomla.form.formfield');

class JFormFieldLinks extends JFormField{
	    protected     $type = 'links';

        protected function getInput(){
			$default=$this->element['default'];
            if(substr($default,0,1)==DS){
                $default=substr($default,1);
            }
            $href=JURI::root().$default;
            $desc=str_replace(array('<br>','<br />','<br/>'),"\n",JText::_($this->element['description']));
            return  "<a style='float:left;margin-bottom: 5px; margin-top: 10px;' title='".$desc."' href='$href' target='_blank'>$href</a>";
        }
}