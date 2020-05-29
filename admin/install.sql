CREATE TABLE IF NOT EXISTS `#__excel2js` (
  `id` int(15) NOT NULL AUTO_INCREMENT,
  `profile` varchar(256) NOT NULL,
  `active` text NOT NULL,
  `config` text NOT NULL,
  `default_profile` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;



CREATE TABLE IF NOT EXISTS `#__excel2js_backups` (
  `backup_id` int(20) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(256) NOT NULL,
  `size` int(20) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__excel2js_fields` (
  `id` int(15) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(256) NOT NULL,
  `type` varchar(256) NOT NULL DEFAULT 'default',
  `example` varchar(256) NOT NULL,
  `extra_id` VARCHAR( 256 ) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name`(255))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

REPLACE INTO `#__excel2js_fields` (`id`, `name`, `title`, `type`, `example`, `extra_id`) VALUES
(1, 'path', 'CATEGORY_NOMBER', 'default', '1;2', '0'),
(2, 'product_id', 'PRODUCT_ID', 'default', '1;2', '0'),
(3, 'product_ean', 'PRODUCT_SKU', 'default', 'GC0012;584542', '0'),
(4, 'name', 'PRODUCT_NAME', 'default', 'Ardo 1745;Samsung TE5685', '0'),
(5, 'alias', 'PRODUCT_ALIAS', 'default', 'ardo1745;samsung-te5685', '0'),
(6, 'product_price', 'COST_PRICE', 'default', '255;300', '0'),
(7, 'product_old_price', 'PRODUCT_OLD_PRICE', 'default', '200;250', '0'),
(8, 'product_buy_price', 'PRODUCT_BUY_PRICE', 'default', '200;250', '0'),
(9, 'short_description', 'SHORT_DESCRIPTION', 'default', 'Ardo 1745;Samsung TE5685', '0'),
(10, 'description', 'PRODUCT_DESCRIPTION', 'default', 'Ardo 1745;Samsung TE5685', '0'),
(11, 'meta_description', 'PRODUCT_META_DESCRIPTION', 'default', 'PRODUCT_META_DESCRIPTION_EXAMPLE;PRODUCT_META_DESCRIPTION_EXAMPLE', '0'),
(12, 'meta_keyword', 'PRODUCT_META_KEY', 'default', 'PRODUCT_META_KEY_EXAMPLE1;PRODUCT_META_KEY_EXAMPLE2', '0'),
(13, 'meta_title', 'CUSTOM_PAGE_TITLE', 'default', 'CUSTOM_PAGE_TITLE_EXAMPLE1;CUSTOM_PAGE_TITLE_EXAMPLE2', '0'),
(14, 'image_name', 'USED_IMAGE_URL', 'default', 'refregerator1.jpg;refregerator2.jpg', '0'),
(15, 'image_title', 'IMAGE_TITLE', 'default', '1;2', '0'),
(16, 'product_publish', 'PUBLISHED', 'default', '1;0', '0'),
(17, 'product_weight', 'WEIGHT', 'default', '50;50000', '0'),
(18, 'product_url', 'PRODUCT_LINK', 'default', 'http://site.ru/product1.html;http://site.ru/product2.html', '0'),
(19, 'product_quantity', 'PRODUCT_IN_STOCK', 'default', '5;-1', '0'),
(21, 'delivery_times', 'DELIVERY_TIMES', 'default', 'PRODUCT_AVAILABILITY_EXAMPLE1;PRODUCT_AVAILABILITY_EXAMPLE2', '0'),
(22, 'units', 'PRODUCT_UNITS', 'default', 'PRODUCT_UNITS_EXAMPLE1;PRODUCT_UNITS_EXAMPLE2', '0'),
(23, 'product_manufacturer_id', 'MANUFACTURER_ID', 'default', '1;2', '0'),
(24, 'mf_name', 'MANUFACTURER', 'default', 'Ardo;Sumsung', '0'),
(25, 'related_products', 'RELATED_PRODUCTS', 'default', '2|55|66;', '0'),
(26, 'related_products_sku', 'RELATED_PRODUCTS_SKU', 'default', 'GC0012|584542;55687|Art_256', '0'),
(28, 'currency', 'CURRENCY2', 'default', 'RUR;UAH', '0'),
(29, 'product_template', 'PRODUCT_TEMPLATE', 'default', 'default;default', '0'),
(30, 'product_tax_id', 'PRODUCT_TAX_ID', 'default', '18;20', '0'),
(31, 'label_id', 'LABEL_ID', 'default', 'New;Sale', '0'),
(32, 'access', 'ACCESS', 'default', '1;2', '0'),
(33, 'digital', 'PRODUCT_DIGITAL', 'default', 'product.zip;some_img.jpg', '0'),
(34, 'product_date_added', 'CREATED_ON', 'default', '2014-12-25 13:22:15;2015-01-05 12:55:36', '0'),
(35, 'date_modify', 'MODIFIED_ON', 'default', '2014-12-25 13:22:15;2015-01-05 12:55:36', '0'),
(36, 'depend_price', 'DEPEND_PRICE', 'depend2', '200;300', '0'),
(37, 'depend_old_price', 'DEPEND_OLD_PRICE', 'depend2', '220;330', '0'),
(38, 'depend_buy_price', 'DEPEND_BUY_PRICE', 'depend2', '150;280', '0'),
(39, 'depend_count', 'DEPEND_COUNT', 'depend2', '20;3', '0'),
(40, 'depend_ean', 'DEPEND_EAN', 'depend2', 'a52545;', '0'),
(41, 'depend_weight', 'DEPEND_WEIGHT', 'depend2', '5;10', '0'),
(42, 'depend_ext_attribute_product_id', 'DEPEND_IMAGE', 'depend2', 'some_img.png|image2.jpg;http://some-site.com/image_3.jpg', '0'),
(43, 'free_attr', 'FREE_ATTR', 'free', '', '0'),
(44, 'product_ordering', 'PRODUCT_ORDERING', 'default', '1;2', '0'),
(45, 'extra_list', 'Список характеристик', 'default', 'Цвет:Красный,Синий|Размер:56;Цвет:Синий|Размер:55,56', '0'),
(46, 'manufacturer_code', 'Артикул', 'default', 'G155;4567', NULL),
(47, 'weight_volume_units', 'Базовая цена', 'default', '1.000;1.000', NULL),
(48, 'vendor_id', 'ID продавца', 'default', '1;2', NULL );

CREATE TABLE IF NOT EXISTS `#__excel2js_log` (
  `log_id` int(20) NOT NULL AUTO_INCREMENT,
  `js_id` int(11) NOT NULL,
  `type` enum('cu','cn','pu','pn') NOT NULL,
  `title` varchar(256) NOT NULL,
  `row` int(10) NOT NULL,
  `extra` text,
  PRIMARY KEY (`log_id`),
  KEY `type` (`type`),
  KEY `js_id` (`js_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__excel2js_related_products` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__excel2js_yml` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `yml_export_path` text NOT NULL,
  `yml_import_path` text NOT NULL,
  `params` text NOT NULL,
  `export_params` text NOT NULL,
  `name` VARCHAR( 256 ) NOT NULL ,
  `default` INT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__excel2js_vk_categories` (
  `internal_id` int(11) NOT NULL,
  `vk_id` int(11) NOT NULL,
  PRIMARY KEY (`internal_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__excel2js_vk_products` (
  `internal_id` int(11) NOT NULL,
  `vk_id` int(11) NOT NULL,
  PRIMARY KEY (`internal_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__excel2js_vk_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `params` text NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;








