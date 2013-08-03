<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{$title}</title>
<link rel="stylesheet" type="text/css" href="/css/style.css" />
</head>

<body>
<p>
  <img src="/images/logo.png" />
</p>

<p>
  <h2>{$title}</h2>
</p>

<p>
<h4>使用 loop 展现列表</h4>
<ul>
	<!--{loop $list $value}-->
		<li>$value</li>
	<!--{/loop}-->

</ul>
</p>

<p>
<h4>使用 loop-key 展现列表</h4>
<ul>
	<!--{loop $list $key $value}-->
		<li>$key. $value</li>
	<!--{/loop}-->
</ul>
</p>

<div class="center">
	<hr size="1" />
	Powered by <a href="http://tmphp.googlecode.com" target="_blank">TMPHP PHP5 Framework</a>
</div>
</body>

</html>
