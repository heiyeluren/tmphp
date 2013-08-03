<?php
/**************************************************************
 *  描述：基本图像处理类
 *  作者：heiyeluren
 *  创建：2007-04-11 16:15
 *  修改：2007-04-12 17:02   基础图片处理函数构建
 *		  2009-12-14 00:55   增加缩略图、验证码、流读取等函数
 *		  2009-12-16 10:49	 更新异常处理方式
 *
 *************************************************************/

/**
 * 包含基础的图像操作函数
 * 生成所旅途、生成验证码图
 */
class TM_Image
{
	/**
	  * 把图片生成缩略图1
	  * @param string $srcFile	源文件			
	  * @param string $dstFile	目标文件
	  * @param int $dstW		目标图片宽度		
	  * @param int $dstH		目标文件高度
	  * @param string $dstFormat	目标文件生成的格式, 有png和jpg两种格式
	  * @return 错误返回错误对象
	  */
	public static function makeThumb1($srcFile, $dstFile, $dstW, $dstH, $dstFormat="png") {
		//打开图片
		$data = GetImageSize($srcFile, &$info);
		switch ($data[2]){
			case 1:	$im = @ImageCreateFromGIF($srcFile); break;
			case 2:	$im = @imagecreatefromjpeg($srcFile); break;
			case 3:	$im = @ImageCreateFromPNG($srcFile); break;
		}
		if (!$im){
			throw new TM_Exception(__CLASS__ .": Create image failed");
		}

		
		//设定图片大小
		$srcW =	ImageSX($im);
		$srcH =	ImageSY($im);
		$ni   = ImageCreate($dstW,$dstH);
		ImageCopyResized($ni, $im, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

		//生成指定格式的图片
		if ($dstFormat == "png"){
			imagepng($ni, $dstFile);
		}elseif ($dstFormat == "jpg"){
			ImageJpeg($ni, $dstFile);
		}else{
			imagepng($ni, $dstFile);
		}
	}


	 /**
	  * 把图片生成缩略图2
	  *
	  * @param string $srcFile	源文件			
	  * @param string $dstFile	目标文件
	  * @param int $dstW		目标图片宽度		
	  * @param int $dstH		目标文件高度
	  * @return 错误返回错误对象
	  */
	public static function makeThumb2($sourFile, $targetFile, $width, $height) {
		$data = getimagesize($sourFile);
		$imageInfo["width"] = $data[0];
		$imageInfo["height"]= $data[1];
		$imageInfo["type"] = $data[2];
		$imageInfo["name"] = basename($sourFile);
		$imageInfo["size"] = filesize($sourFile);
		$newName = substr($sourFile, 0, strrpos($sourFile, ".")) . "_thumb.jpg";

		//打开图片
		switch ($imageInfo["type"]){
			case 1:	$img = imagecreatefromgif($sourFile); break;
			case 2: $img = imagecreatefromjpeg($sourFile); break;
			case 3: $img = imagecreatefrompng($sourFile); break;
			default: return 0; break;
		}
		if (!$img){
			throw new TM_Exception(__CLASS__ .": Create image failed");
		}

		//图片缩小
		$width = ($width > $imageInfo["width"]) ? $imageInfo["width"] : $width;
		$height = ($height > $imageInfo["height"]) ? $imageInfo["height"] : $height;
		$srcW = $imageInfo["width"];
		$srcH = $imageInfo["height"];

		$height = min(round($srcH * $width / $srcW),$height);
		$width = min(round($srcW * $height / $srcH),$width);

		if (function_exists("imagecreatetruecolor")) { //GD2.0.1
			$new = imagecreatetruecolor($width, $height);
			ImageCopyResampled($new, $img, 0, 0, 0, 0, $width, $height, $imageInfo["width"], $imageInfo["height"]);
		}else{
			$new = imagecreate($width, $height);
			ImageCopyResized($new, $img, 0, 0, 0, 0, $width, $height, $imageInfo["width"], $imageInfo["height"]);
		}

		//生成图片
		ImageJPEG($new, $targetFile, 100);
	}


	/**
	 * 生成缩略图3

	 *
	 * @param string $srcFile   图片原始地址
	 * @param int $width        缩略图宽度
	 * @param int $height       缩略图高度
	 * @param bool $isStretch	是否保持比例
	 * @param string $prefix	缩略图的前缀
	 * @return mixed
	 */
	public static function makeThumb3($srcFile, $width, $height, $isStretch = true, $prefix = "thumb") {
		$data = getimagesize ( $srcFile, &$info );
		$pathParts = pathinfo ( $srcFile );
		$baseName = $prefix . $pathParts ['basename'];
		$dscFile = $pathParts ["dirname"] . '/' . $baseName;
		
		switch ($data [2]) {
			case 1 :
				$im = @imagecreatefromgif ( $srcFile );
				break;
			
			case 2 :
				$im = @imagecreatefromjpeg ( $srcFile );
				break;
			
			case 3 :
				$im = @imagecreatefrompng ( $srcFile );
				break;
			case 15 :
				$im = @imagecreatefromwbmp ( $srcFile );
		}
		
		$srcW = imagesx ( $im );
		$srcH = imagesy ( $im );
		
		//如果原图特别小
		if ($srcW <= $width && $srcH <= $height){
			@copy($srcFile, $dscFile);
		} 
		//一般图片则进行图片处理
		else {
			if ($isStretch) {
				if ($srcW >= $width || $srcH >= $height) {
					if (($width / $height) > ($srcW / $srcH)) {
						$temp_height = $height;
						$temp_width = $srcW * ($height / $srcH);
					} else {
						$temp_width = $width;
						$temp_height = $srcH * ($width / $srcW);
					}
				} else {
					$temp_width = $width;
					$temp_height = $height;
				}
			} else {
				if (($srcW / $width) >= ($srcH / $height)) {
					$temp_height = $height;
					$temp_width = $srcW / ($srcH / $height);
					$src_X = abs ( ($width - $temp_width) / 2 );
					$src_Y = 0;
				} else {
					$temp_width = $width;
					$temp_height = $srcH / ($srcW / $width);
					$src_X = 0;
					$src_Y = abs ( ($height - $temp_height) / 2 );
				}
			}
			
			$temp_img = imagecreatetruecolor ( $temp_width, $temp_height );
			imagecopyresized ( $temp_img, $im, 0, 0, 0, 0, $temp_width, $temp_height, $srcW, $srcH );
			
			//$ni = imagecreatetruecolor ( $width, $height );
			//imagecopyresized ( $ni, $temp_img, 0, 0, $src_X, $src_Y, $width, $height, $width, $height );
			$cr = imagejpeg ( $temp_img, $dscFile );
		} 
		chmod ( $dscFile, 0755 );
		
		if ($cr) {
			return $dscFile;
		} else {
			return false;
		}
	}


	/**
	 * 生成一个拼音、数字验证码
	 *
	 * @param int $length 验证码的长度，不超过32位，缺省为4位
	 * @param bool $isUpperCase 是否是大写，缺省是小写
	 * @return 返回最后生成的验证码
	 */
	public static function generateCheckCode($lenght=4, $isUpperCase=false){
		$code = substr(md5(rand()), 0, $lenght);
		return ( $isUpperCase ? strtoupper($code) : strtolower($code) );
	}
	

	/**
	 * 产生一个验证码图片（调用本方法之前必须先调用createRandomCode方法）
	 * 
	 * @param int $imgX 图片的X轴
	 * @param int $imgY  图片Y轴
	 * @return void
	 */
	public static function makeAuthCodeImg($checkCode, $imgX=65, $imgY=22) {
		//产生一个图片
		$im = imagecreate($imgX, $imgY); 
		$black = ImageColorAllocate($im, 0, 0, 0);// 背景颜色
		$white = ImageColorAllocate($im, 255, 255, 255); // 前景颜色
		$gray = ImageColorAllocate($im, 200, 200, 200); 
		imagefill($im, 68, 30,$gray); 

		//将验证码绘入图片 
		imagestring($im, 5, 8, 3, $checkCode, $white);

		//加入干扰象素 
		for($i=0;$i<200;$i++)
		{ 
			$randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
			imagesetpixel($im, rand()%70 , rand()%30 , $randcolor); 
		}
		//输出图像
		Header("Content-type: image/PNG");
		ImagePNG($im); 
		ImageDestroy($im); 
	}
	

	/**
	 * RGB颜色值转换为HSV
	 *
	 * @param int $R
	 * @param int $G
	 * @param int $B
	 * @return array
	 */
	public static function rgb2hsv($R, $G, $B)
	{
	 $tmp = min($R, $G);
	  $min = min($tmp, $B);
	  $tmp = max($R, $G);
	  $max = max($tmp, $B);
	  $V = $max;
	  $delta = $max - $min;
	
	  if($max != 0)
	   $S = $delta / $max; // s
	  else
	  {
	   $S = 0;
	    //$H = UNDEFINEDCOLOR;
	    return;
	  }
	  if($R == $max)
	   $H = ($G - $B) / $delta; // between yellow & magenta
	  else if($G == $max)
	    $H = 2 + ($B - $R) / $delta; // between cyan & yellow
	  else
	    $H = 4 + ($R - $G) / $delta; // between magenta & cyan
	
	  $H *= 60; // degrees
	  if($H < 0)
	   $H += 360;
	  return array($H, $S, $V);
	}

	/**
	 * HSV颜色值转换为RGB
	 *
	 * @param int $H
	 * @param int $S
	 * @param int $V
	 * @return array
	 */
	public static function hsv2rgb($H, $S, $V)
	{
	 if($S == 0)
	  {
	   // achromatic (grey)
	   $R = $G = $B = $V;
	    return;
	  }
	
	  $H /= 60;  // sector 0 to 5
	  $i = floor($H);
	  $f = $H - $i;  // factorial part of h
	  $p = $V * (1 - $S);
	  $q = $V * (1 - $S * $f);
	  $t = $V * (1 - $S * (1 - $f));
	
	  switch($i)
	  {
	   case 0:
	     $R = $V;
	      $G = $t;
	      $B = $p;
	      break;
	    case 1:
	      $R = $q;
	      $G = $V;
	      $B = $p;
	      break;
	    case 2:
	      $R = $p;
	      $G = $V;
	      $B = $t;
	      break;
	    case 3:
	      $R = $p;
	      $G = $q;
	      $B = $V;
	      break;
	    case 4:
	      $R = $t;
	      $G = $p;
	      $B = $V;
	      break;
	    default: // case 5:
	      $R = $V;
	      $G = $p;
	      $B = $q;
	      break;
	 }
	  return array($R, $G, $B);
	}
	

	/**
	 * 产生一个验证码图片2 (生成更复杂更高级的验证码)
	 * 
	 * @param string $authCode 验证码，必须要求为4位
	 * @param int $imgX 图片的X轴
	 * @param int $imgY  图片Y轴
	 * @return void
	 */
	public static function makeAuthCodeImg2($authCode, $imgX = 80, $imgY = 25){
		$authCode = substr($authCode, 0, 4);
		$randStr = preg_split('//', $authCode, -1, PREG_SPLIT_NO_EMPTY);
	
		$size = 20;
		$width = $imgX;
		$height = $imgY;
		$degrees = array(rand(0, 45), rand(0, 45), rand(0, 45), rand(0, 45)); // 生成数字旋转角度
	
		for($i = 0; $i < 4; ++$i)
		{
		 if(rand() % 2);
		 else $degrees[$i] = -$degrees[$i];
		}
	
		$image = imagecreatetruecolor($size, $size);   // 数字图片画布
		$validate = imagecreatetruecolor($width, $height);  // 最终验证码画布
		$back = imagecolorallocate($image, 255, 255, 255);  // 背景色
		$border = imagecolorallocate($image, 0, 0, 0);    // 边框
		imagefilledrectangle($validate, 0, 0, $width, $height, $back); // 画出背景色
	
		// 数字颜色
		for($i = 0; $i < 4; ++$i)
		{
		 // 考虑为使字符容易看清使用颜色较暗的颜色
		 $temp = self::rgb2hsv(rand(0, 255), rand(0, 255), rand(0, 255));
		 
		 if($temp[2] > 60)
		  $temp [2] = 60;
	
		 $temp = self::hsv2rgb($temp[0], $temp[1], $temp[2]);
		 $textcolor[$i] = imagecolorallocate($image, $temp[0], $temp[1], $temp[2]);
		}
	
		for($i = 0; $i < 200; ++$i) //加入干扰象素
		{
		 $randpixelcolor = ImageColorallocate($validate, rand(0, 255), rand(0, 255), rand(0, 255));
		 imagesetpixel($validate, rand(1, 87), rand(1, 27), $randpixelcolor);
		}
	
		// 干扰线使用颜色较明亮的颜色
		$temp = self::rgb2hsv(rand(0, 255), rand(0, 255), rand(0, 255));
	
		if($temp[2] < 200)
		 $temp [2] = 255;
		 
		$temp = self::hsv2rgb($temp[0], $temp[1], $temp[2]);
		$randlinecolor = imagecolorallocate($image, $temp[0], $temp[1], $temp[2]);
	
		// 画5条干扰线
		for ($i = 0;$i < 5; $i ++)
		 imageline($validate, rand(1, 79), rand(1, 24), rand(1, 79), rand(1, 24), $randpixelcolor);
	
		imagefilledrectangle($image, 0, 0, $size, $size, $back); // 画出背景色 
		imagestring($image, 5, 6, 2, $randStr[0], $textcolor[0]);  // 画出数字
		$image = imagerotate($image, $degrees[0], $back);
		imagecolortransparent($image, $back);
		imagecopymerge($validate, $image, 1, 4, 4, 5, imagesx($image) - 10, imagesy($image) - 10, 100);
	
		$image = imagecreatetruecolor($size, $size); // 刷新画板
		imagefilledrectangle($image, 0, 0, $size, $size, $back);  // 画出背景色 
		imagestring($image, 5, 6, 2, $randStr[1], $textcolor[1]);  // 画出数字
		$image = imagerotate($image, $degrees[1], $back);
		imagecolortransparent($image, $back);
		imagecopymerge($validate, $image, 21, 4, 4, 5, imagesx($image) - 10, imagesy($image) - 10, 100);
	
		$image = imagecreatetruecolor($size, $size); // 刷新画板
		imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $back);  // 画出背景色 
		imagestring($image, 5, 6, 2, $randStr[2], $textcolor[2]);  // 画出数字
		$image = imagerotate($image, $degrees[2], $back);
		imagecolortransparent($image, $back);
		imagecopymerge($validate, $image, 41, 4, 4, 5, imagesx($image) - 10, imagesy($image) - 10, 100);
	
		$image = imagecreatetruecolor($size, $size); // 刷新画板
		imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $back);  // 画出背景色 
		imagestring($image, 5, 6, 2, $randStr[3], $textcolor[3]);  // 画出数字
		$image = imagerotate($image, $degrees[3], $back);
		imagecolortransparent($image, $back);
		imagecopymerge($validate, $image, 61, 4, 4, 5, imagesx($image) - 10, imagesy($image) - 10, 100);
	
