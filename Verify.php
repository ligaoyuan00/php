<?php

class Verify
{
	//宽度
	protected $width;
	//高度
	protected $height;
	//字符个数
	protected $length = 4;
	//类型
	protected $type;
	//验证码字符串
	protected $code;
	//图片资源
	protected $img;

	//初始化
	public function __construct($width=200,$height=50,$length=4,$type=0)
	{
		$this->width = $width;
		$this->height = $height;
		if ($length >= 3 && $length <= 9) {
			$this->length = $length;
		}
		$this->type = $type;
	}

	public static function yzm($width=200,$height=50,$length=4,$type=0)
	{
		$v = new Verify(200,50,5,0);
		$v->output();
		return $v->code;
	}

	public function __get($name)
	{
		if ($name == 'code') {
			return $this->code;
		}
	}

	public function getVerifyCode()
	{
		return $this->code;
	}

	public function output()
	{
		$this->createImage();
		$this->setVerifyCode();
		$this->setDisturb();
		$this->sendImage();		
	}

	//创建画布并设置浅色背景
	protected function createImage()
	{
		//创建指定尺寸的画布
		$this->img = imagecreatetruecolor($this->width, $this->height);
		//创建一个浅颜色
		$lightColor = $this->getColor(true);
		//设置浅色背景
		imagefill($this->img, 0, 0, $lightColor);
	}
	//产生验证码，并画到画布上
	protected function setVerifyCode()
	{
		//产生验证码字符串
		$this->code = $this->randString();
		//将验证码字符串画到画布上
		$fontSize = ceil($this->height/2);
		$perWidth = $this->width / $this->length;
		$delta = ($perWidth - $fontSize)/2;
		$offsetY = ($this->height + $fontSize)/2;
		for ($i=0; $i < $this->length; $i++) {
			$ch = mb_substr($this->code, $i, 1);
			$angle = mt_rand(-30,30);
			$offsetX = $i * $perWidth + $delta;
			$darkColor = $this->getColor();
			imagettftext($this->img, $fontSize, $angle, $offsetX, $offsetY, $darkColor, 'xdxwz.ttf', $ch);
		}
	}
	//设置干扰元素
	protected function setDisturb()
	{
		$total = $this->width * $this->height / 20;
		for ($i=0; $i < $total; $i++) { 
			$x = mt_rand(0, $this->width - 1);
			$y = mt_rand(0, $this->height - 1);
			$color = $this->getColor();
			imagesetpixel($this->img, $x, $y, $color);
		}
	}
	//发送验证码图片，释放资源
	protected function sendImage()
	{
		header('Content-Type:image/png');
		imagepng($this->img);
		imagedestroy($this->img);
	}
	//产生随机的验证码
	protected function randString()
	{
		switch ($this->type) {
			case 0:		//纯数字
				$str = $this->randNumber();
				break;
			case 1:		//纯字母
				$str = $this->randAlpha();
				break;
			case 2:		//数字字母混合	
				$str = $this->randMixed();
				break;
			case 3:		//中文
				$str = $this->randChinese();
				break;
			default:
				$str = $this->randUnknow();
				break;
		}
		return $str;
	}
	//纯数字字符串
	protected function randNumber()
	{
		$arr = range(0, 9);
		shuffle($arr);
		$str = join($arr);
		return substr($str, 0, $this->length);
	}
	//纯字母字符串
	protected function randAlpha()
	{
		$str = 'abcdefghijklmnopqrstuvwxyz';
		$str .= strtoupper($str);
		$str = str_shuffle($str);
		return substr($str, 0, $this->length);
	}
	//数字字母混合
	protected function randMixed()
	{
		$str = '';
		for ($i=0; $i < $this->length; $i++) { 
			$type = mt_rand(0,2);
			switch ($type) {
				case 0:		//数字
					$str .= mt_rand(0,9);
					break;
				case 1:		//小写字母
					$str .= chr(mt_rand(ord('a'),ord('z')));
					break;
				case 2:		//大写字母
					$str .= chr(mt_rand(ord('A'),ord('Z')));
					break;
			}
		}
		return $str;
	}
	//中文字符串
	protected function randChinese()
	{
		$str = '';
		for ($i=0; $i<$this->length; $i++) {
			$c1 = mt_rand(176,214);
			$c2 = mt_rand(161,254);
			$str .= chr($c1) . chr($c2);
		}
		return iconv('gbk','utf-8',$str);
	}
	//特定字符串
	protected function randUnknow()
	{
		$num = (string)mt_rand(0,9);
		$arr = array_fill(0, $this->length, $num);
		return join($arr);
	}
	//产生随机颜色
	protected function getColor($isLight= false)
	{
		$start = (int)$isLight * 128;
		$end = $start + 127;

		$red = mt_rand($start,$end);
		$green = mt_rand($start,$end);
		$blue = mt_rand($start,$end);

		return imagecolorallocate($this->img, $red, $green, $blue);
	}
}