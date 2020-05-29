<?php defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.archive');
ini_set('log_errors', 'On');
ini_set('error_log', JPATH_ROOT . DS . 'components' . DS . 'com_excel2js' . DS . 'yml_error.txt');

Use Joomla\Utilities\ArrayHelper;

require_once(dirname(__FILE__) . DS . "updateTable.php");

class Excel2jsModelYml extends JModelLegacy {
	public $params;
	public $trans;
	public $timeout;
	public $cron_yml;
	public $export_config;
	public $row;
	
	function __construct($cron = false) {
		parent:: __construct();
		// отображение ошибок
		
		$this->params = JComponentHelper::getParams('com_excel2js');
		$this->app    = JFactory::getApplication();
		$this->input  = $this->app->input;
		ini_set("max_execution_time", $this->params->get('max_execution_time', 300));
		ini_set("upload_max_filesize", $this->params->get('post_max_size', 20) . "M");
		ini_set("post_max_size", $this->params->get('post_max_size', 20) . "M");
		
		$this->debug                 = $this->params->get('db_debug', 0);
		$this->yml_cache             = $this->params->get('yml_cache', 0);
		$this->delivery              = $this->params->get('delivery', 1);
		$this->cron_yml_import       = $this->params->get('cron_yml_import', 1);
		$this->cron_yml_export       = $this->params->get('cron_yml_export', 1);
		$this->stock                 = $this->params->get('stock', 0);
		$this->pickup                = $this->params->get('pickup', 1);
		$this->manufacturer_warranty = $this->params->get('manufacturer_warranty', 1);
		$this->yml_available         = $this->params->get('yml_available', 0);
		$this->store                 = $this->params->get('store', 1);
		$this->yml_description       = $this->params->get('yml_description', 0);
		$this->cut_description       = $this->params->get('cut_description', 0);
		$this->show_old_price        = $this->params->get('show_old_price', 0);
		$this->price_round           = $this->params->get('price_round', 2);
		$this->local_delivery_cost   = $this->params->get('local_delivery_cost', 0);
		$this->sales_notes           = mb_substr(trim($this->params->get('sales_notes')), 0, 100);
		$this->delivery_options      = trim($this->params->get('delivery_options'));
		$this->utm_source            = trim($this->params->get('utm_source'));
		$this->utm_term              = trim($this->params->get('utm_term'));
		
		$this->cron_yml = $cron;
		
		$this->config_table = new updateTable("#__excel2js_yml", "id", 1);
		
		
		$this->trans = [ "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ё" => "yo", "ж" => "j", "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h", "ц" => "c", "ч" => "ch", "ш" => "sh", "щ" => "sh", "ы" => "y", "э" => "e", "ю" => "u", "я" => "ya", "А" => "a", "Б" => "b", "В" => "v", "Г" => "g", "Д" => "d", "Е" => "e", "Ё" => "yo", "Ж" => "j", "З" => "z", "И" => "i", "Й" => "y", "К" => "k", "Л" => "l", "М" => "m", "Н" => "n", "О" => "o", "П" => "p", "Р" => "r", "С" => "s", "Т" => "t", "У" => "u", "Ф" => "f", "Х" => "h", "Ц" => "c", "Ч" => "ch", "Ш" => "sh", "Щ" => "sh", "Ы" => "y", "Э" => "e", "Ю" => "u", "Я" => "ya", "ь" => "", "Ь" => "", "ъ" => "", "Ъ" => "", "/" => "-", "\\" => "", "-" => "-", ":" => "-", "(" => "-", ")" => "-", "." => "", "," => "", '"' => "-", '>' => "-", '<' => "-", '+' => "-", '«' => '', '»' => '', "'" => "", "і" => "i", "ї" => "yi", "І" => "i", "Ї" => "yi", "є" => "e", "Є" => "e" ];
		
		$user                = JFactory::getUser();
		$this->user_id       = $user->id;
		$this->need_profiler = 0;
		if ( file_exists(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "factory.php") ) {
			require_once(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "factory.php");
			$this->JSconfig = JSFactory::getConfig();
		}
		
	}
	
	function translit($text) {
		$trans = strtolower(strtr($text, $this->trans));
		$trans = str_replace(" ", "-", $trans);
		while (strstr($trans, "--"))
			$trans = str_replace("--", "-", $trans);
		$trans = preg_replace('/[\x00-\x2C\x7B-\xFF]/', '', $trans);
		
		
		return $trans;
	}
	
	function category_list() {
		$config   = $this->getYmlExportConfig();
		$languege = $config->languege;
		if ( !$languege ) {
			$languege = 'ru-RU';
		}
		$this->_db->setQuery("SELECT r.category_id,category_name
						  FROM #__virtuemart_categories_" . $languege . " as r
						  LEFT JOIN #__virtuemart_categories as c ON c.category_id = r.category_id
						  WHERE c.category_id IS NOT NULL");
		
		return $this->_db->loadObjectList('category_id');
	}
	
	function change_yml_profile() {
		$profile_id = $this->input->get('profile_id', '', 'int');
		$this->_db->setQuery("UPDATE #__excel2js_yml SET `default` = 0");
		$this->_db->execute();
		$this->config_table->reset();
		$this->config_table->id      = $profile_id;
		$this->config_table->default = 1;
		$this->config_table->update();
	}
	
	function profile_list_yml($data_only = false) {
		$list = $this->_getList("SELECT id, name FROM #__excel2js_yml ORDER BY id");
		if ( $data_only ) return $list;
		array_unshift($list, JHTML::_('select.option', '', JText::_('ADD_NEW'), 'id', 'name'));
		
		echo "<h3>" . JText::_('SELECT_AN_EXISTING_PROFILE_OR_CREATE_A_NEW_ONE') . ":</h3>";
		echo JHTML::_('select.genericlist', $list, 'save_profile_id', 'size="1" id="save_profile_id" style="width:280px"', 'id', 'name', 1);
		echo '<input type="hidden" name="task" value="create_profile" />';
		echo '<br /><span style="display:none" id="create_new_profile"><strong>' . JText::_('ENTER_THE_NAME_OF_THE_NEW_PROFILE') . '</strong><br /><input type="text" id="name" name="name" value="" /></span>';
		echo '<br /><input type="button" id="create_profile_form" value="' . JText::_('SAVE') . '" />';
		exit();
	}
	
	function getYmlConfig() {
		$this->_db->setQuery("SELECT * FROM #__excel2js_yml WHERE `default` = 1");
		$conf_data = $this->_db->loadObject();
		@$params = json_decode($conf_data->params);
		@$params->yml_export_path = $conf_data->yml_export_path;
		@$params->yml_import_path = $conf_data->yml_import_path;
		
		return $params;
	}
	
	function getYmlExportConfig() {
		$this->_db->setQuery("SELECT export_params FROM #__excel2js_yml WHERE `default` = 1");
		
		return @json_decode($this->_db->loadResult());
	}
	
	function getProfile() {
		$this->_db->setQuery("SELECT id, name FROM #__excel2js_yml WHERE `default` = 1");
		
		return $this->_db->loadObject();
	}
	
	
	/* Функция экспорта */
	
	
	function yml_export() {
		ob_start();
		$this->sef         = $this->app->get('sef');
		$this->sef_rewrite = $this->app->get('sef_rewrite');
		$this->sef_suffix  = $this->app->get('sef_suffix') ? '.html' : '';
		$this->row         = $this->input->get('row', '0', 'int');
		$this->start_time  = time();
		$this->timeout     = time() + $this->params->get('max_execution_time', 300) - 5;
		$this->last_upd    = time() - 4;
		
		require(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "router.php");
		
		$this->immport();
		
		
		if ( count($_POST) ) {
			$this->save_config_yml_export();
		}
		$this->export_config = $this->getYmlExportConfig();
		if ( $this->cron_yml ) {
			$this->_db->setQuery("SELECT yml_export_path, export_params FROM #__excel2js_yml WHERE id = $this->cron_yml_export");
			$export_profile = $this->_db->loadObject();
			if ( !$export_profile ) {
				echo "Проверьте 'Профиль экспорта YML по расписанияю' в общих настройках компонента. Возможно, нужный профиль был удален";
				exit();
			}
			$yml_export_path     = $export_profile->yml_export_path;
			$this->export_config = json_decode($export_profile->export_params);
		}
		else {
			$yml_export_path = $this->input->get('yml_export_path', '', 'string');
		}
		
		if ( !$yml_export_path ) {
			$yml_export_path = JPATH_ROOT . DS . "ymarket.xml";
		}
		$dir = dirname($yml_export_path);
		if ( !file_exists($dir) ) {
			$this->print_answer("Папка $dir не существует. Проверьте правильность пути");
		}
		
		if ( !is_writable($dir) ) {
			$this->print_answer("Папка $dir не доступна на запись. Проверьте права на эту папку");
		}
		
		$ext = pathinfo($yml_export_path, PATHINFO_EXTENSION);
		if ( $ext != 'xml' ) {
			$yml_export_path = str_replace(".$ext", ".xml", $yml_export_path);
		}
		$this->live_site    = str_replace('administrator/components/com_excel2js/models/', '', JURI::root());
		$alternative_domain = $this->params->get('alternative_domain');
		if ( $alternative_domain ) {
			if ( substr($alternative_domain, 0, 7) != 'http://' ) {
				$alternative_domain = 'http://' . $alternative_domain;
			}
			if ( substr($alternative_domain, -1) != '/' ) {
				$alternative_domain = $alternative_domain . '/';
			}
			$this->live_site = $alternative_domain;
		}
		
		
		$this->yml_file = $yml_export_path;
		
		
		//Устанавливаем валюту
		
		$this->_db->setQuery("SELECT mainCurrency FROM #__jshopping_config WHERE id = 1");
		$this->default_currency = $this->_db->loadResult();
		
		$this->_db->setQuery("SELECT currency_id, currency_code, currency_value FROM #__jshopping_currencies");
		$this->currency_list = $this->_db->loadObjectList('currency_id');
		
		if ( $this->export_config->currency ) {
			$this->currency_code    = $this->currency_list[$this->export_config->currency]->currency_code;
			$this->main_currency_id = $this->export_config->currency;
		}
		else {
			$this->currency_code    = $this->currency_list[$this->default_currency]->currency_code;
			$this->main_currency_id = $this->default_currency;
		}
		
		
		if ( !$this->row ) {//Это первый запуск
			file_put_contents($yml_export_path, "");
			copy(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'libraries' . DS . 'shops.dtd', $dir . DS . 'shops.dtd');
			$this->yml_file_init();
			
			$this->print_categories();
			if ( $this->local_delivery_cost AND !$this->delivery_options ) {
				$this->insert_tag("local_delivery_cost", $this->local_delivery_cost, 2);
			}
			
			if ( $this->delivery_options ) {
				$delivery_options = explode("\n", $this->delivery_options);
				if ( count($delivery_options) AND !empty($delivery_options[0]) ) {
					$this->insert_tag("delivery-options", "", 2, 1);
					foreach ($delivery_options as $v) {
						if ( empty($v) ) {
							continue;
						}
						
						$option_attrs = explode(";", $v);
						if ( count($option_attrs) == 2 ) {
							$this->insert_tag("option", '', 3, 0, [ "cost" => $option_attrs[0], "days" => $option_attrs[1] ]);
						}
						elseif ( count($option_attrs) == 3 ) {
							$this->insert_tag("option", '', 3, 0, [ "cost" => $option_attrs[0], "days" => $option_attrs[1], "order-before" => $option_attrs[2] ]);
						}
					}
					
					$this->insert_tag("delivery-options", "", 2, 2);
				}
			}
			
			$this->insert_tag("offers", "", 2, 1);
		}
		else {
			$log_data         = file_get_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'yml-export-log.txt');
			$log_data         = json_decode($log_data);
			$this->start_time = $log_data->start_time;
		}
		
		
		$this->print_products();
		
		$this->insert_tag("offers", "", 2, 2);
		$this->yml_file_end();
		
		$this->updateExportStat($this->total_products, $this->total_products, 1);
		
		if ( $this->real_time ) {
			header('Content-Type: application/xml; charset=utf-8');
			echo file_get_contents($yml_export_path);
			exit();
		}
		
		$link = str_replace(DS, '/', str_replace(JPATH_ROOT . DS, JURI::root(), $yml_export_path));
		
		if ( $this->cron_yml ) {
			$link = str_replace('/administrator/components/com_excel2js/models', '', $link);
		}
		
		$this->print_answer("Ссылка на XML - <a href='$link' target='_blank'>$link</a>", 1);
		
		
	}
	
