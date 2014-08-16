<?php
    namespace app\common\mongo;

    interface MongoRecord
    {
        /**
         * 设置查询的超时间时间，默认是20000毫秒
         * @param unknown_type $timeout
         */
        public static function setFindTimeout($timeout);

        /**
         * 设置是否对当胆集合启用从库的查询，在复制集中默认为true,会自动将读操作路由到从库
         * @param unknown_type $ok
         */
        public static function setSlaveOk($ok  = true);

        /*
        * 获取是否对当前集合启用从库查询
        */
        public static function getSlaveOkay();

        /**
         *  执行批量查找操作，返回符合条件的第一个结果的指定字段
         * @param unknown_type $query
         * @param unknown_type $fields
         */
        public static function findOne($query = array(), $fields = array());

        /**
         * 批量删除符合条件的数据
         * @param unknown_type $criteria
         * @param    $options array  $options array("upsert" => <boolean>,"multiple" => <boolean>,"safe" => <boolean|int>,"fsync" => <boolean>, "timeout" => <milliseconds>)
         *                    选项详情： 是否安全插入 safe:true,false
        是否同步到硬盘 fsync:true,false
        超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
         */
        public static function remove($criteria = null,$options = array());

        /**
         * 执行一个查询，返回一个查询结果的迭代器(游标)
         * （可以当数组使用用for .. as  ...遍历，同时可以用count()方法获得记录总数)
         * @param unknown_type $query
         * @param unknown_type $fields
         * @param    $options 选项详情： 是否安全插入 safe:true,false
                                        是否同步到硬盘 fsync:true,false
                                        超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
         */
        public static function find($query = array(), $fields = array(), $options = array());

        /**
         * 执行一个查询，并将符合条件的数据做为对象数组返回
         * Enter description here ...
         * @param unknown_type $query
         * @param unknown_type $fields
         * @param    $options array  $options array("upsert" => <boolean>,"multiple" => <boolean>,"safe" => <boolean|int>,"fsync" => <boolean>, "timeout" => <milliseconds>)
         *                    选项详情： 是否安全插入 safe:true,false
                                        是否同步到硬盘 fsync:true,false
                                        超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
         */
        public static function findAll($query = array(), $fields = array(), $options = array());

        /**
         * 获取一个查询符合条件的记录总数
         * @param unknown_type $query
         */
        public static function count($query = array());


        /**
         * 批量更新符合query条件的数据，用fields中指定的字段值
         * @param unknown_type $query  mongo  查询字段 		   array( filed1=>condition1,field2=>condition2,...)
         * @param unknown_type $new_object  要更新的对象值必须继承自BaseMongoRecord，注：如果_id字段有值有可能导致更新失败
         * @param    $options array  $options array("upsert" => <boolean>,"multiple" => <boolean>,"safe" => <boolean|int>,"fsync" => <boolean>, "timeout" => <milliseconds>)
*                    选项详情： 是否安全插入 safe:true,false
                    是否同步到硬盘 fsync:true,false
                    超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
         */
        public static function updateAll($query = array() , $new_object , $options = null);

        /**
         * 批量给collection中的符合query条件的对象的指定定段增加或减少指定的整数值，默为为字段+1
         * @param   $query     查询条件  array( field1=>condition,field2=>array($op=>condition),field3...)
         * @param   $fields    需要增加数值的字段  string OR array( 'field1','field2',... )
         *
         * @return  bool
         */
        public static function inc( $query	=	array(),$fields	=	array()	,$incnum	=	1,	$upsert = false,	$safe = false);


        /**
         * 对collection进行按字段分组求和	相当于mysql 的 sum
         * @param   $group_by		用来group by 的字段数组  array(id => true,name => true)
         * @param   $where			查询条件  常规的where数组  array('user_id' => '1260858')
         * $param   $sub_columns		可选，默认1时求count，不为1就只能设为integer类型的字段名称的字符串或数组,
         *                              如:  'field_name' or array('field_name_1','field_name_2')
         * @return  array			Array ( [retval] => Array ( [0] => Array ( [user_id] => 1260858 [count] => 6 ) ) [count] => 2 [keys] => 1 [ok] => 1 )
         */
        public static function sum($group_by = NULL,$where = NULL,$sub_columns=1);

        /**
         * 查找指定id的数据
         * @param string or objectid or array $arr_ids  要查找的id数组(string or MongoId) 如: array('4f92a1768749160f74000001' ,  '4f92a1768749160f74000001' ,  '4f92a1768749160f74000001')
         * @param array $other_query_condtion   ids之外的其它查询条件  如 : array( 'visibility' => 0  , owner_id => '2233334' )
         * @param $conver_to_key_value 是否转换为array( _id=>array() ,_id=array())的关联数组
         * @param   $fields    查询字段  array( 'field1','field2',... )
         * @param   $options  查找选项 array ( sort=>array(field1=>1,field2=>-1,...),skip=>int,limit=>int )
         * @return 返回符合条件文档的数组结果
         */
        public static function findByIds($arr_ids = array(),$other_query_condtion = array() , $conver_to_key_value = false,
                                         $fields = array(), $options = array());
        /**
         * 保存当前对象的变更到数据库中
         * 如果相同_id的对象已经存在则执行更新操作覆盖数据库的记录
         * 如果没有设置 _id，则会插入新记录，并将新插入自动生成的_id保存到当前对象上
         * @param array $options
         @param    $options 选项详情： 是否安全插入 safe:true,false
                                    是否同步到硬盘 fsync:true,false
                                    超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
        @param insertonly     是否只执行插入操作: insertonly :  true,false  当设置为true时，如果当前数据的_id已经在数据库中，则会抛出异常 (仅当options的safe为true时才会获得异常)
         */
        public function save(array $options = array(),$insert_only = false);


        /**
         * 把_id按递增数字存储,实现数字形式的增量id
         * @param    $options array  $options array("upsert" => <boolean>,"multiple" => <boolean>,"safe" => <boolean|int>,"fsync" => <boolean>, "timeout" => <milliseconds>)
         *                    选项详情： 是否安全插入 safe:true,false
                                        是否同步到硬盘 fsync:true,false
                                        超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
         * @return 保存成功，返回true
         */
        public function numsave(array $options = array());


        /**
         * 从库中删除当前对象,使用当前对象的_id做为删除条件
         * @param    $options array  $options array("upsert" => <boolean>,"multiple" => <boolean>,"safe" => <boolean|int>,"fsync" => <boolean>, "timeout" => <milliseconds>)
         *                    选项详情： 是否安全插入 safe:true,false
        是否同步到硬盘 fsync:true,false
        超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
         */
        public function destroy($options = array());

    }

