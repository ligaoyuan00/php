<?php


class Image
{
	//保存路径
	protected $savePath;
	//随机名字
	protected $randName;
	//文件后缀
	protected $extension;
	//完整路径名
	protected $pathName;

	public function __construct(
		$savePath='./',
		$randName=true,
		$extension='png'
	) {
		$this->savePath = $savePath;
		$this->randName = $randName;
		$this->extension = $extension;
	}
	//添加水印
	public function waterMark($dstPath,$srcPath,$pos=9,$pct=100)
	{
		//1、文件目录检查
		if (!is_file($dstPath)){
			return '目标大图不存在';
		} else if (!is_file($srcPath)) {
			return '水印小图不存在';
		} else if (!is_dir($this->savePath)) {
			return '保存路径不存在';
		} else if (!is_writable($this->savePath)) {
			return '保存路径不可写';
		}	
		//2、检查图片尺寸
		list($dstWidth,$dstHeight) = getimagesize($dstPath);
		list($srcWidth,$srcHeight) = getimagesize($srcPath);
		if ($srcWidth > $dstWidth || $srcHeight > $dstHeight) {
			return '水印图片尺寸太大';
		}
		//3、计算水印图片位置
		if ($pos >= 1 && $pos <= 9) {
			$offsetX = ($pos-1)%3 * ($dstWidth-$srcWidth)/2;
			$offsetY = floor(($pos-1)/3) * ($dstHeight-$srcHeight)/2;
		} else {
			$offsetX = mt_rand(0,$dstWidth-$srcWidth);
			$offsetY = mt_rand(0,$dstHeight-$srcHeight);
		}
		//4、将水印图片合并到目标图片上
		$dstImg = $this->openImage($dstPath);
		$srcImg = $this->openImage($srcPath);
		imagecopymerge($dstImg, $srcImg, $offsetX, $offsetY, 0, 0, $srcWidth, $srcHeight, $pct);
		//5、保存图片
		$this->saveImage($dstImg,$dstPath);
		//6、释放资源
		imagedestroy($dstImg);
		imagedestroy($srcImg);
		//7、返回保存后的文件路径名
		return $this->pathName;
	}
	//保存图片
	protected function saveImage($img,$path)
	{
		//处理保存路径  ./abc/def/
		$this->pathName = rtrim($this->savePath,'/') . '/';

		$info = pathinfo($path);

		//处理文件名称
		if ($this->randName) {
			$this->pathName .= uniqid();
		} else {
			$this->pathName .= $info['filename'];
		}

		//处理文件后缀
		if (empty($this->extension)) {
			$this->extension = $info['extension'];
		} else {
			$this->extension = ltrim($this->extension,'.');
		}
		$this->pathName .= '.' . $this->extension;

		//保存图片文件
		if ($this->extension == 'jpg') {
			$this->extension = 'jpeg';
		}
		$saveFunc = 'image' . $this->extension;
		$saveFunc($img,$this->pathName);
		//返回保存的图片路径
		return $this->pathName;
	}
	//打开图片
	protected function openImage($file)
	{
		//获取图片信息
		$info = getimagesize($file);
		//将图片后缀序号转换成后缀
		$extension = image_type_to_extension($info[2],false);
		//拼接打开图片的函数
		$openFunc = 'imagecreatefrom' . $extension;
		//打开图片并返回
		return $openFunc($file);
	}
	//等比缩放
	public function zoomImage($imgPath,$width,$height)
	{
		//1、检查文件目录
		if (!is_file($imgPath)) {
			return '缩放图片不存在';
		} else if (!is_dir($this->savePath)) {
			return '保存路径不存在';
		} else if (!is_writable($this->savePath)) {
			return '保存路径不可写';
		}
		//2、计算缩放尺寸
		list($srcWidth,$srcHeight) = getimagesize($imgPath);
		$size = $this->getSize($width,$height,$srcWidth,$srcHeight);
		//3、合并图片
		$dstImg = imagecreatetruecolor($width, $height);
		$srcImg = $this->openImage($imgPath);
		$this->mergeImage($dstImg,$srcImg,$size);
		//4、保存图片
		$this->saveImage($dstImg,$imgPath);
		//5、释放资源
		imagedestroy($dstImg);
		imagedestroy($srcImg);
		return $this->pathName;
	}
	//处理黑边，合并图片
	protected function mergeImage($dstImg,$srcImg,$size)
	{
		//获取原图的透明色
		$lucidColor = imagecolortransparent($srcImg);
		if ($lucidColor == -1) {
			//没有透明色，将黑色作为透明色
			$lucidColor = imagecolorallocate($dstImg, 0, 0, 0);
		}
		//用透明色填充图片
		imagefill($dstImg, 0, 0, $lucidColor);
		//设置透明色
		imagecolortransparent($dstImg,$lucidColor);
		//合并图片
		imagecopyresampled($dstImg, $srcImg, $size['offsetX'], $size['offsetY'], 0, 0, $size['newWidth'], $size['newHeight'], $size['srcWidth'], $size['srcHeight']);
	}
	//计算尺寸
	protected function getSize($width,$height,$srcWidth,$srcHeight)
	{
		//保存原始尺寸
		$size['srcWidth'] = $srcWidth;
		$size['srcHeight'] = $srcHeight;

		//计算缩放比例
		$scaleWidth = $width / $srcWidth;
		$scaleHeight = $height / $srcHeight;
		$scaleFinal = min($scaleWidth,$scaleHeight);

		//保存真实尺寸
		$size['newWidth'] = $srcWidth * $scaleFinal;
		$size['newHeight'] = $srcHeight * $scaleFinal;

		//计算偏移尺寸
		if ($scaleWidth > $scaleHeight) {
			$size['offsetY'] = 0;
			$size['offsetX'] = round(($width - $size['newWidth'])/2);
		} else {
			$size['offsetX'] = 0;
			$size['offsetY'] = round(($height - $size['newHeight'])/2);
		}
		return $size;
	}
}