	function yml_sanitize($string) {
		$string = preg_replace('/[\x00-\x08\xB\xC\xE-\x1F]/', '', $string);
		
		return htmlspecialchars(trim($string));
	}
	
	function yml_file_wright($string, $level = 0, $force = false) {
		$tabs = '';
		for ($i = 0 ; $i < $level ; $i++) {
			$tabs .= "\t";
		}
		$this->buffer .= $tabs . $string . "\n";
		if ( strlen($this->buffer) > (1024 * 1024) OR $force ) {
			file_put_contents($this->yml_file, $this->buffer, FILE_APPEND);
			$this->buffer = '';
		}
	}
	
	function yml_file_init() {
		
		@$this->buffer = '';
		
		$this->yml_file_wright('<' . '?xml version="1.0" encoding="UTF-8"?' . '>');
		$this->yml_file_wright('<!DOCTYPE yml_catalog SYSTEM "shops.dtd">');
		$this->yml_file_wright('<yml_catalog date="' . date("Y-m-d H:i") . '">');
		$this->yml_file_wright('<shop>', 1);
		
		
		$this->_db->setQuery("SELECT shop_name,company_name FROM #__jshopping_vendors WHERE id = 1");
		$this->vendor = $this->_db->loadObject();
		$company      = $this->vendor->company_name;
		$name         = $this->vendor->shop_name;
		$company      = $company ? $company : "Укажите название магазина в настройках JS";
		$name         = $name ? $name : "Укажите название магазина в настройках JS";
		
		$this->insert_tag("name", mb_substr($name, 0, 20, 'UTF-8'), 2);
		$this->insert_tag("company", $company, 2);
		$this->insert_tag("url", $this->live_site, 2);
		$this->print_currencies();
		
		
	}
	
	function yml_file_end() {
		$this->yml_file_wright('</shop>', 1);
		$this->yml_file_wright('</yml_catalog>', 0, true);
	}
	
	function print_currencies() {
		
		$this->insert_tag("currencies", "", 2, 1);
		
		
		$rate = in_array($this->currency_code, [ "RUR", "RUB", "UAH", "BYN", "KZT" ]) ? 1 : "CBRF";
		if ( $rate != 1 ) {
			$this->insert_tag("currency", "", 3, 3, [ "id" => "RUR", "rate" => 1, "plus" => 0 ]);
		}
		$this->insert_tag("currency", "", 3, 3, [ "id" => $this->currency_code, "rate" => $rate, "plus" => 0 ]);
		
		$this->insert_tag("currencies", "", 2, 2);
		
	}
	
	function insert_tag($tag_name, $value, $level, $type = 0, $atribs = []) {
		$atributes = '';
		if ( count($atribs) ) {
			foreach ($atribs as $key => $v) {
				$atributes .= ' ' . $key . ' = "' . $this->yml_sanitize($v) . '"';
			}
		}
		switch ($type) {
			case 0:
				$this->yml_file_wright('<' . $tag_name . $atributes . '>' . $this->yml_sanitize($value) . '</' . $tag_name . '>', $level);
				break;
			case 1:
				$this->yml_file_wright('<' . $tag_name . $atributes . '>', $level);
				break;
			case 2:
				$this->yml_file_wright('</' . $tag_name . '>', $level);
				break;
			case 3:
				$this->yml_file_wright('<' . $tag_name . $atributes . ' />', $level);
				break;
		}
		
	}
	
	function print_answer($msg, $success = false) {
		ob_get_flush();
		if ( $this->cron_yml ) {
			echo nl2br($msg);
			file_put_contents(dirname(__FILE__) . "/cron_yml_log.txt", date("Y-m-d H:i:s") . " - $msg\n", FILE_APPEND);
			exit();
		}
		else {
			header("Content-Type: content=text/html; charset=utf-8");
			$answer         = new stdClass();
			$answer->msg    = $msg;
			$answer->status = $success ? "ok" : "error";
			echo json_encode($answer);
			exit();
		}
		
	}
	
	function check_timeout($row) {
		if ( time() >= $this->timeout AND !$this->cron_yml ) { //Тайм аут
			ob_get_flush();
			$this->yml_file_wright('', 0, true);
			header("Content-Type: content=text/html; charset=utf-8");
			$answer         = new stdClass();
			$answer->row    = $row + 1;
			$answer->status = "timeout";
			echo json_encode($answer);
			exit();
		}
	}
	
