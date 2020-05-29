<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.model');
ini_set('log_errors', 'On');
ini_set('error_log', JPATH_ROOT . DS . 'components' . DS . 'com_excel2js' . DS . 'vk_error.txt');

use Joomla\Utilities\ArrayHelper;

class Excel2jsModelVk extends JModelLegacy
{

    function __construct()
    {
        parent:: __construct();
        $this->params = JComponentHelper::getParams('com_excel2js');
        $this->group_id = $this->params->get('group_id');
        $mainframe = JFactory::getApplication();
        $this->input = $mainframe->input;
        $this->user_token = $this->input->cookie->get('user_token');
        $this->_db->setQuery("SELECT defaultLanguage FROM #__jshopping_config WHERE id = 1");
        $this->language = $this->_db->loadResult();
        $this->config = $this->getConfig();

        $this->sef = $mainframe->get('sef');
        $this->sef_rewrite = $mainframe->get('sef_rewrite');
        $this->sef_suffix = $mainframe->get('sef_suffix') ? '.html' : '';
    }

    function getConfig()
    {
        $this->_db->setQuery("SELECT * FROM #__excel2js_vk_config WHERE is_default = 1");
        $config = $this->_db->loadObject();
        if ($config->params) {
            $params = json_decode($config->params);
            if (count($params)) {
                foreach ($params as $key => $v) {
                    $config->$key = $v;
                }
            }
        }
        if (file_exists(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "factory.php")) {
            require_once(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "factory.php");
            $this->JSconfig = JSFactory::getConfig();
        }

        return $config;
    }

    function auth()
    {
        if (!$this->group_id) {
            return '{"status":"error", "msg":"Укажите в настройках ID группы"}';
        }
        $new = $this->input->get('new', 0, 'int');
        $params = ["o" => $this->getOrderId(), "d" => $this->getDomain(), "g" => $this->group_id, "task" => "auth", "type" => "js", "new" => $new];
        $data = $this->send_request($params);

        $resp = new stdClass();
        $resp->status = 'ok';
        $resp->url = $data->url;
        $this->input->cookie->set('user_token', $data->user_token, time() + 3600 * 12);

        return json_encode($resp);

    }

    function getOrderId()
    {
        $xml = simplexml_load_file(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_excel2js' . DS . 'excel2js.xml');
        $version = (string)$xml->updateservers->server;
        $version = substr($version, 63, -4);
        if ($version == '{update_data}') {
            return $version;
        }
        $temp = explode(":", $version);
        $version = @$temp[0];

        return $version;
    }

    function getDomain()
    {
        $t = explode('/', JURI:: root());
        $d = $t[2];
        if (substr($d, 0, 4) == 'www.') $d = substr($d, 4);

        return $d;
    }

    function send_request($params)
    {
        echo '{"status":"error", "msg":"Функционал для работы с VK отключен<br>"}';
        exit();
        //=========================================
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://demo-zone.ru/vk.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 35);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_REFERER, JURI::root());
        $file_data = curl_exec($ch);

        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!$file_data OR $response_code != 200) {
            $error = curl_error($ch);
            curl_close($ch);

        }
        curl_close($ch);
        $data = json_decode($file_data);
        if (!isset($data->status)) {
            echo '{"status":"error", "msg":"' . $file_data . '"}';
            exit();
        }

        if ($data->status == 'captcha') {
            echo $file_data;
            exit();
        }

        if ($data->status == 'error') {
            echo $file_data;
            exit();
        }

        return $data;
    }

    function get_vk_categories()
    {
        if (!$this->user_token) {
            return '{"status":"error", "msg":"Необходима повторная авторизация!"}';
        }
        $params = ["token" => $this->user_token, "task" => "getCategories"];
        $data = $this->send_request($params);
        $resp = new stdClass();
        $resp->status = 'ok';
        $resp->html = $this->generateDependencies($data->list);

        return json_encode($resp);

    }

