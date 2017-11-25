<?php

$p = new Paging(58);

$listed = $p->listed();

//extract($listed);
list($head,$prev,$next,$tail) = $listed;

/*
$head = $p->head();
$prev = $p->prev();
$next = $p->next();
$tail = $p->tail();
*/

echo "<a href='$head'>首页</a>&nbsp;";
echo "<a href='$prev'>上一页</a>&nbsp;";
echo "<a href='$next'>下一页</a>&nbsp;";
echo "<a href='$tail'>尾页</a>&nbsp;";


class Paging
{
	//数据的总数
	protected $total;
	//每页的条数
	protected $pageSize;
	//页码变量名
	protected $pageName;
	//总页数
	protected $pageCount;
	//当前页码
	protected $page;
	//基准URL
	protected $url;

	public function __construct($total,$pageSize=5,$pageName='page')
	{
		$this->total = $total;
		$this->pageSize = $pageSize;
		$this->pageCount = ceil($total / $pageSize);
		$this->pageName = $pageName;
		$this->page = $this->getPage();
		$this->url = $this->getUrl();
	}
	public function listed()
	{
		return [
					$this->head(),
					$this->prev(),
					$this->next(),
					$this->tail(),
					'head' 	=> 	$this->head(),
					'prev'	=>	$this->prev(),
					'next'	=>	$this->next(),
					'tail'	=>	$this->tail(),
				];
	}
	//指定页
	public function given($page)
	{
		if ($page < 1) {
			$page = 1;
		} else if ($page > $this->pageCount) {
			$page = $this->pageCount;
		}
		return $this->setUrl($page);
	}
	//首页
	public function head()
	{
		return $this->setUrl(1);
	}
	//尾页
	public function tail()
	{
		return $this->setUrl($this->pageCount);
	}
	//上一页
	public function prev()
	{
		if ($this->page > 1) {
			$page = $this->page - 1;
		} else {
			$page = 1;
		}
		return $this->setUrl($page);
	}
	//下一页
	public function next()
	{
		//计算下页页码
		if ($this->page < $this->pageCount) {
			$page = $this->page + 1;
		} else {
			$page = $this->pageCount;
		}
		return $this->setUrl($page);
	}
	protected function setUrl($page)
	{
		if (strpos($this->url, '?')) {
			return $this->url . '&' . $this->pageName . '=' . $page; 
		} else {
			return $this->url . '?' . $this->pageName . '=' . $page;
		}
	}
	//获取当前页码
	protected function getPage()
	{
		if (empty($_GET[$this->pageName])) {
			return 1;
		}
		$page = (int)$_GET[$this->pageName];
		if ($page < 1) {
			$page = 1;
		} else if ($page > $this->pageCount) {
			$page = $this->pageCount;
		}
		return $page;
	}
	//获取基准URL
	protected function getUrl()
	{
		//获取协议
		$url = $_SERVER['REQUEST_SCHEME'] . '://';
		//拼接主机
		$url .= $_SERVER['HTTP_HOST'];
		//拼接端口
		$url .= ':' . $_SERVER['SERVER_PORT'];
		//拼接URI
		$requestUri = $_SERVER['REQUEST_URI'];
		//如果URI中携带页码，需要将其删除
		if (isset($_GET[$this->pageName])) {
			$replaceStr = $this->pageName . '=' . $this->page;
			$replaceArr = [
								$replaceStr . '&',	//page=3&
								'&' . $replaceStr,	//&page=3
								'?' . $replaceStr,	//?page=3
							];
			$requestUri = str_replace($replaceArr, '', $requestUri);
		}
		return $url . $requestUri;
	}
}