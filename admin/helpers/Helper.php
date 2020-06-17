<?php


	namespace GComHelpers;

	use Joomla\CMS\Filesystem\File;

	defined( '_JEXEC' ) or die( 'Restricted access' );

	class Helper
	{
		/**
		 * Получить параемтр memory_limit
		 * @return false|float
		 * @since 3.9
		 */
		public static function get_mem_total()
		{
			$mem = ini_get( "memory_limit" );
			if( strstr( $mem , "M" ) )
				return (float)$mem;
			else
			{
				return round( $mem / 1048576 , 2 );
			}
		}

		/**
		 * Приводит в порядок имена файлов изображений
		 * @param $FileName
		 * @param $Path
		 * @since 3.9
		 */
		public static function normaliseFileName( $FileName , $Path , $_stage = 1 ){
			switch( $_stage ){
				# Поиск по имени
				case 1 :
					$FileName = File::makeSafe( $FileName );
					if( file_exists( $Path . $FileName ) )
					{
						$_filename = mb_strtolower($FileName);
						# Есть заглавные буквы
						if( $FileName != $_filename )
						{
							File::move( $Path . $FileName , $Path . $_filename );
							return $_filename ;
						}#END IF
						
						return $FileName ;
					}
					break ;
				# Поиск с full_
				case 2 :
					$FileName = File::makeSafe( $FileName );
					$fn = 'full_' . $FileName ;
					if( file_exists( $Path . $fn ) )
					{
						$_filename = File::makeSafe( $FileName );
						# Приведение строки к нижнему регистру
						$_filename = mb_strtolower($_filename);
						File::move( $Path . $fn , $Path . $_filename );
						return $_filename ;
					}
					break ;

				default :
					$_filename = File::makeSafe( $FileName );
					# Приведение строки к нижнему регистру
					$_filename = mb_strtolower($_filename);
					return $_filename ;
			}

			$_stage ++ ;
			return self::normaliseFileName($FileName , $Path , $_stage);

		}

		static $images_resize ;
		static $thumb_replace ;

		/**
		 * Resize image Magic
		 *
		 * @param string path image
		 * @param int width
		 * @param int height
		 * @param int (0 - show full foto, 1 - cut foto )
		 * @param int (2 - fill $color or fill transparent, 1 - fill $color, 0 - not fill)
		 * @param string save to file (if empty - print image)
		 * @param int quality (0,100)
		 * @param int $color_fill (0xffffff - white)
		 * @param int interlace - enable / disable
		 * @since 3.9
		 */
		public static function resizeImageMagic( $img , $w , $h , $thumb_flag = 0 , $fill_flag = 1 , $name = "" , $qty = 85 , $color_fill = 0xffffff , $interlace = 1 )
		{
			if( !self::$images_resize )
			{
				return 1;
			}
			if( !self::$thumb_replace )
			{
				if( file_exists( $name ) )
				{
					return 1;
				}
			}
			$new_w = $w;
			$new_h = $h;
			$path = pathinfo( $img );
			$ext = $path[ 'extension' ];
			$ext = strtolower( $ext );

			$imagedata = @getimagesize( $img );

			$img_w = $imagedata[ 0 ];
			$img_h = $imagedata[ 1 ];

			if( !$img_w && !$img_h ) return 0;

			if( !$w )
			{
				$w = $new2_w = $h * ( $img_w / $img_h );
				$new2_h = $h;
			} else if( !$h )
			{
				$h = $new2_h = $w * ( $img_h / $img_w );
				$new2_w = $w;
			} else
			{

				if( $img_h * ( $new_w / $img_w ) > $new_h )
				{
					$new2_w = $img_w * $new_h / $img_h;
					$new2_h = $new_h;
				} else
				{
					$new2_w = $new_w;
					$new2_h = $img_h * $new_w / $img_w;
				}

				if( $thumb_flag )
				{
					if( $img_h * ( $new_w / $img_w ) < $new_h )
					{
						$new2_w = $img_w * $new_h / $img_h;
						$new2_h = $new_h;
					} else
					{
						$new2_w = $new_w;
						$new2_h = $img_h * $new_w / $img_w;
					}
				}

				if( !$thumb_flag && !$fill_flag )
				{
					$new2_w = $w;
					$new2_h = $h;
				}
			}

			if( ( $ext == "jpg" ) or ( $ext == "jpeg" ) )
			{
				$image = imagecreatefromjpeg( $img );
			} else if( $ext == "gif" )
			{
				$image = imagecreatefromgif( $img );
			} else if( $ext == "png" )
			{
				$image = imagecreatefrompng( $img );

			} else
			{
				return 0;
			}

			$thumb = imagecreatetruecolor( $w , $h );

			if( $fill_flag )
			{
				if( $fill_flag == 2 )
				{
					if( $ext == "png" )
					{
						imagealphablending( $thumb , false );
						imagesavealpha( $thumb , true );
						$trnprt_color = imagecolorallocatealpha( $thumb , 255 , 255 , 255 , 127 );
						imagefill( $thumb , 0 , 0 , $trnprt_color );
					} else if( $ext == "gif" )
					{
						$trnprt_color = imagecolorallocatealpha( $thumb , 255 , 255 , 255 , 127 );
						imagefill( $thumb , 0 , 0 , $trnprt_color );
						imagecolortransparent( $thumb , $trnprt_color );
						imagetruecolortopalette( $thumb , true , 256 );
					} else
					{
						imagefill( $thumb , 0 , 0 , $color_fill );
					}
				} else
				{
					imagefill( $thumb , 0 , 0 , $color_fill );
				}
			}

			if( $thumb_flag )
			{

				imagecopyresampled( $thumb , $image , ( $w - $new2_w ) / 2 , ( $h - $new2_h ) / 2 , 0 , 0 , $new2_w , $new2_h , $imagedata[ 0 ] , $imagedata[ 1 ] );

			} else if( $fill_flag )
			{

				if( $new2_w < $w ) imagecopyresampled( $thumb , $image , ( $w - $new2_w ) / 2 , 0 , 0 , 0 , $new2_w , $new2_h , $imagedata[ 0 ] , $imagedata[ 1 ] );
				if( $new2_h < $h ) imagecopyresampled( $thumb , $image , 0 , ( $h - $new2_h ) / 2 , 0 , 0 , $new2_w , $new2_h , $imagedata[ 0 ] , $imagedata[ 1 ] );
				if( $new2_w == $w && $new2_h == $h ) imagecopyresampled( $thumb , $image , 0 , 0 , 0 , 0 , $new2_w , $new2_h , $imagedata[ 0 ] , $imagedata[ 1 ] );

			} else
			{

				$thumb = @imagecreatetruecolor( $new2_w , $new2_h );
				if( $ext == "png" )
				{
					imagealphablending( $thumb , false );
					imagesavealpha( $thumb , true );
				}
				if( $ext == "gif" )
				{
					$trnprt_color = imagecolorallocatealpha( $thumb , 255 , 255 , 255 , 127 );
					imagefill( $thumb , 0 , 0 , $trnprt_color );
					imagecolortransparent( $thumb , $trnprt_color );
					imagetruecolortopalette( $thumb , true , 256 );
				}
				imagecopyresampled( $thumb , $image , 0 , 0 , 0 , 0 , $new2_w , $new2_h , $imagedata[ 0 ] , $imagedata[ 1 ] );

			}

			switch( $ext )
			{
				case 'png':
					if( phpversion() >= '5.1.2' )
					{
//
						imagepng( $thumb , $name , 10 - max( intval( $qty / 10 ) , 1 ) );
					} else
					{
						imagepng( $thumb , $name );
					}
					break;
				case 'gif':
					if( $name )
						imagegif( $thumb , $name );
					else
						imagegif( $thumb );
					break;
				case 'jpg':
				case 'jpeg':
					imagejpeg( $thumb , $name , $qty );
					break;
			}

			return 1;
		}



	}

