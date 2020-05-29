TRUNCATE TABLE `js_jshopping_categories`;
INSERT INTO `js_jshopping_categories` (`category_id`,`category_image`,`category_parent_id`,`category_publish`,`category_ordertype`,`category_template`,`ordering`,`category_add_date`,`products_page`,`products_row`,`access`,`name_en-GB`,`alias_en-GB`,`short_description_en-GB`,`description_en-GB`,`meta_title_en-GB`,`meta_description_en-GB`,`meta_keyword_en-GB`,`name_ru-RU`,`alias_ru-RU`,`short_description_ru-RU`,`description_ru-RU`,`meta_title_ru-RU`,`meta_description_ru-RU`,`meta_keyword_ru-RU`) VALUES
('1','cat1.jpg','0','1','1','','1','2015-02-07 14:39:27','12','3','1','','','','','','','','Компьютерная техника','1-komputernaya-tehnika','Описание категории краткое','Полное','','',''),
('2','12472.jpg','1','1','1','','1','2015-02-07 14:39:27','12','3','1','','','','','','','','Мониторы','2-monitory','','','','',''),
('3','cat3.jpg','2','1','1','','1','2015-02-07 14:39:27','12','3','1','','','','','','','','Acer','3-acer','','','','',''),
('4','cat4.jpg','2','1','1','','2','2015-02-07 14:39:27','12','3','1','','','','','','','','Asus','4-asus','','','','',''),
('5','cat5.png','1','1','1','','2','2015-02-07 14:39:27','12','3','1','','','','','','','','Комплектующие для ПК','5-komplektuushie-dlya-pk','','','','',''),
('6','cat6.jpg','5','1','1','','1','2015-02-07 14:39:27','12','3','1','','','','','','','','Модули памяти','6-moduli-pamyati','','','','',''),
('7','cat7.jpg','5','1','1','','2','2015-02-07 14:39:27','12','3','1','','','','','','','','Звуковые карты','7-zvukovye-karty','','','','',''),
('8','cat8.jpg','0','1','1','','2','2015-02-07 14:39:27','12','3','1','','','','','','','','Крупнобытовая техника','8-krupnobytovaya-tehnika','','','','',''),
('9','cat9.jpg','8','1','1','','1','2015-02-07 14:39:27','12','3','1','','','','','','','','Холодильники','9-holodilniki','','','','',''),
('10','cat10.jpg','8','1','1','','2','2015-02-07 14:39:27','12','3','1','','','','','','','','Кухонные плиты','10-kuhonnye-plity','','','','','');

TRUNCATE TABLE `js_jshopping_products`;
INSERT INTO `js_jshopping_products` (`product_id`,`parent_id`,`product_ean`,`product_quantity`,`unlimited`,`product_availability`,`product_date_added`,`date_modify`,`product_publish`,`product_tax_id`,`currency_id`,`product_template`,`product_url`,`product_old_price`,`product_buy_price`,`product_price`,`min_price`,`different_prices`,`product_weight`,`product_thumb_image`,`product_name_image`,`product_full_image`,`product_manufacturer_id`,`product_is_add_price`,`add_price_unit_id`,`average_rating`,`reviews_count`,`delivery_times_id`,`hits`,`weight_volume_units`,`basic_price_unit_id`,`label_id`,`vendor_id`,`access`,`name_en-GB`,`alias_en-GB`,`short_description_en-GB`,`description_en-GB`,`meta_title_en-GB`,`meta_description_en-GB`,`meta_keyword_en-GB`,`name_ru-RU`,`alias_ru-RU`,`short_description_ru-RU`,`description_ru-RU`,`meta_title_ru-RU`,`meta_description_ru-RU`,`meta_keyword_ru-RU`) VALUES
('1','0','112213','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','1','1','default','','0.0000','0.0000','150.000000','150.00','0','0.0000','thumb_acer_h226hqlbmid_8436467.jpg','acer_h226hqlbmid_8436467.jpg','full_acer_h226hqlbmid_8436467.jpg','0','0','3','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Acer G11195HQVB','1-acer-g11195hqvb','Краткое описание1','Полное описание','','',''),
('2','0','19473','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','220.000000','0.00','0','0.0000','','','','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Asus ML239H','2-asus-ml239h','Краткое описание2','Полное описание2','','',''),
('3','0','779','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','50.000000','0.00','0','0.0000','thumb_img3.jpg','img3.jpg','full_img3.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','1Gb 400MHz TakeMS BD1024TEC600A bulk','3-1gb-400mhz-takems-bd1024tec600a-bulk','Краткое описание3','Полное описание3','','',''),
('4','0','19253','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','48.000000','0.00','0','0.0000','thumb_img4.jpg','img4.jpg','full_img4.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','1GB 800 Hynix Original l SO DIMM','4-1gb-800-hynix-original-l-so-dimm','Краткое описание4','Полное описание4','','',''),
('5','0','19322','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','80.000000','0.00','0','0.0000','thumb_img5.jpg','img5.jpg','full_img5.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Asus XONAR D1/A (PCI, support 7.1)','5-asus-xonar-d1-a-pci-support-71','Краткое описание5','Полное описание5','','',''),
('6','0','19256','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','70.000000','0.00','0','0.0000','thumb_img6.jpg','img6.jpg','full_img6.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Asus XONAR DG (PCI, Dolby Headphone 5.1)','6-asus-xonar-dg-pci-dolby-headphone-51','Краткое описание6','Полное описание6','','',''),
('7','0','783','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','75.000000','0.00','0','0.0000','thumb_img7.jpg','img7.jpg','full_img7.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Asus Xonar DS A','7-asus-xonar-ds-a','Краткое описание7','Полное описание7','','',''),
('8','0','56785','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','250.000000','0.00','0','0.0000','thumb_img8.jpg','img8.jpg','full_img8.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Ardo COO2210SHC','8-ardo-coo2210shc','Краткое описание8','Полное описание8','','',''),
('9','0','48686','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','200.000000','0.00','0','0.0000','thumb_img9.jpg','img9.jpg','full_img9.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Beko DSA 25000','9-beko-dsa-25000','Краткое описание9','Полное описание9','','',''),
('10','0','785','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','150.000000','0.00','0','0.0000','thumb_img10.jpg','img10.jpg','full_img10.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Ardo A 564 VG6 Inox','10-ardo-a-564-vg6-inox','Краткое описание10','Полное описание10','','',''),
('11','0','18967','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','200.000000','0.00','0','0.0000','thumb_img11.jpg','img11.jpg','full_img11.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Beko CE 51011','11-beko-ce-51011','Краткое описание11','Полное описание11','','',''),
('12','0','888','1.00','1','','2015-02-07 14:39:27','2015-02-07 22:34:15','1','0','1','default','','0.0000','0.0000','170.000000','0.00','0','0.0000','thumb_img12.jpg','img12.jpg','full_img12.jpg','0','0','0','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Ardo PL998CREAM','12-ardo-pl998cream','Краткое описание12','Полное описание12','','','');

