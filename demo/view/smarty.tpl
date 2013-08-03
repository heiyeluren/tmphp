<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{{$title}}</title>
<link rel="stylesheet" type="text/css" href="/css/style.css" />
</head>

<body>
<p>
  <img src="/images/logo.png" />
</p>

<p>
  <h2>{{$title}}</h2>
</p>

<p>
<h4>使用 foreach 展现列表</h4>
<ul>
  {{foreach item=item1 from=$list}}
  <li>{{$item1}}</li>
  {{/foreach}}
</ul>
</p>

<p>
<h4>使用 section 展现列表</h4>
<ol>
  {{section name=sec1 loop=$list}}
  <li>{{$list[sec1]}}</li>
  {{/section}}
</ol>
</p>

<div class="center">
	<hr size="1" />
	Powered by <a href="http://tmphp.googlecode.com" target="_blank">TMPHP PHP5 Framework</a>
</div>
</body>

</html>
