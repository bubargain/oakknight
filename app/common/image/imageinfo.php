<?php
namespace app\common\image;
class ImageInfo{
	public function getImageSizes($images){
           if(!function_exists('getimagesize') ){
               throw new \Exception('需要GD扩展支持:getimagesize');
           }
           $retarray = array();
           foreach($images as $img){
               $filePath = $_SERVER['IMAGE_STORE_PATH']  . $img;
               if(!file_exists($filePath)){
                   throw new \Exception('错误:图片不存在'.$filePath);
               }

               list($width, $height, $type, $attr) = getimagesize($filePath);
               $retarray[] = array( 'img_url'=>$img ,'width'=>$width, 'height'=> $height );
           }
           return $retarray;
       }

       /**
        * 保存二进制图片数据
        * @static
        * @param $image_data
        * @param string $prefix
        * @param string $ext     图片数据的扩展名，如 .jpg  .gif
        * @throws \Exception    保存失败抛出异常
        * @return  array( img_url =>       'http://xxx.xxx.com/xxx/xxx/xxx.jpg',      //图片url地址
        *                 storage_path =>  xxx/xxx/xxx.jpg',                    //图片存储路径，带目录前缀/
        *                 file_path    =>  '/var/www/upload/xxx/xxx/xxx.jpg');   //图片本地完整文件名
        */
        public static  function  saveImageData($image_data,$prefix = 'fanup',$ext = '.jpg') {

            if( !isset($_SERVER['IMAGE_STORE_PATH']) ){
                throw new \Exception('$_SERVER[IMAGE_STORE_PATH] is required');
            }

            if( !isset($_SERVER['IMAGE_STORE_URL']) ){
                throw new \Exception('$_SERVER[IMAGE_STORE_URL] is required');
            }

            //存结目录结构 $_SERVER['IMAGE_STORE_PATH']/flashup/201210/01/md5(file_content).jpg
			if($prefix=='fanup'){
				  $storage_dir = '/' . $prefix . '/' .  date("Ym")  . '/' . date("d") ;
			}else{
				  $storage_dir = '/' . $prefix;
			}

            $save_dir = $_SERVER['IMAGE_STORE_PATH'] . '/' . $storage_dir ;
            //create need  dir
            if(!file_exists($save_dir)){
                $ret = @mkdir($save_dir,0766,true);
                if ( !$ret &&  !file_exists($save_dir) ){
                    throw new \Exception('mkdir fail:' . $save_dir);
                }
            }

            $file_name = md5( $image_data )  . $ext;

            $save_path = $save_dir . '/' . $file_name;
            $fp = fopen( $save_path , 'wb' );
            if( !$fp ) {
                throw new \Exception('save image fail:' . $save_path );
            }

            fwrite( $fp, $image_data );
            fclose( $fp );

            return array( 'img_url' => $_SERVER['IMAGE_STORE_URL'] . $storage_dir . '/' . $file_name ,  //图片url地址
                          'storage_path' => $storage_dir . '/' . $file_name,                //图片存储路径，不带目录前缀
                          'file_path' =>  $save_path );  //图片本地完整文件名
        }
		
	}
?>