    function generateDependencies($vk_data)
    {
        $js_categories = $this->getCategoryList();
        if (!$js_categories) {
            return "<p style='color:red'>Не найдены категории на сайте!</p>";
        }
        $html = "<table id='vk_categories'><tr><th>Категории сайта</th><th>Категории Вконтакте</th></tr>";
        foreach ($js_categories as $key => $c) {
            $this->_db->setQuery("SELECT vk_id FROM #__excel2js_vk_categories WHERE internal_id = '$c->category_id'");
            $selected = (int)$this->_db->loadResult();
            $html .= "<tr><td>";
            $html .= $c->name;
            $html .= "</td><td>";
            $html .= $this->generateVKCatList($vk_data, "vk_cat[$c->category_id]", $selected);
            $html .= "</td></tr>";
        }

        $html .= "</table>";

        return $html;
    }

    function getCategoryList($options = false, $selected_cat = false)
    {
        if (!file_exists(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "functions.php")) {
            return false;
        } else {
            require_once(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "factory.php");
            require_once(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "functions.php");

            $categories = buildTreeCategory(0, 1, 0);

            if (!count($categories)) {
                return false;
            }
            if (!$options) {
                return $categories;
            }
            $list = '';
            foreach ($categories as $v) {
                $selected = '';
                if (is_array($selected_cat) AND !empty($selected_cat)) {
                    $selected = in_array($v->category_id, $selected_cat) ? 'selected=""' : '';
                }
                $list .= "<option $selected value='$v->category_id'>$v->name</option>";
            }

            return $list;

        }
    }

    function generateVKCatList($resp, $select_name, $selected = 0)
    {
        $list = '';
        $optgroup = '';
        foreach ($resp->items as $key => $cat) {
            if (empty($optgroup)) {
                $optgroup = $cat->section->name;
                $list .= "<optgroup label='$optgroup'>";
            }
            if ($optgroup != $cat->section->name) {
                $optgroup = $cat->section->name;
                $list .= "</optgroup><optgroup label='$optgroup'>";
            }

            $list .= "<option value='{$cat->id}' " . ($selected == $cat->id ? ' selected' : '') . ">{$cat->name}</option>";
        }

        return "<select name='{$select_name}'>" . $list . "</select>";
    }

    function vk_categories_save()
    {
        $vk_cat = $this->input->post->get('vk_cat', [], 'array');
        if (empty($vk_cat)) {
            return '{"status":"error", "msg":"Категории не указаны"}';
        }
        $this->_db->setQuery("DELETE FROM #__excel2js_vk_categories");
        $this->_db->execute();

        foreach ($vk_cat as $internal_id => $vk_id) {
            $vk_id = (int)$vk_id;
            $internal_id = (int)$internal_id;
            if (!$vk_id OR !$internal_id) {
                continue;
            }
            $this->_db->setQuery("REPLACE INTO #__excel2js_vk_categories SET internal_id = $internal_id, vk_id = $vk_id");
            $this->_db->execute();
        }

        return '{"status":"ok", "msg":"Соответствия категорий сохранены"}';
    }

    function getManufacturers()
    {
        $this->_db->setQuery("SELECT manufacturer_id, `name_{$this->language}`as mf_name FROM #__jshopping_manufacturers ORDER BY `name_{$this->language}`");

        return $this->_db->loadObjectList();
    }

    function getLabels()
    {
        $this->_db->setQuery("SELECT id, `name_{$this->language}`as label_name FROM #__jshopping_product_labels ORDER BY `name_{$this->language}`");

        return $this->_db->loadObjectList();
    }

    function getAtributes()
    {
        $this->_db->setQuery("SELECT attr_id, `name_{$this->language}`as attr_name FROM #__jshopping_attr WHERE  independent = 1 ORDER BY `name_{$this->language}`");

        return $this->_db->loadObjectList();
    }

    function getExtraFields()
    {
        $this->_db->setQuery("SELECT id, `name_{$this->language}`as extra_name FROM #__jshopping_products_extra_fields ORDER BY `name_{$this->language}`");

        return $this->_db->loadObjectList();
    }

