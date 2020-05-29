TRUNCATE TABLE `js32_jshopping_categories`;
INSERT INTO `js32_jshopping_categories` (`category_id`,`category_image`,`category_parent_id`,`category_publish`,`category_ordertype`,`category_template`,`ordering`,`category_add_date`,`products_page`,`products_row`,`access`,`name_en-GB`,`alias_en-GB`,`short_description_en-GB`,`description_en-GB`,`meta_title_en-GB`,`meta_description_en-GB`,`meta_keyword_en-GB`,`name_ru-RU`,`alias_ru-RU`,`short_description_ru-RU`,`description_ru-RU`,`meta_title_ru-RU`,`meta_description_ru-RU`,`meta_keyword_ru-RU`) VALUES
('1','cat1.jpg','0','1','1','','1','2015-02-05 22:02:40','12','3','1','','','','','','','','Компьютерная техника','1-komputernaya-tehnika','Описание категории краткое','Полное','','',''),
('2','12472.jpg','1','1','1','','1','2015-02-05 22:02:40','12','3','1','','','','','','','','Мониторы','2-monitory','','','','',''),
('3','cat3.jpg','2','1','1','','1','2015-02-05 22:02:40','12','3','1','','','','','','','','Acer','3-acer','','','','',''),
('4','cat4.jpg','2','1','1','','2','2015-02-05 22:02:40','12','3','1','','','','','','','','Asus','4-asus','','','','',''),
('5','cat5.png','1','1','1','','2','2015-02-05 22:02:40','12','3','1','','','','','','','','Комплектующие для ПК','5-komplektuushie-dlya-pk','','','','',''),
('6','cat6.jpg','5','1','1','','1','2015-02-05 22:02:40','12','3','1','','','','','','','','Модули памяти','6-moduli-pamyati','','','','',''),
('7','cat7.jpg','5','1','1','','2','2015-02-05 22:02:40','12','3','1','','','','','','','','Звуковые карты','7-zvukovye-karty','','','','',''),
('8','cat8.jpg','0','1','1','','2','2015-02-05 22:02:40','12','3','1','','','','','','','','Крупнобытовая техника','8-krupnobytovaya-tehnika','','','','',''),
('9','cat9.jpg','8','1','1','','1','2015-02-05 22:02:40','12','3','1','','','','','','','','Холодильники','9-holodilniki','','','','',''),
('10','cat10.jpg','8','1','1','','2','2015-02-05 22:02:40','12','3','1','','','','','','','','Кухонные плиты','10-kuhonnye-plity','','','','','');

