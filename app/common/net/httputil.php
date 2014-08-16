<?php
    namespace app\common\net;

    use \Debug;
    /**
     * Created by JetBrains PhpStorm.
     * User: xwarrior
     * Date: 12-12-1
     * Time: 下午3:27
     * To change this template use File | Settings | File Templates.
     */
    class HttpUtil
    {
        /**
         * 向指定的url使用curl提交数据，并返回相应结果
         * @static
         * @param $url  提交的url
         * @param $curl_post_data  要提交的数据，php array(key=> value)
         * @return bool|mixed   false 读取失败，或返回的结果正文
         */
        public static function post_url($url,$curl_post_data=array(),$reffer = null,$timeout = 8,$cookies = null){

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
            curl_setopt($curl, CURLOPT_TIMEOUT,$timeout);  //timeout,very important for system stable
            curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:16.0) Gecko/20100101 Firefox/16.0');

            if($reffer){
                curl_setopt($curl,CURLOPT_REFERER,$reffer);
            }
            if($cookies){
                curl_setopt($curl,CURLOPT_COOKIE,$cookies); //"fruit=apple; colour=red"
            }

            $curl_response = curl_exec($curl);
            if ( curl_errno($curl) ){
                $info = curl_getinfo($curl);
                $retval =  $info['http_code'].$curl_response;   //100=timeout,500=server error,404 url not found
                \Debug::log("curl fail", $retval);
                curl_close($curl);
                return false;
            }

            curl_close($curl);
            return $curl_response;
        }

        /**
         * 向使用http get读取指定的url，并返回相应结果
         * @static
         * @param $url  提交的url
         * @param $curl_post_data  要提交的数据，php array(key=> value)
         * @return bool|mixed   false 读取失败，或返回的结果正文
         */
        public static function read_url($url,$reffer = null,$timeout = 8,$cookies = null){

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT,$timeout);  //timeout,very important for system stable
            curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:16.0) Gecko/20100101 Firefox/16.0');
            if($reffer){
               curl_setopt($curl,CURLOPT_REFERER,$reffer);
            }
            if($cookies){
                curl_setopt($curl,CURLOPT_COOKIE,$cookies); //"fruit=apple; colour=red"
            }
            $curl_response = curl_exec($curl);
            if ( curl_errno($curl) ){
                $info = curl_getinfo($curl);
                $retval =  $info['http_code'].$curl_response;   //100=timeout,500=server error,404 url not found
                \Debug::log("curl fail", $retval);
                curl_close($curl);
                return false;
            }

            curl_close($curl);
            return $curl_response;
        }

        /**
         * 获取指定url的二级域名信息，如  yoka.com   taobao.com    china.com.cn
         *
         */
        public static function  get_level2_domain($url){
            // a list of decimal-separated TLDs
            static $doubleTlds = array(
                'co.uk', 'me.uk', 'net.uk', 'org.uk', 'sch.uk',
                'ac.uk', 'gov.uk', 'nhs.uk', 'police.uk', 'mod.uk',
                'asn.au', 'com.au', 'net.au', 'id.au', 'org.au',
                'edu.au', 'gov.au', 'csiro.au', 'br.com', 'com.cn',
                'com.tw', 'cn.com', 'de.com', 'eu.com', 'hu.com',
                'idv.tw', 'net.cn', 'no.com', 'org.cn', 'org.tw',
                'qc.com', 'ru.com', 'sa.com', 'se.com', 'se.net',
                'uk.com', 'uk.net', 'us.com', 'uy.com', 'za.com',
                'com.fr', 'com.hk', 'com.kh', 'co.kr', 'co.id',
                'com.ua', 'co.in',  'co.jp',  'com.ph', 'com.my',
                'com.tr', 'co.kr',  'co.id',  'co.id', 'com.br','com.sg'
            );

            // sanitize the URL
            $url = trim( $url );

            // if no hostname, use the current by default
            if ( empty( $url ) || '/' == $url[0] )
            {
                return False;
            }

            // if no scheme, use `http://` by default
            if ( FALSE === strpos( $url, '://' ) )
            {
                $url = 'http://' . $url;
            }

            // can we successfully parse the URL?
            if ( $host = parse_url( $url, PHP_URL_HOST ) )
            {

                // is this an IP?
                if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $host ) )
                {
                    return $host;
                }

                // sanitize the hostname
                $host = strtolower( $host );

                // explode on the decimals
                $parts = explode( '.', $host );

                // is there just one part? (`localhost`, etc)
                if ( ! isset( $parts[1] ) )
                {
                    return $parts[0];
                }

                // grab the TLD
                $tld = array_pop( $parts );

                // grab the hostname
                $host = array_pop( $parts ) . '.' . $tld;

                // have we collected a double TLD?
                if ( ! empty( $parts ) && in_array( $host, $doubleTlds ) )
                {
                    $host = array_pop( $parts ) . '.' . $host;
                }

                // send it on it's way
                return $host;

            }

            // at this point, nah
            return FALSE;
        }
    }
