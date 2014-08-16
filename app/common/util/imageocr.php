<?php
    namespace app\common\util;

    use \app\common\net\HttpUtil;
    use \MongoId;

    /**
     * 图片ocr识别识现类
     * User: xwarrior
     * Date: 12-12-4
     * Time: 下午4:52
     * To change this template use File | Settings | File Templates.
     */
    class ImageOCR
    {
        const TESSERACT_PATH = "/usr/local/bin/tesseract";
        const OCR_TEMP_PATH = "/tmp";


        /**
         * 读取一个远程图片url,并使用ocr转为换字符，返回识别后的字符
         * @param $url
         * @param null $reffer
         * @param int $timeout
         * @param null $cookie
         */
        public static function OCR_URL($url,$reffer=null,$timeout = 8,$cookie= null ){
            $image = HttpUtil::read_url($url,$reffer,$timeout,$cookie);
            if ( !$image ){
                return false;
            }

            $fileName = self::OCR_TEMP_PATH . '/ocr_' . new MongoId();
            $file = fopen($fileName,"wb");
            fwrite($file,$image);
            fclose($file);

            $result = self::Image_OCR($fileName);

            //remove temp file
            unlink($fileName);

            return $result;
        }

        /**
         * 对指定的图片文件执行ocr后，返回ocr的结果字符串
         * @param $image_file
         */
        public static function Image_OCR($image_file){
            $image_file = preg_replace("/[;><!]/","",$image_file);
            $output_filename = self::OCR_TEMP_PATH . '/txt_' . new MongoId();
            $shell = self::TESSERACT_PATH . ' ' . $image_file . ' ' . $output_filename;
            $output = shell_exec($shell);
            $output_filename .= '.txt';
            if( !file_exists($output_filename) ){
                return false;
            }
            $ocr_content = file_get_contents($output_filename);
            unlink($output_filename);
            return $ocr_content;
        }


        /**
         * ocr测试
         * @param $request
         * @param $response
         */
        public static function test_ocr($request,$response){
            $img_url = "http://jprice.360buyimg.com/price/gp754265-1-1-3.png";
            $ocr_result =  self::OCR_URL($img_url,$img_url);
            echo "京东价格图片:<img src=\"$img_url\"/><br/>";
            echo "OCR 识别结果:" . $ocr_result;
        }
    }
