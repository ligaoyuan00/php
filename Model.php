<?php

class Model
{
	//数据库连接
	protected $link;
	//主机地址
	protected $host;
	//用户名
	protected $user;
	//登录密码
	protected $pwd;
	//数据库名称
	protected $dbName;
	//数据表名字
	protected $tableName = 'user';
	//数据表前缀
	protected $prefix;
	//字符集
	protected $charset;
	//缓存字段
	protected $fields;
	//缓存目录
	protected $cache;
	//保存最后的SQL语句
	protected $sql;
	//存储SQL语句中的各种条件
	protected $options;

	protected function initOptions()
	{
		return [
					'fields'	=>	'*',
					'table'		=>	$this->tableName,
					'where'		=> 	'',
					'order'		=>	'',
					'group'		=>	'',
					'having'	=>	'',
					'limit'		=>	'',
				];
	}

	public function __construct($config=null)
	{
		$this->host = $config['DB_HOST'];
		$this->user = $config['DB_USER'];
		$this->pwd = $config['DB_PWD'];
		$this->prefix = $config['DB_PREFIX'];
		$this->dbName = $config['DB_NAME'];
		$this->charset = $config['DB_CHARSET'];
		$this->cache = $this->checkCache($config['DB_CACHE']);
		if (!($this->link = $this->connect())) {
			exit('数据库连接失败');
		}
		$this->tableName = $this->getTableName();
		$this->fields = $this->getFields();
		$this->options = $this->initOptions();
	}
	protected function checkCache($dir)
	{
		$dir = rtrim($dir,'/') . '/';
		if (!is_dir($dir)) {
			mkdir($dir,0777,true);
		}
		if (!is_readable($dir) || !is_writable($dir)) {
			chmod($dir, 0777);
		}
		return $dir;
	}
	protected function getFields()
	{
		//拼接缓存文件的名字
		$cacheFile = $this->cache . $this->tableName . '.php';
		if (file_exists($cacheFile)) {
			return include($cacheFile);
		}
		//不存在缓存文件，需要缓存
		$sql = 'desc ' . $this->tableName;
		$result = $this->query($sql,MYSQLI_ASSOC);
		$fields = [];
		foreach ($result as $value) {
			if ($value['Key'] == 'PRI') {
				$fields['_pk'] = $value['Field'];
			}
			$fields[] = $value['Field'];
		}
		$str = "<?php \n return " . var_export($fields,true) . ';';
		file_put_contents($cacheFile, $str);
		return $fields;
	}
	protected function getTableName()
	{
		//指定表名
		if ($this->tableName) {
			return $this->prefix . $this->tableName;
		}
		//从这里向下，看不懂的可以先不看...
		//没有指定，需要手工获取
		$className = get_class($this);
		//model\UserModel
		if ($pos = strrpos($className, '\\')) {
			$className = substr($className, $pos+1);
		}
		//UserModel
		$className = substr($className, 0, -5);
		//User
		return strtolower($className);
		//user
	}

	protected function connect()
	{
		$link = mysqli_connect($this->host,$this->user,$this->pwd);
		if (!$link) {
			return false;
		}
		if (!mysqli_select_db($link,$this->dbName)) {
			mysqli_close($link);
			return false;
		}
		if (!mysqli_set_charset($link,$this->charset)) {
			mysqli_close($link);
			return false;
		}
		return $link;
	}

	public function getLastSql()
	{
		return $this->sql;
	}

