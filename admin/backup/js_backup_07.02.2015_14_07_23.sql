TRUNCATE TABLE `js_jshopping_categories`;
INSERT INTO `js_jshopping_categories` (`category_id`,`category_image`,`category_parent_id`,`category_publish`,`category_ordertype`,`category_template`,`ordering`,`category_add_date`,`products_page`,`products_row`,`access`,`name_en-GB`,`alias_en-GB`,`short_description_en-GB`,`description_en-GB`,`meta_title_en-GB`,`meta_description_en-GB`,`meta_keyword_en-GB`,`name_ru-RU`,`alias_ru-RU`,`short_description_ru-RU`,`description_ru-RU`,`meta_title_ru-RU`,`meta_description_ru-RU`,`meta_keyword_ru-RU`) VALUES
('1','optimara-laura.png','0','1','1','','2','2013-03-07 21:15:58','12','3','1','Viols','viols','','','','','','Фиалки','viols','','','','','');

TRUNCATE TABLE `js_jshopping_products`;
INSERT INTO `js_jshopping_products` (`product_id`,`parent_id`,`product_ean`,`product_quantity`,`unlimited`,`product_availability`,`product_date_added`,`date_modify`,`product_publish`,`product_tax_id`,`currency_id`,`product_template`,`product_url`,`product_old_price`,`product_buy_price`,`product_price`,`min_price`,`different_prices`,`product_weight`,`product_thumb_image`,`product_name_image`,`product_full_image`,`product_manufacturer_id`,`product_is_add_price`,`add_price_unit_id`,`average_rating`,`reviews_count`,`delivery_times_id`,`hits`,`weight_volume_units`,`basic_price_unit_id`,`label_id`,`vendor_id`,`access`,`name_en-GB`,`alias_en-GB`,`short_description_en-GB`,`description_en-GB`,`meta_title_en-GB`,`meta_description_en-GB`,`meta_keyword_en-GB`,`name_ru-RU`,`alias_ru-RU`,`short_description_ru-RU`,`description_ru-RU`,`meta_title_ru-RU`,`meta_description_ru-RU`,`meta_keyword_ru-RU`) VALUES
('1','0','','1.00','1','','2013-03-07 21:17:19','2014-01-14 21:10:00','1','1','1','default','','0.00','0.00','10.000000','10.00','0','0.0000','thumb_golden-autumn17.png','golden-autumn17.png','full_golden-autumn17.png','0','0','3','0.00','0','0','26','0.0000','0','0','0','1','Emerald city','emerald-city','','','','','','Изумрудный город','emerald-city','','','','',''),
('2','0','458586','1.00','1','','2013-10-16 20:08:03','2014-01-14 21:11:10','1','1','1','default','','0.00','0.00','20.000000','20.00','0','0.0000','thumb_evening--splendor14.png','evening--splendor14.png','full_evening--splendor14.png','0','0','3','0.00','0','0','9','0.0000','0','0','0','1','Neptun\'s Treasure','','','','','','','Сокровище Нептуна','','','','','',''),
('3','0','','1.00','1','','2014-02-13 16:14:05','2014-02-13 16:14:05','1','1','1','default','','0.00','0.00','30.000000','30.00','0','0.0000','thumb_optimara-laura1.png','optimara-laura1.png','full_optimara-laura1.png','0','0','3','0.00','0','0','1','0.0000','0','0','0','1','Laura','','','','','','','Лаура','','Лаура','<p>Лаура</p>','','','');

TRUNCATE TABLE `js_jshopping_products_attr`;
TRUNCATE TABLE `js_jshopping_products_attr2`;
TRUNCATE TABLE `js_jshopping_products_images`;
INSERT INTO `js_jshopping_products_images` (`image_id`,`product_id`,`image_thumb`,`image_name`,`image_full`,`name`,`ordering`) VALUES
('1','1','thumb_golden-autumn17.png','golden-autumn17.png','full_golden-autumn17.png','','1'),
('2','2','thumb_evening--splendor14.png','evening--splendor14.png','full_evening--splendor14.png','','1'),
('3','2','thumb_optimara-laura.png','optimara-laura.png','full_optimara-laura.png','','2'),
('4','3','thumb_optimara-laura1.png','optimara-laura1.png','full_optimara-laura1.png','','1');

TRUNCATE TABLE `js_jshopping_products_prices`;
TRUNCATE TABLE `js_jshopping_products_relations`;
TRUNCATE TABLE `js_jshopping_products_to_categories`;
INSERT INTO `js_jshopping_products_to_categories` (`product_id`,`category_id`,`product_ordering`) VALUES
('1','1','1'),
('2','1','2'),
('3','1','3');

TRUNCATE TABLE `js_jshopping_products_free_attr`;
TRUNCATE TABLE `js_jshopping_products_files`;
TRUNCATE TABLE `js_jshopping_manufacturers`;
