<?php

namespace Kilvin\Cp;

class FileBrowser {

    public $upload_path		= '../uploads/';
    public $filelist		= [];
    public $ignore			= [];
    public $width   		= '';
    public $height  		= '';
    public $imgtype 		= '';
    public $images_only		= false;
    public $cutoff_date		= false;
    public $show_errors		= true;
    public $recursive		= true;

    public $skippable		= [];



    // ------------------------------------
    //  Constructor
    // ------------------------------------

    function __construct()
    {
        // Files with these prefixes we will automatically assume are not images
        $this->skippable = [
        	'mp2', 'mp3', 'm4a', 'm4p', 'asf', 'mov',
			'mpeg', 'mpg', 'wav', 'wma', 'wmv', 'aif',
			'aiff', 'movie', 'dvi', 'pdf', 'avi', 'flv', 'swf', 'm4v'
		];
    }

    // ------------------------------------
    //  List of Files
    // ------------------------------------

    function create_filelist($folder='')
    {
		$this->set_cutoff_date();

        $ignore = (count($this->ignore) > 0) ? TRUE : false;

		if ( ! $handle = @opendir($this->upload_path))
		{
			if ($this->show_errors == FALSE)
			{
				return false;
			}

			return Cp::errorMessage(__('path_does_not_exist'));
		}

		$filedatum = [];
		$fileorder = [];

		while (false !== ($file = @readdir($handle)))
		{
			if (is_file($this->upload_path.$file) && substr($file,0,1) != '.' && $file != "index.html")
			{
				if ($this->cutoff_date > 0)
				{
					if (@filemtime($this->upload_path.$file) < $this->cutoff_date)
						continue;
				}

				$skip = false;

				// ignore the file if the name sans extension ends in the string to be shunned
				if (sizeof($ignore) > 0)
				{
					foreach ($this->ignore as $shun)
					{
						$name = array_shift($temp = explode('.', $file));

						if (substr($name, - strlen($shun)) == $shun)
						{
							$skip = true;
							continue;
						}
					}
				}

				if ($skip === TRUE)
				{
					continue;
				}

				$filedatum[] = array($file, $folder);
				$fileorder[] = $folder.$file;
			}
			elseif(@is_dir($this->upload_path.$file) && substr($file,0,1) != '.')
			{
				if ($this->recursive == TRUE)
				{
					$old_path = $this->upload_path;
					$this->upload_path = $this->upload_path.$file.'/';
					$this->create_filelist($folder.$file.'/');
					$this->upload_path = $old_path;
				}
			}
		}

		if (count($filedatum) > 0)
		{
			$filearray = [];

			natcasesort($fileorder);

			foreach($fileorder as $key => $value)
			{
				$filearray[$key] = $filedatum[$key];
			}

			unset($fileorder);
			unset($filedatum);

			foreach($filearray as $val)
			{
				if (FALSE === $this->image_properties($this->upload_path.$val[0]))
				{
					if ($this->images_only == TRUE)
						continue;

					$this->filelist[] = array('type' 	=> "other",
											  'name' 	=> $val[1].$val[0],
											  'folder'	=> $val[1]
											 );
				}
				else
				{
					if ($this->images_only == TRUE)
					{
						if ($this->imgtype != 1 AND $this->imgtype != 2 AND $this->imgtype != 3)
							continue;
					}

					$this->filelist[] = array('type' 	=> "image",
											  'name' 	=> $val[1].$val[0],
											  'folder'	=> $val[1],
											  'width'   => $this->width,
											  'height'	=> $this->height,
											  'imgtype'	=> $this->imgtype
											 );
				}
			}

		}


		closedir($handle);
		return true;
    }


    // ------------------------------------
    //  Sets a Unix time based on the parameter
    // ------------------------------------

	function set_cutoff_date()
	{
		if ($this->cutoff_date == FALSE OR $this->cutoff_date < 1)
		{
			$this->cutoff_date = false;
			return;
		}

		$min = ($this->cutoff_date > 1) ? $this->cutoff_date * (60*60*24) : 0;
		$midnight = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		$this->cutoff_date = $midnight - $min;
	}


    // ------------------------------------
    //  Set upload directory path
    // ------------------------------------

    function set_upload_path($path)
    {
		if (substr($path, -1) != '/' AND substr($path, -1) != '\\')
		{
			$path .= '/';
		}

        if ( ! @is_dir($path))
        {
			if ($this->show_errors == FALSE)
			{
				return false;
			}

            return Cp::errorMessage(__('path_does_not_exist'));
        }
        else
        {
            $this->upload_path = $path;
        }
    }


    // ------------------------------------
    //  Get image properties
    // ------------------------------------

    function image_properties($file)
    {
  	  	if (function_exists('getimagesize'))
        {
        	foreach($this->skippable as $suffix)
        	{
        		if (substr(strtolower($file), -strlen($suffix)) == $suffix)
        		{
        			return false;
        		}
        	}

            if ( ! $D = @getimagesize($file))
            {
            	return false;
            }

            $this->width   = $D['0'];
            $this->height  = $D['1'];
            $this->imgtype = $D['2'];

            return true;
        }

        return false;
    }
}