	public function where($where)
	{
		if (is_string($where)) {
			$this->options['where'] = 'where ' . $where;
		} else if (is_array($where)) {
			$this->options['where'] = 'where ' . join(' and ',$where);
		}
		return $this;
	}
	public function limit($limit)
	{
		if (is_string($limit)) {
			$this->options['limit'] = 'limit ' . $limit;
		} else if (is_array($limit)) {
			$this->options['limit'] = 'limit ' . join(',',$limit);
		}
		return $this;
	}
	public function group($group)
	{
		if (is_string($group)) {
			$this->options['group'] = 'group by ' . $group;
		} else if (is_array($group)) {
			$this->options['group'] = 'group by ' . join(',',$group);
		}
		return $this;
	}
	public function order($order)
	{
		if (is_string($order)) {
			$this->options['order'] = 'order by ' . $order;
		} else if (is_array($order)) {
			$this->options['order'] = 'order by ' . join(',',$order);
		}
		return $this;
	}
	public function having($having)
	{
		if (is_string($having)) {
			$this->options['having'] = 'having ' . $having;
		} else if (is_array($having)) {
			$this->options['having'] = 'having ' . join(' and ', $having);
		}
		return $this;
	}
	public function table($table)
	{
		//'user,bbs_posts'
		if (is_string($table)) {
			$table = explode(',', $table);
		}
		//['user','bbs_posts']
		if (is_array($table)) {
			$len = strlen($this->prefix);
			foreach ($table as $key => $value) {
				if (!strncmp($value, $this->prefix, $len)) {
					$table[$key] = $this->prefix . $value;
				}
			}
			$this->options['table'] = join(',',$table);
		}
		//'bbs_user,bbs_posts'
		return $this;
	}
	public function fileds($fields)
	{
		if (is_string($fields)) {
			$this->options['fields'] = $fields;
		} else if (is_array($fields)) {
			$this->options['fields'] = join(',',$fields);
		}	
		return $this;
	}
	protected function addQuote($data)
	{
		if (is_array($data)) {
			$fields = array_flip($this->fields);
			$data = array_intersect_key($data, $fields);
			foreach ($data as $key => $value) {
				if (is_string($value)) {
					$data[$key] = '\''.$value.'\'';
				}
			}
		}
		return $data;
	}
	//增加
	public function insert($data,$insertId=false)
	{
		//插入的数据只能是数组
		if (!is_array($data)) {
			return '插入数据时，参数必须是数组@_@';
		}
		$data = $this->addQuote($data);
		$this->options['fields'] = join(',',array_keys($data));
		$this->options['values'] = join(',',$data);

		$sql = 'INSERT INTO %TABLE%(%FIELDS%) VALUES(%VALUES%)';
		$sql = str_replace(
							[
								'%TABLE%',
								'%FIELDS%',
								'%VALUES%',					
							], 
							[
								$this->options['table'],
								$this->options['fields'],
								$this->options['values'],	
							], 
							$sql
						);
		return $this->exec($sql,$insertId);
	}
	//删除
	public function delete()
	{
		if (empty($this->options['where'])) {
			return '删除数据时必须给出where条件';
		}
		$sql = 'DELETE FROM %TABLE% %WHERE% %ORDER% %LIMIT%';
		$sql = str_replace(
							[
								'%TABLE%',
								'%WHERE%',
								'%ORDER%',
								'%LIMIT%',					
							], 
							[
								$this->options['table'],
								$this->options['where'],
								$this->options['order'],	
								$this->options['limit'],	
							], 
							$sql
						);
		return $this->exec($sql);
	}
	//修改
	public function update($data)
	{
		if (empty($this->options['where'])) {
			return '更新数据时，必须传递where条件';
		}
		if (!is_array($data)) {
			return '更新数据必须传递数组作为参数';
		}
		$data = $this->checkUpdate($data);
		$this->options['set'] = join(',', $data);
		$sql = 'UPDATE %TABLE% SET %SET% %WHERE% %ORDER% %LIMIT%';
		$sql = str_replace(
							[
								'%TABLE%',
								'%SET%',
								'%WHERE%',
								'%ORDER%',
								'%LIMIT%',					
							], 
							[
								$this->options['table'],
								$this->options['set'],
								$this->options['where'],
								$this->options['order'],	
								$this->options['limit'],	
							], 
							$sql
						);
		return $this->exec($sql);
	}
	protected function checkUpdate($data)
	{
		$fields = array_flip($this->fields);
		$data = array_intersect_key($data, $fields);	
		$set = [];
		foreach ($data as $key => $value) {
			if (is_string($value)) {
				$set[] = $key . '=\'' . $value .'\'';
			} else {
				$set[] = $key . '=' . $value;
			}
		}
		return $set;
	}
	//查询
	public function select()
	{
		$sql = 'SELECT %FIELDS% FROM %TABLE% %WHERE% %GROUP% %ORDER% %HAVING% %LIMIT%';
		$sql = str_replace(
							[
								'%FIELDS%',
								'%TABLE%',
								'%WHERE%',
								'%GROUP%',
								'%ORDER%',
								'%HAVING%',
								'%LIMIT%',					
							], 
							[
								$this->options['fields'],
								$this->options['table'],
								$this->options['where'],
								$this->options['group'],
								$this->options['order'],
								$this->options['having'],
								$this->options['limit'],	
							], 
							$sql
							);
		return $this->query($sql);
	}
	//用于查询
	protected function query($sql,$resultType=MYSQLI_BOTH)
	{
		//保存SQL语句
		$this->sql = $sql;
		//重新复位初始化的选项
		$this->options = $this->initOptions();

		$result = mysqli_query($this->link,$sql);
		if ($result) {
			return mysqli_fetch_all($result,$resultType);
		}
		return $result;
	}
	//用于增加、删除、修改
	protected function exec($sql,$insertId=false)
	{
		//保存SQL语句
		$this->sql = $sql;
		//重新复位初始化的选项
		$this->options = $this->initOptions();

		$result = mysqli_query($this->link,$sql);
		if ($result && $insertId) {
			return mysqli_insert_id($this->link);
		}
		return $result;
	}

	public function find($id)
	{	
		if (is_array($id)) {
 			$id = join(',',$id);
 		}
		$where = $this->fields['_pk'] . ' in(' . $id . ')';
		return $this->where($where)->select();
	}	
}