TRUNCATE TABLE `js32_jshopping_products`;
INSERT INTO `js32_jshopping_products` (`product_id`,`parent_id`,`product_ean`,`product_quantity`,`unlimited`,`product_availability`,`product_date_added`,`date_modify`,`product_publish`,`product_tax_id`,`currency_id`,`product_template`,`product_url`,`product_old_price`,`product_buy_price`,`product_price`,`min_price`,`different_prices`,`product_weight`,`image`,`product_manufacturer_id`,`product_is_add_price`,`add_price_unit_id`,`average_rating`,`reviews_count`,`delivery_times_id`,`hits`,`weight_volume_units`,`basic_price_unit_id`,`label_id`,`vendor_id`,`access`,`name_en-GB`,`alias_en-GB`,`short_description_en-GB`,`description_en-GB`,`meta_title_en-GB`,`meta_description_en-GB`,`meta_keyword_en-GB`,`name_ru-RU`,`alias_ru-RU`,`short_description_ru-RU`,`description_ru-RU`,`meta_title_ru-RU`,`meta_description_ru-RU`,`meta_keyword_ru-RU`,`extra_field_1`,`extra_field_2`) VALUES
('1','0','112213','20.00','0','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','2','2','default','','0.0000','0.0000','150.000000','0.00','1','0.0000','acer_h226hqlbmid_8436467.jpg','1','1','4','0.00','0','5','0','0.0000','0','5','0','1','','','','','','','','Acer G11195HQVB','alias1','Краткое описание1','Полное описание','title1','metadesc1','key1','',''),
('2','0','19473','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','0','3','1','default','','0.0000','0.0000','220.000000','0.00','1','0.0000','img1.jpg','1','1','4','0.00','0','6','0','0.0000','0','3','0','1','','','','','','','','Asus ML239H','alias2','Краткое описание2','Полное описание2','title2','metadesc2','key2','',''),
('3','0','779','0.00','0','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','4','2','default','','0.0000','0.0000','50.000000','0.00','1','0.0000','img3.jpg','2','1','4','0.00','0','3','0','0.0000','0','0','0','1','','','','','','','','1Gb 400MHz TakeMS BD1024TEC600A bulk','alias3','Краткое описание3','Полное описание3','title3','metadesc3','key3','',''),
('4','0','19253','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','0','1','default','','0.0000','0.0000','48.000000','0.00','1','0.0000','img4.jpg','2','1','4','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','1GB 800 Hynix Original l SO DIMM','4-1gb-800-hynix-original-l-so-dimm','Краткое описание4','Полное описание4','','','','',''),
('5','0','19322','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','1','1','default','','0.0000','0.0000','80.000000','0.00','1','0.0000','img5.jpg','3','1','4','0.00','0','7','0','0.0000','0','0','0','1','','','','','','','','Asus XONAR D1/A (PCI, support 7.1)','5-asus-xonar-d1-a-pci-support-71','Краткое описание5','Полное описание5','','','','',''),
('6','0','19256','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','0','1','default','','0.0000','0.0000','70.000000','0.00','0','0.0000','img6.jpg','3','0','4','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Asus XONAR DG (PCI, Dolby Headphone 5.1)','6-asus-xonar-dg-pci-dolby-headphone-51','Краткое описание6','Полное описание6','','','','',''),
('7','0','783','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','0','1','default','','0.0000','0.0000','75.000000','0.00','0','0.0000','img7.jpg','3','0','4','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Asus Xonar DS A','7-asus-xonar-ds-a','Краткое описание7','Полное описание7','','','','',''),
('8','0','56785','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','0','1','default','','0.0000','0.0000','250.000000','0.00','0','0.0000','img8.jpg','4','0','4','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Ardo COO2210SHC','8-ardo-coo2210shc','Краткое описание8','Полное описание8','','','','',''),
('9','0','48686','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','0','1','default','','0.0000','0.0000','200.000000','0.00','0','0.0000','img9.jpg','4','0','4','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Beko DSA 25000','9-beko-dsa-25000','Краткое описание9','Полное описание9','','','','',''),
('10','0','785','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','0','1','default','','0.0000','0.0000','150.000000','0.00','0','0.0000','img10.jpg','5','0','4','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Ardo A 564 VG6 Inox','10-ardo-a-564-vg6-inox','Краткое описание10','Полное описание10','','','','',''),
('11','0','18967','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','0','1','default','','0.0000','0.0000','200.000000','0.00','0','0.0000','img11.jpg','5','0','4','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Beko CE 51011','11-beko-ce-51011','Краткое описание11','Полное описание11','','','','',''),
('12','0','888','1.00','1','','2015-02-05 22:02:40','2015-02-05 22:02:40','1','0','1','default','','0.0000','0.0000','170.000000','0.00','0','0.0000','img12.jpg','5','0','4','0.00','0','0','0','0.0000','0','0','0','1','','','','','','','','Ardo PL998CREAM','12-ardo-pl998cream','Краткое описание12','Полное описание12','','','','','');

