<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');
require_once(dirname(__FILE__) . DS . "updateTable.php");

class Excel2jsModelBackup extends JModelLegacy {
	public $pagination;
	
	function __construct() {
		parent:: __construct();
		//$params       = JComponentHelper:: getParams("com_excel2js");
		$this->app    = JFactory::getApplication();
		$this->input  = $this->app->input;
		$this->table  = new updateTable("#__excel2js_backups", "backup_id");
		$this->id     = $this->input->get('id', '', 'int');
		$this->config = $this->getConfig();
	}
	
	function getConfig() {
		$this->config_table = new updateTable("#__excel2js", "id");
		$this->_db->setQuery("SELECT id FROM #__excel2js WHERE default_profile = 1");
		$id = $this->_db->loadResult();
		if ( !$id ) {
			$this->_db->setQuery("UPDATE #__excel2js SET default_profile = 1 LIMIT 1");
			$this->_db->execute();
			$this->config_table->load(1, 'default_profile');
		}
		else
			$this->config_table->load($id);
		$this->active_fields  = $this->config_table->active;
		$this->profile        = $this->config_table->id;
		$config               = unserialize($this->config_table->config);
		$config->profile_name = $this->config_table->profile;
		$config->profile_id   = $this->config_table->id;
		
		if ( !$config->language ) {
			$languages = $this->getLanguages();
			if ( !in_array('ru-Ru', $languages) ) {
				$config->language = current($languages)->language;
			}
		}
		
		return $config;
	}
	
	function getLanguages() {
		$this->_db->setQuery("SELECT language,name FROM #__jshopping_languages ORDER BY ordering, name");
		
		return $this->_db->loadObjectList('language');
	}

	/**
	 * Получить список точек восстановления
	 * @return array
	 * @since 3.9
	 */
	function getBackups() {
		$query = "SELECT  *, DATE_FORMAT(date, '%d.%m.%Y %H:%i:%s') AS date2 FROM #__excel2js_backups ORDER BY date DESC";
		return $this->_getList($query);
	}

	/**
	 * Создание точки восстановления
	 * @since 3.9
	 */
	function new_backup() {
		$time_start = $this->getmicrotime();
		$resp       = new stdClass();
		$tables     = [ "#__jshopping_categories", "#__jshopping_products", "#__jshopping_products_attr", "#__jshopping_products_attr2", "#__jshopping_products_images", "#__jshopping_products_prices", "#__jshopping_products_relations", "#__jshopping_products_to_categories", "#__jshopping_products_free_attr", "#__jshopping_products_files", "#__jshopping_manufacturers", "#__jshopping_attr", "#__jshopping_attr_values" ];
		
		foreach ($tables as $key => $t) {
			$tables[$key] = str_replace("#__", $this->_db->getPrefix(), $t);
		}
		if ( !$this->config->backup_type ) {//Обычный SQL
			
			$backup_filename = "js_backup_" . date("d.m.Y_H_i_s") . ".sql";
			$fp              = fopen(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $backup_filename, "a");
			if ( !$fp ) {
				$resp->status = "error";
				if ( !is_writeable(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS) ) {
					$resp->html = JText::_('ERROR_OCCURED_DURING_BACKUP_CHECK_IS_THE_FOLDER_WRIGHTABLE') . JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS;
				}
				else {
					$resp->html = "Возникла ошибка. Проверьте, достаточно ли дискового пространства на хостинге";
				}
				echo json_encode($resp);
				exit();
			}
			
			foreach ($tables as $table) {
				$table = str_replace('#__', $this->_db->getPrefix(), $table);
				
				$fields_list_array = $this->_db->getTableColumns($table);
				$fields_list       = [];
				foreach ($fields_list_array as $key => $field) {
					$fields_list[] = $key;
				}
				$this->_db->setQuery("SELECT COUNT(*) FROM `{$table}`");
				$total = $this->_db->loadResult();
				fwrite($fp, "TRUNCATE TABLE `{$table}`;\n");
				$i = 0;
				for (; ;) {
					if ( $i >= $total ) break;
					$this->_db->setQuery("SELECT * FROM `{$table}`", $i, 200);
					$data = $this->_db->loadAssocList();
					$i    += 200;
					if ( !$data ) break;
					if ( count($fields_list) ) {
						fwrite($fp, "INSERT INTO `{$table}` (`" . implode("`,`", $fields_list) . "`) VALUES\n");
					}
					else {
						fwrite($fp, "INSERT INTO `{$table}` VALUES\n");
					}
					
					
					$rows = [];
					
					foreach ($data as $key => $row) {
						$fields = [];
						foreach ($row as $field) {
							$field    = str_replace(";\n", ";", $field);
							$fields[] = $this->_db->Quote($field);
						}
						$rows[] = "(" . implode(",", $fields) . ")";
					}
					if ( count($rows) ) {
						fwrite($fp, implode(",\n", $rows) . ";\n\n");
					}
					else
						fwrite($fp, ";\n\n");
					
				}
			}
			
			fclose($fp);
			$size = filesize(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $backup_filename);
			if ( $size ) {
				$this->_db->setQuery("INSERT INTO #__excel2js_backups SET file_name = '$backup_filename',size='$size'");
				$this->_db->execute();
			}
			else {
				$resp->status = "error";
				if ( !is_writeable(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS) ) {
					$resp->html = JText::_('ERROR_OCCURED_DURING_BACKUP_CHECK_IS_THE_FOLDER_WRIGHTABLE') . JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS;
				}
				else {
					$resp->html = "Возникла ошибка. Проверьте, достаточно ли дискового пространства на хостинге";
				}
				echo json_encode($resp);
				exit();
			}
		}
		else {
			$backup_filename = "js_backup_" . date("d.m.Y_H_i_s") . ".gz";
			$mainframe       = JFactory::getApplication();
			$command         = "mysqldump -h" . $mainframe->get('host') . " -u" . $mainframe->get('user') . " -p" . $mainframe->get('password') . " " . $mainframe->get('db') . " " . implode(" ", $tables) . " | gzip -9> " . JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $backup_filename;
			
			
			system($command, $output);
			
			if ( $output === 0 ) {
				$size = filesize(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $backup_filename);
				$this->_db->setQuery("INSERT INTO #__excel2js_backups SET file_name = '$backup_filename',size='$size'");
				$this->_db->execute();
			}
			else {
				$resp->status = "error";
				$resp->html   = JText::_('ERROR_OCCURED_DURING_BACKUP_TRY_SQL_BACKUP_METHOD');
				echo json_encode($resp);
				exit();
			}
		}
		
		$id = $this->_db->insertid();
		$this->table->load($id);
		$this->table->dateFormat('date', 'd.m.Y H:i:s');
		$link           = "components/com_excel2js/backup/" . $backup_filename;
		$time_end       = $this->getmicrotime();
		$execution_time = round($time_end - $time_start, 3);
		
		$resp->status = "ok";
		$resp->time   = $execution_time;
		$resp->html   = "<tr id='$id' style='display:none'>
				<td>$id</td>
				<td><a href='$link' target='_blank'>$backup_filename</a></td>
				<td>" . $this->getSize($size) . "</td>
				<td>{$this->table->date}</td>
				<td><li style='display: inline-block' class='ui-state-default ui-corner-all'><span title='Удалить' rel='$id' class='ui-icon ui-icon-circle-close'></span></li></td>
				<td><li style='display: inline-block' class='ui-state-default ui-corner-all'><span title='" . JText::_('RECOVER') . "' rel='$id' class='ui-icon ui-icon-arrowreturnthick-1-w'></span></li></td>

			 </tr>";
		echo json_encode($resp);
		exit();
	}
	
