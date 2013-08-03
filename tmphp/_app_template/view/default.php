<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title;?></title>
<link rel="stylesheet" type="text/css" href="/css/style.css" />
</head>

<body>
<p>
  <img src="/images/logo.png" />
</p>

<p>
  <h2><?php echo $title;?></h2>
</p>

<p>
<ul>
<?php
foreach($list as $li){
	echo <<<EOT
		<li><a href="{$li[link2]}">Url Rewrite访问</a>  |  <a href="{$li[link]}">{$li[title]}</a>  </li>

EOT;
}
?>
</ul>
</p>

<div class="center">
	<hr size="1" />
	Powered by <a href="http://tmphp.googlecode.com" target="_blank">TMPHP PHP5 Framework</a>
</div>
</body>

</html>