		imagerectangle($validate, 0, 0, $width - 1, $height - 1, $border);  // 画出边框
	
		header('Content-type: image/png');
		imagepng($validate);
		imagedestroy($validate);
		imagedestroy($image);	
	}
	

	/**
	 * 生成一个中文验证码
	 *
	 * @param int $len 验证码的长度，缺省为4位
	 * @return 返回最后生成的验证码
	 */
	public static function generateCnAuthCode($len = 4){
		$s = array('的','一','是','在','不','了','有','和','人','这','中','大','为','上','个','国','我','以','要','他','时','来','用','们','生','到','作','地','于','出','就','分','对','成','会','可','主','发','年','动','同','工','也','能','下','过','子','说','产','种','面','而','方','后','多','定','行','学','法','所','民','得','经','十','三','之','进','着','等','部','度','家','电','力','里','如','水','化','高','自','二','理','起','小','物','现','实','加','量','都','两','体','制','机','当','使','点','从','业','本','去','把','性','好','应','开','它','合','还','因','由','其','些','然','前','外','天','政','四','日','那','社','义','事','平','形','相','全','表','间','样','与','关','各','重','新','线','内','数','正','心','反','你','明','看','原','又','么','利','比','或','但','质','气','第','向','道','命','此','变','条','只','没','结','解','问','意','建','月','公','无','系','军','很','情','者','最','立','代','想','已','通','并','提','直','题','党','程','展','五','果','料','象','员','革','位','入','常','文','总','次','品','式','活','设','及','管','特','件','长','求','老','头','基','资','边','流','路','级','少','图','山','统','接','知','较','将','组','见','计','别','她','手','角','期','根','论','运','农','指','几','九','区','强','放','决','西','被','干','做','必','战','先','回','则','任','取','据','处','队','南','给','色','光','门','即','保','治','北','造','百','规','热','领','七','海','口','东','导','器','压','志','世','金','增','争','济','阶','油','思','术','极','交','受','联','什','认','六','共','权','收','证','改','清','己','美','再','采','转','更','单','风','切','打','白','教','速','花','带','安','场','身','车','例','真','务','具','万','每','目','至','达','走','积','示','议','声','报','斗','完','类','八','离','华','名','确','才','科','张','信','马','节','话','米','整','空','元','况','今','集','温','传','土','许','步','群','广','石','记','需','段','研','界','拉','林','律','叫','且','究','观','越','织','装','影','算','低','持','音','众','书','布','复','容','儿','须','际','商','非','验','连','断','深','难','近','矿','千','周','委','素','技','备','半','办','青','省','列','习','响','约','支','般','史','感','劳','便','团','往','酸','历','市','克','何','除','消','构','府','称','太','准','精','值','号','率','族','维','划','选','标','写','存','候','毛','亲','快','效','斯','院','查','江','型','眼','王','按','格','养','易','置','派','层','片','始','却','专','状','育','厂','京','识','适','属','圆','包','火','住','调','满','县','局','照','参','红','细','引','听','该','铁','价','严');
		$ks = array_rand($s, $len);
		$ret = '';
		for ($i=0; $i<$len; $i++){
			$ret .= $s[$ks[$i]];
		}
		return $ret;	
	}

	/**
	 * 产生一个中文验证码图片
	 * 
	 * 说明：必须保证存在 authcode-cn.ttf 这个字体，如果没有，请到如下地址下载放在本类所在目录：
	 *		 地址1：http://tmphp.googlecode.com/files/authcode-cn.ttf
	 *		 地址2: http://heiyeluren.googlecode.com/files/authcode-cn.ttf
	 * 
	 * @param int $imgX 图片的X轴
	 * @param int $imgY  图片Y轴
	 * @return void
	 */
	public static function makeCnAuthCodeImg($authCode, $imgX=150, $imgY=50, $font = dirname(__FILE__). '/authcode-cn.ttf') {
		$font = $font!= '' ? $font : dirname(__FILE__). '/authcode-cn.ttf';
		if (!is_file($font) || !is_readable($font)){
			throw new TM_Exception(__CLASS__ ." font file $font not exist or not readable");
		}
		$im = imagecreate($imgX,$imgY); 
		$bkg = ImageColorAllocate($im, 128,64,225); 
		$clr = ImageColorAllocate($im, 255,255,255); 
				
		//绘制背景和干扰线
		$white=imagecolorallocate($im,234,185,95);
		imagearc($im, 150, 8, 20, 20, 75, 170, $white);
		imagearc($im, 180, 7,50, 30, 75, 175, $white);
		imageline($im,20,20,180,30,$white);
		imageline($im,20,18,170,50,$white);
		imageline($im,25,50,80,50,$white);
		
		//填充文字后输出
		header("Content-type: image/PNG");
		ImageTTFText($im, 20, 10, 25,40, $clr, $font, $authCode); //写ttf文字到图中 
		ImagePNG($im); 
		ImageDestroy($im);
	}
	
	

	/* 
	* PHP图片水印 (水印支持图片或文字) 
	*  
	* @param string   $groundImage   背景图片，即需要加水印的图片，暂只支持GIF,JPG,PNG格式； 
	* @param int      $waterPos     水印位置，有10种状态，0为随机位置； 
	*                   1为顶端居左，2为顶端居中，3为顶端居右； 
	*                   4为中部居左，5为中部居中，6为中部居右； 
	*                   7为底端居左，8为底端居中，9为底端居右； 
	* @param string   $waterImage     图片水印，即作为水印的图片，暂只支持GIF,JPG,PNG格式； 
	* @param string   $waterText     文字水印，即把文字作为为水印，支持ASCII码，不支持中文； 
	* @param string   $textFont     文字大小，值为1、2、3、4或5，默认为5； 
	* @param string   $textColor     文字颜色，值为十六进制颜色值，默认为#FF0000(红色)； 
	* @return 失败返回错误对象
	*
	* 注意：Support GD 2.0，Support FreeType、GIF Read、GIF Create、JPG 、PNG 
	*     $waterImage 和 $waterText 最好不要同时使用，选其中之一即可，优先使用 $waterImage。 
	*     当$waterImage有效时，参数$waterString、$stringFont、$stringColor均不生效。 
	*     加水印后的图片的文件名和 $groundImage 一样。 
	*/ 
	public static function makeImageWater($groundImage,$waterPos=0,$waterImage="",$waterText="",$textFont=5,$textColor="#FF0000"){ 

		$isWaterImage = false; 
		$formatMsg = "暂不支持该文件格式，请用图片处理软件将图片转换为GIF、JPG、PNG格式。"; 

		//读取水印文件 
		if(!empty($waterImage) && is_file($waterImage)) { 
			$isWaterImage = TRUE; 
			$water_info = getimagesize($waterImage); 
			$water_w   = $water_info[0]; //取得水印图片的宽 
			$water_h   = $water_info[1]; //取得水印图片的高 

			switch($water_info[2]) { //取得水印图片的格式 
				case 1:$water_im = imagecreatefromgif($waterImage);break; 
				case 2:$water_im = imagecreatefromjpeg($waterImage);break; 
				case 3:$water_im = imagecreatefrompng($waterImage);break; 
				default:
					throw new TM_Exception(__CLASS__ .": Make image water not supoort image file format");
			} 
		} 

		//读取背景图片 
		if(!empty($groundImage) && is_file($groundImage)) { 
			$ground_info = getimagesize($groundImage); 
			$ground_w   = $ground_info[0]; //取得背景图片的宽 
			$ground_h   = $ground_info[1]; //取得背景图片的高 

			switch($ground_info[2]) { //取得背景图片的格式 
				case 1:$ground_im = imagecreatefromgif($groundImage);break; 
				case 2:$ground_im = imagecreatefromjpeg($groundImage);break; 
				case 3:$ground_im = imagecreatefrompng($groundImage);break; 
				default:die($formatMsg); 
			} 
		} else { 
			throw new TM_Exception(__CLASS__ .": Process image file not exist");
		} 

		//水印位置 
		if($isWaterImage) {//图片水印 
			$w = $water_w; 
			$h = $water_h; 
			$label = "图片的"; 
		} else {//文字水印 
			$temp = imagettfbbox(ceil($textFont*5),0,"./cour.ttf",$waterText);//取得使用 TrueType 字体的文本的范围 
			$w = $temp[2] - $temp[6]; 
			$h = $temp[3] - $temp[7]; 
			unset($temp); 
			$label = "文字区域"; 
		} 
		if( ($ground_w<$w) || ($ground_h<$h) ) 	{
			throw new TM_Exception(__CLASS__ .": Process image file width/height too small, can't make water");
		} 
		switch($waterPos) { 
			case 0://随机 
				$posX = rand(0,($ground_w - $w)); 
				$posY = rand(0,($ground_h - $h)); 
				break; 
			case 1://1为顶端居左 
				$posX = 0; 
				$posY = 0; 
				break; 
			case 2://2为顶端居中 
				$posX = ($ground_w - $w) / 2; 
				$posY = 0; 
				break; 
			case 3://3为顶端居右 
				$posX = $ground_w - $w; 
				$posY = 0; 
				break; 
			case 4://4为中部居左 
				$posX = 0; 
				$posY = ($ground_h - $h) / 2; 
				break; 
			case 5://5为中部居中 
				$posX = ($ground_w - $w) / 2; 
				$posY = ($ground_h - $h) / 2; 
				break; 
			case 6://6为中部居右 
				$posX = $ground_w - $w; 
				$posY = ($ground_h - $h) / 2; 
				break; 
			case 7://7为底端居左 
				$posX = 0; 
				$posY = $ground_h - $h; 
				break; 
			case 8://8为底端居中 
				$posX = ($ground_w - $w) / 2; 
				$posY = $ground_h - $h; 
				break; 
			case 9://9为底端居右 
				$posX = $ground_w - $w; 
				$posY = $ground_h - $h; 
				break; 
			default://随机 
				$posX = rand(0,($ground_w - $w)); 
				$posY = rand(0,($ground_h - $h)); 
				break;   
		} 

		//设定图像的混色模式 
		imagealphablending($ground_im, true); 

		if($isWaterImage) {//图片水印 
			imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $water_w,$water_h);//拷贝水印到目标文件       
		} else {//文字水印 
			if( !empty($textColor) && (strlen($textColor)==7) ) { 
				$R = hexdec(substr($textColor,1,2)); 
				$G = hexdec(substr($textColor,3,2)); 
				$B = hexdec(substr($textColor,5)); 
			} else { 
				throw new TM_Exception(__CLASS__ .": Make water string color format error");
			} 
			imagestring ( $ground_im, $textFont, $posX, $posY, $waterText, imagecolorallocate($ground_im, $R, $G, $B));       
		} 

		//生成水印后的图片 
		@unlink($groundImage); 
		switch($ground_info[2]) {//取得背景图片的格式 
			case 1: imagegif($ground_im,$groundImage); break; 
			case 2: imagejpeg($ground_im,$groundImage); break; 
			case 3: imagepng($ground_im,$groundImage); break; 
			default: die($errorMsg); 
		} 

		//释放内存 
		if(isset($water_info)) unset($water_info); 
		if(isset($water_im)) imagedestroy($water_im); 
		unset($ground_info); 
		imagedestroy($ground_im); 
	} 


	/**
	 * 获取流图像 (一般用来处理Flash传递图片)
	 *
	 */
	public static function makeStreamImage($imgPath, $streamHandler = "php://input"){
		$photo_stream = file_get_contents($streamHandler);
		if ($photo_stream == ''){
			return '';
		}
		file_put_contents($imgPath, $photo_stream);

		$im = imagecreatefromjpeg($imgPath);
		imagejpeg($im, $imgPath);
		return $imgPath;
	}



}