    function save_config_vk()
    {
        unset($_POST['task']);
        unset($_POST['option']);

        if (isset($_POST['export_categories'])) {
            $_POST['export_categories'] = ArrayHelper::toInteger($_POST['export_categories']);
        } else {
            $_POST['export_categories'] = [];
        }

        if (isset($_POST['export_manufacturers'])) {
            $_POST['export_manufacturers'] = ArrayHelper::toInteger($_POST['export_manufacturers']);
        } else {
            $_POST['export_manufacturers'] = [];
        }

        if (isset($_POST['export_labels'])) {
            $_POST['export_labels'] = ArrayHelper::toInteger($_POST['export_labels']);
        } else {
            $_POST['export_labels'] = [];
        }

        if (isset($_POST['export_extra_fields'])) {
            $_POST['export_extra_fields'] = ArrayHelper::toInteger($_POST['export_extra_fields']);
        } else {
            $_POST['export_extra_fields'] = [];
        }

        if (isset($_POST['export_atributes'])) {
            $_POST['export_atributes'] = ArrayHelper::toInteger($_POST['export_atributes']);
        } else {
            $_POST['export_atributes'] = [];
        }

        $this->_db->setQuery("UPDATE #__excel2js_vk_config SET params=" . $this->_db->Quote(json_encode($_POST)));
        if ($this->_db->execute()) {
            return "Настройки сохранены";
        } else {
            return "Возникла ошибка при сохранении настроек";
        }
    }