TRUNCATE TABLE `js32_jshopping_products_attr`;
TRUNCATE TABLE `js32_jshopping_products_attr2`;
TRUNCATE TABLE `js32_jshopping_products_images`;
INSERT INTO `js32_jshopping_products_images` (`image_id`,`product_id`,`image_name`,`name`,`ordering`) VALUES
('963','180','acer_h226hqlbmid_8428940.jpg','','3'),
('962','180','acer_h226hqlbmid_8428914.jpg','','2'),
('961','180','acer_h226hqlbmid_8436467.jpg','','1'),
('965','181','img2.jpg','','2'),
('964','181','img1.jpg','','1'),
('966','182','img3.jpg','','1'),
('967','183','img4.jpg','','1'),
('968','184','img5.jpg','','1'),
('969','185','img6.jpg','','1'),
('970','186','img7.jpg','','1'),
('978','187','img8.jpg','','1'),
('979','188','img9.jpg','','1'),
('973','189','img10.jpg','','1'),
('974','190','img11.jpg','','1'),
('975','191','img12.jpg','','1'),
('997','1','acer_h226hqlbmid_8428940.jpg','title1|title2|title3','3'),
('996','1','acer_h226hqlbmid_8428914.jpg','title1|title2|title3','2'),
('995','1','acer_h226hqlbmid_8436467.jpg','title1|title2|title3','1'),
('999','2','img2.jpg','title1|title2','2'),
('998','2','img1.jpg','title1|title2','1'),
('1000','3','img3.jpg','title1','1'),
('1001','4','img4.jpg','title2','1'),
('1002','5','img5.jpg','title4','1'),
('1003','6','img6.jpg','title5','1'),
('1004','7','img7.jpg','title6','1'),
('1005','8','img8.jpg','title9','1'),
('1006','9','img9.jpg','title10','1'),
('1007','10','img10.jpg','title12','1'),
('1008','11','img11.jpg','title13','1'),
('1009','12','img12.jpg','title14','1');

TRUNCATE TABLE `js32_jshopping_products_prices`;
INSERT INTO `js32_jshopping_products_prices` (`price_id`,`product_id`,`discount`,`product_quantity_start`,`product_quantity_finish`) VALUES
('1','1','26.666667','6','0'),
('2','1','13.333333','2','5'),
('3','2','10.000000','6','0'),
('4','2','5.000000','2','5'),
('5','3','40.000000','6','0'),
('6','3','20.000000','2','5'),
('7','4','5.000000','6','0'),
('8','4','3.000000','2','5'),
('9','5','62.500000','6','0'),
('10','5','37.500000','2','5');

TRUNCATE TABLE `js32_jshopping_products_relations`;
INSERT INTO `js32_jshopping_products_relations` (`id`,`product_id`,`product_related_id`) VALUES
('1','2','1'),
('2','7','6'),
('3','7','5'),
('4','1','2'),
('5','5','6'),
('6','5','7');

TRUNCATE TABLE `js32_jshopping_products_to_categories`;
INSERT INTO `js32_jshopping_products_to_categories` (`product_id`,`category_id`,`product_ordering`) VALUES
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

TRUNCATE TABLE `js32_jshopping_products_free_attr`;
TRUNCATE TABLE `js32_jshopping_products_files`;
TRUNCATE TABLE `js32_jshopping_manufacturers`;
INSERT INTO `js32_jshopping_manufacturers` (`manufacturer_id`,`manufacturer_url`,`manufacturer_logo`,`manufacturer_publish`,`products_page`,`products_row`,`ordering`,`name_en-GB`,`alias_en-GB`,`short_description_en-GB`,`description_en-GB`,`meta_title_en-GB`,`meta_description_en-GB`,`meta_keyword_en-GB`,`name_ru-RU`,`alias_ru-RU`,`short_description_ru-RU`,`description_ru-RU`,`meta_title_ru-RU`,`meta_description_ru-RU`,`meta_keyword_ru-RU`) VALUES
('1','','','0','0','0','0','','','','','','','','Корея','','','','','',''),
('2','','','0','0','0','0','','','','','','','','Китай','','','','','',''),
('3','','','0','0','0','0','','','','','','','','Россия','','','','','',''),
('4','','','0','0','0','0','','','','','','','','Франция','','','','','',''),
('5','','','0','0','0','0','','','','','','','','Италия','','','','','','');