	function getSize($bytes) {
		if ( $bytes < 1024 )
			return $bytes . " B<br>";
		elseif ( $bytes < 1024 * 1024 )
			return round($bytes / 1024) . " KB<br>";
		else
			return round($bytes / (1024 * 1024), 2) . " MB<br>";
	}
	
	function clear() {
		$query       = [];
		$inputCookie = JFactory::getApplication()->input->cookie;
		
		
		$products      = $this->input->get('products', '', 'cmd');
		$cats          = $this->input->get('cats', '', 'cmd');
		$images        = $this->input->get('images', '', 'cmd');
		$manufacturers = $this->input->get('manufacturers', '', 'cmd');
		$options       = $this->input->get('options', '', 'cmd');
		$backups       = $this->input->get('backups', '', 'cmd');
		
		if ( $products == 'true' OR $cats == 'true' ) {
			$inputCookie->set('b_products', 1, time() + (365 * 24 * 3600));
			$query[] = "TRUNCATE TABLE `#__jshopping_products`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_attr`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_attr2`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_free_attr`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_files`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_prices`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_relations`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_reviews`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_to_categories`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_reviews`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_images`";
		}
		else {
			$inputCookie->set('b_products', 0, time() + (365 * 24 * 3600));
		}
		
		if ( $cats == 'true' ) {
			$inputCookie->set('b_cats', 1, time() + (365 * 24 * 3600));
			$query[] = "TRUNCATE TABLE `#__jshopping_categories`";
		}
		else {
			$inputCookie->set('b_cats', 0, time() + (365 * 24 * 3600));
		}
		
		if ( $options == 'true' ) {
			$inputCookie->set('b_options', 1, time() + (365 * 24 * 3600));
			$query[] = "TRUNCATE TABLE `#__jshopping_attr_values`";
			$query[] = "TRUNCATE TABLE `#__jshopping_products_extra_field_values`";
		}
		else {
			$inputCookie->set('b_options', 0, time() + (365 * 24 * 3600));
		}
		
		if ( $manufacturers == 'true' ) {
			$inputCookie->set('b_manufacturers', 1, time() + (365 * 24 * 3600));
			$query[] = "TRUNCATE TABLE `#__jshopping_manufacturers`";
		}
		else {
			$inputCookie->set('b_manufacturers', 0, time() + (365 * 24 * 3600));
		}
		
		if ( $backups == 'true' ) {
			$inputCookie->set('b_backups', 1, time() + (365 * 24 * 3600));
			$this->delete_files(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup', [ 'index.html' ]);
			$this->_db->setQuery("TRUNCATE TABLE #__excel2js_backups");
			$this->_db->execute();
		}
		else {
			$inputCookie->set('b_backups', 0, time() + (365 * 24 * 3600));
		}
		
		foreach ($query as $q) {
			$this->_db->setQuery($q);
			$this->_db->execute();
		}
		
		if ( $images == 'true' ) {
			$inputCookie->set('b_images', 1, time() + (365 * 24 * 3600));
			$this->delete_files(JPATH_ROOT . DS . 'components' . DS . 'com_jshopping' . DS . 'files' . DS . 'img_products', [ 'index.html' ]);
			$this->delete_files(JPATH_ROOT . DS . 'components' . DS . 'com_jshopping' . DS . 'files' . DS . 'img_categories', [ 'index.html' ]);
		}
		else {
			$inputCookie->set('b_images', 0, time() + (365 * 24 * 3600));
		}
		
		echo "Очистка прошла успешно";
		exit();
	}
	
	function restore() {



		$this->table->load($this->id);
		$this->table->dateFormat('date', 'd.m.Y H:i:s');
		if ( substr($this->table->file_name, -3) == 'sql' ) {
			if ( !JFile::exists(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $this->table->file_name) ) {
				echo '<b><span style="color:#FF0000">' . JText::_('DATA_WAS_NOT_RESTORED_FILE_REMOVED') . '</span></b><br />' . $this->_db->ErrorMsg();
				exit();
			}
			
			$query        = '';
			$success      = 0;
			$counter      = 0;
			$file_handler = fopen(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $this->table->file_name, "r");
			while (!feof($file_handler)) {
				$counter++;

				$query .= fgets($file_handler, 16192);

//				echo'<pre>';print_r(  $query );echo'</pre>'.__FILE__.' '.__LINE__;

				try
				{
				    // Code that may throw an Exception or Error.
					if ( substr(trim($query), -1) == ";" ) {
						$this->_db->setQuery($query);
						if ( $this->_db->execute() ) {
							$success++;
							$query = '';
						}
					}
				    // throw new Exception('Code Exception '.__FILE__.':'.__LINE__) ;
				}
				catch (Exception $e)
				{
					echo'<pre>';print_r( $query );echo'</pre>'.__FILE__.' '.__LINE__;
					
				    // Executed only in PHP 5, will not be reached in PHP 7
				    echo 'Выброшено исключение: ',  $e->getMessage(), "\n";
				    echo'<pre>';print_r( $e );echo'</pre>'.__FILE__.' '.__LINE__;
				    die(__FILE__ .' '. __LINE__ );
				}

				
				
			}
//			die(__FILE__ .' '. __LINE__ );

			if ( $success )
				echo JText::_('DATA_SUCCESSFULLY_RECOVERED_AT_THE_TIME_OF') . $this->table->date . ". <br>Количество запросов - $success";
			else
				echo '<b><span style="color:#FF0000">' . JText::_('DATA_WAS_NOT_RESTORED') . '</span></b><br />' . $this->_db->ErrorMsg();
			
			exit();
		}
		else {
			$mainframe = JFactory::getApplication();
			$command   = "gunzip < " . JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $this->table->file_name . " | mysql -h" . $mainframe->get('host') . " -u" . $mainframe->get('user') . " -p" . $mainframe->get('password') . " " . $mainframe->get('db');
			
			system($command, $output);
			if ( $output === 0 )
				echo JText::_('DATA_SUCCESSFULLY_RECOVERED_AT_THE_TIME_OF') . $this->table->date;
			else
				echo '<b><span style="color:#FF0000">' . JText::_('DATA_WAS_NOT_RESTORED') . '</span></b><br />' . $this->_db->ErrorMsg();
			exit();
		}
		
	}
	
	function delete_backup() {
		$this->table->load($this->id);
		if ( !JFile::delete(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $this->table->file_name) ) {
			if ( JFile::exists(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $this->table->file_name) ) {
				echo '<b><span style="color:#FF0000">' . sprintf(JText::_('FILE_S_CAN_NOT_BE_REMOVED'), $this->table->file_name) . '</span></b>';
				exit();
			}
			
		}
		if ( $this->table->delete($this->id) )
			echo sprintf(JText::_('FILE_S_REMOVED'), $this->table->file_name);
		else
			echo sprintf(JText::_('ROW_S_NOT_REMOVED'), $this->table->backup_id);
		exit();
	}
	
	function getmicrotime() {
		list($usec, $sec) = explode(" ", microtime());
		
		return ((float) $usec + (float) $sec);
	}
	
	function delete_files($source, $exclude = []) {
		$dh = opendir($source);
		
		while (($file = readdir($dh)) !== false) {
			if ( filetype($source . DIRECTORY_SEPARATOR . $file) == 'file' AND !in_array($file, $exclude) ) {
				
				unlink($source . DIRECTORY_SEPARATOR . $file);
			}
		}
	}
}

