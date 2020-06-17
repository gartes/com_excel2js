<?php

	ini_set( "upload_max_filesize" , "100M" );
	ini_set( "post_max_size" , "100M" );


	defined( '_JEXEC' ) or die( 'Restricted access' );


	jimport( 'joomla.application.component.model' );
	jimport( 'joomla.filesystem.file' );
	jimport( 'joomla.filesystem.folder' );

	use Joomla\Archive\Archive;
	use Joomla\CMS\MVC\Model\BaseDatabaseModel;
	use Joomla\Utilities\ArrayHelper;

	require_once( dirname( __FILE__ ) . DS . "updateTable.php" );

	/**
	 * Class Excel2jsModelExcel2js
	 * @since 3.9
	 *
	 */
	class Excel2jsModelExcel2js extends JModelLegacy
	{
		public $pagination;
		/**
		 * Настройки текущего профиля (Имопрт - Экспорт)
		 * @var mixed
		 * @since version
		 * @since 3.9
		 */
		public $config;
		/**
		 * Массив активных столбцов текущего профиля
		 * @var array
		 * @since 3.9
		 */
		public $active;

//    	protected $extra_table_depend;
//    	public $active;
		/**
		 * Индикатор CRON
		 * @var bool
		 * @since 3.9
		 */
		protected $cron;
		/**
		 * параемтр memory_limit
		 * @var false|float
		 * @since 3.9
		 */
		protected $mem_total;
		/**
		 * Время начала операции
		 * @var int
		 * @since 3.9
		 */
		protected $real_start_time;
		/**
		 * Директория с файлами excel
		 * @var string
		 * @since 3.9
		 */
		protected $path_to_file;
		/**
		 * Директория с файлами excel для CRON
		 * @var string
		 * @since 3.9
		 */
		protected $cron_file_dir;
		/**
		 * Параметр настроейки компонента - показывать результирующую таблицу
		 * @var int
		 * @since 3.9
		 */
		protected $show_results;
		/**
		 * @var
		 */
		protected $status;
		/**
		 * @var int Время исполнения скрипта
		 * @since 3.9
		 */
		protected $max_execution_time;
		/**
		 * @var int
		 */
		protected $start_time;
		/**
		 * @var array Хранение статистики
		 */
		protected $stat;
		/**
		 * @var mixed список изображений
		 * @since 3.9
		 */
		protected $images_collection;
		protected $reimport;
		protected $import_file_name;
		/**
		 * Номер строки начала чтения
		 * @var int
		 * @since 3.9
		 */
		protected $numRow;

		protected $active_fields;
		/**
		 * Шаг считывания при импорте.
		 * Настройки компонента
		 * @var INT
		 * @since 3.9
		 */
		protected $chunkSize;
		/**
		 * № первой строки - С какой строки начинается импорт товаров
		 * [Настройки импорта/экспорта=>Основные настройки]
		 * @var int
		 * @since 3.9
		 */
		protected $first_row;
		/**
		 * Кэш Кодов товаров
		 * @var bool
		 * @since 3.9
		 */
		private $sku_cache;
		/**
		 * @var object class updateTable
		 * @since 3.9
		 */
		protected $js_product;
		/**
		 * Переименование скачиваемых изображений [Configuration => Расширенные параметры ]
		 * @var bool
		 * @since 3.9
		 */
		private $images_rename;
		/**
		 * Путь где лежат фотографии
		 * @var string
		 * @since 3.9
		 */
		public $image_product_path;
		protected $independ;
		protected $depend;
		/**
		 * @var array зависимые поля
		 * @since 3.9
		 */
		private $depend2;
		/**
		 * @var BaseDatabaseModel
		 * @since 3.9
		 */
		protected $_db;


		function __construct( $cron = false )
		{
			parent:: __construct();

			JLoader::registerNamespace('GComHelpers', JPATH_ADMINISTRATOR.'/components/com_excel2js/helpers',$reset=false,$prepend=false,$type='psr4');






			$this->params = JComponentHelper:: getParams( 'com_excel2js' );


			$this->app = JFactory::getApplication();
			$this->input = $this->app->input;

			$this->cron = $cron;
			if( $this->cron )
			{
				$this->cron_file_dir = $this->params->get( 'directory_path' );
				$custom_remote_file = $this->input->get( 'filename' , '' , 'string' );
				if( strstr( $custom_remote_file , "http" ) )
				{
					$this->remote_file = urldecode( $custom_remote_file );
				} else
				{
					$this->remote_file = $this->params->get( 'remote_file' );
				}

				if( substr( $this->cron_file_dir , -1 ) != DS )
				{
					$this->cron_file_dir .= DS;
				}
			}

			$this->chunkSize = $this->params->get( 'chunk_size' , 1000 );
			$this->exclude = $this->params->get( 'exclude' , 0 );
			$this->custom_clear = $this->params->get( 'custom_clear' , '-' );
			$this->csv_field_delimiter = $this->params->get( 'csv_field_delimiter' , ';' );
			$this->csv_row_delimiter = $this->params->get( 'csv_row_delimiter' , '' );
			$this->csv_convert = $this->params->get( 'csv_convert' , 1 );
			# Кэш Кодов товаров
			$this->sku_cache = $this->params->get( 'sku_cache' , 0 );

			$this->images_rename = $this->params->get( 'images_rename' , 0 );
			$this->images_resize = $this->params->get( 'images_resize' , 1 );
			$this->thumb_replace = $this->params->get( 'thumb_replace' , 1 );

			# Время исполнения скрипта
			$this->max_execution_time = $this->params->get( 'max_execution_time' , 30 );
			$this->productid_cache = $this->params->get( 'productid_cache' , 0 );
			$this->config_table = new updateTable( "#__excel2js" , "id" , 1 );
			$this->config = $this->getConfig();
			$this->get_version();

			$this->active = $this->getActive();



			$this->reimport = $this->input->get( 'reimport' , 0 , 'int' );
			$this->show_results = (int)$this->input->get( 'show_results' , '' , 'int' );


			$this->first_row = $this->config->first;

			$this->default_object = @ file_get_contents( dirname( __FILE__ ) . "/catprod_default.txt" );
			$this->default_articles_object = @ file_get_contents( dirname( __FILE__ ) . "/articles_default.txt" );

			$this->trans = [ "а" => "a" , "б" => "b" , "в" => "v" , "г" => "g" , "д" => "d" , "е" => "e" , "ё" => "yo" , "ж" => "j" , "з" => "z" , "и" => "i" , "й" => "y" , "к" => "k" , "л" => "l" , "м" => "m" , "н" => "n" , "о" => "o" , "п" => "p" , "р" => "r" , "с" => "s" , "т" => "t" , "у" => "u" , "ф" => "f" , "х" => "h" , "ц" => "c" , "ч" => "ch" , "ш" => "sh" , "щ" => "sh" , "ы" => "y" , "э" => "e" , "ю" => "u" , "я" => "ya" , "А" => "a" , "Б" => "b" , "В" => "v" , "Г" => "g" , "Д" => "d" , "Е" => "e" , "Ё" => "yo" , "Ж" => "j" , "З" => "z" , "И" => "i" , "Й" => "y" , "К" => "k" , "Л" => "l" , "М" => "m" , "Н" => "n" , "О" => "o" , "П" => "p" , "Р" => "r" , "С" => "s" , "Т" => "t" , "У" => "u" , "Ф" => "f" , "Х" => "h" , "Ц" => "c" , "Ч" => "ch" , "Ш" => "sh" , "Щ" => "sh" , "Ы" => "y" , "Э" => "e" , "Ю" => "u" , "Я" => "ya" , "ь" => "" , "Ь" => "" , "ъ" => "" , "Ъ" => "" , "/" => "-" , "\\" => "" , "-" => "-" , ":" => "-" , "(" => "-" , ")" => "-" , "." => "" , "," => "" , '"' => "-" , '>' => "-" , '<' => "-" , '+' => "-" , '«' => '' , '»' => '' , "'" => "" , "і" => "i" , "ї" => "yi" , "І" => "i" , "Ї" => "yi" , "є" => "e" , "Є" => "e" , "%" => "" ];
			$this->backup_tables_array = [ "#__jshopping_categories" , "#__jshopping_products" , "#__jshopping_products_attr" , "#__jshopping_products_attr2" , "#__jshopping_products_images" , "#__jshopping_products_prices" , "#__jshopping_products_relations" , "#__jshopping_products_to_categories" , "#__jshopping_products_free_attr" , "#__jshopping_products_files" , "#__jshopping_manufacturers" , "#__jshopping_attr" , "#__jshopping_attr_values" ];
			$this->category_list = $this->category_list();
			$this->manufacturers_list = $this->manufacturers_list();
			$user = JFactory:: getUser();
			$this->user_id = $user->id;
			$this->letters = range( 'A' , 'Z' );
			$leters = range( 'A' , 'Z' );
			foreach( $leters as $l2 )
			{
				foreach( $leters as $letter )
					$this->letters[] = $l2 . $letter;
			}

		}

		function getConfig()
		{
			$id = 0;
			if( $this->cron )
			{
				$profile = $this->input->get( 'profile' , '' , 'string' );
				if( $profile )
				{
					$profile = urldecode( $profile );
					$this->_db->setQuery( "SELECT id FROM #__excel2js WHERE profile=" . $this->_db->Quote( $profile ) );
					$id = $this->_db->loadResult();
					if( !$id )
					{
						$this->cron_log( "Профиль '$profile' не найден. Импорт прерван." );
						exit();
					}
				} else
				{
					$id = $this->params->get( 'cron_profile' );
				}

			}
			if( !$id )
			{
				$this->_db->setQuery( "SELECT id FROM #__excel2js WHERE default_profile = 1" );
				$id = $this->_db->loadResult();
			}
			if( !$id )
			{
				$this->_db->setQuery( "UPDATE #__excel2js SET default_profile = 1 LIMIT 1" );
				$this->_db->execute();
				$this->config_table->load( 1 , 'default_profile' );
			} else
				$this->config_table->load( $id );
			$this->active_fields = $this->config_table->active;
			$config = unserialize( $this->config_table->config );
			$config->profile_name = $this->config_table->profile;
			$config->profile_id = $this->config_table->id;
			if( !$config->language )
			{
				$languages = $this->getLanguages();
				if( !in_array( 'ru-Ru' , $languages ) )
				{
					$config->language = current( $languages )->language;
				}
			}
			if( !@$config->currency_rate )
			{
				$config->currency_rate = 1;
			}

			return $config;
		}

		function cron_log( $msg )
		{
			$fp = fopen( dirname( __FILE__ ) . DS . "cron_log.txt" , "a" );
			fwrite( $fp , date( "Y-m-d H:i:s" ) . " - " . $msg . "\r\n" );
			fclose( $fp );
			echo "$msg<br>";
		}

		function getLanguages()
		{
			$this->_db->setQuery( "SELECT language,name FROM #__jshopping_languages ORDER BY ordering, name" );

			return $this->_db->loadObjectList( 'language' );
		}

		function get_version()
		{
			$xml = simplexml_load_file( JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jshopping' . DS . 'jshopping.xml' );
			$version = (string)$xml->version;
			if( substr( $version , 0 , 1 ) == 3 )
			{
				$this->old_version = true;
			} else
			{
				$this->old_version = false;
			}
		}

		function getActive()
		{
			$this->active_fields = $this->active_fields ? $this->active_fields : 1;
			$query = "SELECT *
        FROM #__excel2js_fields
        WHERE id IN({$this->active_fields})
        ORDER BY FIELD(id,{$this->active_fields})";
			$this->_db->setQuery( $query );
			$list = $this->_db->loadObjectList( 'name' );
			$i = 0;
			foreach( $list as $key => $l )
			{
				$i++;
				@ $list[ $key ]->ordering = $i;
			}

			return $list;
		}

		function category_list()
		{
			$this->_db->setQuery( "SELECT category_id, `name_{$this->config->language}` as  category_name
              FROM #__jshopping_categories
              WHERE category_id NOT IN ($this->exclude)" );

			return $this->_db->loadObjectList( 'category_id' );
		}

		function manufacturers_list()
		{
			$this->_db->setQuery( "SELECT manufacturer_id,`name_{$this->config->language}` as mf_name
              FROM #__jshopping_manufacturers" );

			return $this->_db->loadObjectList( 'manufacturer_id' );
		}

		function searchManufacturer( $name )
		{
			$name_words = explode( " " , $name );
			foreach( $name_words as $key => $name_word )
			{
				$name_words[ $key ] = $this->_strtolower( strtr( $name_word , [ '"' => '' , "'" => "" , '`' => '' ] ) );
				if( strlen( $name_word ) < 2 )
					unset ( $name_words[ $key ] );
			}
			if( count( $this->manufacturers_list ) < 100 )
				foreach( $this->manufacturers_list as $mf )
				{
					$mf_words = explode( " " , $mf->mf_name );
					foreach( $mf_words as $mf_word )
					{
						if( strlen( $mf_word ) < 2 )
							continue;
						foreach( $name_words as $name_word )
						{
							if( preg_match( "#^" . mb_substr( $this->_strtolower( $mf_word ) , 0 , 4 ) . "#" , $name_word ) or $this->_strtolower( $mf_word ) == $name_word )
								return $mf->manufacturer_id;
						}
					}
				}

			return false;
		}

		function change_profile()
		{
			$profile_id = $this->input->get( 'profile_id' , '' , 'int' );
			$this->_db->setQuery( "UPDATE #__excel2js SET default_profile = 0" );
			$this->_db->execute();
			$this->config_table->reset();
			$this->config_table->id = $profile_id;
			$this->config_table->default_profile = 1;
			$this->config_table->update();
		}

		function profile_list()
		{
			$list = $this->_getList( "SELECT id, profile FROM #__excel2js ORDER BY id" );

			return $list;
		}

		function upload()
		{
			$success = 0;
			$errors = [ "Неизвестная ошибка" , "Размер прайса превысил максимально допустимый размер, который задан директивой upload_max_filesize конфигурационного файла php.ini. Обратитесь в тех. поддержку хостинга с просьбой увеличить лимит" , "Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме" , "Загружаемый файл был получен только частично. Это может быть связано с нестабильным интернет-соединением или с проблемами на хостинге. Повторите попытку позже" , "Файл не был загружен" , "" , "Отсутствует временная папка" , "Не удалось записать файл на диск. Проверьте, достаточно ли места на диске" , "PHP-расширение остановило загрузку файла" ];
			$xls_dir = JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls';
			$perms = substr( sprintf( '%o' , fileperms( $xls_dir ) ) , -4 );
			if( ( !is_executable( $xls_dir ) or !is_readable( $xls_dir ) or !is_writable( $xls_dir ) ) and DS == '/' )
			{
				echo "<b><span style='color:#FF0000'>Прайс не может быть загружен, т.к. на папку $xls_dir установлены права - $perms. Установите права - 755</span></b>";
				exit ();
			}
			$total_files = count( $_FILES[ 'xls_file' ][ 'name' ] );
			for( $i = 0; $i < $total_files; $i++ )
			{
				$file_type = strtolower( pathinfo( $_FILES[ 'xls_file' ][ 'name' ][ $i ] , PATHINFO_EXTENSION ) );
				if( !in_array( $file_type , [ 'xls' , 'csv' , 'xlsx' ] ) )
				{
					echo "<b><span style='color:#FF0000'>" . JText:: _( 'WRONG_FILE_FORMAT' ) . "</span></b>";
					exit ();
				}
				if( !JFile:: upload( $_FILES[ 'xls_file' ][ 'tmp_name' ][ $i ] , $xls_dir . DS . $_FILES[ 'xls_file' ][ 'name' ][ $i ] ) )
				{
					if( !$_FILES[ 'xls_file' ][ 'error' ][ $i ] )
					{
						echo "<b><span style='color:#FF0000'>" . JText:: _( 'ERROR_DURING_UPLOAD' ) . " - Ошибка не известна. Проверьте, достаточно ли места на сервере</span></b>";
					} else
					{
						echo "<b><span style='color:#FF0000'>" . JText:: _( 'ERROR_DURING_UPLOAD' ) . " - " . $errors[ $_FILES[ 'xls_file' ][ 'error' ][ $i ] ] . "</span></b>";
					}
					exit ();
				} else
				{
					$success++;
				}
			}
			if( $success == $total_files )
			{
				echo "Ok";
			} else
			{
				echo "<b><span style='color:#FF0000'>Загружено файлов: $success из $total_files</span></b>";
			}
		}


		/**
		 * Import Точка входа
		 * @throws PHPExcel_Exception
		 * @throws PHPExcel_Reader_Exception
		 * @throws Exception
		 * @since 3.9
		 */
		function import()
		{

			$app = JFactory::getApplication();
			
			# Время начала выполнения
			$this->start_time = time();

			$this->timeout = time() + $this->max_execution_time - 5;

			@file_put_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'abort.txt' , 0 );

			$this->check_abort = time() + 10;



			# Если это не крон - сохранить в Cookies настройку показа сообщения
			# статистики о импорте товара
			if( !$this->cron )
			{
				$this->setCookies();
			}

			$lock = fopen( dirname( __FILE__ ) . DS . 'lock.run' , 'w' );
			if( !flock( $lock , LOCK_EX | LOCK_NB ) )
			{
				header( 'HTTP/1.1 502 Gateway Time-out' );
				jexit();
			}


			$uploaded_file = $app->input->get( 'uploaded_file' , [] , 'ARRAY' );
			/**
			 * Если нет отмеченных файлов для импорта
			 */
			if( !count( $uploaded_file ) )
			{

			}#END IF


			# Получить параемтр memory_limit
			$this->mem_total = \GComHelpers\Helper::get_mem_total();
			# Время начала операции
			$this->real_start_time = time();

			if( $this->cron )
			{
				$this->path_to_file = $this->cron_file_dir;
			} else
			{
				$this->path_to_file = JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls' . DS;
			}

			if( $this->reimport )
			{
				$this->_db->setQuery( "SELECT js_id FROM #__excel2js_log WHERE type = 'cn' OR type = 'cu' ORDER BY log_id DESC" , 0 , 1 );
				$this->category_id = $this->_db->loadResult();
				$log = json_decode( file_get_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'log.txt' ) );
				$_FILES[ 'xls_file' ] = unserialize( @ file_get_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'uploaded_files.txt' ) );
				$this->start_time = time() - @ $log->time;
				$this->file_index = @ $log->file_index;
				$this->first_row = @ $log->cur_row;

				if( $this->first_row < $this->config->first )
				{
					$this->first_row = $this->config->first;
				}

				$stat_type = [ 'pn' , 'pu' , 'cn' , 'cu' ];
				foreach( $stat_type as $type )
				{
					@ $this->stat[ $type ] = $log->type;
				}
				if( !file_exists( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . "xls-dump.txt" ) )
				{
					throw new Exception( "Не найден файл xls-dump.txt" );
				}
				$dump = file_get_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . "xls-dump.txt" );
				@$dump = unserialize( $dump );

				if( @$dump->level ) $this->level = $dump->level;
				if( @$dump->last_child ) $this->last_child = $dump->last_child;
				if( @$dump->last_parent ) $this->last_parent = $dump->last_parent;
				if( @$dump->last_path ) $this->last_path = $dump->last_path;
				if( @$dump->category_levels ) $this->category_levels = $dump->category_levels;
				if( @$dump->last_parrent_array ) $this->last_parrent_array = $dump->last_parrent_array;
				if( @$dump->tree ) $this->tree = $dump->tree;


				if( $this->config->images_import_method )
				{
					# Получить список изображений
					$this->images_collection = unserialize( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'images_collection.txt' );
				}
			}
			else
			{

				file_put_contents( JPATH_ROOT . DS . 'components' . DS . 'com_excel2js' . DS . "error_log.txt" , "" );

				/**
				 * Создание бэкапа DB
				 */
				if( $this->config->auto_backup )
				{
					# "Создаётся бэкап таблиц магазина"
					$this->status = JText::_( 'BACKUP_OF_TABLES' );
					$this->updateStat();

					if( $this->config->backup_type )
						$this->backup2( $this->backup_tables_array );
					else
					{
						$this->backup( $this->backup_tables_array );
					}
				}


				/**
				 * Выборка категорий
				 */
				if( $this->config->unpublish )
				{
					if( !@ $this->config->unpublish_categories
						or ( count( @ $this->config->unpublish_categories ) == 1
							and @ $this->config->unpublish_categories[ 0 ] == 0
						)
					)
					{
						$this->_db->setQuery( "UPDATE #__jshopping_products SET product_publish = 0" );
						$this->_db->execute();
					} else
					{
						$this->config->unpublish_categories = ArrayHelper:: toInteger( $this->config->unpublish_categories );
						$cats = implode( "," , $this->config->unpublish_categories );
						$this->_db->setQuery( "UPDATE #__jshopping_products as p
                                          LEFT JOIN #__jshopping_products_to_categories as c USING(product_id)
                                          SET product_publish = 0
                                          WHERE category_id IN($cats)
                                          " );
						$this->_db->execute();
					}
				}

				/**
				 * Выборка товаров
				 */
				if( $this->config->reset_stock )
				{
					if( !@ $this->config->reset_categories
						or ( count( @ $this->config->reset_categories ) == 1
							and @ $this->config->reset_categories[ 0 ] == 0
						)
					)
					{
						$this->_db->setQuery( "UPDATE #__jshopping_products SET product_quantity = 0, unlimited = 0" );
						$this->_db->execute();
					} else
					{
						$this->config->reset_categories = ArrayHelper:: toInteger( $this->config->reset_categories );
						$cats = implode( "," , $this->config->reset_categories );
						$this->_db->setQuery( "UPDATE #__jshopping_products as p
                                          LEFT JOIN #__jshopping_products_to_categories as c USING(product_id)
                                          SET product_quantity = 0, unlimited = 0
                                          WHERE category_id IN($cats)
                                          " );
						$this->_db->execute();
					}
				}



				$this->file_index = 0;

				/**
				 * Пересоздать таблицу логов
				 */
				$this->_db->setQuery( "TRUNCATE TABLE #__excel2js_log" );
				$this->_db->execute();

				/**
				 * 'pn' - Количиство новых продуктов
				 * 'pu' - Количиство обновленных продуктов
				 * 'cn' - Количиство новых категори
				 * 'cu' - Количиство обновленных категори
				 */
				$stat_type = [ 'pn' , 'pu' , 'cn' , 'cu' ];
				foreach( $stat_type as $type )
				{
					@$this->stat[ $type ] = 0;
				}
				unset( $type );
			}
			$this->setJSsettings();
			$uploaded_file = $this->input->get( 'uploaded_file' , '' , 'array' );

			if( @$_FILES[ 'zip_file' ][ 'name' ] )
			{
				$this->status = "Распаковка изображений";
				$this->updateStat();
				$this->load_img();
			}



			if( $this->cron and $this->import_file_name )
			{
				$this->total_files = 1;
			} else if( count( $uploaded_file ) and $uploaded_file[ 0 ] != '' )
			{
				$this->total_files = count( $uploaded_file );
			} else
			{
				echo "<b><span style='color:#FF0000'>" . JText:: _( 'UNKNOWN_FILE_IMPORT' ) . "!</span></b>";
				exit ();
			}

			$this->status = "";

			# Уходим к импорту
			for( $this->file_index; $this->file_index < $this->total_files; $this->file_index++ ) :

				if( $this->cron and $this->import_file_name )
				{
					$this->filename = $filename = $this->import_file_name;
				} else
					$this->filename = $filename = $uploaded_file[ $this->file_index ];

				$this->last_upd = time() - 1;
				$file_type = substr( $filename , -4 );

				if( $file_type == '.csv' )
				{
					$handle2 = fopen( $this->path_to_file . $filename , "r" );
					$this->numRow = 0;
					$last = (int)( $this->config->last );
					while( !feof( $handle2 ) )
					{
						fgets( $handle2 , 8096 );
						$this->numRow++;
						if( $this->numRow > $last and $last > 0 )
						{
							$this->numRow = $last;
							break;
						}
					}
					fclose( $handle2 );
					unset ( $handle2 );
					$handle = fopen( $this->path_to_file . $filename , "r" );
					$this->row = 0;
					while( !feof( $handle ) )
					{
						$this->row++;
						$cells = fgets( $handle , 8096 );
						if( $this->row < $this->first_row )
							continue;
						if( $this->row >= $this->numRow )
							break;
						if( empty ( $cells ) )
							continue;
						if( $this->csv_convert )
							$cells = iconv( 'WINDOWS-1251' , 'UTF-8' , $cells );

						if( $this->csv_field_delimiter == '\t' )
						{
							$cells_array = explode( "\t" , $cells );
						} else
						{
							$cells_array = explode( $this->csv_field_delimiter , $cells );
						}


						for( $i = 0; $i < count( $cells_array ); $i++ )
						{
							if( $i == 0 )
								$cells_array[ $i ] = str_replace( $this->csv_row_delimiter , '' , $cells_array[ $i ] );
							if( $i == count( $cells_array ) - 1 )
								$cells_array[ $i ] = str_replace( $this->csv_row_delimiter , '' , $cells_array[ $i ] );
							$cells_array[ $i ] = str_replace( '%3B' , ';' , $cells_array[ $i ] );
						}
						array_unshift( $cells_array , 0 );
						unset ( $cells_array[ 0 ] );



						switch( $this->type( $cells_array , true ) )
						{
							case 'product' :
								$this->insertProduct( $this->prepare( $cells_array , false , false , true ) );
								break;
							case 'category' :
								$this->insertCategory( $this->prepare( $cells_array , false , true ) );
								break;
						}
						$this->updateStat();
					}
					fclose( $handle );
				} #1.Import Start
				else if( $file_type == '.xls' or $file_type == 'xlsx' )
				{
					if( $this->file_index == 0 )
					{
						$this->first_row--;
						$this->config->cat_col--;
					}
					/*require_once (JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_excel2js' . DS . 'libraries' . DS . 'reader.php');$data = new Spreadsheet_Excel_Reader();
$data->setOutputEncoding('UTF-8');
$data->read($this->path_to_file . $filename);*/
					require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'libraries' . DS . 'PHPExcel' . DS . 'IOFactory.php' );
					$cacheMethod = PHPExcel_CachedObjectStorageFactory :: cache_in_memory_gzip;
					$cacheSettings = [ 'memoryCacheSize' => '8MB' ];
					PHPExcel_Settings:: setCacheStorageMethod( $cacheMethod , $cacheSettings );
					$objReader = PHPExcel_IOFactory:: createReader( $file_type == '.xls' ? 'Excel5' : 'Excel2007' );

					$this->numRow = 65536;

					if( is_numeric( $this->config->last ) )
					{
						$this->numRow = $this->config->last ;
						if( $this->config->last < $this->chunkSize )
							$this->chunkSize = $this->config->last;
					}

					/*
					$chunkFilter = new chunkReadFilter();
					$objReader->setReadFilter($chunkFilter);
					*/
					$objReader->setLoadSheetsOnly( 0 );
					$last_col_latter = $this->letters[ count( $this->active ) - 1 ];
					$this->row = $this->first_row;

					# Если изображения находятся в файле
					if( $this->config->images_import_method )
					{
						$chunkFilter = new chunkReadFilter();
						$objReader->setReadFilter( $chunkFilter );
						$chunkFilter->setRows( $this->row , 10 );
						$objPHPExcel2 = $objReader->load( $this->path_to_file . $filename );
						$this->extractImages( $objPHPExcel2 );
						unset ( $objPHPExcel2 );
					}

					$data = $objReader->listWorksheetInfo( $this->path_to_file . $filename );
					$total_rows = $data[ 0 ][ 'totalRows' ];

					unset ( $objReader );

					/**
					 * Массив для сохранения списка заданий. Исползуется если в конфигурации провеля включен
					 * параметр [ Метод импорта => Построчный импорт ]
					 * @var array
					 */
					$ArrayImportToLine = [];


					/**
					 * @var int Счетчик пустых знаений
					 */
					$empty_value = 0;
					/**
					 * Шаг считывания при импорте
					 */
					for( $startRow = $this->first_row; $startRow < $this->numRow; $startRow += $this->chunkSize )
					{


						$chunkFilter = new chunkReadFilter();
						$objReader = PHPExcel_IOFactory:: createReader( $file_type == '.xls' ? 'Excel5' : 'Excel2007' );
						$objReader->setReadFilter( $chunkFilter );
						$objReader->setLoadSheetsOnly( 0 );
						$chunkFilter->setRows( $startRow , ( $this->chunkSize > $this->numRow - $startRow ) ? ( $this->numRow - $startRow + 1 ) : ( $this->chunkSize + 1 ) );

						if( $this->config->price_template != 8 )
							$objReader->setReadDataOnly( true );

						$before_read_time = time();

						$objPHPExcel = $objReader->load( $this->path_to_file . $filename );

						$total_read_time = time() - $before_read_time;
						if( $this->numRow > $total_rows )
							$this->numRow = $total_rows;
						/**
						 * @var INT Последняя строка импорта
						 */
						$end_row = $startRow + $this->chunkSize > $this->numRow ? $this->numRow : $startRow + $this->chunkSize;

						/** @var bool индикатор завершения перебора строк. При повторении 3 пустых строк == TRUE */
						$exit = false ;

						
						
						# Прочитать файл
						$all_cells = $objPHPExcel->getActiveSheet()
							->rangeToArray( "A" . ( $startRow + 1 ) . ":" . $last_col_latter . ( $end_row ) , null , true , true , false );




						# Внутренний цикл перебора строк
						# Переберам строки - $all_cells
						foreach( $all_cells as $key => $cells )
						{

							$this->row++;
							# Удалить Строку из общего массива строк
							unset ( $all_cells[ $key ] );

							$level = $this->config->price_template == 8 ? $objPHPExcel->getActiveSheet()->getRowDimension( $this->row )->getOutlineLevel() : 0;

							switch( $this->type( $cells ) )
							{
								# Обработка товаров
								case 'product' :
									# Создать для столбцов системные заголовки
									$resultPrepare = $this->prepare( $cells , $level );


									/**
									 * Если включен построковый режим импорта в
									 * настройкай профиля
									 * @var bool
									 */
									if( $this->config->line_import_on )
									{
										if ( empty($resultPrepare) ) $empty_value++ ;
										if ($empty_value == 3)		//после трех пустых значений, завершаем обработку файла, думая, что это конец
										{
											$exit = true;
											break 2 ;
										}

										

										# Записываем в массив для возвращения списка заданий клиенту 
										$ArrayImportToLine[] = $resultPrepare;
										
										
										
									} else
									{
										#Созадем товары !!!
										$this->insertProduct( $resultPrepare );
									}#END IF


									break;
								# Обработка Категорий
								case 'category' :
									$this->insertCategory( $this->prepare( $cells , $level , true ) );
									break;
							}
							/*if( $exit )
							{
								$startRow = $this->chunkSize ;
								break;
							}#END IF*/
							if( $key % 10 == 0 )
								$this->updateStat();
							unset ( $cells );
						}
						# Чистим память
						$objPHPExcel->disconnectWorksheets();
						unset ( $objPHPExcel );
						unset ( $objReader );

						# Если включен построковый режим импорта
						if( $this->config->line_import_on )
						{

							echo new JResponseJson( $ArrayImportToLine , 'Result' );
							die();
						}
//

						$this->last_upd = -2;
						if( ( time() + $total_read_time + 2 ) >= $this->timeout and $total_rows - $this->row > 1 and !$this->cron )
						{
							$this->updateStat( false , true );
						} else
						{
							$this->updateStat();
						}


					}
				}

				if( $this->reimport )
				{
					$this->reimport = 0;
					$this->first_row = $this->config->first;
				}
			endfor;


			$this->last_upd = -2;
			$this->row++;
			$this->updateStat( true );
			$this->_db->setQuery( "SELECT product_id, sku FROM #__excel2js_related_products" );
			$data = $this->_db->loadObjectList();
			if( count( $data ) )
			{
				foreach( $data as $r )
				{
					$this->_db->setQuery( "SELECT product_id
                                FROM #__jshopping_products
                                WHERE product_ean=" . $this->_db->Quote( $r->sku ) );
					$related_id = $this->_db->loadResult();
					if( $related_id )
					{
						$this->_db->setQuery( "INSERT INTO #__jshopping_products_relations SET product_id = '{$r->product_id}', product_related_id = '$related_id'" );
						$this->_db->execute();
					} else
					{
						$this->error_log( JText:: _( 'SKU_NOT_FOUND' ) . ":$r->sku" );
					}
				}
				$this->_db->setQuery( "TRUNCATE TABLE #__excel2js_related_products" );
				$this->_db->execute();
			}
			file_put_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . "xls-dump.txt" , "" );
			@ unlink( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'uploaded_files.txt' );
			@ unlink( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'images_collection.txt' );


//        echo'<pre>';print_r( $this->file_memory_cron );echo'</pre>'.__FILE__.' '.__LINE__.'  ((  ::'.__FUNCTION__.' - $file_memory_cron ))<br>';
//        echo'<pre>';print_r( $this->files_arr );echo'</pre>'.__FILE__.' '.__LINE__.'  ((  ::'.__FUNCTION__.' - $this->files_arr ))<br>';
//        die('<b>DIE : '.__FILE__.' '.__LINE__.'  => '.__CLASS__.'::'.__FUNCTION__.'</b>' );


			if( !$this->cron )
			{
				$this->response();
				exit ();
			}
		}

		protected function saveTempFileImportToLine( $arrData )
		{

		}


		/**
		 * Cookie Set - Если показовать результать
		 * @throws Exception
		 * @since 3.9
		 */
		function setCookies()
		{
			$inputCookie = JFactory::getApplication()->input->cookie;
			$inputCookie->set( 'showResults' , $this->show_results , time() + ( 365 * 24 * 3600 ) );
		}

		/**
		 * Получить параемтр memory_limit
		 * @return false|float
		 * @since 3.9
		 * @deprecated use \GComHelpers\Helper::get_mem_total() ;
		 */
		function get_mem_total()
		{
			 return \GComHelpers\Helper::get_mem_total();
		}

		/**
		 * Обновить статистику импорта
		 * @param bool $not_interrupt
		 * @param bool $force_timeout
		 * @since 3.9
		 */
		function updateStat( $not_interrupt = false , $force_timeout = false )
		{
			if( time() - @ $this->last_upd > 1 )
			{
				$this->last_upd = time();
				$data = new stdClass();
				$data->cur_row = @ $this->row - 1;
				$data->num_row = @ $this->numRow;
				$data->pn = (int)@ $this->stat[ 'pn' ];
				$data->pu = (int)@ $this->stat[ 'pu' ];
				$data->cn = (int)@ $this->stat[ 'cn' ];
				$data->cu = (int)@ $this->stat[ 'cu' ];
				$data->time = time() - $this->start_time;
				$data->cur_time = time();
				$data->cur_cat = @ $this->current[ 'category' ];
				$data->cur_prod = @ $this->current[ 'product' ];
				$data->mem = $this->get_mem();
				$data->mem_total = $this->mem_total;
				$data->mem_peak = $this->get_mem_peak();
				$data->filename = @ $this->filename;
				$data->file_index = @ $this->file_index == @ $this->total_files ? @ $this->total_files - 1 : $this->file_index;
				$data->total_files = @ $this->total_files;
				$data->status = @ $this->status;
				if( $this->check_abort < time() and !$not_interrupt )
				{
					if( file_get_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'abort.txt' ) )
					{
						$this->response();
					}
					$this->check_abort = time() + 10;
				}
				if( ( time() >= $this->timeout and !$not_interrupt ) or $force_timeout )
				{
					$data->timeout = 1;
					$data->cur_row++;
					if( $this->cron )
					{
						file_put_contents( dirname( __FILE__ ) . "/cron_import_start.txt" , $data->cur_row );
						$this->cron_log( "Импорт завершен по таймауту. Строка - $data->cur_row" );
					} else
					{
						echo "timeout";
					}

				}
				file_put_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'log.txt' , json_encode( $data ) );
				if( ( time() >= $this->timeout and !$not_interrupt ) or $force_timeout )
				{
					$dump = new stdClass();
					$dump->category_levels = $this->category_levels;
					$dump->level = @$this->level;
					$dump->last_child = @$this->last_child;
					$dump->last_parent = @$this->last_parent;
					$dump->last_path = @$this->last_path;
					$dump->last_parrent_array = @$this->last_parrent_array;
					$dump->tree = @$this->tree;

					file_put_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . "xls-dump.txt" , serialize( $dump ) );

					exit();
				}
			}
		}

		function get_mem()
		{
			if( function_exists( "memory_get_usage" ) )
			{
				$mem_usage = memory_get_usage( true );

				return round( $mem_usage / 1048576 , 2 );
			} else
				return false;
		}

		function get_mem_peak()
		{
			if( function_exists( "memory_get_peak_usage" ) )
			{
				$mem_usage = memory_get_peak_usage( true );

				return round( $mem_usage / 1048576 , 2 );
			} else
				return false;
		}

		function response()
		{
			if( !$this->show_results )
			{
				echo "<h2 style='color:green'>Импорт завершен</h2>";
				exit ();
			}

			$errors = file_get_contents( JPATH_ROOT . DS . 'components' . DS . 'com_excel2js' . DS . "error_log.txt" );
			if( $errors )
			{
				$errors = array_diff( explode( "\n" , $errors ) , [ '' ] );
				echo "<h3 class='spoiler'>Список ошибок:</h3><br />";
				echo "<span id='spoiler_span' style='display:none'><ol style='text-align:left;display: inline-block;'><li>" . ( implode( "</li><li>" , $errors ) ) . "</li></ol></span><br />";

			}

			$this->_db->setQuery( "SELECT COUNT(log_id) AS num,type FROM #__excel2js_log GROUP BY type" );
			$stat = $this->_db->loadObjectList( 'type' );
			$response = "<table cellspacing='0' cellpadding='0' border='1' style='margin:5px auto;text-align: left;'><tr>";
			if( isset ( $stat[ 'cn' ]->num ) )
				$response .= "<th style='padding:10px; font-size:18px'>" . JText:: _( 'NEW_CATEGORIES' ) . "</th>";
			if( isset ( $stat[ 'cu' ]->num ) )
				$response .= "<th style='padding:10px; font-size:18px'>" . JText:: _( 'UPDATED_CATEGORIES' ) . "</th>";
			if( isset ( $stat[ 'pn' ]->num ) )
				$response .= "<th style='padding:10px; font-size:18px'>" . JText:: _( 'NEW_PRODUCTS' ) . "</th>";
			if( isset ( $stat[ 'pu' ]->num ) )
				$response .= "<th style='padding:10px; font-size:18px'>" . JText:: _( 'UPDATED_PRODUCTS' ) . "</th>";
			$response .= "</tr><tr>";
			if( isset ( $stat[ 'cn' ]->num ) )
			{
				$this->_db->setQuery( "SELECT js_id,title FROM #__excel2js_log WHERE type='cn' ORDER BY log_id" );
				$data = $this->_db->loadObjectList( 'js_id' );
				$response .= "<td style='padding:10px;' valign='top'>";
				foreach( $data as $key => $item )
					$response .= "<a target='_blank' href='index.php?option=com_jshopping&controller=categories&task=edit&category_id=$key'>$item->title</a><br />";
				$response .= "</td>";
			}
			if( isset ( $stat[ 'cu' ]->num ) )
			{
				$this->_db->setQuery( "SELECT js_id,title FROM #__excel2js_log WHERE type='cu' ORDER BY log_id" );
				$data = $this->_db->loadObjectList( 'js_id' );
				$response .= "<td style='padding:10px;' valign='top'>";
				foreach( $data as $key => $item )
					$response .= "<a target='_blank' href='index.php?option=com_jshopping&controller=categories&task=edit&category_id=$key'>$item->title</a><br />";
				$response .= "</td>";
			}
			if( isset ( $stat[ 'pn' ]->num ) )
			{
				$this->_db->setQuery( "SELECT js_id,title FROM #__excel2js_log WHERE type='pn' ORDER BY log_id" );
				$data = $this->_db->loadObjectList( 'js_id' );
				$response .= "<td style='padding:10px;' valign='top'>";
				foreach( $data as $key => $item )
					$response .= "<a target='_blank' href='index.php?option=com_jshopping&controller=products&task=edit&product_id=$key'>$key.$item->title</a><br />";
				$response .= "</td>";
			}
			if( isset ( $stat[ 'pu' ]->num ) )
			{
				$this->_db->setQuery( "SELECT js_id,title FROM #__excel2js_log WHERE type='pu' ORDER BY log_id" );
				$data = $this->_db->loadObjectList( 'js_id' );
				$response .= "<td style='padding:10px;' valign='top'>";
				foreach( $data as $key => $item )
					$response .= "<a target='_blank' href='index.php?option=com_jshopping&controller=products&task=edit&product_id=$key'>$key.$item->title</a><br />";
				$response .= "</td>";
			}
			$response .= "</tr></table>";
			echo "$response";
			exit ();
		}

		function backup2( $tables )
		{
			$tables = (array)$tables;
			foreach( $tables as $key => $t )
			{
				$tables[ $key ] = str_replace( "#__" , $this->_db->getPrefix() , $t );
			}
			$backup_filename = "js_backup_" . date( "d.m.Y_H_i_s" ) . ".gz";
			$mainframe = JFactory:: getApplication();
			$command = "mysqldump -h" . $mainframe->get( 'host' ) . " -u" . $mainframe->get( 'user' ) . " -p" . $mainframe->get( 'password' ) . " " . $mainframe->get( 'db' ) . " " . implode( " " , $tables ) . " | gzip -9> " . JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $backup_filename;
			system( $command , $output );
			if( $output === 0 )
			{
				$size = filesize( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $backup_filename );
				$this->_db->setQuery( "INSERT INTO #__excel2js_backups SET file_name = '$backup_filename',size='$size'" );
				$this->_db->execute();
			}
		}

		function backup( $tables )
		{
			$tables = (array)$tables;
			$backup_filename = "js_backup_" . date( "d.m.Y_H_i_s" ) . ".sql";
			$fp = fopen( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $backup_filename , "a" );
			foreach( $tables as $table )
			{
				$table = str_replace( '#__' , $this->_db->getPrefix() , $table );
				$fields_list_array = $this->_db->getTableColumns( $table );
				$fields_list = [];
				foreach( $fields_list_array as $key => $field )
				{
					$fields_list[] = $key;
				}
				$this->_db->setQuery( "SELECT COUNT(*) FROM `{$table}`" );
				$total = $this->_db->loadResult();
				fwrite( $fp , "TRUNCATE TABLE `{$table}`;\n" );
				$i = 0;
				for( ; ; )
				{
					if( $i >= $total )
						break;
					$this->_db->setQuery( "SELECT * FROM `{$table}`" , $i , 200 );
					$data = $this->_db->loadAssocList();
					$i += 200;
					if( !$data )
						break;
					if( count( $fields_list ) )
					{
						fwrite( $fp , "INSERT INTO `{$table}` (`" . implode( "`,`" , $fields_list ) . "`) VALUES\n" );
					} else
					{
						fwrite( $fp , "INSERT INTO `{$table}` VALUES\n" );
					}
					$rows = [];
					foreach( $data as $key => $row )
					{
						$fields = [];
						foreach( $row as $field )
						{
							$field = str_replace( ";\n" , ";" , $field );
							$fields[] = $this->_db->Quote( $field );
						}
						$rows[] = "(" . implode( "," , $fields ) . ")";
					}
					if( count( $rows ) )
					{
						fwrite( $fp , implode( ",\n" , $rows ) . ";\n\n" );
					} else
						fwrite( $fp , ";\n\n" );
				}
			}
			fclose( $fp );
			$size = filesize( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'backup' . DS . $backup_filename );
			if( $size )
			{
				$this->_db->setQuery( "INSERT INTO #__excel2js_backups SET file_name = '$backup_filename',size='$size'" );
				$this->_db->execute();

				return true;
			} else
				return false;
		}

		/**
		 * Настройка класса для JS импорта аналог конструктора
		 * @since 3.9
		 */
		function setJSsettings()
		{
			require_once( JPATH_ROOT . DS . "components" . DS . "com_jshopping" . DS . "lib" . DS . "factory.php" );

			$this->JSconfig = JSFactory::getConfig();
			if( $this->params->get( 'images_path' , 0 ) )
			{
				$this->image_product_path = JPATH_ROOT . DS . 'components' . DS . 'com_jshopping' . DS . 'files' . DS . 'img_products' . DS;
				$this->image_category_path = JPATH_ROOT . DS . 'components' . DS . 'com_jshopping' . DS . 'files' . DS . 'img_categories' . DS;
			} else
			{
				$this->image_product_path = $this->JSconfig->image_product_path;
				$this->image_category_path = $this->JSconfig->image_category_path;
			}

			if( substr( $this->image_product_path , -1 ) != DS )
			{
				$this->image_product_path = $this->image_product_path . DS;
			}

			if( substr( $this->image_category_path , -1 ) != DS )
			{
				$this->image_category_path = $this->image_category_path . DS;
			}
			$this->m_date = date( "Y-m-d H:i:s" );
			$this->immport();

			$this->js_product = new updateTable( "#__jshopping_products" , "product_id" );

			$this->js_category = new updateTable( "#__jshopping_categories" , "category_id" );
			$this->js_product_categories = new updateTable( "#__jshopping_products_to_categories" , "product_id" );
			$this->js_products_images = new updateTable( "#__jshopping_products_images" , "image_id" );
			$this->price_table = new updateTable( "#__jshopping_products_prices" , "price_id" );
			$this->extra_table_depend = new updateTable( "#__jshopping_products_attr" , "product_attr_id" );
			$this->extra_table_independ = new updateTable( "#__jshopping_products_attr2" , "id" );
			$this->log_table = new updateTable( "#__excel2js_log" , "log_id" );
			$this->relations = new updateTable( "#__jshopping_products_relations" , "id" );
			$this->_db->setQuery( "SELECT f.name,f.extra_id, `name_{$this->config->language}` as attr_name
                FROM #__excel2js_fields as f
                          LEFT JOIN #__jshopping_attr as a ON a.attr_id=f.extra_id
                WHERE f.id IN ($this->active_fields) AND f.type = 'independ' ORDER BY f.id" );
			$this->independ = $this->_db->loadObjectList( 'extra_id' );

			$this->_db->setQuery( "
				SELECT f.name,f.extra_id, `name_{$this->config->language}` as attr_name
                FROM #__excel2js_fields as f
                          LEFT JOIN #__jshopping_attr as a ON a.attr_id=f.extra_id
                WHERE f.id IN ($this->active_fields) AND f.type = 'depend' ORDER BY f.id" );
			$this->depend = $this->_db->loadObjectList( 'extra_id' );


			$this->_db->setQuery( "SELECT f.name,f.extra_id
                FROM #__excel2js_fields as f
                WHERE f.id IN ($this->active_fields) AND f.type = 'depend2' ORDER BY f.id" );
			$this->depend2 = $this->_db->loadObjectList();
			$this->_db->setQuery( "SELECT f.name,f.extra_id, `name_{$this->config->language}` as attr_name, a.type
                FROM #__excel2js_fields as f
                          LEFT JOIN #__jshopping_products_extra_fields as a ON a.id=f.extra_id
                WHERE f.id IN ($this->active_fields) AND f.type = 'extra' ORDER BY f.id" );
			$this->extra = $this->_db->loadObjectList( 'extra_id' );
			if( count( $this->independ ) )
				foreach( $this->independ as &$i )
				{
					$this->_db->setQuery( "SELECT value_id,`name_{$this->config->language}` as value
                                      FROM #__jshopping_attr_values
                                      WHERE attr_id = '$i->extra_id'" );
					$i->values = array_combine( $this->_db->loadColumn( 0 ) , $this->_db->loadColumn( 1 ) );
				}
			if( count( $this->depend ) )
				foreach( $this->depend as &$d )
				{
					$this->_db->setQuery( "SELECT value_id,`name_{$this->config->language}` as value
                                      FROM #__jshopping_attr_values
                                      WHERE attr_id = '$d->extra_id'" );
					$d->values = array_combine( $this->_db->loadColumn( 0 ) , $this->_db->loadColumn( 1 ) );
				}
			if( count( $this->extra ) )
				foreach( $this->extra as &$e )
				{
					if( $e->type )
						continue;
					$this->_db->setQuery( "SELECT id,`name_{$this->config->language}` as value
                                      FROM #__jshopping_products_extra_field_values
                                      WHERE field_id = '$e->extra_id'" );
					$e->values = array_combine( $this->_db->loadColumn( 0 ) , $this->_db->loadColumn( 1 ) );
				}
		}

		function immport()
		{
			return true;
		}

		function load_img()
		{
			$image_ext = [ 'gif' , 'jpg' , 'png' , 'bmp' , 'peg' ];
			if( isset ( $_FILES[ 'zip_file' ][ 'type' ] ) and substr( $_FILES[ 'zip_file' ][ 'name' ] , -3 ) != 'zip' )
			{
				echo "<br /><b><span style='color:#FF0000'>" . JText:: _( 'IMAGES_MUST_BE_PACKED_IN_ZIP_ARCHIVE' ) . "!</span></b><br />";

				return false;
			}
			$Archive_object = new Archive();
			$zip = $Archive_object->getAdapter( 'zip' );
			# Извлечь сжатый файл по указанному пути
			$zip->extract( $_FILES[ 'zip_file' ][ 'tmp_name' ] , JPATH_ROOT . DS . 'temp' . DS . 'images' );
			$files = JFolder:: files( JPATH_ROOT . DS . 'temp' . DS . 'images' );
			$folders = JFolder:: folders( JPATH_ROOT . DS . 'temp' . DS . 'images' );
			if( count( $folders ) )
			{
				foreach( $folders as $folder )
					JFolder:: delete( JPATH_ROOT . DS . 'temp' . DS . 'images' . DS . $folder );
			}
			$success = 0;
			if( count( $files ) > 0 )
			{
				foreach( $files as $f )
				{
					$ext = strtolower( substr( $f , -3 ) );
					if( in_array( $ext , $image_ext ) )
					{
						if( rename( JPATH_ROOT . DS . 'temp' . DS . 'images' . DS . $f , $this->image_product_path . 'full_' . $f ) )
						{
							$success++;
						} else
						{
							$error = error_get_last();
							$this->error_log( "Ошибка при перемещении изображения $f. $error[message]" );
							@ unlink( JPATH_ROOT . DS . 'temp' . DS . 'images' . DS . $f );
						}
					} else
					{
						$this->error_log( "Файл $f имеет недопустимое расширение" );
						@ unlink( JPATH_ROOT . DS . 'temp' . DS . 'images' . DS . $f );
					}
				}
				echo "<h3>Загружено изображений: $success</h3><br />";
			} else
				echo "<br /><b><span style='color:#FF0000'>" . JText:: _( 'ARCHIVE_IS_EMPTY' ) . "</span></b><br />";
		}

		function error_log( $msg )
		{
			$fp = fopen( JPATH_ROOT . DS . 'components' . DS . 'com_excel2js' . DS . "error_log.txt" , "a" );
			fwrite( $fp , $msg . "\n" );
			fclose( $fp );
		}

		function type( $cells , $csv = false )
		{
			$i = $csv ? 0 : 1;
			foreach( $this->active as $a )
			{
				if( in_array( $a->name , [ 'image_name' , 'short_description' , 'alias' , 'description' , 'product_publish' , 'meta_keyword' , 'meta_title' , 'meta_description' , 'template' , 'product_template' , 'category_template' ] ) )
				{
					if( $a->ordering - $i == $this->config->cat_col )
					{
						continue;
					}
					unset ( $cells[ $a->ordering - $i ] );
				}
				if( $a->type == 'empty' and $a->ordering - $i != $this->config->cat_col )
				{
					unset ( $cells[ $a->ordering - $i ] );
				}
			}
			switch( $this->config->price_template )
			{
				case 1 :
				case 2 :
				case 3 :
				case 8 :
					foreach( $cells as $key => $cell )
					{
						$cell = trim( $cell );
						if( empty ( $cell ) and $cell !== 0 )
						{
							if( !( strlen( $cell ) == 1 and is_string( $cell ) and $cell === "0" ) )
							{
								unset ( $cells[ $key ] );
							}
						}
					}
					if( count( $cells ) == 1 and @ $cells[ $this->config->cat_col ] )
					{
						return "category";
					} else if( count( $cells ) > 1 )
					{
						return "product";
					}
					break;
				case 4 :
					$col = $this->active[ 'path' ]->ordering;
					if( $col )
					{
						if( @ $cells[ $col - 1 ] )
							return "category";
						else
							return "product";
					} else
					{
						echo '<span style="color:#FF0000">' . JText:: _( 'YOU_DID_NOT_SPECIFY_THE_COLUMN_NUMBER_CATEGORY' ) . '!</span>';
						exit ();
					}
					break;
				case 5 :
				case 6 :
				case 7 :
					return "product";
					break;
			}
		}


		/**
		 * Хранение результата импорта строки
		 * @var object
		 * @since 3.9
		 */
		protected $ResultImportLine;

		/**
		 * Импорт товаров по строкам Точка входа Ajax
		 * @throws Exception
		 * @since 3.9
		 */
		function importLines()
		{
			$app = JFactory::getApplication();
			$row = $app->input->get( 'line' , [] , 'ARRAY' );


			try
			{
				// Code that may throw an Exception or Error.
				$this->setJSsettings();
				$res = $this->insertProduct( $row );
				// throw new Exception('Code Exception '.__FILE__.':'.__LINE__) ;
			}
			catch( Exception $e )
			{
				// Executed only in PHP 5, will not be reached in PHP 7
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo '<pre>'; print_r( $e ); echo '</pre>' . __FILE__ . ' ' . __LINE__;
				die( __FILE__ . ' ' . __LINE__ );
			}
			echo new JResponseJson( $this->ResultImportLine , 'importLines' );
			$app->close();

		}


		/**
		 * Обработка Добавление полей товара
		 * @param $row array массив параметров товара
		 * @return bool|void
		 * @since 3.9
		 */
		function insertProduct( $row )
		{
			/*echo "<hr><hr><hr>";
            echo $this->profiler->mark( $this->row );*/

			if( empty ( $row ) )
				return;

			if( $this->config->price_template == 6 and !isset ( $this->active[ 'path' ] ) )
			{
				echo '<span style="color:#FF0000">Не указана колонка для ID категорий! ("Номер категории")</span>';
				exit ();
			}


			if( @ $row[ 'date_modify' ] )
			{
				$row[ 'date_modify' ] = is_numeric( $row[ 'date_modify' ] )
					? gmdate( "Y-m-d H:i:s" , PHPExcel_Shared_Date:: ExcelToPHP( $row[ 'date_modify' ] ) )
					: date( "Y-m-d H:i:s" , strtotime( $row[ 'date_modify' ] ) );
			}
			else
			{
				$row[ 'date_modify' ] = $this->m_date;
			}
			if( @ $row[ 'product_date_added' ] )
			{
				$row[ 'product_date_added' ] = is_numeric( $row[ 'product_date_added' ] ) ? gmdate( "Y-m-d H:i:s" , PHPExcel_Shared_Date:: ExcelToPHP( $row[ 'product_date_added' ] ) ) : date( "Y-m-d H:i:s" , strtotime( $row[ 'product_date_added' ] ) );
			}

			/*if(isset($row['product_desc']))
$row['product_desc']=str_replace("'",'"',stripslashes(str_replace("\n","<br />",$row['product_desc'])));
if(isset($row['product_s_desc']))
$row['product_s_desc']=str_replace("'",'"',stripslashes(str_replace("\n","<br />",$row['product_s_desc'])));*/


			$new = 0;


			# Если нет product_id но есть Код товара завистмое поле "Код товар"
			if( !@ $row[ 'product_id' ] and @ $row[ 'product_ean' ] )
			{
				if( $this->sku_cache )
				{
					$row[ 'product_id' ] = $this->get_productId_by_sku( $row[ 'product_ean' ] );
				}
				else
				{
					$this->_db->setQuery( "SELECT p.product_id
                                FROM #__jshopping_products AS p
                                WHERE product_ean='" . $this->escape( $row[ 'product_ean' ] ) . "'" );
					$row[ 'product_id' ] = $this->_db->loadResult();
				}
			}
			else if( !@ $row[ 'product_id' ] and @ $row[ 'name' ] )
			{
				$this->_db->setQuery( "SELECT product_id
                                FROM #__jshopping_products
                                WHERE `name_{$this->config->language}`='" . $this->escape( $row[ 'name' ] ) . "'                                  ORDER BY product_id" , 0 , 1 );
				$row[ 'product_id' ] = $this->_db->loadResult();
				if( $row[ 'product_id' ] and !$this->config->update_without_sku )
				{
					return false;
				}
				if( !$row[ 'product_id' ] and !$this->config->create_without_sku )
				{
					return false;
				}
			}
			else if( @ $row[ 'product_id' ] )
			{
				if( $this->productid_cache )
				{
					$new = $this->is_productId_new( $row[ 'product_id' ] );
				}
				else
				{
					$this->_db->setQuery( "SELECT product_id FROM #__jshopping_products WHERE product_id = '$row[product_id]'" );
					$new = ( $this->_db->loadResult() ) ? 0 : 1;
				}
			}
			else
			{
				$new = 1;
			}

			list( $category_id , $category_ids ) = $this->getCategoryID_and_IDs( $row );


			if( !$category_id and !@ $this->config->create_without_category and !@ $row[ 'product_id' ] )
			{
				return false;
			}
			if( !$this->config->create and !@ $row[ 'product_id' ] )
				return false;


			$this->js_product->reset( 1 );
			$this->js_product->bind( $row );

			$this->ResultImportLine[ 'product_id' ] = $this->js_product->product_id;
			$this->ResultImportLine[ 'name' ] = $this->js_product->{'name_ru-RU'};
			$this->ResultImportLine[ 'date_modify' ] = $this->js_product->date_modify;

			/*echo'<pre>';print_r( $this->ResultImportLine );echo'</pre>'.__FILE__.' '.__LINE__;
        echo'<pre>';print_r( $this->js_product );echo'</pre>'.__FILE__.' '.__LINE__;
        die(__FILE__ .' '. __LINE__ );*/


			if( !empty( $this->js_product->product_quantity ) )
			{
				if( $this->js_product->product_quantity == -1 )
				{
					$this->js_product->unlimited = 1;
					$this->js_product->product_quantity = 1;
				}
				else
				{
					$this->js_product->unlimited = 0;
				}
			}

			$this->js_product->product_manufacturer_id = $this->getManufacturer( $row );

			$this->js_product->label_id = $this->getLabel( @ $row[ 'label_id' ] );

			$this->js_product->delivery_times_id = $this->getDelivery( @ $row[ 'delivery_times' ] );
			$this->js_product->product_tax_id = $this->getTax( @ $row[ 'product_tax_id' ] , $new );


			if( isset ( $row[ 'product_price' ] ) and @ $row[ 'product_price' ] != '' )
			{
				$this->js_product->product_price = $this->str2float( $row[ 'product_price' ] * $this->config->currency_rate );

			}
			else
			{
				$this->js_product->product_price = null;
			}

			# Единицы прайса
			if( isset ( $row[ 'units' ] ) and @ $row[ 'units' ] != '' )
			{
				$this->_db->setQuery( "SELECT id FROM #__jshopping_unit WHERE `name_{$this->config->language}` = " . $this->_db->Quote( $row[ 'units' ] ) );
				$unit_id = $this->_db->loadResult();
				if( !$unit_id )
				{
					$this->_db->setQuery( "INSERT INTO #__jshopping_unit SET `name_{$this->config->language}` = " . $this->_db->Quote( $row[ 'units' ] ) );
					$this->_db->execute();
					$unit_id = $this->_db->insertid();
				}
				$this->js_product->add_price_unit_id = $unit_id;
				$this->js_product->basic_price_unit_id = $unit_id;
			}

			# Валюта прайса
			if( isset ( $row[ 'currency' ] ) and @ $row[ 'currency' ] != '' )
			{
				$this->_db->setQuery( "SELECT currency_id FROM #__jshopping_currencies WHERE `currency_name` = " . $this->_db->Quote( $row[ 'currency' ] ) . " OR `currency_code` = " . $this->_db->Quote( $row[ 'currency' ] ) . " OR `currency_code_iso` = " . $this->_db->Quote( $row[ 'currency' ] ) );
				$currency_id = $this->_db->loadResult();

				if( $currency_id )
				{
					$this->js_product->currency_id = $currency_id;
				}
				else
				{
					$this->error_log( "Не найдена валюта - $row[currency]" );
				}
			}

			if( !$this->js_product->currency_id )
			{
				if( @$row[ 'depend_price' ] )
				{
					$this->js_product->currency_id = null;
				}
				else
				{
					$this->js_product->currency_id = $this->config->currency;
				}

			}



			# Если продукт есть в DB
			if( @ $row[ 'product_id' ] and !$new )
			{

				if( !isset ( $row[ 'product_publish' ] ) and $this->config->published_old > -1 )
				{
					$this->js_product->product_publish = $this->config->published_old;
				}
				# Обновлять SEO-параметры
				if( !$this->config->update_seo )
				{
					$this->js_product->{'description_' . $this->config->language} = null;
					$this->js_product->{'meta_title_' . $this->config->language} = null;
					$this->js_product->{'meta_description_' . $this->config->language} = null;
					$this->js_product->{'meta_keyword_' . $this->config->language} = null;
				}

				if( $this->config->prices_update and $this->js_product->product_price )
				{
					$this->_db->setQuery( "SELECT product_price FROM #__jshopping_products WHERE product_id = '{$row['product_id']}'" );
					$old_price = $this->_db->loadResult();
					if( $this->config->prices_update == 1 and $old_price >= $this->js_product->product_price )
					{
						$this->js_product->product_price = null;
					}
					else if( $this->config->prices_update == 2 and $old_price <= $this->js_product->product_price )
					{
						$this->js_product->product_price = null;
					}
				}

				# Обработка изображений
				$this->bind_image( $row , $this->js_product , true , $new );
				$this->js_product->update();

				$this->log( 'pu' , $row[ 'product_id' ] , @ $row[ 'name' ] ? $row[ 'name' ] : $this->js_product->product_ean );
				$this->current[ 'product' ] = @ $row[ 'name' ] ? $row[ 'name' ] : $this->js_product->product_ean;
			}
			else
			{
				$new = 1;
				$this->js_product->product_publish = !isset ( $row[ 'product_publish' ] ) ? $this->config->published : $row[ 'product_publish' ];
				$this->_db->setQuery( "SHOW TABLE STATUS LIKE '" . $this->_db->getPrefix() . "jshopping_products'" );
				$table = $this->_db->loadObject();
				if( !$row[ 'product_id' ] )
				{
					$row[ 'product_id' ] = $this->js_product->product_id = $table->Auto_increment;
				}
				else
				{
					$this->js_product->product_id = $row[ 'product_id' ];
				}

				$this->bind_image( $row , $this->js_product , true , $new );


				if( !@ $row[ 'product_ean' ] )
				{
					if( @ $row[ 'alias' ] )
						$this->js_product->product_ean = $row[ 'product_ean' ] = substr( $row[ 'alias' ] , 0 , 64 );
					else if( @ $row[ 'name' ] )
						$this->js_product->product_ean = $row[ 'product_ean' ] = substr( $this->translit( trim( $row[ 'name' ] ) ) , 0 , 64 );
					else if( @ $this->last_path )
						$this->js_product->product_ean = $row[ 'product_ean' ] = implode( '-' , $this->last_path ) . '-' . ( $this->product_order + 1 );
				}
				if( !$this->js_product->product_date_added )
				{
					$this->js_product->product_date_added = $this->m_date;
				}
				if( !$this->js_product->{'alias_' . $this->config->language} )
					$this->js_product->{'alias_' . $this->config->language} = $this->getAlias( stripslashes( @ $row[ 'name' ] ) , $row[ 'product_id' ] , stripslashes( @ $row[ 'product_ean' ] ) );
				if( !isset ( $row[ 'product_quantity' ] ) )
				{
					$this->js_product->product_quantity = (int)$this->config->quantity_default;
					if( $this->js_product->product_quantity == -1 )
					{
						$this->js_product->unlimited = 1;
						$this->js_product->product_quantity = 1;
					}
					else
					{
						$this->js_product->unlimited = 0;
					}
				}

				if( !$this->js_product->insert() )
				{
					echo "Ошибка при создании товара. Строка - $this->row.<br>";
					$this->error_log( "Ошибка при создании товара. Строка - $this->row." );

					return false;
				}
				$this->log( 'pn' , $row[ 'product_id' ] , @ $row[ 'name' ] ? $row[ 'name' ] : $this->js_product->product_ean );
				if( $this->sku_cache )
				{
					$this->temp_product_table[ $this->js_product->product_ean ] = $row[ 'product_id' ];
				}
				if( $this->productid_cache )
				{
					$this->temp_productID_table[] = $row[ 'product_id' ];
				}
				$this->current[ 'product' ] = @ $row[ 'name' ] ? $row[ 'name' ] : $this->js_product->product_ean;
			}


			@ $this->product_order++;

			if( $this->config->change_category or $new )
			{
				if( @ $category_id and count( $category_ids ) <= 1 )
				{
					if( !$this->config->multicategories )
					{
						$this->_db->setQuery( "DELETE FROM #__jshopping_products_to_categories WHERE product_id ='{$row['product_id']}'" );
						$this->_db->execute();
					}

					$this->_db->setQuery( "SELECT category_id FROM #__jshopping_products_to_categories WHERE product_id = '{$row['product_id']}' AND `category_id` = '$category_id'" );
					$product_category = $this->_db->loadResult();


					if( !$product_category )
					{
						$product_ordering = (int)@ $row[ 'product_ordering' ] ? $row[ 'product_ordering' ] : $this->product_order;
						$this->_db->setQuery( "REPLACE INTO `#__jshopping_products_to_categories` (`product_id`, `category_id`,`product_ordering`) VALUES ('{$row['product_id']}', '$category_id', '$product_ordering')" );
						$this->_db->execute();
					}
					else if( @ $row[ 'product_ordering' ] )
					{
						$product_ordering = (int)@ $row[ 'product_ordering' ];
						$this->_db->setQuery( "UPDATE `#__jshopping_products_to_categories` SET `product_ordering` = $product_ordering WHERE  product_id = '{$row['product_id']}' AND `category_id` = '$category_id'" );
						$this->_db->execute();
					}
				}
				else if( count( $category_ids ) > 1 )
				{
					foreach( $category_ids as $category_id )
					{
						$category_id = (int)trim( $category_id );
						if( $category_id )
						{
							$this->_db->setQuery( "SELECT category_id FROM #__jshopping_products_to_categories WHERE product_id = '{$row['product_id']}' AND `category_id` = '$category_id'" );
							$product_category = $this->_db->loadResult();
							if( !$product_category )
							{
								$this->_db->setQuery( "REPLACE INTO `#__jshopping_products_to_categories` (`product_id`, `category_id`,`product_ordering`) VALUES ('{$row['product_id']}', '$category_id', '$this->product_order')" );
								$this->_db->execute();
							}
						}
					}
				}
			}

			$this->_db->setQuery( "
				SELECT name,extra_id 
				FROM #__excel2js_fields 
				WHERE id IN ($this->active_fields) AND type = 'price'" );

			$prices = $this->_db->loadObjectList();
			if( $prices )
			{
				if( $this->config->spec_price_clear )
				{
					$this->_db->setQuery( "DELETE FROM #__jshopping_products_prices WHERE product_id = '{$row['product_id']}'" );
					$this->_db->execute();
				}
				$this->_db->setQuery( "SELECT add_price_unit_id FROM #__jshopping_products WHERE product_id = '{$row['product_id']}'" );
				if( !$this->_db->loadResult() )
				{
					$this->_db->setQuery( "UPDATE #__jshopping_products SET add_price_unit_id = '{$this->config->units}',basic_price_unit_id = '{$this->config->units}' WHERE product_id = '{$row['product_id']}'" );
					$this->_db->execute();
				}
				foreach( $prices as $p )
				{
					$this->price_table->reset( 1 );
					$this->price_table->product_id = $row[ 'product_id' ];
					if( @ $row[ $p->name ] )
					{
						if( $row[ $p->name ] < 1 )
						{
							$this->price_table->discount = abs( $this->str2float( $row[ $p->name ] ) ) * 100;
						}
						else
						{
							$discounted_price = abs( str_replace( "," , "." , floatval( $row[ $p->name ] ) ) * $this->config->currency_rate );
							$this->_db->setQuery( "SELECT product_price FROM #__jshopping_products WHERE product_id = '{$row['product_id']}'" );
							$original_price = $this->_db->loadResult();
							if( $original_price == $discounted_price or ( $original_price == 0 or $discounted_price == 0 ) )
							{
								continue;
							}
							$this->price_table->discount = ( $original_price - $discounted_price ) / $original_price * 100;
						}
						$this->price_table->bind( json_decode( $p->extra_id ) );
						if( $this->config->spec_price_clear )
						{
							$this->price_table->insert();
						}
						else
						{
							$this->_db->setQuery( "SELECT price_id FROM #__jshopping_products_prices WHERE product_id = '{$row['product_id']}' AND product_quantity_start = '{$this->price_table->product_quantity_start}' AND product_quantity_finish = '{$this->price_table->product_quantity_finish}'" );
							$this->price_table->price_id = $this->_db->loadResult();
							if( $this->price_table->price_id )
								$this->price_table->update();
							else
								$this->price_table->insert();
						}
					}
				}
			}


			$this->_db->setQuery( "SELECT COUNT(price_id) FROM #__jshopping_products_prices WHERE product_id = '{$row['product_id']}'" );
			if( $this->_db->loadResult() )
			{
				$this->_db->setQuery( "UPDATE #__jshopping_products SET different_prices = 1, product_is_add_price = 1 WHERE product_id = '{$row['product_id']}'" );
				$this->_db->execute();
				/*$this->_db->setQuery("SELECT product_price FROM #__jshopping_products WHERE product_id = '{$row['product_id']}'");
			$original_price = $this->_db->loadResult();*/
			}
			else
			{
				$this->_db->setQuery( "UPDATE #__jshopping_products SET different_prices = 0, product_is_add_price = 0 WHERE product_id = '{$row['product_id']}'" );
				$this->_db->execute();
			}




			if( $this->independ )
			{
				foreach( $this->independ as &$e )
				{
					$this->extra_table_independ->reset( 1 );
					$this->extra_table_independ->product_id = $row[ 'product_id' ];
					$this->extra_table_independ->attr_id = $e->extra_id;
					if( @ $row[ $e->name ] == $this->custom_clear )
					{
						$this->_db->setQuery( "DELETE FROM #__jshopping_products_attr2 WHERE  attr_id = $e->extra_id AND product_id = {$row['product_id']}" );
						$this->_db->execute();
						continue;
					}
					if( empty ( $row[ $e->name ] ) )
						continue;
					if( $this->config->extra_fields_clear )
					{
						if( !$this->check_product_attribute( $row[ 'product_id' ] , $e->extra_id ) )
						{
							$this->_db->setQuery( "DELETE FROM #__jshopping_products_attr2 WHERE  attr_id = $e->extra_id AND product_id = '{$row['product_id']}'" );
							$this->_db->execute();
						}
					}
					$attr_data = explode( "|" , $row[ $e->name ] );
					foreach( $attr_data as $data )
					{
						$data = explode( ";" , $data );
						if( count( $data ) == 1 )
						{
							$this->extra_table_independ->price_mod = '+';
							$this->extra_table_independ->addprice = 0;
						}
						else if( count( $data ) == 2 )
						{
							$attr_price = trim( $data[ 1 ] );
							$mod = substr( $attr_price , 0 , 1 );
							if( in_array( $mod , [ '+' , '-' , '*' , '/' , '%' ] ) )
							{
								$this->extra_table_independ->price_mod = $mod;
								$this->extra_table_independ->addprice = $this->str2float( substr( $attr_price , 1 ) );
							}
							else
							{
								$this->extra_table_independ->price_mod = '=';
								$this->extra_table_independ->addprice = $this->str2float( $attr_price );
							}
						}
						if( !$attr_value_id = array_search( $data[ 0 ] , $e->values ) )
						{
							$this->_db->setQuery( "SELECT MAX(value_ordering) FROM #__jshopping_attr_values WHERE  attr_id =  $e->extra_id" );
							$ordering = $this->_db->loadResult() + 1;
							$this->_db->setQuery( "INSERT INTO #__jshopping_attr_values SET  attr_id =  $e->extra_id, value_ordering = '$ordering', `name_{$this->config->language}` = " . $this->_db->Quote( $data[ 0 ] ) );
							$this->_db->execute();
							$attr_value_id = $this->_db->insertid();
							$e->values[ $attr_value_id ] = $data[ 0 ];
						}
						$this->extra_table_independ->attr_value_id = $attr_value_id;
						$this->_db->setQuery( "SELECT id FROM #__jshopping_products_attr2 WHERE product_id = '{$row['product_id']}' AND attr_id = $e->extra_id AND attr_value_id = $attr_value_id" );
						$attr_product_value_id = $this->_db->loadResult();
						if( $attr_product_value_id )
						{
							$this->extra_table_independ->id = $attr_product_value_id;
							$this->extra_table_independ->update();
						}
						else
						{
							$this->extra_table_independ->id = null;


							$this->extra_table_independ->insert();


						}
						if( $this->config->extra_fields_clear )
						{
							$this->mark_product_attribute( $row[ 'product_id' ] , $e->extra_id );
						}
					}
				}
			}


			# Зависимые поля
			if( $this->depend )
			{
				$total_array = [];
				$num_values = 0;


				foreach( $this->depend as &$e )
				{
					if( empty ( $row[ $e->name ] ) )
						continue;
					$attr_values = explode( "|" , $row[ $e->name ] );
					$this->attr_ids[] = $e->extra_id;
					foreach( $attr_values as $attr_value )
					{
						if( $attr_value == $this->custom_clear )
						{
							$this->delete_depended_images( $row[ 'product_id' ] );
							$this->_db->setQuery( "DELETE FROM #__jshopping_products_attr WHERE product_id = '{$row['product_id']}'" );
							$this->_db->execute();
							continue;
						}
						if( !$attr_value_id = array_search( $attr_value , $e->values ) )
						{
							$this->_db->setQuery( "SELECT MAX(value_ordering) FROM #__jshopping_attr_values WHERE  attr_id =  $e->extra_id" );
							$ordering = $this->_db->loadResult() + 1;
							$this->_db->setQuery( "INSERT INTO #__jshopping_attr_values SET  attr_id =  $e->extra_id, value_ordering = '$ordering', `name_{$this->config->language}` = " . $this->_db->Quote( $attr_value ) );
							$this->_db->execute();
							$attr_value_id = $this->_db->insertid();
							$e->values[ $attr_value_id ] = $attr_value;
						}
						$total_array[ $e->extra_id ][] = $attr_value_id;
						$num_values++;
					}
				}


				# Удаление значений атрибутов [ Настройки импорта/экспорта => Очистка доп. полей ]
				if( $this->config->extra_fields_clear and $num_values )
				{
					if( !$this->check_product_attribute( $row[ 'product_id' ] , 'depend' ) )
					{

						$this->delete_depended_images( $row[ 'product_id' ] );
						$this->_db->setQuery( "DELETE FROM #__jshopping_products_attr WHERE product_id = '{$row['product_id']}'" );
						$this->_db->execute();
					}
				}
				if( $num_values )
				{
					$this->extra_table_depend->reset( 1 );
					$this->extra_table_depend->product_id = $row[ 'product_id' ];
					$this->depend_attr_bind( $row );
					$this->attr_recursion( $total_array );
					$this->_db->setQuery( "SELECT SUM(count) FROM #__jshopping_products_attr WHERE product_id = " . $this->_db->Quote( $row[ 'product_id' ] ) );
					$new_count = $this->_db->loadResult();
					$this->_db->setQuery( "UPDATE #__jshopping_products SET product_quantity = '$new_count' WHERE product_id = " . $this->_db->Quote( $row[ 'product_id' ] ) );
					$this->_db->execute();
					if( $this->config->extra_fields_clear )
					{
						$this->mark_product_attribute( $row[ 'product_id' ] , 'depend' );
					}
				}
			}
			if( $this->extra )
			{
				foreach( $this->extra as &$e )
				{
					if( @ $row[ $e->name ] == $this->custom_clear )
					{
						$this->_db->setQuery( "UPDATE #__jshopping_products SET `extra_field_{$e->extra_id}`='' WHERE product_id = {$row['product_id']}" );
						$this->_db->execute();
						continue;
					}
					if( empty ( $row[ $e->name ] ) )
						continue;
					if( !$e->type )
					{
						$extra_data = explode( "|" , $row[ $e->name ] );
						$extra_value_ids = [];
						foreach( $extra_data as $data )
						{
							if( !$extra_value_id = array_search( $data , $e->values ) )
							{
								$this->_db->setQuery( "SELECT MAX(ordering)
                                                 FROM #__jshopping_products_extra_field_values
                                                 WHERE   field_id =  $e->extra_id" );
								$ordering = $this->_db->loadResult() + 1;
								$this->_db->setQuery( "INSERT INTO #__jshopping_products_extra_field_values SET  field_id =  '$e->extra_id', ordering = '$ordering', `name_{$this->config->language}` = " . $this->_db->Quote( $data ) );
								$this->_db->execute();
								$extra_value_id = $this->_db->insertid();
								$e->values[ $extra_value_id ] = $data;
							}
							$extra_value_ids[] = $extra_value_id;
						}
						$extra_data = implode( "," , $extra_value_ids );
					}
					else
					{
						$extra_data = $row[ $e->name ];
					}

					$this->_db->setQuery( "UPDATE #__jshopping_products SET `extra_field_{$e->extra_id}`= " . $this->_db->Quote( $extra_data ) . " WHERE product_id = '{$row['product_id']}'" );
					$this->_db->execute();
				}
			}
			if( @ $row[ 'extra_list' ] )
			{
				$extra_list_array = explode( "|" , $row[ 'extra_list' ] );
				foreach( $extra_list_array as $extra_field )
				{
					$extra_field = explode( ":" , $extra_field );
					$extra_field_name = $extra_field[ 0 ];
					$extra_field_values = $extra_field[ 1 ];
					if( strstr( $extra_field_values , '"' ) )
					{
						$extra_field_values = str_replace( '"' , '' , $extra_field_values );
						$type = 1;
						$multilist = 0;
					}
					else
					{
						$extra_field_values = explode( "," , $extra_field_values );
						$type = 0;
						$multilist = 1;
					}

					$this->_db->setQuery( "
                 SELECT id,type,multilist
                 FROM #__jshopping_products_extra_fields
                 WHERE `name_{$this->config->language}` = " . $this->_db->Quote( $extra_field_name ) );
					$extra_field_data = $this->_db->loadObject();

					if( !@$extra_field_data->id )
					{
						$this->_db->setQuery( "SELECT MAX(ordering) FROM #__jshopping_products_extra_fields" );
						$ordering = (int)$this->_db->loadResult() + 1;
						$this->_db->setQuery( "
                      INSERT INTO #__jshopping_products_extra_fields
                      SET
                      `name_{$this->config->language}` = " . $this->_db->Quote( $extra_field_name ) . ",
                      type='$type',
                      multilist='$multilist',
                      allcats=1,
                      cats='a:0:{}',
                      ordering='$ordering'
                      " );
						$this->_db->execute();
						$extra_field_id = $this->_db->insertid();

						$this->_db->setQuery( "SELECT COLUMN_NAME FROM information_schema.columns WHERE COLUMN_NAME = 'extra_field_{$extra_field_id}' AND TABLE_NAME = " . $this->_db->Quote( $this->_db->getPrefix() . "jshopping_products" ) );
						if( !$this->_db->loadResult() )
						{
							$this->_db->setQuery( "ALTER TABLE #__jshopping_products ADD `extra_field_{$extra_field_id}` VARCHAR(100) " );
							$this->_db->execute();

						}

						@$extra_field_data->id = $extra_field_id;
						$extra_field_data->type = $type;
						$extra_field_data->multilist = $multilist;
					}


					if( $extra_field_data->type )
					{
						if( is_array( $extra_field_values ) )
						{
							$extra_field_values = implode( "," , $extra_field_values );
						}
						$this->_db->setQuery( "UPDATE #__jshopping_products SET `extra_field_{$extra_field_data->id}`= " . $this->_db->Quote( $extra_field_values ) . " WHERE product_id = '{$row['product_id']}'" );
						$this->_db->execute();
					}
					else
					{
						$extra_value_ids = [];

						if( !isset( $this->extrafields_values[ $extra_field_data->id ] ) )
						{
							$this->_db->setQuery( "SELECT id,`name_{$this->config->language}` FROM #__jshopping_products_extra_field_values WHERE field_id = $extra_field_data->id" );
							$this->extrafields_values[ $extra_field_data->id ] = array_combine( $this->_db->loadColumn( 0 ) , $this->_db->loadColumn( 1 ) );
						}


						foreach( $extra_field_values as $data )
						{
							if( !$extra_value_id = array_search( $data , $this->extrafields_values[ $extra_field_data->id ] ) )
							{
								$this->_db->setQuery( "SELECT MAX(ordering)
                                                     FROM #__jshopping_products_extra_field_values
                                                     WHERE   field_id =  $extra_field_data->id" );
								$ordering = $this->_db->loadResult() + 1;
								$this->_db->setQuery( "INSERT INTO #__jshopping_products_extra_field_values SET  field_id =  '$extra_field_data->id', ordering = '$ordering', `name_{$this->config->language}` = " . $this->_db->Quote( $data ) );
								$this->_db->execute();
								$extra_value_id = $this->_db->insertid();
								$this->extrafields_values[ $extra_field_data->id ][ $extra_value_id ] = $data;
							}
							$extra_value_ids[] = $extra_value_id;
						}
						$extra_data = implode( "," , $extra_value_ids );
						$this->_db->setQuery( "UPDATE #__jshopping_products SET `extra_field_{$extra_field_data->id}`= " . $this->_db->Quote( $extra_data ) . " WHERE product_id = '{$row['product_id']}'" );
						$this->_db->execute();
					}
				}
			}

			if( @ $row[ 'free_attr' ] )
			{
				if( @ $row[ 'free_attr' ] == $this->custom_clear )
				{
					$this->_db->setQuery( "DELETE FROM #__jshopping_products_free_attr  WHERE product_id = '{$row['product_id']}'" );
					$this->_db->execute();

				}
				else
				{
					$free_attrs = explode( "|" , $row[ 'free_attr' ] );
					foreach( $free_attrs as $free_attr )
					{
						$this->_db->setQuery( "SELECT id  FROM #__jshopping_free_attr WHERE `name_{$this->config->language}` = " . $this->_db->Quote( $free_attr ) );
						$id = $this->_db->loadResult();
						if( !$id )
						{
							$this->_db->setQuery( "INSERT INTO #__jshopping_free_attr SET `name_{$this->config->language}` = " . $this->_db->Quote( $free_attr ) );
							$this->_db->execute();
							$id = $this->_db->insertid();
						}
						$this->_db->setQuery( "INSERT INTO #__jshopping_products_free_attr SET  product_id = '{$row['product_id']}', attr_id = $id" );
						$this->_db->execute();
					}
				}
			}
			if( @ $row[ 'related_products' ] )
			{
				$this->relations->reset( 1 );
				$this->relations->product_id = $row[ 'product_id' ];
				$ids = explode( "|" , $row[ 'related_products' ] );
				$this->_db->setQuery( "DELETE FROM #__jshopping_products_relations WHERE product_id = '{$row['product_id']}'" );
				$this->_db->execute();
				foreach( $ids as $id )
				{
					$this->relations->product_related_id = $id;
					$this->relations->insert();
				}
			}
			if( @ $row[ 'related_products_sku' ] )
			{
				$this->_db->setQuery( "DELETE FROM #__jshopping_products_relations WHERE product_id = '{$row['product_id']}'" );
				$this->_db->execute();
				$this->relations->reset( 1 );
				$this->relations->product_id = $row[ 'product_id' ];
				$sku_array = explode( "|" , $row[ 'related_products_sku' ] );
				foreach( $sku_array as $sku )
				{
					$sku = $this->escape( $sku );
					$this->_db->setQuery( "SELECT product_id
                                FROM #__jshopping_products
                                WHERE product_ean='$sku'" );
					$related_id = $this->_db->loadResult();
					if( $related_id )
					{
						$this->relations->product_related_id = $related_id;
						$this->relations->insert();
					}
					else
					{
						$this->_db->setQuery( "INSERT INTO #__excel2js_related_products SET product_id = '{$row['product_id']}', sku = '$sku'" );
						$this->_db->execute();
					}
				}
				$this->relations->reset( 1 );
			}
			if( @ $row[ 'digital' ] )
			{
				if( $row[ 'digital' ] == $this->custom_clear )
				{
					$this->_db->setQuery( "DELETE FROM #__jshopping_products_files WHERE product_id= '{$row['product_id']}' " );
					$this->_db->execute();
				}
				else
				{
					$files = explode( "|" , $row[ 'digital' ] );
					foreach( $files as $file )
					{
						$this->_db->setQuery( "SELECT id FROM #__jshopping_products_files WHERE file = " . $this->_db->Quote( $file ) . " AND  product_id= '{$row['product_id']}'" );
						if( !$this->_db->loadResult() )
						{
							$this->_db->setQuery( "INSERT INTO #__jshopping_products_files SET file = " . $this->_db->Quote( $file ) . ",  product_id='{$row['product_id']}'" );
							$this->_db->execute();
						}
					}
				}
			}

//        echo'<pre>';print_r( $row );echo'</pre>'.__FILE__.' '.__LINE__;
//        die(__FILE__ .' '. __LINE__ );
			$this->setMinPrice( $row[ 'product_id' ] );
		}

		function get_productId_by_sku( $sku )
		{
			if( !@ $this->temp_product_table )
			{
				$this->_db->setQuery( "SELECT product_id,product_ean
                                FROM #__jshopping_products" );
				$this->temp_product_table = array_combine( $this->_db->loadColumn( 1 ) , $this->_db->loadColumn( 0 ) );
			}
			if( isset ( $this->temp_product_table[ $sku ] ) )
			{
				return $this->temp_product_table[ $sku ];
			} else
			{
				return false;
			}
		}

		function escape( $string )
		{
			if( method_exists( $this->_db , "escape" ) )
				return $this->_db->escape( $string );
			else if( method_exists( $this->_db , "getEscaped" ) )
				return $this->_db->getEscaped( $string );
			else
			{

				return mysql_escape_string( $string );
			}

		}

		function is_productId_new( $product_id )
		{
			if( !@ $this->temp_productID_table )
			{
				$this->_db->setQuery( "SELECT product_id
                                FROM #__jshopping_products" );
				$this->temp_productID_table = $this->_db->loadColumn();
			}

			return in_array( $product_id , $this->temp_productID_table );
		}

		function getCategoryID_and_IDs( $row )
		{
			$category_ids = [];
			$category_id = 0;
			if( !@ $this->category_id and $this->config->price_template == 5 )
			{
				$category_id = $this->searchCategory( $row[ 'name' ] );
			} else if( $this->config->price_template == 6 )
			{
				$category_id = (int)@ $row[ 'path' ];
				$category_ids = explode( "," , str_replace( "." , "," , $row[ 'path' ] ) );
			} else if( $this->config->price_template == 7 )
			{
				$category_ids = [];
				if( @ $row[ 'path' ] )
				{
					$category_paths = explode( $this->config->category_delimiter , $row[ 'path' ] );
					foreach( $category_paths as $category_path )
					{
						$this->current[ 'category' ] = $category_path;
						$path = explode( $this->config->level_delimiter , $category_path );
						$parrent_id = 0;
						foreach( $path as $category_name )
						{
							$category_id = $this->getCategoryID( $category_name , $parrent_id , count( $path ) > 1 );
							if( !$category_id )
							{
								$category_id = $this->createCategory( $category_name , $parrent_id );
							}
							$parrent_id = $category_id;
						}
						$category_ids[] = $category_id;
					}
				}
			} else if( $this->config->price_template == 8 )
			{
				$category_id = @ $this->last_parrent_array[ $row[ 'level' ] - 1 ];
			} else if( @ $this->category_id )
			{
				$category_id = $this->category_id;
			}

			return [ $category_id , $category_ids ];
		}

		function searchCategory( $name )
		{
			$name_words = explode( " " , $name );
			foreach( $name_words as $key => $name_word )
			{
				$name_words[ $key ] = $this->_strtolower( strtr( $name_word , [ '"' => '' , "'" => "" , '`' => '' ] ) );
				if( strlen( $name_word ) < 4 )
					unset ( $name_words[ $key ] );
			}
			foreach( $this->category_list as $cat )
			{
				$cat_words = explode( " " , $cat->category_name );
				foreach( $cat_words as $cat_word )
				{
					if( strlen( $cat_word ) < 4 )
						continue;
					foreach( $name_words as $name_word )
					{
						if( preg_match( "#^" . mb_substr( $this->_strtolower( $cat_word ) , 0 , 4 ) . "#" , $name_word ) )
						{
							return $cat->category_id;
						}
					}
				}
			}
			$this->_db->setQuery( "SELECT category_id FROM #__jshopping_categories WHERE `name_{$this->config->language}` =" . $this->_db->Quote( $this->config->extra_category ) );
			$extra_category_id = $this->_db->loadResult();
			if( $extra_category_id )
				return $extra_category_id;
			else
			{
				return $this->createCategory( $this->config->extra_category );
			}
		}

		function _strtolower( $string )
		{
			$small = [ 'а' , 'б' , 'в' , 'г' , 'д' , 'е' , 'ё' , 'ж' , 'з' , 'и' , 'й' , 'к' , 'л' , 'м' , 'н' , 'о' , 'п' , 'р' , 'с' , 'т' , 'у' , 'ф' , 'х' , 'ч' , 'ц' , 'ш' , 'щ' , 'э' , 'ю' , 'я' , 'ы' , 'ъ' , 'ь' , 'э' , 'ю' , 'я' ];
			$large = [ 'А' , 'Б' , 'В' , 'Г' , 'Д' , 'Е' , 'Ё' , 'Ж' , 'З' , 'И' , 'Й' , 'К' , 'Л' , 'М' , 'Н' , 'О' , 'П' , 'Р' , 'С' , 'Т' , 'У' , 'Ф' , 'Х' , 'Ч' , 'Ц' , 'Ш' , 'Щ' , 'Э' , 'Ю' , 'Я' , 'Ы' , 'Ъ' , 'Ь' , 'Э' , 'Ю' , 'Я' ];

			return str_replace( $large , $small , $string );
		}

		function createCategory( $category_name , $parent_id = 0 )
		{
			$this->_db->setQuery( "SELECT MAX(category_id) FROM #__jshopping_categories" );
			$new_catid = $this->_db->loadResult() + 1;
			$this->_db->setQuery( "INSERT INTO #__jshopping_categories
                              SET
                              category_parent_id=$parent_id,
                              category_publish=1,

                              ordering=1,
                              category_add_date ='$this->m_date',
                              products_page='{$this->JSconfig->count_products_to_page}',
                              products_row='{$this->JSconfig->count_products_to_row}',
                              access='1',
                              `name_{$this->config->language}` ='{$category_name}',
                              `alias_{$this->config->language}` =" . $this->_db->Quote( $new_catid . "-" . $this->translit( $category_name ) ) . "
                              " );
			$this->_db->execute();
			$category_id = $this->_db->insertid();
			$this->log( 'cn' , $category_id , $category_name );

			return $category_id;
		}

		function translit( $text )
		{
			$trans = strtolower( strtr( $text , $this->trans ) );
			$trans = str_replace( chr( 160 ) , "-" , $trans );
			$trans = str_replace( chr( 194 ) , "-" , $trans );
			$trans = str_replace( " " , "-" , $trans );
			$trans = preg_replace( "/[^0-9a-z\-]/" , "" , $trans );
			while( strstr( $trans , "--" ) ) $trans = str_replace( "--" , "-" , $trans );

			return $trans;
		}

		function log( $type , $js_id , $title )
		{
			@ $this->stat[ $type ]++;
			$this->log_table->type = $type;
			$this->log_table->js_id = $js_id;
			$this->log_table->title = $title;
			$this->log_table->row = $this->row;
			$this->log_table->insert();
		}

		function getCategoryID( $category_name , $parrent = 0 , $check_parrent = false )
		{
			$where = $check_parrent ? " AND category_parent_id = $parrent" : "";
			$this->_db->setQuery( "SELECT category_id
              FROM #__jshopping_categories
              WHERE `name_{$this->config->language}`='$category_name'
              $where
              ORDER BY category_parent_id ASC
              LIMIT 0,1" );

			return $this->_db->loadResult();
		}

		function getManufacturer( $row )
		{
			if( @ $row[ 'product_manufacturer_id' ] == $this->custom_clear or @ $row[ 'mf_name' ] == $this->custom_clear )
			{
				return 0;
			}
			if( !@ $row[ 'product_manufacturer_id' ] and !@ $row[ 'mf_name' ] )
			{
				return null;
			}
			if( !@ $row[ 'product_manufacturer_id' ] )
			{
				$this->_db->setQuery( "SELECT manufacturer_id FROM #__jshopping_manufacturers WHERE `name_{$this->config->language}`=" . $this->_db->Quote( $row[ 'mf_name' ] ) );
				$row[ 'product_manufacturer_id' ] = $this->_db->loadResult();
			}
			if( !$row[ 'product_manufacturer_id' ] )
			{
				$this->_db->setQuery( "INSERT INTO #__jshopping_manufacturers SET `name_{$this->config->language}`=" . $this->_db->Quote( $row[ 'mf_name' ] ) . ", manufacturer_publish = 1, products_page = '{$this->JSconfig->count_products_to_page}',  products_row ='{$this->JSconfig->count_products_to_row}'" );
				$this->_db->execute();
				$row[ 'product_manufacturer_id' ] = $this->_db->insertid();
			}

			return (int)$row[ 'product_manufacturer_id' ];
		}

		function getLabel( $label )
		{
			if( empty ( $label ) )
			{
				return null;
			}
			if( $label == $this->custom_clear )
			{
				return 0;
			}
			$this->_db->setQuery( "SELECT id FROM #__jshopping_product_labels WHERE `name_{$this->config->language}`=" . $this->_db->Quote( $label ) );
			$label_id = $this->_db->loadResult();
			if( !$label_id )
			{
				$this->_db->setQuery( "INSERT INTO #__jshopping_product_labels SET `name_{$this->config->language}`=" . $this->_db->Quote( $label ) );
				$this->_db->execute();
				$label_id = $this->_db->insertid();
			}

			return (int)$label_id;
		}

		function getDelivery( $delivery )
		{
			if( empty ( $delivery ) )
			{
				return null;
			}
			if( $delivery == $this->custom_clear )
			{
				return 0;
			}
			$this->_db->setQuery( "SELECT id FROM #__jshopping_delivery_times WHERE `name_{$this->config->language}`=" . $this->_db->Quote( $delivery ) );
			$delivery_id = $this->_db->loadResult();
			if( !$delivery_id )
			{
				preg_match( "#\d+#" , $delivery , $matches );
				$days = (int)@ $matches[ 0 ] ? $matches[ 0 ] : 1;
				$this->_db->setQuery( "INSERT INTO #__jshopping_delivery_times SET `name_{$this->config->language}`=" . $this->_db->Quote( $delivery ) . ", days = '$days'" );
				$this->_db->execute();
				$delivery_id = $this->_db->insertid();
			}

			return (int)$delivery_id;
		}

		function getTax( $tax , $new )
		{
			if( empty ( $tax ) )
			{
				if( $new )
				{
					$this->_db->setQuery( "SELECT MIN(tax_id) FROM #__jshopping_taxes" );

					return (int)$this->_db->loadResult();
				} else
				{
					return null;
				}
			}
			if( $tax == $this->custom_clear )
			{
				return 0;
			}
			$tax = (float)$tax;
			$this->_db->setQuery( "SELECT tax_id FROM #__jshopping_taxes WHERE 	tax_value =" . $this->_db->Quote( $tax ) );
			$tax_id = $this->_db->loadResult();
			if( !$tax_id )
			{
				$this->_db->setQuery( "INSERT INTO #__jshopping_taxes SET tax_value=" . $this->_db->Quote( $tax ) . ", tax_name = " . $this->_db->Quote( $tax . "%" ) );
				$this->_db->execute();
				$tax_id = $this->_db->insertid();
			}

			return (int)$tax_id;
		}

		function str2float( $string )
		{
			$string = trim( $string );
			$string = str_replace( ',' , '.' , $string );
			$float = '';
			for( $i = 0; $i < strlen( $string ); $i++ )
			{
				if( ord( $string[ $i ] ) == 44 or ord( $string[ $i ] ) == 46 )
					$float .= ".";
				if( ( ord( $string[ $i ] ) >= 48 and ord( $string[ $i ] ) <= 57 ) or ord( $string[ $i ] ) == 45 )
					$float .= $string[ $i ];
			}

			return (float)$float;
		}

		/**
		 * Обработка изображений
		 * @param $row
		 * @param $obj
		 * @param bool $is_product
		 * @param bool $new
		 * @return bool|string
		 * @since 3.9
		 */
		function bind_image( $row , &$obj , $is_product = false , $new = false )
		{




			if( $is_product )
			{

				/** @var string [Активные столбцы => Изображение ] */
				$image = @ $row[ 'image_name' ];

				if( ( strstr( $image , 'http://' ) or strstr( $image , 'https://' ) )
					and !@$this->config->images_load )
				{
					if( !$new )
					{
						$this->_db->setQuery( "SELECT COUNT(image_id) FROM #__jshopping_products_images WHERE product_id ={$row['product_id']}" );
						if( $this->_db->loadResult() )
						{
							return false;
						}
					}
				}
			}
			else
			{
				$image = @ $row[ 'category_image' ];
			}


			$image_title = @ $row[ 'image_title' ];
			$product_id = @ $row[ 'product_id' ];

			if( $this->config->images_import_method and isset ( $this->active[ 'image_name' ] ) )
			{
				$coordinates = $this->letters[ $this->active[ 'image_name' ]->ordering - 1 ] . ( $this->row );
				if( isset ( $this->images_collection[ $coordinates ] ) )
				{
					$image = $this->images_collection[ $coordinates ]->name;
				}
			}

			if( $is_product )
			{
				if( !$image and @ $this->config->unpublish_image )
				{
					$this->_db->setQuery( "UPDATE #__jshopping_products SET product_publish = 0 WHERE product_id = '$product_id'" );
					$this->_db->execute();
					$obj->product_publish = 0;

					return '';
				}
				else if( @ $image == $this->custom_clear )
				{
					$this->_db->setQuery( "DELETE FROM #__jshopping_products_images WHERE product_id = '$product_id'" );
					$this->_db->execute();

					return '';
				}
			}
			$image_exists = 0;
			if( $image )
			{
				$image = str_replace( "," , "|" , $image );

				/** @var array Массив изображений */
				$image_name_array = explode( '|' , $image );

				$file_meta_array = $image_title ? explode( '|' , $image_title ) : [];
				if( $is_product )
				{
					$this->_db->setQuery( "SELECT image_name FROM #__jshopping_products_images WHERE product_id = '$product_id'" );
					$old_images = $this->_db->loadColumn();
					if( count( $old_images ) and $this->config->old_images_delete )
					{
						foreach( $old_images as $old_image )
						{
							if( !in_array( $old_image , $image_name_array ) )
							{
								@ unlink( $this->image_product_path . $old_image );
								@ unlink( $this->image_product_path . 'full_' . $old_image );
								@ unlink( $this->image_product_path . 'thumb_' . $old_image );
							}
						}
					}
					$this->_db->setQuery( "DELETE FROM #__jshopping_products_images WHERE product_id = '$product_id'" );
					$this->_db->execute();
				}

				$image_name_array2 = [];



				foreach( $image_name_array as $key => $image_name )
				{
					# Если Прямая ссылка на изображение
					if( strstr( $image_name , 'http://' ) or strstr( $image_name , 'https://' ) )
					{
						$rename_params = [];
						if( $this->images_rename )
						{
							$rename_params[ 'index' ] = $key;
							if( $is_product )
							{
								if( $this->js_product->product_ean )
								{
									$rename_params[ 'ean' ] = $this->js_product->product_ean;
								} else if( $row[ 'name' ] )
								{
									$rename_params[ 'ean' ] = $row[ 'name' ];
								} else
								{
									$this->_db->setQuery( "SELECT product_ean, `name_{$this->config->language}` as name FROM #__jshopping_products WHERE product_id = '$product_id' " );
									$product_data = $this->_db->loadObject();
									if( $product_data->product_ean )
									{
										$rename_params[ 'ean' ] = $product_data->product_ean;
									} else if( $product_data->name )
									{
										$rename_params[ 'ean' ] = $product_data->name;
									}
								}
							} else
							{
								if( $obj->{'name_' . $this->config->language} )
								{
									$rename_params[ 'ean' ] = $obj->{'name_' . $this->config->language};
								} else if( $this->category_id )
								{
									$this->_db->setQuery( "SELECT `name_{$this->config->language}` as name FROM #__jshopping_categories WHERE category_id = '$this->category_id' " );
									$rename_params[ 'ean' ] = $this->_db->loadResult();
								}

							}
						}
						$url = $image_name;
						$image_name = $this->remote_images( $image_name , $rename_params );
						if( !$image_name )
						{
							$this->error_log( "Отсутствует удаленное изображение товара. Строка - $this->row. {$url}" );
							continue;
						}
					}

					if( $is_product )
					{
						if( !file_exists( $this->image_product_path . $image_name ) and !file_exists( $this->image_product_path . 'full_' . $image_name ) )
						{
							$this->error_log( "Отсутствует изображение товара. Строка - $this->row. {$this->image_product_path}{$image_name}" );
							continue;
						}
					}


					$image_name_array2[] = $image_name;

				}

				if( $is_product )
				{
					foreach( $image_name_array2 as $key => $image_name )
					{
						if( !$this->make_thumb( $image_name ) )
						{
							unset( $image_name_array2[ $key ] );
							continue;
						}
						if( file_exists( $this->image_product_path . 'full_' . $image_name ) )
						{
							$image_exists++;
						} else
						{

							unset( $image_name_array2[ $key ] );
							continue;
						}

						$img_table = new stdClass();
						$img_table->product_id = $product_id;
						$img_table->image_name = $image_name;
						$img_table->name = @$file_meta_array[ $key ];
						$img_table->ordering = $key + 1;
						if( $this->old_version )
						{
							$img_table->image_full = 'full_' . $image_name;
							$img_table->image_thumb = 'thumb_' . $image_name;
						}
						$this->_db->insertObject( '#__jshopping_products_images' , $img_table );
					}

					$main_image = array_shift( $image_name_array2 );
					if( !$main_image )
					{
						$main_image = '';
					}
					if( $this->old_version )
					{
						$obj->product_name_image = $main_image;
						$obj->product_full_image = 'full_' . $main_image;
						$obj->product_thumb_image = 'thumb_' . $main_image;
					} else
					{
						$obj->image = $main_image;
					}


					if( $image_exists == 0 and @ $this->config->unpublish_image )
					{
						$this->_db->setQuery( "UPDATE #__jshopping_products SET product_publish = 0 WHERE product_id = '$product_id'" );
						$this->_db->execute();
					}
				} else
				{
					$obj->category_image = array_shift( $image_name_array2 );
					if( file_exists( $this->image_product_path . 'full_' . $obj->category_image ) )
					{

						if( $this->ResizeImageMagic( $this->image_product_path . 'full_' . $obj->category_image , $this->JSconfig->image_product_original_width , $this->JSconfig->image_product_original_height , $this->JSconfig->image_cut , $this->JSconfig->image_fill , $this->image_category_path . $obj->category_image , $this->JSconfig->image_quality , $this->JSconfig->image_fill_color , $this->JSconfig->image_interlace ) )
						{
							$this->error_log( "Создана миниатюра изображения категории - $obj->category_image" );
							@ unlink( $this->image_product_path . 'full_' . $obj->category_image );
						} else
						{
							$this->error_log( "Ошибка при создании миниатюры изображения категории - $obj->category_image" );
						}
					}
				}
			}
		}


		protected $file_memory_cron = JPATH_COMPONENT_ADMINISTRATOR . '/xls/file_memory_cron.json';
		protected $files_arr = [];

		/**
		 * Загрузка и ковертирование фотографий
		 * @param $image_name
		 * @param array $rename_params
		 * @return bool
		 * @since 3.9
		 */
		function remote_images( $image_name , $rename_params = [] )
		{

			$image_name = trim( $image_name );

			# если загружать в прямом потоке
			if( !$this->config->load_img_cron )
			{
				$ch = curl_init();
				curl_setopt( $ch , CURLOPT_URL , $image_name );
				curl_setopt( $ch , CURLOPT_RETURNTRANSFER , 1 );
				curl_setopt( $ch , CURLOPT_FOLLOWLOCATION , 1 );
				curl_setopt( $ch , CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13' );
				$file = curl_exec( $ch );
				$response_code = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
				$error = curl_error( $ch );
				curl_close( $ch );
				if( $response_code != 200 or !$file )
				{
					$this->error_log( "Не удалось получить изображение. Строка - $this->row. Код ответа - $response_code. Ошибка - $error" );
					return false;
				}
				$this->ResultImportLine[ 'photo' ][ 'status' ] = 'loaded';
			} else
			{
				$saveUrlImg = $image_name;
			}#END IF


			$temp_path = explode( "/" , $image_name );
			$image_name = end( $temp_path );
			$image_name = strtolower( $image_name );
			$ext = pathinfo( $image_name , PATHINFO_EXTENSION );
			if( substr( $ext , 0 , 3 ) == 'php' )
			{
				if( strlen( $ext ) > 3 )
				{
					$query = substr( $ext , 3 );
					$query = str_replace( [ "?" , "&" , "=" , " " ] , "_" , $query );
					$image_name = $image_name = str_replace( ".$ext" , '' , $image_name );
					$image_name .= $query . ".jpg";
				} else
				{
					$image_name = str_replace( ".$ext" , '.jpg' , $image_name );
				}
			} else if( !in_array( $ext , [ 'jpg' , 'gif' , 'bmp' , 'png' ] ) )
			{
				$image_name = str_replace( ".$ext" , '.jpg' , $image_name );
				$ext = 'jpg';
			}


			if( $this->images_rename and $rename_params[ 'ean' ] )
			{
				if( !in_array( $ext , [ 'jpg' , 'gif' , 'bmp' , 'png' ] ) )
				{
					$ext = 'jpg';
				}
				$image_name = $this->translit( $rename_params[ 'ean' ] ) . "_" . ( $rename_params[ 'index' ] + 1 ) . "." . $ext;
			}


			$image_path = $this->image_product_path . 'full_' . $image_name;

			$this->files_arr[] = [ 'url' => $saveUrlImg , 'save_path' => $image_path ];
			/* echo'<pre>';print_r( $this->config->load_img_cron );echo'</pre>'.__FILE__.' '.__LINE__.'  ((  ::'.__FUNCTION__.' - $load_img_cron ))<br>';
	    echo'<pre>';print_r( $image_path );echo'</pre>'.__FILE__.' '.__LINE__.'  ((  ::'.__FUNCTION__.' - $image_name ))<br>';
	    echo'<pre>';print_r( $saveUrlImg );echo'</pre>'.__FILE__.' '.__LINE__.'  ((  ::'.__FUNCTION__.' - $image_name ))<br>';
	    echo'<pre>';print_r( $this->files_arr );echo'</pre>'.__FILE__.' '.__LINE__.'  ((  ::'.__FUNCTION__.' - $image_name ))<br>';
	    die('<b>DIE : '.__FILE__.' '.__LINE__.'  => '.__CLASS__.'::'.__FUNCTION__.'</b>' );*/

			$is_file = false;
			# если файл получен - сохраняем !
			if( $file )
			{

				$is_file = true;
				if( !file_put_contents( $image_path , $file ) )
				{
					$error = print_r( error_get_last() , true );
					$this->error_log( "Не удалось сохранить изображение. Строка - $this->row.  Ошибка - $error" );
					return false;
				}
				unset ( $file );
				$this->ResultImportLine[ 'photo' ][ 'status' ] = 'saved';
				$this->ResultImportLine[ 'photo' ][ 'path' ] = $image_path;
			}
			if( $is_file )
			{

				# Конверт PNG => JPG
				if( $ext == 'png' && $this->config->png_to_jpg_convert )
				{
					$Quality = isset( $this->config->png_to_jpg_convert_quality ) ? $this->config->png_to_jpg_convert_quality : 50;
					$image = imagecreatefrompng( $image_path );
					$bg = imagecreatetruecolor( imagesx( $image ) , imagesy( $image ) );
					imagefill( $bg , 0 , 0 , imagecolorallocate( $bg , 255 , 255 , 255 ) );
					imagealphablending( $bg , true );
					imagecopy( $bg , $image , 0 , 0 , 0 , 0 , imagesx( $image ) , imagesy( $image ) );
					imagedestroy( $image );

					// 0 = worst / smaller file, 100 = better / bigger file
					imagejpeg( $bg , $image_path . ".jpg" , $Quality );
					imagedestroy( $bg );
					$image_name = $image_name . ".jpg";
					$this->ResultImportLine[ 'photo' ][ 'path' ] = $image_path . ".jpg";
					$this->ResultImportLine[ 'photo' ][ 'convertToJpg' ] = 1;

				}#END IF
				# Конверт BMP => JPG
				if( $ext == 'bmp' )
				{
					list( $width , $height ) = getimagesize( $this->image_product_path . 'full_' . $image_name );
					$image = $this->imagecreatefrombmp( $this->image_product_path . 'full_' . $image_name );
					unlink( $this->image_product_path . 'full_' . $image_name );
					$image_name = str_replace( ".bmp" , ".jpg" , $image_name );
					$image_p = imagecreatetruecolor( $width , $height );
					imagecopyresampled( $image_p , $image , 0 , 0 , 0 , 0 , $width , $height , $width , $height );
					imagejpeg( $image_p , $this->image_product_path . 'full_' . $image_name , 100 );
					unset( $image );
					unset( $image_p );
				}
			}#END IF

//        $this->ResultImportLine['photo']['convertToJpg'] = 1 ;


			return $image_name;
		}

		/**
		 * # Author:     DHKold
		 * Date:     The 15th of June 2005
		 * Version:    2.0B
		 * Purpose:    To create an image from a BMP file.
		 * Param in:   BMP file to open.
		 * Param out:  Return a resource like the other ImageCreateFrom functions
		 * Reference:  http://us3.php.net/manual/en/function.imagecreate.php#53879
		 * Bug fix:    Author:   domelca at terra dot es
		 *             Date:   06 March 2008
		 *             Fix:    Correct 16bit BMP support
		 * Notes:
		 *
		 * @param $filename
		 * @return bool|false|resource
		 */
		function imagecreatefrombmp( $filename )

		{
			if( !$f1 = fopen( $filename , "rb" ) ) return false;
			$FILE = unpack( "vfile_type/Vfile_size/Vreserved/Vbitmap_offset" , fread( $f1 , 14 ) );
			if( $FILE[ 'file_type' ] != 19778 ) return false;
			$BMP = unpack( 'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
				'/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
				'/Vvert_resolution/Vcolors_used/Vcolors_important' , fread( $f1 , 40 ) );
			$BMP[ 'colors' ] = pow( 2 , $BMP[ 'bits_per_pixel' ] );
			if( $BMP[ 'size_bitmap' ] == 0 ) $BMP[ 'size_bitmap' ] = $FILE[ 'file_size' ] - $FILE[ 'bitmap_offset' ];
			$BMP[ 'bytes_per_pixel' ] = $BMP[ 'bits_per_pixel' ] / 8;
			$BMP[ 'bytes_per_pixel2' ] = ceil( $BMP[ 'bytes_per_pixel' ] );
			$BMP[ 'decal' ] = ( $BMP[ 'width' ] * $BMP[ 'bytes_per_pixel' ] / 4 );
			$BMP[ 'decal' ] -= floor( $BMP[ 'width' ] * $BMP[ 'bytes_per_pixel' ] / 4 );
			$BMP[ 'decal' ] = 4 - ( 4 * $BMP[ 'decal' ] );
			if( $BMP[ 'decal' ] == 4 ) $BMP[ 'decal' ] = 0;
			$PALETTE = [];
			if( $BMP[ 'colors' ] < 16777216 )
			{
				$PALETTE = unpack( 'V' . $BMP[ 'colors' ] , fread( $f1 , $BMP[ 'colors' ] * 4 ) );
			}
			$IMG = fread( $f1 , $BMP[ 'size_bitmap' ] );
			$VIDE = chr( 0 );
			$res = imagecreatetruecolor( $BMP[ 'width' ] , $BMP[ 'height' ] );
			$P = 0;
			$Y = $BMP[ 'height' ] - 1;
			while( $Y >= 0 )
			{
				$X = 0;
				while( $X < $BMP[ 'width' ] )
				{
					if( $BMP[ 'bits_per_pixel' ] == 24 )
						$COLOR = unpack( "V" , substr( $IMG , $P , 3 ) . $VIDE );
					else if( $BMP[ 'bits_per_pixel' ] == 16 )
					{
						/*
                     * BMP 16bit fix
                     * =================
                     *
                     * Ref: http://us3.php.net/manual/en/function.imagecreate.php#81604
                     *
                     * Notes:
                     * "don't work with bmp 16 bits_per_pixel. change pixel
                     * generator for this."
                     *
                     */
						$COLOR = unpack( "v" , substr( $IMG , $P , 2 ) );
						$blue = ( $COLOR[ 1 ] & 0x001f ) << 3;
						$green = ( $COLOR[ 1 ] & 0x07e0 ) >> 3;
						$red = ( $COLOR[ 1 ] & 0xf800 ) >> 8;
						$COLOR[ 1 ] = $red * 65536 + $green * 256 + $blue;
					} else if( $BMP[ 'bits_per_pixel' ] == 8 )
					{
						$COLOR = unpack( "n" , $VIDE . substr( $IMG , $P , 1 ) );
						$COLOR[ 1 ] = $PALETTE[ $COLOR[ 1 ] + 1 ];
					} else if( $BMP[ 'bits_per_pixel' ] == 4 )
					{
						$COLOR = unpack( "n" , $VIDE . substr( $IMG , floor( $P ) , 1 ) );
						if( ( $P * 2 ) % 2 == 0 ) $COLOR[ 1 ] = ( $COLOR[ 1 ] >> 4 );
						else $COLOR[ 1 ] = ( $COLOR[ 1 ] & 0x0F );
						$COLOR[ 1 ] = $PALETTE[ $COLOR[ 1 ] + 1 ];
					} else if( $BMP[ 'bits_per_pixel' ] == 1 )
					{
						$COLOR = unpack( "n" , $VIDE . substr( $IMG , floor( $P ) , 1 ) );
						if( ( $P * 8 ) % 8 == 0 ) $COLOR[ 1 ] = $COLOR[ 1 ] >> 7;
						else if( ( $P * 8 ) % 8 == 1 ) $COLOR[ 1 ] = ( $COLOR[ 1 ] & 0x40 ) >> 6;
						else if( ( $P * 8 ) % 8 == 2 ) $COLOR[ 1 ] = ( $COLOR[ 1 ] & 0x20 ) >> 5;
						else if( ( $P * 8 ) % 8 == 3 ) $COLOR[ 1 ] = ( $COLOR[ 1 ] & 0x10 ) >> 4;
						else if( ( $P * 8 ) % 8 == 4 ) $COLOR[ 1 ] = ( $COLOR[ 1 ] & 0x8 ) >> 3;
						else if( ( $P * 8 ) % 8 == 5 ) $COLOR[ 1 ] = ( $COLOR[ 1 ] & 0x4 ) >> 2;
						else if( ( $P * 8 ) % 8 == 6 ) $COLOR[ 1 ] = ( $COLOR[ 1 ] & 0x2 ) >> 1;
						else if( ( $P * 8 ) % 8 == 7 ) $COLOR[ 1 ] = ( $COLOR[ 1 ] & 0x1 );
						$COLOR[ 1 ] = $PALETTE[ $COLOR[ 1 ] + 1 ];
					} else
						return false;
					imagesetpixel( $res , $X , $Y , $COLOR[ 1 ] );
					$X++;
					$P += $BMP[ 'bytes_per_pixel' ];
				}
				$Y--;
				$P += $BMP[ 'decal' ];
			}
			fclose( $f1 );

			return $res;
		}


		private $images_resize ;
		private $thumb_replace ;
		private $JSconfig ;
		/**
		 * Создание миниатюры
		 * @param $name_image string имя fuul картинки
		 * @return bool
		 * @since 3.9
		 */
		function make_thumb( $name_image )
		{

			
			$name_thumb = 'thumb_' . $name_image;
			$name_full = 'full_' . $name_image;

			$path_image = $this->image_product_path . $name_image;
			$path_thumb = $this->image_product_path . $name_thumb;
			$path_full = $this->image_product_path . $name_full;

			\GComHelpers\Helper::$images_resize = $this->images_resize ;
			\GComHelpers\Helper::$thumb_replace = $this->thumb_replace ;





			if( !file_exists( $path_full ) )
			{
				@chmod( $path_image , 0777 );
				if( !file_exists( $path_image ) )
				{
					$this->error_log( "Миниатюра файла $name_image не создана, т.к. файл не существует" );

					return false;
				}
				if( !rename( $path_image , $path_full ) )
				{
					$error = error_get_last();
					$this->error_log( "Ошибка при перемещении изображения $name_image. $error[message]" );

					return false;
				}
			}



			# 800 x 800
			if( $this->JSconfig->image_product_original_width or $this->JSconfig->image_product_original_height )
			{
				if( !\GComHelpers\Helper::resizeImageMagic
					(
						$path_full ,
						$this->JSconfig->image_product_original_width ,
						$this->JSconfig->image_product_original_height ,
						$this->JSconfig->image_cut ,
						$this->JSconfig->image_fill ,
						$path_full ,
						$this->JSconfig->image_quality ,
						$this->JSconfig->image_fill_color ,
						$this->JSconfig->image_interlace
					)
				)
				{
					$this->error_log( "Ошибка при изменении размера полного изображения $name_image." );
					unlink( $path_full );
					return false;
				}
			}




			# 180 x 180 превью товара
			if( !\GComHelpers\Helper::resizeImageMagic( $path_full ,
				$this->JSconfig->image_product_width ,
				$this->JSconfig->image_product_height ,
				$this->JSconfig->image_cut ,
				$this->JSconfig->image_fill ,
				$path_thumb ,
				$this->JSconfig->image_quality ,
				$this->JSconfig->image_fill_color ,
				$this->JSconfig->image_interlace ) )
			{
				$this->error_log( "Ошибка при создании превью товара  $name_image." );
				unlink( $path_full );

				return false;
			}



			# 450 x 450 изображения товара
			if( !\GComHelpers\Helper::resizeImageMagic( $path_full , $this->JSconfig->image_product_full_width , $this->JSconfig->image_product_full_height , $this->JSconfig->image_cut , $this->JSconfig->image_fill , $path_image , $this->JSconfig->image_quality , $this->JSconfig->image_fill_color , $this->JSconfig->image_interlace ) )
			{
				$this->error_log( "Ошибка при создании изображения товара  $name_image." );
				unlink( $path_full );
				return false;
			}

			return true;
		}


		function getAlias( $name , $id , $sku , $product = true , $template = false , $sep = "-" )
		{
			$alias = $this->genAlias( $name , $id , $sku , $template , $sep );
			if( $product )
			{
				for( ; ; )
				{
					$this->_db->setQuery( "SELECT product_id FROM #__jshopping_products WHERE `alias_{$this->config->language}`='$alias'" );
					if( $this->_db->loadResult() and $this->_db->loadResult() != $id )
					{
						$alias = $alias . $sep . rand( 1111111111 , 9999999999 );
					} else
					{
						return $alias;
					}
				}
			} else
			{
				for( ; ; )
				{
					$this->_db->setQuery( "SELECT category_id FROM #__jshopping_categories WHERE `alias_{$this->config->language}`=" . $this->_db->Quote( $alias ) );
					if( $this->_db->loadResult() and $this->_db->loadResult() != $id )
						$alias = $alias . $sep . rand( 1111111111 , 9999999999 );
					else
						return $alias;
				}
			}
		}

		function genAlias( $name , $id , $sku , $template = false , $sep = "-" )
		{
			if( $name )
				$name = $this->translit( $name );
			if( $sku )
				$sku = $this->translit( $sku );
			if( !$template )
				$template = $this->config->alias_template;
			switch( $template )
			{
				case 1 :
					if( $name )
						$alias = $name;
					else if( $sku )
					{
						$alias = $this->genAlias( $name , $id , $sku , 10 , $sep );
					} else
					{
						/*echo '<span style="font-size: 14px;color:red">'.JText::_('ALIAS_COULD_NOT_BE_GENERATED').($this->row+1)." (".$name.$sep.$id.$sep.$sku.')</span>';

exit();*/
						$alias = $id . $sep . rand( 1111111111 , 9999999999 );
					}
					break;
				case 2 :
					$alias = $id . $sep . $this->genAlias( $name , $id , $sku , 1 , $sep );
					break;
				case 3 :
					$alias = $this->genAlias( $name , $id , $sku , 1 , $sep ) . $sep . $id;
					break;
				case 4 :
					if( $sku and $name )
					{
						$alias = $sku . $sep . $name;
					}
					break;
				case 5 :
					if( $sku and $name )
					{
						$alias = $name . $sep . $sku;
					}
					break;
				case 6 :
					if( $sku )
					{
						$alias = $sku . $sep . $this->genAlias( $name , $id , $sku , 2 , $sep );
					}
					break;
				case 7 :
					if( $sku and $name )
						$alias = $id . $sep . $sku . $sep . $name;
					break;
				case 8 :
					if( $sku and $name )
						$alias = $name . $sep . $sku . $sep . $id;
					break;
				case 9 :
					if( $sku and $name )
						$alias = $name . $sep . $id . $sep . $sku;
					break;
				case 10 :
					if( $sku )
						$alias = $sku;
					break;
				case 11 :
					if( $id )
						$alias = $id;
					break;

			}
			if( empty( $alias ) )
				$alias = $this->genAlias( $name , $id , $sku , 2 , $sep );
			while( substr( $alias , -1 ) == '-' ) $alias = substr( $alias , 0 , -1 );

			return $alias;
		}

		function check_product_attribute( $product_id , $attr_id )
		{
			$this->_db->setQuery( "SELECT extra FROM #__excel2js_log WHERE js_id='$product_id' ORDER BY log_id" , 0 , 1 );
			$extra = $this->_db->loadResult();
			if( !$extra )
			{
				return false;
			}
			$extra = @ unserialize( $extra );

			return isset ( $extra[ $attr_id ] );
		}

		function mark_product_attribute( $product_id , $attr_id )
		{
			$this->_db->setQuery( "SELECT log_id,extra FROM #__excel2js_log WHERE js_id='$product_id' ORDER BY log_id" , 0 , 1 );
			$data = $this->_db->loadObject();
			if( !$data )
			{
				return false;
			}
			$extra = $data->extra;
			if( !$extra )
			{
				$extra = [];
			} else
			{
				$extra = unserialize( $data->extra );
			}
			@ $extra[ $attr_id ] = 1;
			$data->extra = serialize( $extra );
			$this->_db->setQuery( "UPDATE #__excel2js_log SET extra='$data->extra' WHERE log_id = '$data->log_id'" );
			$this->_db->execute();
		}

		function delete_depended_images( $product_id )
		{
			$this->_db->setQuery( "
				SELECT ext_attribute_product_id  
				FROM #__jshopping_products_attr 
				WHERE product_id = '$product_id'"
			);
			$old_depended_images = $this->_db->loadColumn();



			if( !count( $old_depended_images ) )
			{
				return false;
			}
			$old_depended_images = implode( "," , $old_depended_images );
			
			$this->_db->setQuery( "
				DELETE FROM #__jshopping_products 
				WHERE product_id IN ($old_depended_images) 
					AND parent_id = $product_id"
			);
			$this->_db->execute();
			
			$this->_db->setQuery( "
				DELETE FROM #__jshopping_products_images 
				WHERE product_id IN ($old_depended_images)"
			);
			$this->_db->execute();
		}

		/**
		 * Обработка зависимых полей
		 * @param $row array - поля товара
		 * @since 3.9
		 */
		function depend_attr_bind( $row )
		{

			if( count( $this->depend2 ) )
			{
				foreach( $this->depend2 as $field )
				{
					$table_field_name = str_replace( "depend_" , "" , $field->name );
					$this->extra_table_depend->$table_field_name = @ $row[ $field->name ];

				}
			}

			if( @$this->extra_table_depend->price )
			{
				$this->extra_table_depend->price = str_replace( "," , "." , $this->extra_table_depend->price ) * $this->config->currency_rate;
			}





			if( @ $this->extra_table_depend->ext_attribute_product_id )
			{
				$this->extra_table_depend->ext_attribute_product_id = $this->depended_images( $this->extra_table_depend->ext_attribute_product_id , $row[ 'product_id' ] );
			}



			if( ( !$this->extra_table_depend->ean and !@ $row[ 'product_ean' ] ) or ( !@ $row[ 'product_price' ] and !$this->extra_table_depend->price ) )
			{
				$this->_db->setQuery( "SELECT product_ean,product_price,product_quantity
                                      FROM #__jshopping_products
                                      WHERE product_id = " . $this->_db->Quote( $row[ 'product_id' ] ) );
				$data = $this->_db->loadObject();
				if( !$this->extra_table_depend->ean )
				{
					$this->extra_table_depend->ean = $data->product_ean;
				}
				if( !$this->extra_table_depend->price )
				{
					$this->extra_table_depend->price = $data->product_price;
				}
				/*if($this->extra_table_depend->count == NULL) {
        $this->extra_table_depend->count = $data->product_quantity;
      }*/
			}
			if( !$this->extra_table_depend->ean and @ $row[ 'product_ean' ] )
			{
				$this->extra_table_depend->ean = $row[ 'product_ean' ];
			}
			if( !$this->extra_table_depend->price and @ $row[ 'product_price' ] )
			{
				$this->extra_table_depend->price = str_replace( "," , "." , $row[ 'product_price' ] ) * $this->config->currency_rate;
			}
			if( !$this->extra_table_depend->count )
			{
				$this->extra_table_depend->count = abs( $this->config->quantity_depended );
			}
		}

		/**
		 * Обработка поля [Изображение (Зависимый атр.)]
		 * @param $depended_images
		 * @param $product_id
		 * @return int
		 * @since 3.9 
		 */
		function depended_images( $depended_images , $product_id )
		{


			if( $this->old_version )
			{
				return 0;
			}

			$depended_images = str_replace( "," , "|" , $depended_images );
			$depended_images = explode( "|" , $depended_images );



			foreach( $depended_images as $key => &$image_name )
			{
				# Если прямая ссылка
				if( strstr( $image_name , 'http://' ) or strstr( $image_name , 'https://' ) )
				{
					# Закачмваем фотографию
					$image_name = $this->remote_images( $image_name );
				}

				$image_name = \GComHelpers\Helper::normaliseFileName( $image_name , $this->image_product_path );




				if( !file_exists( $this->image_product_path . $image_name ) )
				{
					$this->error_log( "Отсутствует изображение товара. Строка - $this->row. {$this->image_product_path}{$image_name}" );
					unset( $depended_images[ $key ] );
					continue;
				}



				# Создание миниатюр
				$this->make_thumb( $image_name );
			}




			if( $depended_images[ 0 ] )
			{
				$this->_db->setQuery( "INSERT INTO #__jshopping_products SET parent_id = $product_id, image = " . $this->_db->Quote( $depended_images[ 0 ] ) );
				$this->_db->execute();
				$children_product_id = $this->_db->insertid();

				$this->_db->setQuery( "SELECT image FROM #__jshopping_products WHERE product_id = $product_id" );
				if( !$this->_db->loadResult() )
				{
					$this->_db->setQuery( "UPDATE  #__jshopping_products SET image = " . $this->_db->Quote( $depended_images[ 0 ] ) . " WHERE product_id = $product_id" );
					$this->_db->execute();
				}
			} else
			{
				return 0;
			}

			foreach( $depended_images as $key => $v )
			{
				$this->_db->setQuery( "
					INSERT INTO #__jshopping_products_images 
					SET 
					product_id = '$children_product_id', 
					image_name = " . $this->_db->Quote( $v ) . ", 
					ordering=" . $this->_db->Quote( $key + 1 )
				);
				$this->_db->execute();
			}

			return $children_product_id;

		}




		function attr_recursion( $total )
		{
			$attr_id = key( $total );
			if( !$attr_id )
			{
				$this->depend_attr_save();

				return;
			}
			$attr_values = $total[ $attr_id ];
			unset ( $total[ $attr_id ] );
			foreach( $attr_values as $attr_value_id )
			{
				$field_name = 'attr_' . $attr_id;
				$this->extra_table_depend->$field_name = $attr_value_id;
				$this->attr_recursion( $total );
			}
		}

		function depend_attr_save()
		{
			$where = [];
			foreach( $this->attr_ids as $attr_id )
			{
				$field_name = 'attr_' . $attr_id;
				$where[] = "`$field_name` = " . $this->_db->Quote( $this->extra_table_depend->$field_name ) . "";
			}
			$where[] = "`product_id` = " . $this->_db->Quote( $this->extra_table_depend->product_id ) . "";
			$where = implode( " AND " , $where );
			$this->_db->setQuery( "SELECT product_attr_id FROM #__jshopping_products_attr WHERE $where" );
			$product_attr_id = $this->_db->loadResult();
			if( $product_attr_id )
			{
				$this->extra_table_depend->product_attr_id = $product_attr_id;
				$this->extra_table_depend->update();
			} else
			{
				$this->extra_table_depend->product_attr_id = null;
				$this->extra_table_depend->insert();
			}
		}

		function setMinPrice( $product_id )
		{
			$prices = 0;
			$this->_db->setQuery( "SELECT product_price FROM #__jshopping_products WHERE product_id = $product_id" );
			$base_brice = $this->_db->loadResult();
			if( $base_brice > 0 )
			{
				$prices++;
			}
			$this->_db->setQuery( "SELECT MAX(discount) as discount,COUNT(discount) as num FROM #__jshopping_products_prices WHERE product_id = $product_id" );
			$discount_data = $this->_db->loadObject();
			$max_discount = $discount_data->discount;
			if( $discount_data->num > 0 )
			{
				$prices += $discount_data->num;
			}
			$this->_db->setQuery( "SELECT * FROM #__jshopping_products_attr2 WHERE product_id = $product_id" );
			$attrib_ind_price_data = $this->_db->loadObjectList();
			if( count( $attrib_ind_price_data ) > 0 )
			{
				$prices += count( $attrib_ind_price_data );
			}
			$this->_db->setQuery( "SELECT MIN(price) as min,COUNT(price) as num FROM #__jshopping_products_attr WHERE product_id = $product_id AND price > 0" );
			$min_depend_price_data = $this->_db->loadObject();
			$min_depend_price = (float)$min_depend_price_data->min;
			if( $min_depend_price_data->num > 0 )
			{
				$prices += $min_depend_price_data->num;
			}

			$different_prices = $prices > 1 ? 1 : 0;

			if( $base_brice > 0 )
			{
				$min_price = $min_depend_price > $base_brice ? $base_brice : $min_depend_price;
			} else
			{
				$min_price = $min_depend_price;
			}

			foreach( $attrib_ind_price_data as $v )
			{
				if( $v->price_mod == "+" )
				{
					$tmpprice[] = $min_price + $v->addprice;
				} else if( $v->price_mod == "-" )
				{
					$tmpprice[] = $min_price - $v->addprice;
				} else if( $v->price_mod == "*" )
				{
					$tmpprice[] = $min_price * $v->addprice;
				} else if( $v->price_mod == "/" )
				{
					$tmpprice[] = $min_price / $v->addprice;
				} else if( $v->price_mod == "%" )
				{
					$tmpprice[] = $min_price * $v->addprice / 100;
				} else if( $v->price_mod == "=" )
				{
					$tmpprice[] = $v->addprice;
				}
			}

			if( isset( $tmpprice ) )
			{
				$min_price = min( $tmpprice );
			}


			if( $max_discount > 0 )
			{
				if( $this->JSconfig->product_price_qty_discount == 1 )
				{
					$min_price = $min_price - $max_discount;
				} else
				{
					$min_price = $min_price - ( $min_price * $max_discount / 100 );
				}
			}

			$this->_db->setQuery( "UPDATE #__jshopping_products
        SET
        different_prices = $different_prices,
        min_price = '$min_price'
        WHERE product_id = '$product_id'" );
			$this->_db->execute();

		}

		/**
		 * Создать для столбцов системные заголовки
		 * @param $cells
		 * @param int $level
		 * @param bool $cat
		 * @param bool $csv
		 * @return bool
		 * @since 3.9
		 */
		function prepare( $cells , $level = 0 , $cat = false , $csv = false )
		{
			$lang_fields = [
				'name' ,
				'short_description' ,
				'description' ,
				'meta_description' ,
				'meta_keyword' ,
				'meta_keyword' ,
				'meta_title' ,
				'alias',
			];
			# Поля для категорий
			$fields_cat = [
				'category_image' => 'image_name' ,
				'category_publish' => 'product_publish' ,
				'category_template' => 'product_template' ,
				'category_add_date' => 'product_date_added',
			];

			$i = $csv ? 0 : -1;
			
//			echo'<pre>';print_r( $this->active );echo'</pre>'.__FILE__.' '.__LINE__;

			
			foreach( $this->active as $f )
			{
				$i++;
				/*@$row[$f->name] = str_replace("'",'`',trim($cells[$i]));
@$row[$f->name] = str_replace("\n","",$row[$f->name]);*/

				if( $cat and $this->config->cat_col == $i )
				{
					$row[ 'category_name' ] = $cells[ $i ];
					continue;
				}
				@ $row[ $f->name ] = trim( $cells[ $i ] );
				if( in_array( $f->name , $lang_fields ) and @ $row[ $f->name ] != '' )
				{
					@ $row[ $f->name . '_' . $this->config->language ] = $row[ $f->name ];
				}
				if( in_array( $f->name , $fields_cat ) and $cat and $row[ $f->name ] != '' )
				{
					@ $row[ array_search( $f->name , $fields_cat ) ] = $row[ $f->name ];
				}
				if( isset ( $row[ $f->name ] ) )
				{
					if( @ $row[ $f->name ] == '' )
						unset ( $row[ $f->name ] );
				}
			}
			if( empty( $row ) )
				return false;
			$row[ 'level' ] = $level;

			return $row;
		}

		function insertCategory( $row )
		{
			$mark_up = 1;
			$cell = '';
			$parent_id = 0;
			$nomber = 0;
			$string = '';
			switch( $this->config->price_template )
			{
				case 1 :
					$cell = trim( $row[ 'category_name' ] );
					if( !preg_match( "/^\d{1,3}\./" , $cell ) and $this->last_parent )
					{
						$cell = $this->last_parent . '.' . ++$this->last_child . '.' . $cell;
						$mark_up = 0;
					}
					break;
				case 2 :
					$this->level = 1;
					while( $row[ 'category_name' ][ 0 ] == $this->config->simbol )
					{
						$this->level++;
						$row[ 'category_name' ] = substr( $row[ 'category_name' ] , 1 );
					}
					@ $this->category_levels[ $this->level ]++;
					$prefix = '';
					for( $l = 1; $l <= $this->level; $l++ )
						$prefix .= $this->category_levels[ $l ] ? $this->category_levels[ $l ] . '.' : '';
					$cell = $prefix . trim( $row[ 'category_name' ] );
					break;
				case 3 :
					$this->level = 1;
					while( $row[ 'category_name' ][ strlen( $row[ 'category_name' ] ) - 1 ] == $this->config->simbol )
					{
						$this->level++;
						$row[ 'category_name' ] = substr( $row[ 'category_name' ] , 0 , -1 );
					}
					@ $this->category_levels[ $this->level ]++;
					$prefix = '';
					for( $l = 1; $l <= $this->level; $l++ )
						$prefix .= $this->category_levels[ $l ] ? $this->category_levels[ $l ] . '.' : '';
					$cell = $prefix . trim( $row[ 'category_name' ] );
					break;
				case 4 :
					$cell = $row[ 'path' ] . '.' . $row[ 'category_name' ];
					break;
			}
			if( $this->config->price_template != 8 )
			{
				$bak = $this->escape( $cell );
				$temp = explode( "." , $cell );
				$path = [];
				while( preg_match( "/^\d{1,3}$/" , trim( $temp[ 0 ] ) ) and count( $temp ) > 1 )
				{
					$path[] = (int)array_shift( $temp );
				}
				$cell = implode( '.' , $temp );
				if( $mark_up )
				{
					$this->last_parent = implode( '.' , @ $path );
					$this->last_child = 0;
				}
				$this->last_path = $path;
				if( isset ( $path ) )
					$nomber = array_pop( $path );
				if( empty ( $path ) )
				{
					$string = '$this->tree';
					$parent_id = 0;
				} else
				{
					$string = '$this->tree[' . implode( '][' , $path ) . ']';
					@ eval ( "\$parent_id =" . $string . "['id'];" );
				}
				$cell = trim( $this->escape( $cell ) );
			} else
			{
				$parent_id = (int)@ $this->last_parrent_array[ $row[ 'level' ] - 1 ];
				$cell = trim( $this->escape( $row[ 'category_name' ] ) );
				$bak = $cell;
				for( $i = 1; $i <= $row[ 'level' ]; $i++ )
				{
					if( $i == 1 )
						$bak = "|_" . $bak;
					else
						$bak = "|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $bak;
				}
				$this->_db->setQuery( "SELECT MAX(ordering)
                                  FROM #__jshopping_categories
                  WHERE category_parent_id = $parent_id" );
				$nomber = $this->_db->loadResult() + 1;
			}
			if( $cell == '' )
				return false;
			$cell = stripslashes( $cell );
			$query = "SELECT category_id
              FROM #__jshopping_categories
              WHERE `name_{$this->config->language}`=" . $this->_db->Quote( $cell );
			$this->_db->setQuery( $query );
			$category_ids = $this->_db->loadColumn();
			if( $category_ids and is_int( $parent_id ) )
			{
				$query = "SELECT category_id FROM #__jshopping_categories WHERE category_parent_id=$parent_id AND category_id IN (" . implode( "," , $category_ids ) . ")";
				$this->_db->setQuery( $query );
				$this->category_id = $this->_db->loadResult() ? $this->_db->loadResult() : 0;
			} else
				$this->category_id = @ $category_ids[ 0 ];
			$this->js_category->reset( 1 );
			$this->js_category->bind( $row );
			$this->js_category->{'name_' . $this->config->language} = $cell;
			$this->bind_image( $row , $this->js_category );
			if( @ $row[ 'category_add_date' ] )
			{
				$this->js_category->category_add_date = is_numeric( $row[ 'category_add_date' ] ) ? date( "Y-m-d H:i:s" , PHPExcel_Shared_Date:: ExcelToPHP( $row[ 'category_add_date' ] ) ) : date( "Y-m-d H:i:s" , strtotime( $row[ 'category_add_date' ] ) );
			}
			if( $this->category_id )
			{
				$this->js_category->category_id = $this->category_id;
				$this->js_category->update();
				$this->log( 'cu' , $this->category_id , stripslashes( $bak ) );
			} else
			{
				if( !$this->js_category->category_add_date )
				{
					$this->js_category->category_add_date = $this->m_date;
				}
				$this->_db->setQuery( "SHOW TABLE STATUS LIKE '" . $this->_db->getPrefix() . "jshopping_categories'" );
				$table = $this->_db->loadObject();
				$this->js_category->category_id = $table->Auto_increment;
				$this->js_category->products_page = $this->JSconfig->count_products_to_page;
				$this->js_category->products_row = $this->JSconfig->count_products_to_row;
				$this->js_category->category_publish = isset ( $row[ 'category_publish' ] ) ? $row[ 'category_publish' ] : 1;
				$this->js_category->ordering = $nomber;
				$this->js_category->category_parent_id = $parent_id;
				if( $this->js_category->{'alias_' . $this->config->language} == null )
				{
					$this->js_category->{'alias_' . $this->config->language} = $this->getAlias( stripslashes( $cell ) , $this->js_category->category_id , false , false );
				}
				if( !$this->category_id = $this->js_category->insert() )
				{
					echo "Ошибка при создании категории. Строка - $this->row.<br>";

					return false;
				}
				$this->log( 'cn' , $this->category_id , stripslashes( $bak ) );
			}
			if( $this->config->price_template != 8 )
			{
				eval ( $string . "[$nomber]['id']=$this->category_id;" );
			} else
			{
				$this->last_parrent_array[ $row[ 'level' ] ] = $this->category_id;
			}
			$this->product_order = 0;
			$this->current[ 'category' ] = $bak;
		}

		function extractImages( $objPHPExcel )
		{
			/*echo '<pre>';
print_r($objPHPExcel->getActiveSheet()->getDrawingCollection());
echo '</pre>';
exit();*/

			$drawing_array = $objPHPExcel->getActiveSheet()->getDrawingCollection();
			unset ( $objPHPExcel );
			foreach( $drawing_array as $key => $drawing )
			{
				if( $drawing instanceof PHPExcel_Worksheet_MemoryDrawing )
				{
					/*echo '<pre>';
  print_r($drawing);
  echo '</pre>';
exit();*/
					ob_start();
					call_user_func( $drawing->getRenderingFunction() , $drawing->getImageResource() );
					$imageContents = ob_get_contents();
					ob_end_clean();
					$name = md5( $imageContents ) . "." . ( $drawing->getMimeType() == 'image/png' ? 'png' : 'jpg' );
					$this->images_collection[ $drawing->getCoordinates() ]->name = $name;
					file_put_contents( $this->image_product_path . 'full_' . $name , $imageContents );
				}
				if( $drawing instanceof PHPExcel_Worksheet_Drawing )
				{
					$extension = $drawing->getExtension();
					$imageContents = file_get_contents( $drawing->getPath() );
					$name = md5( $imageContents ) . "." . ( $extension == 'png' ? 'png' : 'jpg' );
					$this->images_collection[ $drawing->getCoordinates() ]->name = $name;
					file_put_contents( $this->image_product_path . 'full_' . $name , $imageContents );
				}
			}
			file_put_contents( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . "images_collection.txt" , serialize( @ $this->images_collection ) );
		}

		function _strtoupper( $string )
		{
			$small = [ 'а' , 'б' , 'в' , 'г' , 'д' , 'е' , 'ё' , 'ж' , 'з' , 'и' , 'й' , 'к' , 'л' , 'м' , 'н' , 'о' , 'п' , 'р' , 'с' , 'т' , 'у' , 'ф' , 'х' , 'ч' , 'ц' , 'ш' , 'щ' , 'э' , 'ю' , 'я' , 'ы' , 'ъ' , 'ь' , 'э' , 'ю' , 'я' , 'a' , 'b' , 'c' , 'd' , 'e' , 'f' , 'g' , 'h' , 'i' , 'j' , 'k' , 'l' , 'm' , 'n' , 'o' , 'p' , 'q' , 'r' , 's' , 't' , 'u' , 'v' , 'w' , 'x' , 'y' , 'z' ];
			$large = [ 'А' , 'Б' , 'В' , 'Г' , 'Д' , 'Е' , 'Ё' , 'Ж' , 'З' , 'И' , 'Й' , 'К' , 'Л' , 'М' , 'Н' , 'О' , 'П' , 'Р' , 'С' , 'Т' , 'У' , 'Ф' , 'Х' , 'Ч' , 'Ц' , 'Ш' , 'Щ' , 'Э' , 'Ю' , 'Я' , 'Ы' , 'Ъ' , 'Ь' , 'Э' , 'Ю' , 'Я' , 'A' , 'B' , 'C' , 'D' , 'E' , 'F' , 'G' , 'H' , 'I' , 'L' , 'K' , 'L' , 'M' , 'N' , 'O' , 'P' , 'Q' , 'R' , 'S' , 'T' , 'U' , 'V' , 'W' , 'X' , 'Y' , 'Z' ];

			return str_replace( $small , $large , $string );
		}

		function getVersion()
		{
			$xml = simplexml_load_file( JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_excel2js' . DS . 'excel2js.xml' );

			return (string)$xml->version;
		}

		function checkArticlesVersion()
		{
			if( isset ( $this->ArticlesVersion ) )
				return $this->ArticlesVersion;
			$xml = simplexml_load_file( JPATH_ROOT . DS . 'plugins' . DS . 'vmcustom' . DS . 'articles' . DS . 'articles.xml' );
			$version = (string)$xml->version;
			$temp = explode( "." , $version );
			if( $temp[ 0 ] > 1 )
			{
				$this->ArticlesVersion = true;
			} else if( $temp[ 1 ] >= 3 )
			{
				$this->ArticlesVersion = true;
			} else
			{
				$this->ArticlesVersion = false;
			}

			return $this->ArticlesVersion;
		}

		function update_files()
		{
			$data = $this->get_files();
			$files = '';
			foreach( $data as $key => $f )
			{
				$files .= '<tr id="row_' . $key . '">';
				$files .= '<td><input name="uploaded_file[]" id="uploaded_file_' . $key . '" type="checkbox" value="' . $f->file . '" style="margin-left: 14px"></td>';
				$files .= '<td><label for="uploaded_file_' . $key . '">' . $f->file . '</label></td>';
				$files .= '<td>' . $this->getSize( $f->size ) . '</td>';
				$files .= '<td>' . date( "Y-m-d H:i" , $f->time ) . '</td>';
				$files .= '<td><a href="index.php?option=com_excel2js&task=download&file=' . $f->file . '"><img src="' . JURI:: base() . '/components/com_excel2js/assets/images/download.png" width="16" height="16" alt=""></a></td>';
				$files .= '<td><img style="cursor: pointer" rel="' . $key . '" file="' . $f->file . '"  class="delete" src="' . JURI:: base() . '/components/com_excel2js/assets/images/delete.png" width="16" height="16" alt=""></td>';
				$files .= '</tr>';
			}
			echo $files;
		}

		function get_files()
		{
			$uploaded_files = JFolder:: files( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls' );
			$data = [];
			foreach( $uploaded_files as $key => $file )
			{
				if( in_array( substr( $file , -4 ) , [ '.xls' , '.csv' , 'xlsx' ] ) )
				{
					@ $data[ $key ]->file = $file;
					$data[ $key ]->size = $size = filesize( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls' . DS . $file );
					$data[ $key ]->time = $size = filemtime( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls' . DS . $file );
				}
			}

			return $data;
		}

		function getSize( $bytes )
		{
			if( $bytes < 1024 )
				return $bytes . " B<br>";
			else if( $bytes < 1024 * 1024 )
				return round( $bytes / 1024 ) . " KB<br>";
			else
				return round( $bytes / ( 1024 * 1024 ) , 2 ) . " MB<br>";
		}

		function download()
		{
			$file = $_GET[ 'file' ];
			if( !$file )
				exit ();
			$uploaded_files = JFolder:: files( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls' );
			foreach( $uploaded_files as $key => $f )
			{
				if( !in_array( substr( $f , -4 ) , [ '.xls' , '.csv' , 'xlsx' ] ) )
				{
					unset ( $uploaded_files[ $key ] );
				}
			}
			if( !in_array( $file , $uploaded_files ) )
			{
				echo "Файл не найден";
				exit ();
			}
			$mainframe = JFactory:: getApplication();
			$mainframe->redirect( JURI:: base() . "/components/com_excel2js/xls/" . $file );
		}

		function delete()
		{
			$file = $_GET[ 'file' ];
			if( !$file )
				exit ();
			$uploaded_files = JFolder:: files( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls' );
			foreach( $uploaded_files as $key => $f )
			{
				if( !in_array( substr( $f , -4 ) , [ '.xls' , '.csv' , 'xlsx' ] ) )
				{
					unset ( $uploaded_files[ $key ] );
				}
			}
			if( !in_array( $file , $uploaded_files ) )
			{
				echo "Файл не найден";
				exit ();
			}
			if( unlink( JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls' . DS . $file ) )
			{
				exit ();
			} else
			{
				print_r( error_get_last() );
				exit ();
			}
		}

		function getFileForCron()
		{
			$files = JFolder:: files( $this->cron_file_dir );
			$new_array = [];
			foreach( $files as $file )
			{
				$ext = pathinfo( $file , PATHINFO_EXTENSION );
				if( !in_array( $ext , [ 'xls' , 'xlsx' , 'csv' ] ) )
				{
					continue;
				}
				$time = filemtime( $this->cron_file_dir . $file );
				$new_array[ $time ] = $file;
			}
			krsort( $new_array );

			return array_shift( $new_array );
		}

		function getIndependedOptions( $attr_id , $variant )
		{
			$this->_db->setQuery( "SELECT `name_{$this->config->language}` FROM #__jshopping_attr_values WHERE attr_id = '$attr_id'" );
			$options = $this->_db->loadColumn();
			if( count( $options ) > 2 )
			{
				if( !$variant )
				{
					return $options[ 0 ] . ";+20|" . $options[ 1 ] . ";-10|" . $options[ 2 ];
				} else
				{
					return $options[ 0 ] . ";=200|" . $options[ 1 ] . ";=170|" . $options[ 2 ] . ";=150";
				}
			} else if( count( $options ) == 2 )
			{
				if( !$variant )
				{
					return $options[ 0 ] . ";+20|" . $options[ 1 ];
				} else
				{
					return $options[ 0 ] . ";=200|" . $options[ 1 ] . ";=170";
				}
			} else
			{
				if( !$variant )
				{
					return "Красный;+20|Синий";
				} else
				{
					return "Зеленый;=200|Белый;=170";
				}
			}
		}


	}