    function vk_export($captcha = false)
    {
        $step = $this->params->get('step', 5);
        $crop_width = $this->params->get('crop_width', 604);
        $crop_x = $this->params->get('crop_x', 0);
        $crop_y = $this->params->get('crop_y', 0);
        $start = $this->input->get('start', 0, 'int');
        $stat_start = 0;
        $this->_db->setQuery("SELECT currency_code_iso, currency_value FROM #__jshopping_currencies");
        $currencies = $this->_db->loadObjectList('currency_code_iso');

        if ($this->config->link_in_desc) {
            require(JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "router.php");
        }

        $img_path = $this->JSconfig->image_product_live_path;
        $filter = '';
        if (@count($this->config->export_categories)) {
            if ($this->config->export_resume) {
                $filter = " AND c.category_id NOT IN(" . implode(",", $this->config->export_categories) . ")";
            } else {
                $filter = " AND c.category_id IN(" . implode(",", $this->config->export_categories) . ")";
            }
        }

        if (@count($this->config->export_manufacturers)) {
            $filter .= " AND p.product_manufacturer_id IN(" . implode(",", $this->config->export_manufacturers) . ")";
        }

        if (@count($this->config->export_labels)) {
            $filter .= " AND p.label_id IN(" . implode(",", $this->config->export_labels) . ")";
        }

        if (!$this->config->export_old) {
            $filter .= " AND vkp.vk_id IS NULL";
            $stat_start = $start;
            $start = 0;
        }

        if ($captcha) {
            $filter .= " AND p.product_id= " . $this->_db->Quote($this->input->get('product_id', 0, 'int')) . "";
            $start = 0;
            $step = 1;
        }

        $description_type = $this->config->full_description ? ('description_' . $this->language) : ('short_description_' . $this->language);

        $this->_db->setQuery('SELECT
          SQL_CALC_FOUND_ROWS
          DISTINCT p.product_id,
          p.image,
          p.`name_' . $this->language . '` as product_name,
          p.`' . $description_type . '` as product_desc,
          p.product_price,
          vkc.vk_id as vk_category_id,
          vkp.vk_id as vk_product_id,
          jc.currency_code_iso,
		  c.category_id as js_category_id
          FROM #__jshopping_products p
          LEFT JOIN #__jshopping_products_to_categories as c ON c.product_id = p.product_id
          LEFT JOIN #__jshopping_manufacturers as m ON p.product_manufacturer_id = m.manufacturer_id
          LEFT JOIN #__excel2js_vk_categories as vkc ON vkc.internal_id = c.category_id
          LEFT JOIN #__excel2js_vk_products as vkp ON vkp.internal_id = p.product_id
          LEFT JOIN #__jshopping_currencies as jc ON jc.currency_id = p.currency_id
          WHERE p.product_publish = 1
          AND p.product_price >= 0.01
          AND (p.product_quantity != 0 OR unlimited = 1)
          AND vkc.vk_id IS NOT NULL
          AND p.image !=""
          ' . $filter . '
          GROUP BY p.product_id', $start, $step);

        $products = $this->_db->loadObjectList();

        $this->_db->setQuery("SELECT FOUND_ROWS()");

        $resp = new stdClass();
        $resp->total_products = $this->_db->loadResult() + $stat_start;
        $resp->cur_product = ($start + $stat_start + $step <= $resp->total_products) ? $start + $stat_start + $step : $resp->total_products;

        $resp->status = 'ok';
        $resp->products = [];

        if ($this->config->extra_in_desc) {
            $filter_extra = '';
            if (count($this->config->export_extra_fields)) {
                if ($this->config->export_extra_resume) {
                    $filter_extra = " WHERE id NOT IN(" . implode(",", $this->config->export_extra_fields) . ")";
                } else {
                    $filter_extra = " WHERE id IN(" . implode(",", $this->config->export_extra_fields) . ")";
                }
            }

            $this->_db->setQuery("SELECT id,`name_" . $this->language . "` as param_name, type
                         FROM #__jshopping_products_extra_fields
                         $filter_extra
                         ");
            $customs = $this->_db->loadObjectList();
        }
        foreach ($products as $key => $v) {
            if ($v->product_desc) {

                $products[$key]->product_desc = strip_tags($products[$key]->product_desc);
            }


            if ($this->config->extra_in_desc) {

                if (!empty($customs)) {
                    foreach ($customs as $custom) {
                        try {
                            $this->_db->setQuery("SELECT extra_field_{$custom->id} FROM #__jshopping_products WHERE product_id = $v->product_id");
                            $value = $this->_db->loadResult();
                        } catch (Exception $e) {
                            continue;
                        }

                        if (!$value) {
                            continue;
                        }

                        if ($custom->type == 1) {
                            $products[$key]->product_desc .= "\n" . $custom->param_name . ": " . $value;
                        } else {
                            $this->_db->setQuery("SELECT `name_" . $this->language . "` FROM #__jshopping_products_extra_field_values WHERE id IN($value) AND field_id = $custom->id");
                            $values = $this->_db->loadColumn();
                            if (count($values)) {
                                $products[$key]->product_desc .= "\n" . $custom->param_name . ": " . implode(", ", $values);
                            }
                        }

                    }
                }
            }

            if ($this->config->attr_in_desc) {
                $filter_attr = '';
                if (count($this->config->export_atributes)) {
                    if ($this->config->export_attr_resume) {
                        $filter_attr = " AND attr_id NOT IN(" . implode(",", $this->config->export_atributes) . ")";
                    } else {
                        $filter_attr = " AND attr_id IN(" . implode(",", $this->config->export_atributes) . ")";
                    }
                }

                $this->_db->setQuery("SELECT DISTINCT attr_id FROM #__jshopping_products_attr2 WHERE product_id = $v->product_id $filter_attr");
                $attributes = $this->_db->loadColumn();
                if (count($attributes)) {
                    foreach ($attributes as $attr_id) {

                        $this->_db->setQuery("SELECT `name_" . $this->language . "` FROM #__jshopping_attr WHERE attr_id = $attr_id");
                        $param_name = $this->_db->loadResult();

                        if (!$param_name) {
                            continue;
                        }

                        $this->_db->setQuery("
                       SELECT `name_" . $this->language . "`
                       FROM #__jshopping_products_attr2 as a
                       LEFT JOIN #__jshopping_attr_values as v ON v.value_id = a.attr_value_id
                       WHERE a.attr_id = $attr_id AND product_id = $v->product_id
                       ");
                        $values = $this->_db->loadColumn();

                        if (count($values)) {
                            $products[$key]->product_desc .= "\n" . $param_name . ": " . implode(", ", $values);
                        }

                    }
                }
            }

            if ($this->config->link_in_desc) {

                if ($this->sef) {
                    $link = ($this->sef_rewrite ? "" : "index.php/") . $this->get_slug_path($v->product_id, $v->js_category_id);
                } else {
                    $link = 'index.php?option=com_jshopping&controller=product&task=view&product_id=' . $v->product_id . '&category_id=' . $v->js_category_id;
                }

                if ($link) {
                    $products[$key]->product_desc .= "\n" . "Ссылка на товар - " . JURI::root() . $link;
                }


            }


            if (!in_array($v->currency_code_iso, ["RUB", "RUR"])) {
                $current_exchange = $currencies[$v->currency_code_iso]->currency_value;
                if (isset($currencies["RUR"]->currency_value)) {
                    $rub_exchange = $currencies["RUR"]->currency_value;
                } elseif (isset($currencies["RUB"]->currency_value)) {
                    $rub_exchange = $currencies["RUB"]->currency_value;
                } else {
                    return '{"status":"error","msg":"Отсутствует валюта - RUR"}';
                }
                if ($current_exchange == 0) {
                    return '{"status":"error","msg":"Обменный курс валюты ' . $v->currency_code_iso . ' не может быть нулевым!"}';
                }
                $products[$key]->product_price = round($v->product_price / $current_exchange * $rub_exchange, 2);
            }


            $products[$key]->image_url = $img_path . '/full_' . $v->image;
            $img_root_path = str_replace(JURI::root(), JPATH_ROOT . DS, $products[$key]->image_url);
            $img_root_path = str_replace("/", DS, $img_root_path);
            $img_data = getimagesize($img_root_path);
            if (!$img_data) {
                $resp->products[] = "<li>{$v->product_name}. <span class='red'>Ошибка: Изображение отсутствует или повреждено</span></li>";
                unset($products[$key]);
                continue;
            }
            if ($img_data[0] < 400) {
                $resp->products[] = "<li>{$v->product_name}. <span class='red'>Ошибка: Ширина изображения меньше 400px!</span></li>";
                unset($products[$key]);
                continue;
            }
            if ($img_data[1] < 400) {
                $resp->products[] = "<li>{$v->product_name}. <span class='red'>Ошибка: Высота изображения меньше 400px!</span></li>";
                unset($products[$key]);
                continue;
            }
            if (strlen($v->product_name) < 4) {
                $resp->products[] = "<li>{$v->product_name}. <span class='red'>Ошибка: Название товара меньше 4 символов</span></li>";
                unset($products[$key]);
                continue;
            }
            if (strlen($v->product_name) > 100) {
                if (function_exists('mb_substr')) {
                    $products[$key]->product_name = mb_substr($v->product_name, 0, 100);
                } else {
                    $products[$key]->product_name = substr($v->product_name, 0, 100);
                }
            }
            if (strlen($products[$key]->product_desc) < 10) {
                $resp->products[] = "<li>{$v->product_name}. <span class='red'>Ошибка: Описание товара меньше 10 символов</span></li>";
                unset($products[$key]);
                continue;
            }

            if ($this->config->export_all_photoes) {
                $this->_db->setQuery("SELECT image_name FROM #__jshopping_products_images WHERE product_id = $v->product_id AND image_name!=" . $this->_db->Quote($v->image));
                $extra_images = $this->_db->loadColumn();
                if (count($extra_images)) {
                    foreach ($extra_images as $k => $ei) {
                        $extra_img_url = $img_path . '/full_' . $ei;
                        $extra_img_root_path = str_replace(JURI::root(), JPATH_ROOT . DS, $extra_img_url);
                        $extra_img_root_path = str_replace("/", DS, $extra_img_root_path);
                        $extra_img_data = getimagesize($extra_img_root_path);
                        if ($extra_img_data[0] >= 400 AND $extra_img_data[1] >= 400) {
                            @$products[$key]->extra_image_url[$k] = $extra_img_url;
                        }
                    }
                }
            }
        }

        if (count($products)) {
            $params = ["crop_width" => $crop_width, "crop_x" => $crop_x, "crop_y" => $crop_y, "task" => "addProducts", "token" => $this->user_token, "products_data" => json_encode($products)];

            if ($captcha) {
                $params["captcha_key"] = $this->input->get('captcha_key');
                $params["captcha_sid"] = $this->input->get('captcha_sid');
                $params["main_photo_id"] = $this->input->get('main_photo_id');
                $params["extra_photo_ids"] = $this->input->get('extra_photo_ids');
            }

            $data = $this->send_request($params);
            $data->products = (array)$data->products;
            if (is_array($data->products)) {
                foreach ($data->products as $product_id => $v) {
                    if ($v->status == 'ok') {
                        $resp->products[] = "<li>{$v->product_name}. <span class='green'>$v->msg</span></li>";
                        if (isset($v->vk_id) AND $v->vk_id) {
                            $this->_db->setQuery("REPLACE INTO #__excel2js_vk_products SET internal_id = '$product_id', vk_id = '$v->vk_id'");
                            $this->_db->execute();
                        }
                    } else {
                        $resp->products[] = "<li>{$v->product_name}. <span class='red'>$v->msg</span></li>";
                    }
                }
            } else {
                $resp->status = 'error';
                $resp->msg = 'Ошибка разбора данных';
            }
        }

        return json_encode($resp);

    }

    function get_slug_path($product_id, $category_id)
    {

        $query = ["controller" => "product", "view" => "product", "category_id" => $category_id, "task" => "view", "product_id" => $product_id];
        $path = jshoppingBuildRoute($query);

        return implode("/", $path) . $this->sef_suffix;
    }

    function vk_get_products()
    {
        $list_nomber = $this->params->get('list_nomber', 20);
        $list_offset = $this->input->get('list_offset', 0);
        $params = ["task" => "getProducts", "token" => $this->user_token, "list_nomber" => $list_nomber, "list_offset" => $list_offset];
        $data = $this->send_request($params);
        $resp = new stdClass();
        $resp->total_products = $data->products->count;
        $resp->loaded_products = ($list_offset + $list_nomber > $data->products->count) ? $data->products->count : $list_offset + $list_nomber;
        $resp->next_step = $resp->total_products - $resp->loaded_products;
        if ($resp->next_step < 0) {
            $resp->next_step = 0;
        }
        if ($resp->next_step > $list_nomber) {
            $resp->next_step = $list_nomber;
        }
        $resp->status = 'ok';

        if (is_array($data->products->items)) {

            foreach ($data->products->items as $n => $v) {
                $nomber = $list_offset + $n + 1;
                $resp->products[] =
                    "<tr id='row_{$v->id}'>
                    <td><input type='checkbox' value='{$v->id}'></td>
                    <td>{$nomber}</td>
                    <td><img height ='100' src='{$v->thumb_photo}' /></td>
                    <td>{$v->id}</td>
                    <td>{$v->title}</td>
                    <td>" . nl2br($v->description) . "</td>
                    <td>{$v->price->text}</td>
                    <td>{$v->category->name} ({$v->category->section->name})</td>
                </tr>";

            }
        } else {
            $resp->status = 'error';
            $resp->msg = 'Ошибка разбора данных';
        }

        return json_encode($resp);

    }

    function vk_delete_products()
    {
        $products_list = $this->input->get('products_list', '[]', 'json');
        $params = ["task" => "deleteProducts", "token" => $this->user_token, "products_list" => $products_list];
        $data = $this->send_request($params);
        $products = (array)$data->products;
        $resp = new stdClass();
        $resp->status = 'ok';
        $resp->counter = 0;
        $resp->deleted = [];
        if (is_array($products)) {

            foreach ($products as $vk_id => $data) {
                if ($data->status == 'ok') {
                    if ($vk_id) {
                        $this->_db->setQuery("DELETE FROM #__excel2js_vk_products WHERE vk_id = '$vk_id'");
                        $this->_db->execute();
                        $resp->deleted[] = $vk_id;
                        $resp->counter++;
                    }
                } else {
                    $resp->errors .= "Товар #$vk_id. " . $data->msg . "<br>";
                }
            }
        } else {
            $resp->status = 'error';
            $resp->msg = 'Ошибка разбора данных';
        }

        return json_encode($resp);

    }

}