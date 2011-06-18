<?php
/**
 * 
 */
class sprite_img {
	public $width = 0;
	public $height = 0;
	public $rc = NULL;
	private $type = NULL;
	public $name = '';
	public $x, $y;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 */
	public function __construct($file = NULL, $name = '')
	{
		if (extension_loaded('gd') AND imagetypes() & IMG_PNG AND imagetypes() & IMG_GIF AND imagetypes() & IMG_JPG)
			//echo 'Class sprite_img is loadead...'.PHP_EOL;
			true;
		else
			//echo 'Unable loading... sorry';
			exit;
		
		if ($size = @getimagesize($file))
		{
			$this->width = $size[0];
			$this->height = $size[1];
			$this->type = $size[2];
			$this->name = $name;
		}
		else
			throw new Exception('Wrong input file.');
		switch ($this->type)
		{
			case IMAGETYPE_GIF: $this->rc = imagecreatefromgif($file);
				break;
			case IMAGETYPE_JPEG: $this->rc = imagecreatefromjpeg($file);
				break;
			case IMAGETYPE_PNG: $this->rc = imagecreatefrompng($file);
				break;
		}
	}
	
	public function __destruct()
	{
		if ($this->rc)
		imagedestroy($this->rc);
	}

	public function info()
	{
		echo $this->name.' -> '.$this->width.' -> '.$this->height.PHP_EOL;
	}
}
/**
 * 
 */
class sprite {
	private $folder			= ''; // ../imgs/data/
	private $scroll			= 0; // [0. Horizontal; 1. Verticall. 2. auto]
	private $img_background		= FALSE; // If false transperant
	private $img_width		= 0; // 32x*
	private $img_height		= 0; // *x32
	private $img_type		= IMAGETYPE_PNG;
	private $prefix			= ''; // 16_
	private $suffix			= ''; // _ico
	private $output_dir		= ''; // PATH
	private $output_filename	= ''; // filename
	private $demo			= FALSE;
	
	private $rc			= NULL; // IMGS
	private $css			= NULL;
	private $css_main_class		= 'icon';
	private $output_obj		= array();


	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 */
	public function __construct($params = NULL)
	{
		if (extension_loaded('gd') AND imagetypes() & IMG_PNG AND imagetypes() & IMG_GIF AND imagetypes() & IMG_JPG)
			true;
		else
			//echo 'Unable loading... sorry';
			exit;
		if (count($params) > 0)
			$this->initialize($params);
	}
	public function __destruct()
	{
		if ($this->rc)
			imagedestroy($this->rc);
		if ($this->css)
			fclose($this->css);
		if ($this->demo)
			fclose($this->demo);
	}
	

	/**
	 * Initialize Preferences
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 * @return	void
	 */
	public function initialize($params = array())
	{
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				if (isset($this->$key))
					$this->$key = $val;
			}
		}
	}
	public function generate($output_filename = '',$output_dir = NULL)
	{
		if ($output_dir)
			$this->output_dir = $output_dir;
		if ($output_filename)
			$this->output_filename = $output_filename;
		//empty
		$this->output_obj = array();
		
		$files = scandir($this->folder);
		foreach ($files as $file)
		{
			if ($file == '.' OR $file == '..')
				continue;
			try
			{
				$this->output_obj[$file] = new sprite_img($this->folder.$file, $this->prefix.preg_replace('/(.+)\..*$/', '$1', $file).$this->suffix);
				//echo "Init Ok $file".PHP_EOL;
			}
			catch (Exception $exc)
			{
				/*$this->output_obj[$file]->des*/
				unset($this->output_obj[$file]);
				//echo 'Error file not images.'.PHP_EOL;
			}
		}
		/*Sorting*/
		//usort($this->output_obj, array('sprite','array_cmp'));
		
		$this->create_size();
		/* Create img resource */
		$this->rc = imagecreatetruecolor($this->img_width,$this->img_height);
		if ($this->img_background)
		{
			$this->img_background = str_replace('#', '', $this->img_background);
			switch (strlen($this->img_background))
			{
				case 3:
					$this->img_background .= $this->img_background;
					break;
				case 6: break;
				default: $this->img_background = '000000';
			}
			imagefill($this->rc, 0, 0, imagecolorallocate($this->rc, hexdec(substr($this->img_background, 0, 2)), hexdec(substr($this->img_background, 2, 2)), hexdec(substr($this->img_background, 4, 2))));
		}
		else
			imagefill($this->rc, 0, 0, imagecolorallocatealpha ($this->rc, 0, 0, 0, 127));
		
		/* Create css file */
		$this->css = fopen($this->output_dir.$this->output_filename.'.css','w');
		fwrite($this->css, '.'.$this->css_main_class.' {background: url("'.$this->output_filename.'.png"); display: block;}');
		/* Write demo file*/
		if ($this->demo)
		{
			$this->demo = fopen($this->output_dir.$this->output_filename.'.html','w');
			fwrite($this->demo, '
				<hrml>
					<head>
						<title>Demo</title>
						<link rel="stylesheet" type="text/css" href="'.$this->output_filename.'.css">
					</head>
				<body>');
		}
		$x_curr = 0;
		$y_curr = 0;
		foreach ($this->output_obj as $file => $obj)
		{
			$obj->x = $x_curr;
			$obj->y = $y_curr;
			/* Write to css*/
			fwrite($this->css, ".$obj->name {background-position: ".$x_curr."px ".$y_curr."px;}".PHP_EOL);
			/* Write demo*/
			if ($this->demo)
				fwrite($this->demo, "<span style='width: $obj->width; height: $obj->height' class='$obj->name $this->css_main_class'></span>");
			
			//echo ".$obj->name {background-position: $x_curr px $y_curr px;}".PHP_EOL;
			imagecopyresized($this->rc, $obj->rc, $x_curr,$y_curr, 0,0, $obj->width, $obj->height, $obj->width,$obj->height);
			if ($this->scroll == 0)
			{
				$x_curr += $obj->width;
			}
			if ($this->scroll == 1)
			{
				$y_curr += $obj->height;
			}
		}
		switch ($this->img_type)
		{
			case IMAGETYPE_GIF: imagegif($this->rc, $this->output_dir.$this->output_filename.'.gif');
				break;
			case IMAGETYPE_JPEG: imagejpeg($this->rc, $this->output_dir.$this->output_filename.'.jpg');
				break;
			case IMAGETYPE_PNG: 
				imagesavealpha($this->rc, true);
				imagepng($this->rc, $this->output_dir.$this->output_filename.'.png');
				break;
		}
		
		return true;
	}
	private function create_size()
	{
		$x_curr = 0;
		$y_curr = 0;
		foreach ($this->output_obj as $file => $obj)
		{
			if ($this->scroll == 0)
			{
				$x_curr += $obj->width;
				if ($y_curr < $obj->height)
					$y_curr = $obj->height;
			}
			if ($this->scroll == 1)
			{
				$y_curr += $obj->height;
				if ($x_curr < $obj->width)
					$x_curr = $obj->width;
			}
		}
		$this->img_width = $x_curr;
		$this->img_height = $y_curr;
	}
	public function array_cmp($a, $b)
	{
		if ($a->width == $b->width)
			return 0;
		else
			return ($a > $b) ? -1 : +1;
	}
}