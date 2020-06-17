<?php
	/**
	 * @package     ${NAMESPACE}
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	class CellValue
	{
		protected $params;
		
		/**
		 * CellValue constructor.
		 */
		public function __construct ( $params )
		{
			$this->params = $params;
		}
		
		public function processRow ( $rowArray )
		{
			foreach ($this->params as $param)
			{
				$operaton = $param->column_operaton;
				$this->{$operaton}( $rowArray , $param );
				
			}#END FOREACH
			
			return $rowArray ;
		}
		
		/**
		 * Добавить значение ячейки к другой по правилу  {X}&{V}
		 *  {X} - значение получателя
		 *  {V} - значение донора
		 *   &  - произвольная строка
		 * @param $arr  - масив ячеек в строке
		 * @param $rule - правило для этой операции
		 *
		 *
		 * @since version
		 */
		private function add ( &$arr , $rule )
		{
			$exc = $rule->_column_exc ;
			$past = $rule->_column_past ;
			
			$X = $arr[$past] ;
			$V = $arr[$exc] ;
			
			$arr[$past] = str_replace( [ '{X}','{V}' ] , [ $X , $V ], $rule->value_replace ) ;
		}
		
		/**
		 * Удалить ячейку и перечситать идексы
		 * @param $arr
		 * @param $rule
		 *
		 * @since version
		 */
		private function del ( &$arr , $rule )
		{
			$exc = $rule->_column_exc ;
			unset( $arr[$exc] ) ;
			$arr = array_values( $arr );
		}
		
		/**
		 * Найти в ячейке подстраку и заменить другой
		 *   strFind , strReplace
		 * @param $arr
		 * @param $rule
		 *
		 * @since version
		 */
		private function replace_f( &$arr , $rule ){
			$Arr = explode(',' , $rule->value_replace ) ;
			if( !isset( $arr[$rule->_column_exc] ) ) return  ; #END IF
			$arr[$rule->_column_exc] = str_replace( $Arr[0] , $Arr[1], $arr[$rule->_column_exc] ) ;
		}
	
	
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	