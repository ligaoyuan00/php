<?php

class Upload
{
	//文件保存路径
	protected $savePath = './';
	//启用日期目录
	protected $datePath = true;
	//使用随机名字
	protected $randName = true;
	//默认文件后缀
	protected $extension = 'png';
	//支持的MIMIES
	protected $mimes = ['image/png','image/jpeg','image/gif'];
	//支持的文件后缀
	protected $suffixes = ['png','jpg','jpeg','gif'];
	//最大文件大小
	protected $maxSize = 2000000;
	//错误代号
	protected $errno = 0;
	//错误信息
	protected $error = '上传成功';
	//保存上传信息
	protected $uploadInfo;
	//保存文件名
	protected $pathName;

	public function __construct($options=null)
	{
		$this->setOption($options);
	}
	//设置属性，参数是一个数组
	public function setOption($options)
	{
		if (is_array($options)) {
			//获取当前类的所有属性名
			$keys = get_class_vars(__CLASS__);
			foreach ($options as $key => $value) {
				//判断是否是有效的属性名
				if (in_array($key, $keys)) {
					$this->key = $value;
				}	
			}	
		}
	}

	public function uploadFile($field)
	{
		//1、检查保存路径
		if (!$this->checkSavePath()) {
			return false;
		}
		//2、检查上传信息
		if (!$this->checkUploadInfo($field)) {
			return false;
		}
		//3、检查标准错误
		if (!$this->checkUploadError()) {
			return false;
		}
		//4、检查自定义错误
		if (!$this->checkAllowOption()) {
			return false;
		}
		//5、检查是否是上传文件
		if (!$this->checkUploadFile()) {
			return false;
		}
		//6、拼接保存路径
		$this->getPathName();
		//7、检查移动结果
		if (!$this->moveUploadFile()) {
			return false;
		}
		return $this->pathName;
	}

	protected function moveUploadFile()
	{
		if (move_uploaded_file($this->uploadInfo['tmp_name'], $this->pathName)) {
			return true;
		}
		$this->errno = -8;
		$this->error = '文件保存失败';
		return false;	
	}

	protected function getPathName()
	{
		//路径
		$this->pathName = $this->savePath;
		if ($this->datePath) {
			$this->pathName .= date('Y/m/d/');
			if (!file_exists($this->pathName)) {
				mkdir($this->pathName,0777,true);
			}
		}
		//名字
		if ($this->randName) {
			$this->pathName .= uniqid();
		} else {
			$info = pathinfo($this->uploadInfo['name']);
			$this->pathName .= $info['filename'];
		}
		//后缀
		$this->pathName .= $this->extension;
	}

	protected function checkSavePath()
	{
		if (!is_dir($this->savePath)) {
			$this->errno = -1;
			$this->error = '保存路径不存在';
			return false;
		}
		if (!is_writable($this->savePath)) {
			$this->errno = -2;
			$this->error = '保存路径不可写';
			return false;
		}
		$this->savePath = rtrim($this->savePath,'/') . '/';
		return true;
	}

	protected function checkUploadInfo($field)
	{
		if (empty($_FILES[$field])) {
			$this->errno = -3;
			$this->error = '没有'.$field.'上传信息';
			return false;
		}
		//保存上传信息
		$this->uploadInfo = $_FILES[$field];
		return true;
	}

	protected function checkUploadError()
	{
		if ($this->uploadInfo['error'] == UPLOAD_ERR_OK) {
			return true;
		}
		switch ($this->uploadInfo['error']) {
			case UPLOAD_ERR_INI_SIZE:
				$this->error = '超过了php.ini中upload_max_filesize选项的值';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$this->error = '超过了HTML中MAX_FILE_SIZE的值'
				break;
			case UPLOAD_ERR_PARTIAL:
				$this->error = '只有部分文件被上传';
				break;
			case UPLOAD_ERR_NO_FILE:
				$this->error = '没有文件被上传';
				break;
			case UPLOAD_ERR_NO_FILE:
				$this->error = '找不到临时文件夹';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$this->error = '文件写入失败';
				break;
			default:
				$this->error = '未知错误';
				break;
		}
		$this->errno = $this->uploadInfo['error'];
		return false;
	}

	protected function checkAllowOption()
	{
		//检查MIMIE
		if (!in_array($this->uploadInfo['type'], $this->mimes)) {
			$this->errno = -4;
			$this->error = '不支持的MIMIE:'.$this->uploadInfo['type'];
			return false;
		}
		//检查后缀
		if (!in_array($this->extension, $this->suffixes)) {
			$this->errno = -5;
			$this->error = '不支持的后缀:'.$this->extension;
			return false;
		}
		//检查大小
		if ($this->uploadInfo['size'] > $this->maxSize) {
			$this->errno = -6;
			$this->error = '超出了规定大小:'.$this->maxSize;
			return false;
		}
		return true;
	}
	protected function checkUploadFile()
	{
		if (!is_uploaded_file($this->uploadInfo['tmp_name'])) {
			$this->errno = -7;
			$this->error = '不是上传文件';
			return false;
		}
		return true;
	}
}