TRUNCATE TABLE `js_jshopping_products_attr`;
TRUNCATE TABLE `js_jshopping_products_attr2`;
TRUNCATE TABLE `js_jshopping_products_images`;
INSERT INTO `js_jshopping_products_images` (`image_id`,`product_id`,`image_thumb`,`image_name`,`image_full`,`name`,`ordering`) VALUES
('3','1','thumb_acer_h226hqlbmid_8436467.jpg','acer_h226hqlbmid_8436467.jpg','full_acer_h226hqlbmid_8436467.jpg','','1'),
('4','1','thumb_acer_h226hqlbmid_8428914.jpg','acer_h226hqlbmid_8428914.jpg','full_acer_h226hqlbmid_8428914.jpg','','2'),
('5','1','thumb_acer_h226hqlbmid_8428940.jpg','acer_h226hqlbmid_8428940.jpg','full_acer_h226hqlbmid_8428940.jpg','','3'),
('8','3','thumb_img3.jpg','img3.jpg','full_img3.jpg','','1'),
('9','4','thumb_img4.jpg','img4.jpg','full_img4.jpg','','1'),
('10','5','thumb_img5.jpg','img5.jpg','full_img5.jpg','','1'),
('11','6','thumb_img6.jpg','img6.jpg','full_img6.jpg','','1'),
('12','7','thumb_img7.jpg','img7.jpg','full_img7.jpg','','1'),
('13','8','thumb_img8.jpg','img8.jpg','full_img8.jpg','','1'),
('14','9','thumb_img9.jpg','img9.jpg','full_img9.jpg','','1'),
('15','10','thumb_img10.jpg','img10.jpg','full_img10.jpg','','1'),
('16','11','thumb_img11.jpg','img11.jpg','full_img11.jpg','','1'),
('17','12','thumb_img12.jpg','img12.jpg','full_img12.jpg','','1');

TRUNCATE TABLE `js_jshopping_products_prices`;
TRUNCATE TABLE `js_jshopping_products_relations`;
TRUNCATE TABLE `js_jshopping_products_to_categories`;
INSERT INTO `js_jshopping_products_to_categories` (`product_id`,`category_id`,`product_ordering`) VALUES
('1','3','1'),
('2','4','1'),
('3','6','1'),
('4','6','2'),
('5','7','1'),
('6','7','2'),
('7','7','3'),
('8','9','1'),
('9','9','2'),
('10','10','1'),
('11','10','2'),
('12','10','3');

TRUNCATE TABLE `js_jshopping_products_free_attr`;
TRUNCATE TABLE `js_jshopping_products_files`;
TRUNCATE TABLE `js_jshopping_manufacturers`;