	function getCategories($cat_id, &$cat_array = []) {
		
		if ( !$cat_id ) {
			return false;
		}
		
		$this->_db->setQuery("SELECT category_child_id
                      FROM #__virtuemart_category_categories
                      WHERE category_parent_id = $cat_id
                      ");
		$children = $this->_db->loadColumn();
		foreach ($children as $id) {
			$cat_array[] = $id;
			$this->getCategories($id, $cat_array);
		}
		
		return $cat_array;
		
	}
	
	function getManufacturers() {
		$config   = $this->getYmlExportConfig();
		$languege = $config->languege;
		if ( !$languege ) {
			$languege = 'ru-RU';
		}
		$this->_db->setQuery("SELECT manufacturer_id, `name_{$languege}`as mf_name FROM #__jshopping_manufacturers ORDER BY `name_{$languege}`");
		
		return $this->_db->loadObjectList();
	}
	
	function print_categories() {
		try {
			$query = 'SELECT category_parent_id, category_id, `name_' . $this->export_config->languege . '` as category_name
            FROM #__jshopping_categories
            ORDER BY category_id';
			$this->_db->setQuery($query);
			
			$rows = $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			$this->print_answer($e->getMessage());
		}
		$this->insert_tag("categories", "", 2, 1);
		if ( !empty($rows) ) {
			foreach ($rows as $row) {
				$cat_parent_id = $row->category_parent_id;
				$cat_child_id  = $row->category_id;
				$cat_name      = $row->category_name;
				if ( $cat_name == '' ) {
					continue;
				}
				$params = [];
				if ( $cat_parent_id > 0 ) {
					$params["parentId"] = $cat_parent_id;
				}
				$params["id"] = $cat_child_id;
				
				$this->insert_tag("category", $cat_name, 3, 0, $params);
			}
		}
		
		$this->insert_tag("categories", "", 2, 2);
	}
	
	function print_products() {
		
		$filter = '';
		if ( @count($this->export_config->export_categories) ) {
			if ( $this->export_config->export_resume ) {
				$filter = " AND c.category_id NOT IN(" . implode(",", $this->export_config->export_categories) . ")";
			}
			else {
				$filter = " AND c.category_id IN(" . implode(",", $this->export_config->export_categories) . ")";
			}
		}
		
		if ( @count($this->export_config->export_manufacturers) ) {
			$filter .= " AND p.product_manufacturer_id IN(" . implode(",", $this->export_config->export_manufacturers) . ")";
		}
		
		if ( @$this->export_config->not_in_stock ) {
			$filter .= " AND p.product_quantity > 0 ";
		}
		
		
		$i = $this->row;
		for (; ;) {
			$query = '
          SELECT
          SQL_CALC_FOUND_ROWS
          DISTINCT p.product_id,
          p.*,
          p.product_ean,
          p.product_quantity,
          p.`name_' . $this->export_config->languege . '` as product_name,
          p.`description_' . $this->export_config->languege . '` as product_desc,
          p.product_old_price,
          p.product_price,
          p.currency_id,
          m.`name_' . $this->export_config->languege . '` as mf_name,
          p.product_manufacturer_id,
          GROUP_CONCAT(c.category_id ORDER BY c.category_id ASC) as category_id
          FROM #__jshopping_products p
          LEFT JOIN #__jshopping_products_to_categories as c ON c.product_id = p.product_id
          LEFT JOIN #__jshopping_manufacturers as m ON p.product_manufacturer_id = m.manufacturer_id
          WHERE p.product_publish = 1 AND p.product_price > 0 ' . $filter . '
          GROUP BY p.product_id';
			try {
				$this->_db->setQuery($query, $i, 500);
				
				$rows = $this->_db->loadObjectList();
			}
			catch (Exception $e) {
				$this->print_answer($e->getMessage());
			}
			
			$i = $i + 500;
			$this->_db->setQuery("SELECT FOUND_ROWS()");
			$this->total_products = $this->_db->loadResult();
			
			if ( empty($rows) ) {
				break;
			}
			$total_rows = count($rows);
			for ($a = 0 ; $a < $total_rows ; $a++) {
				$product_name = $rows[$a]->product_name;
				if ( !$product_name ) {
					continue;
				}
				$product_id = $rows[$a]->product_id;
				
				$product_cat_ids = explode(",", $rows[$a]->category_id);
				
				
				$product_cat_id = $product_cat_ids[0];
				
				$offer_params = [];
				
				if ( $rows[$a]->mf_name ) {
					$offer_params['type'] = "vendor.model";
				}
				$offer_params['id'] = $product_id;
				if ( $this->yml_available ) {
					$offer_params['available'] = 'true';
				}
				else {
					$offer_params['available'] = $rows[$a]->product_quantity > 0 ? 'true' : 'false';
				}
				
				$this->insert_tag("offer", '', 3, 1, $offer_params);
				$url = str_replace('/' . '/index.php', '/index.php', $this->live_site . $this->urlMarketEncode('index.php?option=com_jshopping&controller=product&task=view&product_id=' . $product_id . '&category_id=' . $product_cat_id));
				if ( $this->utm_source ) {
					$url .= (strpos($url, "?") ? "&amp;" : "?") . "utm_source=" . $this->utm_source;
				}
				if ( $this->utm_term ) {
					$url .= (strpos($url, "?") ? "&amp;" : "?") . "utm_term=" . $product_id;
				}
				$this->insert_tag("url", $url, 4);
				
				//пересчет цены
				$product_price = $rows[$a]->product_price;
				$rate=1;
				if ( $this->main_currency_id == $rows[$a]->currency_id ) {
					$rate = 1;
				}
				elseif(isset($this->currency_list[$rows[$a]->currency_id])) {
					$rate = $this->currency_list[$this->main_currency_id]->currency_value / $this->currency_list[$rows[$a]->currency_id]->currency_value;
				}
				$product_price = $product_price * $rate * $this->export_config->export_factor;
				
				$this->insert_tag("price", round($product_price, $this->price_round), 4);
				if ( $this->show_old_price ) {
					$old_price = $rows[$a]->product_old_price * $rate * $this->export_config->export_factor;
					if ( $old_price > 0 AND $old_price > $product_price ) {
						$this->insert_tag("oldprice", round($old_price, $this->price_round), 4);
					}
					
				}
				$this->insert_tag("currencyId", $this->currency_code, 4);
				
				if ( $rows[$a]->product_weight > 0 ) {
					$this->insert_tag("weight", $rows[$a]->product_weight, 4);
				}
				
				//Указываем все категории
				foreach ($product_cat_ids as $product_cat_id) {
					$this->insert_tag("categoryId", $product_cat_id, 4);
				}
				$this->insert_tag("delivery", $this->delivery ? "true" : "false", 4);
				
				
				if ( $rows[$a]->mf_name ) {
					$this->insert_tag("vendor", $rows[$a]->mf_name, 4);
					$this->insert_tag("model", $product_name, 4);
					$this->insert_tag("vendorCode", $rows[$a]->product_ean, 4);
				}
				else {
					$this->insert_tag("name", $product_name, 4);
				}
				
				
				$this->getImages($product_id);
				
				$this->insert_tag("manufacturer_warranty", $this->manufacturer_warranty ? "true" : "false", 4);
				if ( $this->pickup > 0 ) {
					$this->insert_tag("pickup", $this->pickup ? "true" : "false", 4);
				}
				if ( $this->store > 0 ) {
					$this->insert_tag("store", $this->store ? "true" : "false", 4);
				}
				
				
				if ( $this->sales_notes ) {
					$this->insert_tag("sales_notes", $this->sales_notes, 4);
				}
				
				
				if ( $rows[$a]->product_desc ) {
					if ( $this->cut_description ) {
						switch ($this->yml_description) {
							case 0:
								$this->insert_tag("description", $this->substr(htmlspecialchars($rows[$a]->product_desc), 175), 4);
								break;
							case 1:
								$this->insert_tag("description", $this->substr(strip_tags($rows[$a]->product_desc), 175), 4);
								break;
							case 2:
								$this->insert_tag("description", "<![CDATA[" . $this->substr($rows[$a]->product_desc, 163) . "]]>", 4);
								break;
						}
					}
					else {
						switch ($this->yml_description) {
							case 0:
								$this->insert_tag("description", htmlspecialchars($rows[$a]->product_desc), 4);
								break;
							case 1:
								$this->insert_tag("description", strip_tags($rows[$a]->product_desc), 4);
								break;
							case 2:
								$this->insert_tag("description", "<![CDATA[" . $rows[$a]->product_desc . "]]>", 4);
								break;
						}
					}
					
					
				}
				
				
				//Характеристики
				
				$this->_db->setQuery("SELECT id,`name_" . $this->export_config->languege . "` as param_name, type
                   FROM #__jshopping_products_extra_fields
                   ");
				
				$customs = $this->_db->loadObjectList();
				
				if ( count($customs) ) {
					foreach ($customs as $c) {
						
						if ( empty($rows[$a]->{'extra_field_' . $c->id}) ) {
							continue;
						}
						else {
							$value = $rows[$a]->{'extra_field_' . $c->id};
							if ( $c->type == 0 ) {
								$this->_db->setQuery("SELECT `name_" . $this->export_config->languege . "` FROM #__jshopping_products_extra_field_values WHERE id = '$value'");
								$value = $this->_db->loadResult();
							}
							$this->insert_tag("param", $value, 4, 0, [ "name" => $c->param_name ]);
						}
						
					}
				}
				
				
				$this->insert_tag("offer", '', 3, 2);
				
				$this->updateExportStat($i - 500 + $a, $this->total_products);
				
				unset($rows[$a]);
				$this->check_timeout($i - 500 + $a);
			}
		}
		
		
	}
	
	function urlMarketEncode($url) {
		if ( $this->sef ) {
			$url_parsed = parse_url($url);
			parse_str($url_parsed['query'], $query);
			$url = ($this->sef_rewrite ? "" : "index.php/") . $this->get_slug_path($query['product_id']);
		}
		
		/*$url_arr = explode('/', $url);
    	$url_st = '';
    	foreach ($url_arr as $st) {
    		$url_st .= '/'.urlencode($st);
    	}*/
		$url_st = preg_replace("#(?<!^http:)/{2,}#i", "/", $url);
		
		//return substr($url_st,1);
		return $url_st;
	}
	
	function get_slug_path($product_id) {
		$this->_db->setQuery("SELECT category_id FROM #__jshopping_products_to_categories WHERE product_id = $product_id");
		$category_id = $this->_db->loadResult();
		
		if ( !$category_id ) return false;
		
		$query = [ "controller" => "product", "view" => "product", "category_id" => $category_id, "task" => "view", "product_id" => $product_id ];
		$path  = jshoppingBuildRoute($query);
		
		return implode("/", $path) . $this->sef_suffix;
	}
	
	
	function getImages($id) {
		$img_path = $this->JSconfig->image_product_live_path;
		if ( substr($img_path, -1) != "/" ) {
			$img_path = $img_path . "/";
		}
		$query = 'SELECT image_name
                  FROM #__jshopping_products_images
                  WHERE product_id = ' . $id . ' ORDER BY ordering, image_id';
		$this->_db->setQuery($query);
		$images = $this->_db->loadColumn();
		if ( $images ) {
			foreach ($images as $image) {
				$this->insert_tag("picture", $img_path . str_replace(' ', '%20', 'full_' . $image), 4);
			}
		}
	}
	
	
	/* Функции импорта */
	function yml_import() {
		$lock = fopen(dirname(__FILE__) . DS . 'yml.run', 'w');
		if ( !flock($lock, LOCK_EX | LOCK_NB) ) {
			header('HTTP/1.1 502 Gateway Time-out');
			jexit();
		}
		ob_start();
		$this->start_time   = time();
		$this->check_abort  = time() + 10;
		$max_execution_time = ini_get('max_execution_time');
		$max_execution_time = $max_execution_time ? $max_execution_time : 300;
		$this->timeout      = time() + $max_execution_time - 5;
		$this->mem_total    = $this->get_mem_total();
		
		$this->_db->setQuery("SELECT * FROM #__jshopping_config WHERE id = 1");
		$this->JSConfig = $this->_db->loadObject();
		
		
		$this->products_row  = $this->JSConfig->count_products_to_row;
		$this->products_page = $this->JSConfig->count_products_to_page;
		
		
		$this->reimport = $this->input->get('reimport', '', 'int');
		if ( $this->reimport ) {
			@$data = json_decode(file_get_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'yml-log.txt'));
			@$this->stat['pn'] = (int) $data->pn;
			@$this->stat['pu'] = (int) $data->pu;
			@$this->stat['cn'] = (int) $data->cn;
			@$this->stat['cu'] = (int) $data->cu;
			@$this->counter = (int) $data->cur_row;
			$this->start_time = (int) time() - $data->time;
		}
		else {
			$this->counter = 1;
			$this->immport();
		}
		if ( $this->cron_yml ) {
			$this->_db->setQuery("SELECT yml_import_path,params FROM #__excel2js_yml WHERE id = $this->cron_yml_import");
			$conf_data       = $this->_db->loadObject();
			$yml_import_path = $conf_data->yml_import_path;
			@$this->import_config = json_decode($conf_data->params);
		}
		else {
			$yml_import_path = $this->input->get('yml_import_path', '', 'string');
			$this->save_config_yml_import();
			@$this->import_config = $this->getYmlConfig();
		}
		
		if ( !$yml_import_path ) $this->print_answer("Не указан файл для импорта");
		
		if ( $this->yml_cache AND !$this->cron_yml ) {
			if ( !file_exists(dirname(__FILE__) . DS . 'local.xml') ) {
				file_put_contents(dirname(__FILE__) . DS . 'local.xml', file_get_contents($yml_import_path));
			}
			$yml_import_path = dirname(__FILE__) . DS . 'local.xml';
		}
		
		$this->_db->setQuery("SELECT attr_id, `name_" . $this->import_config->languege . "` as name FROM #__jshopping_attr WHERE independent = 0");
		$this->depend = $this->_db->loadObjectList('attr_id');
		
		$xml = simplexml_load_file($yml_import_path);
		
		
		if ( !$xml ) {
			$xml_errors      = libxml_get_errors();
			$xml_errors_list = [];
			if ( count($xml_errors) ) {
				
				foreach ($xml_errors as $key => $v) {
					$xml_errors_list[] = ($key + 1) . ") Строка - $v->line; Столбец - $v->column;  $v->message";
				}
				$xml_errors_list = implode("\n\r", $xml_errors_list);
				
			}
			$this->print_answer("Файл импорта не может быть прочитан т.к. содержит ошибки:\n\r" . $xml_errors_list);
		}
		
		$this->check_currencies($xml->shop->currencies->currency);
		
		if ( !$this->reimport ) {
			$this->categories($xml->shop->categories->category);
		}
		
		if ( method_exists($xml->shop->offers->offer, "count") ) {
			$this->numRow = $xml->shop->offers->offer->count();
		}
		else {
			echo "<span style=\"color: #CC0000\">Импортируемый файл не соответствует формату YML</span>";
			exit();
		}
		
		
		$this->products($xml->shop->offers->offer);
		$this->end_import();
		exit();
	}
	
	function check_currencies($currencies) {
		$this->curr_preset        = [];
		$this->curr_preset['RUB'] = json_decode('{"currency_name":"Russian ruble","currency_code":"RUB","currency_numeric_code":643}');
		$this->curr_preset['RUR'] = json_decode('{"currency_name":"Russian ruble","currency_code":"RUB","currency_numeric_code":643}');
		$this->curr_preset['BYR'] = json_decode('{"currency_name":"Belarusian ruble","currency_code":"BYR","currency_numeric_code":974}');
		$this->curr_preset['KZT'] = json_decode('{"currency_name":"Kazakhstani tenge","currency_code":"KZT","currency_numeric_code":398}');
		$this->curr_preset['UAH'] = json_decode('{"currency_name":"Ukrainian hryvnia","currency_code":"UAH","currency_numeric_code":980}');
		$this->curr_preset['USD'] = json_decode('{"currency_name":"United States dollar","currency_code":"USD","currency_numeric_code":840}');
		$this->curr_preset['EUR'] = json_decode('{"currency_name":"Euro","currency_code":"EUR","currency_numeric_code":978}');
		
		if ( !isset($currencies) ) {
			return false;
		}
		
		foreach ($currencies as $key => $c) {
			/** @noinspection PhpUndefinedMethodInspection */
			$attr = $c->attributes();
			$id   = (string) $attr->id;
			if ( isset($this->curr_preset[$id]) ) {
				if ( $this->curr_preset[$id]->currency_code == 'RUR' OR $this->curr_preset[$id]->currency_code == 'RUB' ) {
					$this->_db->setQuery("SELECT currency_id FROM #__jshopping_currencies WHERE currency_code = 'RUR' OR currency_code = 'RUB'");
				}
				else {
					$this->_db->setQuery("SELECT currency_id FROM #__jshopping_currencies WHERE currency_code = " . $this->_db->Quote($this->curr_preset[$id]->currency_code));
				}
				
				$currency_id = $this->_db->loadResult();
				if ( !$currency_id ) {
					$this->_db->setQuery("INSERT INTO #__jshopping_currencies SET
                    currency_code = " . $this->_db->Quote($this->curr_preset[$id]->currency_code) . ",
                    currency_name = " . $this->_db->Quote($this->curr_preset[$id]->currency_name) . ",
                    currency_publish = 1,
                    currency_code_num = " . $this->_db->Quote($this->curr_preset[$id]->currency_numeric_code));
					$this->_db->execute();
					$currency_id = $this->_db->insertid();
				}
				$this->curr_preset[$id]->currency_id = $currency_id;
			}
			
		}
	}
	
	function categories($categories) {
		foreach ($categories as $category) {
			/** @noinspection PhpUndefinedMethodInspection */
			$attr      = $category->attributes();
			$cat_id    = (int) $attr['id'];
			$parent_id = (int) $attr['parentId'];
			$cat_name  = (string) $category;
			$alias     = $cat_id . "-" . $this->translit($cat_name);
			$this->_db->setQuery("SELECT category_id FROM #__jshopping_categories WHERE category_id = $cat_id");
			if ( !$this->_db->loadResult() ) {
				$this->_db->setQuery("INSERT INTO #__jshopping_categories
                              SET category_id = $cat_id,
                              category_parent_id = $parent_id,
                              products_page='$this->products_page',
                              products_row='$this->products_row',
                              category_add_date = NOW(),
                              `name_" . $this->import_config->languege . "`= " . $this->_db->Quote($cat_name) . ",
                              `alias_" . $this->import_config->languege . "`= " . $this->_db->Quote($alias) . "
                              ");
				$this->_db->execute();
				@$this->stat['cn']++;
			}
			else {
				@$this->stat['cu']++;
			}
		}
	}
	
	function products($products) {
		
		
		for ($this->counter ; $this->counter <= $this->numRow ; $this->counter++) {
			$p = $products[$this->counter - 1];
			
			
			$product_id = $this->getProductID($p, $new);
			if ( !$product_id ) continue;
			
			if ( $p->vendor ) {
				$manufacturer_id = (int) $this->getManufacturer((string) $p->vendor);
				$this->assign_manufacturer($product_id, $manufacturer_id);
			}
			
			$this->assign_category($product_id, (int) $p->categoryId, $new);
			
			
			if ( $this->import_config->images_mode == 2 OR ($this->import_config->images_mode == 1 AND $new) ) {
				$this->assign_pictures($product_id, (array) $p->picture);
			}
			
			$this->assign_params($product_id, $p);
			
			$this->updateStat();
			
		}
	}
	
	function getManufacturer($mf_name) {
		$manufacturer_id = array_search($mf_name, $this->manufaturers_list);
		if ( !$manufacturer_id ) {
			$this->_db->setQuery("SELECT manufacturer_id FROM #__jshopping_manufacturers WHERE `name_" . $this->import_config->languege . "`=" . $this->_db->Quote($mf_name));
			$manufacturer_id = $this->_db->loadResult();
			
		}
		if ( !$manufacturer_id ) {
			$this->_db->setQuery("INSERT INTO #__jshopping_manufacturers SET manufacturer_id=NULL, manufacturer_publish=1, `name_" . $this->import_config->languege . "`=" . $this->_db->Quote($mf_name) . ",`alias_" . $this->import_config->languege . "`=" . $this->_db->Quote($this->translit($mf_name)) . ", products_page = '{$this->products_page}', products_row = '{$this->products_row}'");
			$this->_db->execute();
			$manufacturer_id = $this->_db->insertid();
		}
		if ( $manufacturer_id ) {
			$this->manufaturers_list[$manufacturer_id] = $mf_name;
		}
		
		return $manufacturer_id;
	}
	
	function getCurrencyID($code) {
		if ( !@$this->currencyIDs[$code] ) {
			if ( $code == 'RUR' OR $code == 'RUB' ) {
				$this->_db->setQuery("SELECT currency_id FROM #__jshopping_currencies WHERE currency_code = 'RUB' OR currency_code = 'RUR'");
			}
			else {
				$this->_db->setQuery("SELECT currency_id FROM #__jshopping_currencies WHERE currency_code = " . $this->_db->Quote($code) );
			}
			
			$currency_id = $this->_db->loadResult();
			if ( $currency_id ) {
				$this->currencyIDs[$code] = $this->_db->loadResult();
			}
			
		}
		
		return $this->currencyIDs[$code];
	}
	
	function getProductID($p, &$new) {
		
		/** @noinspection PhpUndefinedMethodInspection */
		$attr         = $p->attributes();
		$product_id   = (int) @$attr->id;
		$group_id     = (int) @$attr->group_id;
		$product_sku  = (string) @$p->vendorCode;
		$product_name = (string) @$p->name;
		if ( !$product_name ) {
			$product_name = (string) @$p->model;
		}
		@$this->current_product = $product_name ? $product_name : $product_sku;
		$published      = (string) @$attr->available;
		$published      = ($published == 'true') ? 1 : 0;
		$full_desc      = htmlspecialchars_decode((string) @$p->description);
		$product_weight = (float) @$p->weight;
		
		
		switch ($this->import_config->identity) {
			case 0:
				if ( !$product_id ) return false;
				$new = $this->is_productId_new($product_id);
				break;
			case 1:
				if ( !$product_sku ) return false;
				$product_id = $this->get_productId_by_sku($product_sku);
				$new        = !$product_id ? true : false;
				break;
			case 2:
				if ( !$product_name ) return false;
				$product_id = $this->get_productId_by_name($product_name);
				$new        = !$product_id ? true : false;
				break;
			default:
				return false;
			
		}
		
		if ( $group_id ) {
			$product_id = $group_id;
			$new        = $this->is_productId_new($product_id);
		}
		else {
			if ( $new AND !$this->import_config->is_create ) return false;
			if ( !$new AND !$this->import_config->is_update ) return false;
		}
		
		
		$product_id = $product_id ? $product_id : NULL;
		if ( !isset($this->import_config->product_in_stock_default) ) {
			$this->import_config->product_in_stock_default = 10;
		}
		$in_stock = $this->import_config->product_in_stock_default;
		if ( isset($p->outlets) ) {
			/** @noinspection PhpUndefinedMethodInspection */
			$outlet = $p->outlets->outlet[0]->attributes();
			
			$in_stock = (int) $outlet->instock;
		}
		
		
		$alias = $this->getAlias($product_name, $product_id, $product_sku);
		
		$product_price = (float) @$p->price;
		$product_price = round($product_price * $this->import_config->import_factor, 2);
		
		$product_old_price = (float) @$p->oldprice;
		$product_old_price = round($product_old_price * $this->import_config->import_factor, 2);
		
		$currency_id = $this->getCurrencyID((string) @$p->currencyId);
		
		$this->_db->setQuery("
                INSERT INTO #__jshopping_products
                SET
                product_id = '$product_id',
                product_ean=" . $this->_db->Quote($product_sku) . ",
                product_weight=" . $this->_db->Quote(@$product_weight) . ",
                product_quantity =" . $this->_db->Quote((int) $in_stock) . ",
                product_publish =" . $this->_db->Quote($published) . ",
                product_date_added = NOW(),
                product_old_price  = " . $product_old_price . ",
                product_price  = " . $product_price . ",
                currency_id  = " . $currency_id . ",
                `name_" . $this->import_config->languege . "` = " . $this->_db->Quote($product_name) . ",
                `alias_" . $this->import_config->languege . "` = " . $this->_db->Quote($alias) . ",
                `description_" . $this->import_config->languege . "` = " . $this->_db->Quote($full_desc) . "

                ON DUPLICATE KEY UPDATE
                product_ean=" . $this->_db->Quote($product_sku) . ",
                product_weight=" . $this->_db->Quote(@$product_weight) . ",
                product_quantity =" . $this->_db->Quote((int) $in_stock) . ",
                product_publish =" . $this->_db->Quote($published) . ",
                date_modify = NOW(),
                product_old_price  = " . $product_old_price . ",
                product_price  = " . $product_price . ",
                currency_id  = " . $currency_id . ",
                `name_" . $this->import_config->languege . "` = " . $this->_db->Quote($product_name) . ",
                `alias_" . $this->import_config->languege . "` = " . $this->_db->Quote($alias) . ",
                `description_" . $this->import_config->languege . "` = " . $this->_db->Quote($full_desc) );
		$this->_db->execute();
		$product_id = $product_id ? $product_id : $this->_db->insertid();
		
		if ( $new ) {
			@$this->stat['pn']++;
			switch ($this->import_config->identity) {
				case 0:
					$this->temp_productID_table[] = $product_id;
					break;
				case 1:
					$this->temp_product_table[$product_id] = $product_sku;
					break;
				case 2:
					$this->temp_product_table_by_name[$product_id] = $product_name;
					break;
				
				
			}
		}
		else {
			@$this->stat['pu']++;
		}
		
		return $product_id;
	}
	
	function is_productId_new($product_id) {
		if ( !$product_id ) return true;
		if ( !@$this->temp_productID_table ) {
			$this->_db->setQuery("SELECT product_id
	                              FROM #__jshopping_products");
			$this->temp_productID_table = $this->_db->loadColumn();
		}
		
		return !in_array($product_id, $this->temp_productID_table);
	}
	
	function get_productId_by_sku($sku) {
		if ( !@$this->temp_product_table ) {
			
			$this->_db->setQuery("SELECT product_id,product_ean
	                              FROM #__jshopping_products");
			$this->temp_product_table = array_combine($this->_db->loadColumn(0), $this->_db->loadColumn(1));
			
		}
		
		return array_search($sku, $this->temp_product_table);
	}
	
	function get_productId_by_name($name) {
		if ( !@$this->temp_product_table_by_name ) {
			
			$this->_db->setQuery("SELECT product_id,`name_" . $this->import_config->languege . "`
	                              FROM #__jshopping_products");
			$this->temp_product_table_by_name = array_combine($this->_db->loadColumn(0), $this->_db->loadColumn(1));
			
		}
		
		return array_search($name, $this->temp_product_table_by_name);
	}
	
	function genAlias($name, $id, $sku, $template = false, $sep = "-") {
		if ( $name )
			$name = $this->translit($name);
		if ( $sku )
			$sku = $this->translit($sku);
		if ( !$template ) $template = $this->import_config->alias_template;
		switch ($template) {
			case 1:
				
				if ( $name )
					$alias = $name;
				elseif ( $sku ) {
					$alias = $this->genAlias($name, $id, $sku, 10, $sep);
				}
				else {
					/*echo '<span style="font-size: 14px;color:red">'.JText::_('ALIAS_COULD_NOT_BE_GENERATED').($this->row+1)." (".$name.$sep.$id.$sep.$sku.')</span>';

					exit();*/
					$alias = $id . $sep . rand(1111111111, 9999999999);
				}
				break;
			case 2:
				$alias = $id . $sep . $this->genAlias($name, $id, $sku, 1, $sep);
				break;
			case 3:
				$alias = $this->genAlias($name, $id, $sku, 1, $sep) . $sep . $id;
				break;
			case 4:
				if ( $sku AND $name ) {
					$alias = $sku . $sep . $name;
				}
				break;
			case 5:
				if ( $sku AND $name ) {
					$alias = $name . $sep . $sku;
				}
				
				break;
			case 6:
				if ( $sku ) {
					$alias = $sku . $sep . $this->genAlias($name, $id, $sku, 2, $sep);
				}
				break;
			case 7:
				if ( $sku AND $name )
					$alias = $id . $sep . $sku . $sep . $name;
				
				break;
			case 8:
				if ( $sku AND $name )
					$alias = $name . $sep . $sku . $sep . $id;
				break;
			case 9:
				if ( $sku AND $name )
					$alias = $name . $sep . $id . $sep . $sku;
				break;
			case 10:
				if ( $sku )
					$alias = $sku;
				break;
			case 11:
				if ( $id )
					$alias = $id;
				break;
			
		}
		if ( empty($alias) )
			$alias = $this->genAlias($name, $id, $sku, 2, $sep);
		while (substr($alias, -1) == '-')
			$alias = substr($alias, 0, -1);
		
		return $alias;
	}
	
	function getAlias($name, $id, $sku, $product = true, $template = false, $sep = "-") {
		
		$alias = $this->genAlias($name, $id, $sku, $template, $sep);
		
		if ( $product ) {
			for (; ;) {
				$this->_db->setQuery("SELECT product_id FROM #__jshopping_products WHERE `alias_" . $this->import_config->languege . "`='$alias'");
				if ( $this->_db->loadResult() AND $this->_db->loadResult() != $id ) {
					$alias = $alias . $sep . rand(1111111111, 9999999999);
				}
				else {
					return $alias;
				}
			}
		}
		else {
			for (; ;) {
				$this->_db->setQuery("SELECT category_id FROM #__jshopping_categories WHERE `alias_" . $this->import_config->languege . "`='$alias'");
				if ( $this->_db->loadResult() AND $this->_db->loadResult() != $id )
					$alias = $alias . $sep . rand(1111111111, 9999999999);
				else
					return $alias;
			}
		}
		
	}
	
	function end_import() {
		$this->last_upd -= 2;
		$this->counter--;
		$this->updateStat();
		
		if ( $this->yml_cache ) {
			@unlink(dirname(__FILE__) . DS . 'local.xml');
		}
		$msg = "Категорий создано: " . (int) @$this->stat['cn'] . "\n";
		$msg .= "Категорий обновлено: " . (int) @$this->stat['cu'] . "\n";
		$msg .= "Товаров создано: " . (int) @$this->stat['pn'] . "\n";
		$msg .= "Товаров обновлено: " . (int) @$this->stat['pu'] . "\n";
		$this->print_answer($msg, true);
	}
	
	function assign_manufacturer($product_id, $manufacturer_id) {
		$this->_db->setQuery("SELECT product_manufacturer_id FROM #__jshopping_products WHERE product_id={$product_id}");
		if ( $this->_db->loadResult() != $manufacturer_id ) {
			$this->_db->setQuery("UPDATE #__jshopping_products SET product_manufacturer_id='{$manufacturer_id}' WHERE product_id={$product_id}");
			$this->_db->execute();
		}
	}
	
	function assign_category($product_id, $category_ids, $new) {
		$category_ids = (array) $category_ids;
		if ( !$new ) {
			$this->_db->setQuery("DELETE FROM #__jshopping_products_to_categories WHERE product_id = '{$product_id}'");
			$this->_db->execute();
		}
		foreach ($category_ids as $category_id) {
			$ordering = $this->counter;
			$this->_db->setQuery("REPLACE INTO `#__jshopping_products_to_categories` (`product_id`, `category_id`,`product_ordering`) VALUES ('$product_id', '$category_id', '$ordering')");
			$this->_db->execute();
		}
	}
	
	function assign_pictures($product_id, $pictures) {
		if ( @empty($pictures[0]) ) {
			return false;
		}
		$img_path = $this->JSconfig->image_product_live_path;
		$this->_db->setQuery("SELECT image_name FROM #__jshopping_products_images WHERE product_id = '$product_id'");
		$old_images = $this->_db->loadColumn();
		if ( count($old_images) ) {
			foreach ($old_images as $old_image) {
				if ( !in_array($old_image, $pictures) ) {
					@ unlink($img_path . $old_image);
					@ unlink($img_path . 'full_' . $old_image);
					@ unlink($img_path . 'thumb_' . $old_image);
				}
			}
			$this->_db->setQuery("DELETE FROM #__jshopping_products_images WHERE product_id = '$product_id'");
			$this->_db->execute();
		}
		
		
		foreach ($pictures as $key => $v) {
			$file_url = $this->get_images_http($v, $product_id);
			if ( !$file_url ) {
				continue;
			}
			if ( $key == 0 ) {
				$this->_db->setQuery("UPDATE #__jshopping_products SET image = " . $this->_db->Quote($file_url) . " WHERE product_id = $product_id");
				$this->_db->execute();
			}
			$this->_db->setQuery("
           INSERT INTO #__jshopping_products_images
           SET
           image_name  = " . $this->_db->Quote($file_url) . ",
           product_id = '$product_id',
           ordering=" . $this->_db->Quote($key + 1) . "
           ");
			$this->_db->execute();
			
		}
		
	}
	
	function get_images_http($file_url, $id) {
		$file_url_ext = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
		if ( strstr($file_url_ext, "?") ) {
			$file_url_ext = substr($file_url_ext, 0, strpos($file_url_ext, "?"));
		}
		$extensions = [ 'jpg', 'jpeg', 'gif', 'png', 'bmp' ];
		
		if ( in_array($file_url_ext, $extensions) ) {
			if ( strstr($file_url, "sima-land.ru") ) {
				$file_url = str_replace(".$file_url_ext", "-nw.$file_url_ext", $file_url);
			}
			//$file=file_get_contents(str_replace(' ', '%20', $file_url));
			
			//if(!@$file){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, str_replace(' ', '%20', $file_url));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$file  = curl_exec($ch);
			$error = curl_error($ch);
			$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ( $code != 200 ) {
				@$this->errors[] = "Строка - $this->counter. Изображение $file_url не загружено. Код ответа - $code." . ($file ? " Ошибка - $file" : "");
				
				return NULL;
			}
			if ( !$file ) {
				@$this->errors[] = "Строка - $this->counter. Изображение $file_url не загружено." . ($error ? " Ошибка - $error" : "");
				
				return NULL;
			}
			//}
			if ( !$file ) return NULL;
			$temp_path = explode("/", $file_url);
			$file_name = strtolower(end($temp_path));
			if ( strstr($file_name, "?") ) {
				$file_name = substr($file_name, 0, strpos($file_name, "?"));
			}
			$file_name = str_replace("." . $file_url_ext, "", $file_name);
			$file_name = $this->translit($file_name);
			$file_name = $id . '_' . $file_name . "." . $file_url_ext;
			
			
			if ( strstr($file_name, '.jpeg') ) {
				$file_name = str_replace('.jpeg', '.jpg', $file_name);
			}
			$pos = strpos($file_name, '?');
			if ( $pos ) {
				$file_name = substr($file_name, 0, $pos);
			}
			
			
			$path = $this->JSconfig->image_product_path;
			
			if ( substr($path, -1) != "/" ) {
				$path .= "/";
			}
			$put_path = str_replace("/", DS, $path);
			
			
			file_put_contents($put_path . 'full_' . $file_name, $file);
			unset($file);
			//Создаем миниатюры
			$this->resizeImageMagic($put_path . 'full_' . $file_name, $this->JSConfig->image_product_full_width, $this->JSConfig->image_product_full_height, 1, 0, $put_path . $file_name);
			$this->resizeImageMagic($put_path . 'full_' . $file_name, $this->JSConfig->image_product_width, $this->JSConfig->image_product_height, 1, 0, $put_path . 'thumb_' . $file_name);
			
			return $file_name;
		}
		else {
			return NULL;
		}
	}
	
	function getParamID($param_name) {
		$param_id = array_search($param_name, $this->param_names);
		
		if ( $param_id ) {
			return $param_id;
		}
		
		$this->_db->setQuery("SELECT id FROM #__jshopping_products_extra_fields
                              WHERE `name_" . $this->import_config->languege . "` = " . $this->_db->Quote($param_name) . "
        ");
		$param_id = $this->_db->loadResult();
		if ( $param_id ) {
			$this->param_names[$param_id] = $param_name;
			
			return $param_id;
		}
		
		$this->_db->setQuery("INSERT INTO #__jshopping_products_extra_fields SET `name_" . $this->import_config->languege . "` = " . $this->_db->Quote($param_name) . " , allcats = 1, cats = 'a:0:{}', type = '1'");
		$this->_db->execute();
		$param_id = $this->_db->insertid();
		
		$this->_db->setQuery("ALTER TABLE #__jshopping_products ADD `extra_field_{$param_id}` VARCHAR( 100 ) NOT NULL");
		$this->_db->execute();
		
		$this->param_names[$param_id] = $param_name;
		
		return $param_id;
	}
	
	function getParamType($param_id) {
		if ( isset($this->param_types[$param_id]) ) {
			return $this->param_types[$param_id];
		}
		$this->_db->setQuery("SELECT type FROM #__jshopping_products_extra_fields WHERE id = $param_id");
		$this->param_types[$param_id] = $this->_db->loadResult();
		
		return $this->param_types[$param_id];
	}
	
	function getOptionID($param_id, $value) {
		$option_id = array_search($value, $this->option_ids[$param_id]);
		if ( $option_id ) {
			return $option_id;
		}
		$this->_db->setQuery("SELECT id FROM #__jshopping_products_extra_field_values WHERE field_id = $param_id AND `name_" . $this->import_config->languege . "` = " . $this->_db->Quote($value) );
		$option_id = $this->_db->loadResult();
		
		if ( $option_id ) {
			$this->option_ids[$param_id][$option_id] = $value;
			
			return $option_id;
		}
		
		$this->_db->setQuery("INSERT INTO #__jshopping_products_extra_field_values SET  field_id = $param_id, `name_" . $this->import_config->languege . "` = " . $this->_db->Quote($value) );
		$this->_db->execute();
		
		$option_id                               = $this->_db->insertid();
		$this->option_ids[$param_id][$option_id] = $value;
		
		return $option_id;
	}
	
	function depended_attr($param, $product_id, $p) {
		/** @noinspection PhpUndefinedMethodInspection */
		$param_attr = $param->attributes();
		$param_name = (string) $param_attr->name;
		foreach ($this->depend as $key => $d) {
			if ( $param_name == $d->name ) {
				$in_stock = $this->import_config->product_in_stock_default;
				if ( isset($p->outlets) ) {
					/** @noinspection PhpUndefinedMethodInspection */
					$outlet = $p->outlets->outlet[0]->attributes();
					
					$in_stock = (int) $outlet->instock;
				}
				$product_sku = (string) @$p->vendorCode;
				$param_id    = $d->attr_id;
				$value       = (string) $param;
				
				$product_price = (float) @$p->price;
				$product_price = round($product_price * $this->import_config->import_factor, 2);
				
				$this->_db->setQuery("SELECT value_id FROM #__jshopping_attr_values WHERE attr_id = $param_id AND `name_" . $this->import_config->languege . "` = " . $this->_db->Quote($value) );
				$value_id = $this->_db->loadResult();
				
				if ( !$value_id ) {
					$this->_db->setQuery("INSERT INTO #__jshopping_attr_values SET attr_id = $param_id , `name_" . $this->import_config->languege . "` = " . $this->_db->Quote($value) );
					$this->_db->execute();
					$value_id = $this->_db->insertid();
				}
				try {
					$this->_db->setQuery("SELECT product_attr_id FROM #__jshopping_products_attr WHERE `attr_{$param_id}` = $value_id AND product_id = $product_id");
					$product_attr_id = $this->_db->loadResult();
					if ( $product_attr_id ) { // обновляем
						$this->_db->setQuery("UPDATE #__jshopping_products_attr SET price = $product_price, count = $in_stock, ean = " . $this->_db->Quote($product_sku) . " WHERE product_attr_id = $product_attr_id");
						$this->_db->execute();
					}
					else {//Добавляем
						$this->_db->setQuery("INSERT INTO #__jshopping_products_attr SET price = $product_price, count = $in_stock, ean = " . $this->_db->Quote($product_sku) . ", `attr_{$param_id}` = $value_id, product_id = $product_id");
						$this->_db->execute();
					}
				}
				catch (Exception $e) {
					return false;
				}
				
				
				return true;
			}
		}
		
		return false;
	}
	
	function assign_params($product_id, $p) {
		$params = $p->param;
		if ( !$params ) {
			return false;
		}
		
		foreach ($params as $param) {
			/** @noinspection PhpUndefinedMethodInspection */
			$param_attr = $param->attributes();
			$param_name = (string) $param_attr->name;
			if ( $this->depend ) {
				if ( $this->depended_attr($param, $product_id, $p) ) {
					continue;
				}
			}
			$param_id = $this->getParamID($param_name);
			
			$value = (string) $param;
			if ( $param_attr->unit ) {
				$value .= " " . $param_attr->unit;
			}
			$param_type = $this->getParamType($param_id);
			if ( !$param_type ) { //список
				$value = $this->getOptionID($param_id, $value);
			}
			try {
				$this->_db->setQuery("UPDATE #__jshopping_products SET `extra_field_{$param_id}` = " . $this->_db->Quote($value) . " WHERE product_id = $product_id");
				$this->_db->execute();
			}
			catch (Exception $e) {
				$this->_db->setQuery("ALTER TABLE #__jshopping_products ADD `extra_field_{$param_id}` VARCHAR( 100 ) NOT NULL");
				$this->_db->execute();
				
				$this->_db->setQuery("UPDATE #__jshopping_products SET `extra_field_{$param_id}` = " . $this->_db->Quote($value) . " WHERE product_id = $product_id");
				$this->_db->execute();
			}
			
		}
	}
	
	function updateStat($not_interrupt = false) {
		if ( time() - @$this->last_upd > 1 ) {
			$this->last_upd = time();
			$data           = new stdClass();
			$data->cur_row  = @$this->counter;
			$data->num_row  = @$this->numRow;
			$data->pn       = (int) @$this->stat['pn'];
			$data->pu       = (int) @$this->stat['pu'];
			$data->cn       = (int) @$this->stat['cn'];
			$data->cu       = (int) @$this->stat['cu'];
			$data->time     = time() - $this->start_time;
			$data->cur_time = time();
			
			$data->cur_prod  = @$this->current_product;
			$data->mem       = $this->get_mem();
			$data->mem_total = $this->mem_total;
			$data->mem_peak  = $this->get_mem_peak();
			
			$data->timeout = 0;
			if ( $this->check_abort < time() AND !$not_interrupt ) {
				if ( @file_get_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'yml-abort.txt') ) {
					file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'yml-abort.txt', 0);
					$this->end_import();
				}
				
			}
			if ( time() >= $this->timeout AND !$not_interrupt ) {
				$data->timeout = 1;
				$data->cur_row++;
				if ( $this->cron_yml ) {
					$max_execution_time = ini_get('max_execution_time');
					$need               = round($data->num_row / $data->cur_row * $max_execution_time);
					$this->print_answer("Импорт остановлен из-за таймуата. Импортировано {$data->cur_row} строк из {$data->num_row}. Для завершения импорта в автоматическом режиме необходимо, чтобы значение max_execution_time было не меньше, чем $need сек.");
				}
				$answer         = new stdClass();
				$answer->status = 'timeout';
				if ( @count($this->errors) ) {
					$answer->errors = implode("<br>", $this->errors);
				}
				else {
					$answer->errors = '';
				}
				echo json_encode($answer);
			}
			file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'yml-log.txt', json_encode($data));
			if ( time() >= $this->timeout AND !$not_interrupt ) {
				exit();
			}
			
		}
	}
	
	function updateExportStat($cur_row, $total_products, $is_end = 0) {
		if ( time() - @$this->last_upd > 1 OR $is_end ) {
			$this->last_upd   = time();
			$data             = new stdClass();
			$data->cur_row    = $cur_row;
			$data->num_row    = $total_products;
			$data->start_time = $this->start_time;
			$data->time       = time() - $this->start_time;
			$data->cur_time   = time();
			$data->is_end     = $is_end;
			file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'yml-export-log.txt', json_encode($data));
		}
	}
	
	function get_mem() {
		if ( function_exists("memory_get_usage") ) {
			$mem_usage = memory_get_usage(true);
			
			return round($mem_usage / 1048576, 2);
		}
		else return false;
	}
	
	function get_mem_total() {
		$mem = ini_get("memory_limit");
		if ( strstr($mem, "M") ) return (float) $mem;
		else {
			return round($mem / 1048576, 2);
		}
	}
	
	function get_mem_peak() {
		if ( function_exists("memory_get_peak_usage") ) {
			$mem_usage = memory_get_peak_usage(true);
			
			return round($mem_usage / 1048576, 2);
		}
		else return false;
	}

    function immport()
    {
        return true;
	}
	
	function substr($string, $len) {
		if ( strlen($string) > $len ) {
			$string = mb_substr($string, 0, $len - 5) . "...";
		}
		
		return $string;
		
	}
	
	function getCurrencies() {
		$this->_db->setQuery("SELECT currency_id, currency_name FROM #__jshopping_currencies ORDER BY currency_id");
		$currencies = $this->_db->loadObjectList('currency_id');
		array_unshift($currencies, JHTML::_('select.option', '0', "Валюта по-умолчанию", 'currency_id', 'currency_name'));
		
		return $currencies;
	}
	
	function getCategoryList($selected_cat = false) {
		if ( !file_exists(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "functions.php") ) {
			throw new Exception("Установите JoomShopping");
		}
		else {
			require_once(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "factory.php");
			require_once(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "functions.php");
			
			$categories = buildTreeCategory(0, 1, 0);
			
			if ( !count($categories) ) {
				//JError::raiseError('',"Создайте категории");
				return false;
			}
			$list = '';
			foreach ($categories as $v) {
				$selected = '';
				if ( is_array($selected_cat) ) {
					$selected = in_array($v->category_id, $selected_cat) ? 'selected=""' : '';
				}
				$list .= "<option $selected value='$v->category_id'>$v->name</option>";
			}
			
			return $list;
		}
	}
	
	function save_config_yml_import() {
		
		$yml_import_path = $this->input->get('yml_import_path', '', 'string');
		$import_config   = $_POST;
		unset($import_config['reimport']);
		unset($import_config['task']);
		unset($import_config['option']);
		unset($import_config['yml_import_path']);
		
		if ( !$import_config['languege'] ) {
			$import_config['languege'] = 'ru-RU';
		}
		$import_config['import_factor'] = (float) str_replace(",", ".", $this->input->get('import_factor', '1', 'string'));
		if ( $import_config['import_factor'] == 0 ) {
			$import_config['import_factor'] = 1;
		}
		$import_config = (object) $import_config;
		$this->_db->setQuery("UPDATE #__excel2js_yml SET  yml_import_path = " . $this->_db->Quote($yml_import_path) . ", params = " . $this->_db->Quote(json_encode($import_config)) . " WHERE `default` = 1");
		$this->_db->execute();
		
	}
	
	function save_config_yml_export() {
		$yml_export_path = $this->input->get('yml_export_path', '', 'string');
		if ( !$yml_export_path ) {
			$yml_export_path = JPATH_ROOT . DS . "ymarket.xml";
		}
		$export_params['languege']             = $this->input->get('languege', '', 'string');
		$export_params['not_in_stock']         = $this->input->get('not_in_stock', '0', 'int');
		$export_params['currency']             = $this->input->get('currency', '0', 'int');
		$export_params['export_factor']        = (float) str_replace(",", ".", $this->input->get('export_factor', '1', 'string'));
		$export_params['export_resume']        = $this->input->get('export_resume', '0', 'int');
		$export_params['export_categories']    = $this->input->get('export_categories', [], 'array');
		$export_params['export_manufacturers'] = $this->input->get('export_manufacturers', [], 'array');
		$export_params['export_categories']    = ArrayHelper::toInteger($export_params['export_categories']);
		$export_params['export_manufacturers'] = ArrayHelper::toInteger($export_params['export_manufacturers']);
		
		if ( !$export_params['languege'] ) {
			$export_params['languege'] = 'ru-RU';
		}
		if ( $export_params['export_factor'] == 0 ) {
			$export_params['export_factor'] = 1;
		}
		$this->_db->setQuery("UPDATE #__excel2js_yml SET  yml_export_path = " . $this->_db->Quote($yml_export_path) . ",export_params = " . $this->_db->Quote(json_encode($export_params)) . " WHERE `default` = 1");
		$this->_db->execute();
		
	}
	
	//Создание/сохранение профиля
	function create_profile() {
		$profile    = $this->input->get('new_profile_name', '', 'string');
		$profile_id = $this->input->get('profile_id_value', '', 'int');
		if ( $profile ) {
			$this->_db->setQuery("UPDATE #__excel2js_yml SET `default` = 0");
			$this->_db->execute();
			
			$this->_db->setQuery("SELECT id FROM #__excel2js_yml WHERE name=" . $this->_db->Quote($profile) );
			$id = $this->_db->loadResult();
			if ( $id ) {
				$this->_db->setQuery("UPDATE #__excel2js_yml SET `default` = 1 WHERE id =$id");
				$this->_db->execute();
				echo sprintf(JText::_('PROFILE_S_EXISTS'), $profile);
			}
			else {
				$this->_db->setQuery("INSERT INTO #__excel2js_yml SET `default` = 1, name = " . $this->_db->Quote($profile) );
				$this->_db->execute();
				echo sprintf(JText::_('PROFILE_S_ADDED'), $profile);;
			}
			
		}
		elseif ( $profile_id ) {
			$this->change_yml_profile();
			echo JText::_('PROFILE_IS_SAVED_AND_SET_AS_DEFAULT');
			
		}
		
		exit();
	}
	
	function getLanguages() {
		$this->_db->setQuery("SELECT language,name FROM #__jshopping_languages");
		
		return $this->_db->loadObjectList();
	}
	
	function delete_profile() {
		$this->_db->setQuery("SELECT COUNT(id) FROM #__excel2js_yml");
		if ( $this->_db->loadResult() < 2 ) {
			return JText::_('YOU_CAN_NOT_DELETE_THE_LAST_PROFILE');
		}
		$this->_db->setQuery("SELECT id FROM #__excel2js_yml WHERE `default`=1");
		$default = $this->_db->loadResult();
		
		$this->_db->setQuery("DELETE FROM #__excel2js_yml WHERE id = $default");
		$this->_db->execute();
		
		$this->_db->setQuery("SELECT id FROM #__excel2js_yml ORDER BY id", 0, 1);
		$new_default = $this->_db->loadResult();
		
		$this->_db->setQuery("UPDATE #__excel2js_yml SET `default` = 1 WHERE id = $new_default");
		$this->_db->execute();
		
		return JText::_('PROFILE_DELETED');
		
	}
	
	function resizeImageMagic($img, $w, $h, $thumb_flag = 0, $fill_flag = 1, $name = "", $qty = 85, $color_fill = 0xffffff, $interlace = 1) {
		//ini_set("memory_limit", "120M");
		$new_w = $w;
		$new_h = $h;
		$path  = pathinfo($img);
		$ext   = $path['extension'];
		$ext   = strtolower($ext);
		
		$imagedata = @getimagesize($img);
		
		$img_w = $imagedata[0];
		$img_h = $imagedata[1];
		
		if ( !$img_w && !$img_h ) return 0;
		
		if ( !$w ) {
			$w      = $new2_w = $h * ($img_w / $img_h);
			$new2_h = $h;
		}
		elseif ( !$h ) {
			$h      = $new2_h = $w * ($img_h / $img_w);
			$new2_w = $w;
		}
		else {
			
			if ( $img_h * ($new_w / $img_w) > $new_h ) {
				$new2_w = $img_w * $new_h / $img_h;
				$new2_h = $new_h;
			}
			else {
				$new2_w = $new_w;
				$new2_h = $img_h * $new_w / $img_w;
			}
			
			if ( $thumb_flag ) {
				if ( $img_h * ($new_w / $img_w) < $new_h ) {
					$new2_w = $img_w * $new_h / $img_h;
					$new2_h = $new_h;
				}
				else {
					$new2_w = $new_w;
					$new2_h = $img_h * $new_w / $img_w;
				}
			}
			
			if ( !$thumb_flag && !$fill_flag ) {
				$new2_w = $w;
				$new2_h = $h;
			}
		}
		
		if ( ($ext == "jpg") or ($ext == "jpeg") ) {
			$image = imagecreatefromjpeg($img);
		}
		elseif ( $ext == "gif" ) {
			$image = imagecreatefromgif($img);
		}
		elseif ( $ext == "png" ) {
			$image = imagecreatefrompng($img);
		}
		else {
			return 0;
		}
		
		$thumb = imagecreatetruecolor($w, $h);
		
		if ( $fill_flag ) {
			if ( $fill_flag == 2 ) {
				if ( $ext == "png" ) {
					imagealphablending($thumb, false);
					imagesavealpha($thumb, true);
					$trnprt_color = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
					imagefill($thumb, 0, 0, $trnprt_color);
				}
				elseif ( $ext == "gif" ) {
					$trnprt_color = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
					imagefill($thumb, 0, 0, $trnprt_color);
					imagecolortransparent($thumb, $trnprt_color);
					imagetruecolortopalette($thumb, true, 256);
				}
				else {
					imagefill($thumb, 0, 0, $color_fill);
				}
			}
			else {
				imagefill($thumb, 0, 0, $color_fill);
			}
		}
		
		if ( $thumb_flag ) {
			
			imagecopyresampled($thumb, $image, ($w - $new2_w) / 2, ($h - $new2_h) / 2, 0, 0, $new2_w, $new2_h, $imagedata[0], $imagedata[1]);
			
		}
		elseif ( $fill_flag ) {
			
			if ( $new2_w < $w ) imagecopyresampled($thumb, $image, ($w - $new2_w) / 2, 0, 0, 0, $new2_w, $new2_h, $imagedata[0], $imagedata[1]);
			if ( $new2_h < $h ) imagecopyresampled($thumb, $image, 0, ($h - $new2_h) / 2, 0, 0, $new2_w, $new2_h, $imagedata[0], $imagedata[1]);
			if ( $new2_w == $w && $new2_h == $h ) imagecopyresampled($thumb, $image, 0, 0, 0, 0, $new2_w, $new2_h, $imagedata[0], $imagedata[1]);
			
		}
		else {
			
			$thumb = @imagecreatetruecolor($new2_w, $new2_h);
			if ( $ext == "png" ) {
				imagealphablending($thumb, false);
				imagesavealpha($thumb, true);
			}
			if ( $ext == "gif" ) {
				$trnprt_color = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
				imagefill($thumb, 0, 0, $trnprt_color);
				imagecolortransparent($thumb, $trnprt_color);
				imagetruecolortopalette($thumb, true, 256);
			}
			imagecopyresampled($thumb, $image, 0, 0, 0, 0, $new2_w, $new2_h, $imagedata[0], $imagedata[1]);
			
		}
		
		if ( $interlace ) {
			imageinterlace($thumb, 1);
		}
		
		if ( $ext == "png" ) {
			if ( phpversion() >= '5.1.2' ) {
				imagepng($thumb, $name, 10 - max(intval($qty / 10), 1));
			}
			else {
				imagepng($thumb, $name);
			}
		}
		if ( $ext == "gif" ) {
			if ( $name )
				imagegif($thumb, $name);
			else
				imagegif($thumb);
		}
		if ( ($ext == "jpg") or ($ext == "jpeg") ) imagejpeg($thumb, $name, $qty);
		
		return 1;
	}
	
}

