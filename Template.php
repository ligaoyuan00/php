<?php


class Template
{
	//模板路径
	protected $tplPath;
	//缓存路径
	protected $tplCache;
	//有效期
	protected $validTime;
	//保存分配过来的变量
	protected $vars = [];

	public function __construct(
		$tplPath = './view',
		$tplCache = './cache/template',
		$validTime = 3600
	) {
		$this->tplPath = $this->checkDir($tplPath);
		$this->tplCache = $this->checkDir($tplCache);
		$this->validTime = $validTime;
	}

	public function assign($name,$value=null)
	{
		if (is_array($name)) {
			foreach ($name as $key => $value) {
				$this->vars[$key] = $value;
			}
		} else {
			$this->vars[$name] = $value;
		}
	}

	public function display($tplFile,$isExecute=true)
	{
		//0、拼接缓存文件全路径
		$cacheFile = $this->getCacheFile($tplFile); 
		//1、拼接模板文件全路径
		$tplFile = $this->tplPath . $tplFile;
		//2、判断模板文件是否存在
		if (!file_exists($tplFile)) {
			exit($tplFile . '模板文件不存在');
		}
		if (!file_exists($cacheFile)
			||filemtime($cacheFile) < filemtime($tplFile)
			||filemtime($cacheFile) + $this->validTime < time()
		) {		
			//3、编译模板文件
			$content = $this->compile($tplFile);
			//4、保存缓存文件
			file_put_contents($cacheFile, $content);
		} else {
			//处理内部包含的模板文件
			$this->updateInclude($tplFile);
		}
		//5、将分配过来的变量导入到当前符号表
		if (!empty($this->vars)) {
			extract($this->vars);
		}
		//6、是否包含缓存文件
		if ($isExecute) {
			include $cacheFile;
		}
	}

	protected function updateInclude($file)
	{
		$content = file_get_contents($file);
		if (preg_match_all('/\{include (.+)\}/U', $content, $matches)) {
			foreach ($matches[1] as $value) {
				$tplFile = trim($value,'\'"');
				$this->display($tplFile,false);
			}
		}
	}

	protected function compile($tplFile)
	{
		$file = file_get_contents($tplFile);

		$keys = [
					'{$%%}'			=>	'<?=$\1;?>',
					'{if %%}'		=>	'<?php if (\1): ?>',
					'{/if}'			=>	'<?php endif; ?>',
					'{else}'		=>	'<?php else : ?>',
					'{elseif %%}'	=>	'<?php elseif (\1): ?>',
					'{else if %%}'	=>	'<?php elseif (\1): ?>',
					'{foreach %%}'	=>	'<?php foreach (\1): ?>',
					'{/foreach}'	=>	'<?php endforeach; ?>',
					'{include %%}'	=>	'<?php include "\1"; ?>'
				];
		foreach ($keys as $key => $value) {
			//添加正则的转义
			$key = preg_quote($key,'#');
			//拼接完整的正则表达式，将'%%'替换成'(.+)'
			$reg = '#' . str_replace('%%', '(.+)', $key) . '#U';
			if (strpos($reg, 'include')) {
				$file = preg_replace_callback($reg, [$this,'compileInclude'], $file);
			} else {
				$file = preg_replace($reg, $value, $file);
			}			
		}
		return $file;
	}

	protected function compileInclude($matches)
	{
		$file = trim($matches[1],'\'"');
		$this->display($file,false);
		$cacheFile = $this->getCacheFile($file);
		return "<?php include '$cacheFile'; ?>";
	}

	protected function getCacheFile($tplFile)
	{
		//index.html => ./cache/template/index_html.php
		return $this->tplCache . str_replace('.', '_', $tplFile) . '.php';
	}

	protected function checkDir($dir)
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

	public function clearCache()
	{
		$this->deleteDir($this->tplCache);
	}

	protected function deleteDir($dir)
	{
		$dir = rtrim($dir,'/') . '/';
		$dp = opendir($dir);
		while ($file = readdir($dp)) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			$fileName = $dir . $file;
			if (is_dir($fileName)) {
				$this->deleteDir($fileName);
			} else {
				unlink($fileName);
			}
		}
		closedir($dp);
		rmdir($dir);
	}
}