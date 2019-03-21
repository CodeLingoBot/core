<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * UnZip Class
 *
 * This class is based on a library I found at PHPClasses:
 * http://phpclasses.org/package/2495-PHP-Pack-and-unpack-files-packed-in-ZIP-archives.html
 *
 * The original library is a little rough around the edges so I
 * refactored it and added several additional methods -- Phil Sturgeon
 *
 * This class requires extension ZLib Enabled.
 *
 * @package		Fuel
 * @subpackage	Core
 * @category	Encryption
 * @author		Alexandre Tedeschi
 * @author		Phil Sturgeon
 * @license
 * @version     1.0.0
 */
class Unzip
{
	private $compressed_list = array();

	// List of files in the ZIP
	private $central_dir_list = array();

	// Central dir list... It's a kind of 'extra attributes' for a set of files
	private $end_of_central = array();

	// End of central dir, contains ZIP Comments
	private $info = array();
	private $error = array();
	private $_zip_file = '';
	private $_target_dir = false;
	private $apply_chmod = 0777;
	private $fh;
	private $zip_signature = "\x50\x4b\x03\x04";

	// local file header signature
	private $dir_signature = "\x50\x4b\x01\x02";

	// central dir header signature
	private $central_signature_end = "\x50\x4b\x05\x06";

	// ignore these directories (useless meta data)
	private $_skip_dirs = array('__MACOSX');

	private $_allow_extensions = NULL; // What is allowed out of the zip

	// --------------------------------------------------------------------

	/**
	 * Unzip all files in archive.
	 *
	 * @param  string  $zip_file
	 * @param  string  $target_dir
	 * @param  string  $preserve_filepath
	 * @return array
	 * @throws \FuelException
	 */
	public function extract($zip_file, $target_dir = NULL, $preserve_filepath = TRUE)
	{
		$this->_zip_file = $zip_file;
		$this->_target_dir = $target_dir ? $target_dir : dirname($this->_zip_file);

		if ( ! $files = $this->_list_files())
		{
			throw new \FuelException('ZIP folder was empty.');
		}

		$file_locations = array();
		foreach ($files as $file => $trash)
		{
			$dirname = pathinfo($file, PATHINFO_DIRNAME);
			$extension = pathinfo($file, PATHINFO_EXTENSION);

			$folders = explode('/', $dirname);
			$out_dn = $this->_target_dir . '/' . $dirname;

			// Skip stuff in stupid folders
			if (in_array(current($folders), $this->_skip_dirs))
			{
				continue;
			}

			// Skip any files that are not allowed
			if (is_array($this->_allow_extensions) AND $extension AND ! in_array($extension, $this->_allow_extensions))
			{
				continue;
			}

			if ( ! is_dir($out_dn) AND $preserve_filepath)
			{
				$str = "";
				foreach ($folders as $folder)
				{
					$str = $str ? $str . '/' . $folder : $folder;
					if ( ! is_dir($this->_target_dir . '/' . $str))
					{
						$this->set_debug('Creating folder: ' . $this->_target_dir . '/' . $str);

						if ( ! @mkdir($this->_target_dir . '/' . $str))
						{
							throw new \FuelException('Desitnation path is not writable.');
						}

						// Apply chmod if configured to do so
						$this->apply_chmod AND chmod($this->_target_dir . '/' . $str, $this->apply_chmod);
					}
				}
			}

			if (substr($file, -1, 1) == '/')
			{
				continue;
			}

			$file_location = realpath($this->_target_dir . '/' . ($preserve_filepath ? $file : basename($file)));
			if ($file_location and strpos($file_location, $this->_target_dir) === 0)
			{
				$file_locations[] = $file_location;
				$this->_extract_file($file, $file_location);
			}
			else
			{
				throw new \FuelException('ZIP file attempted to use the zip-slip-vulnerability. Extraction aborted.');
			}
		}

		return $file_locations;
	}

	// --------------------------------------------------------------------

	/**
	 * What extensions do we want out of this ZIP
	 *
	 * @param  string $ext
	 */
	public function allow($ext = NULL)
	{
		$this->_allow_extensions = $ext;
	}

	// --------------------------------------------------------------------

	/**
	 * Show error messages
	 *
	 * @param  string $open
	 * @param  string $close
	 * @return string
	 */
	public function error_string($open = '<p>', $close = '</p>')
	{
		return $open . implode($close . $open, $this->error) . $close;
	}

	// --------------------------------------------------------------------

	/**
	 * Show debug messages
	 *
	 * @param  string $open
	 * @param  string $close
	 * @return string
	 */
	public function debug_string($open = '<p>', $close = '</p>')
	{
		return $open . implode($close . $open, $this->info) . $close;
	}

	// --------------------------------------------------------------------

	/**
	 * Save errors
	 *
	 * @param $string
	 */
	function set_error($string)
	{
		$this->error[] = $string;
	}

	// --------------------------------------------------------------------

	/**
	 * Save debug data
	 *
	 * @param $string
	 */
	function set_debug($string)
	{
		$this->info[] = $string;
	}

	// --------------------------------------------------------------------

	/**
	 * List all files in archive.
	 *
	 * @param   bool $stop_on_file
	 * @return  array
	 * @throws  \FuelException
	 */
	

	// --------------------------------------------------------------------

	/**
	 * Unzip file in archive.
	 *
	 * @param  string      $compressed_file_name
	 * @param  string      $target_file_name
	 * @return int|string|bool
	 * @throws \FuelException
	 */
	

	// --------------------------------------------------------------------

	/**
	 * Free the file resource.
	 */
	public function close()
	{
		// Free the file resource
		if ($this->fh)
		{
			fclose($this->fh);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Free the file resource Automatic destroy.
	 */
	public function __destroy()
	{
		$this->close();
	}

	// --------------------------------------------------------------------

	/**
	 * Uncompress file. And save it to the targetFile.
	 *
	 * @param  mixed   $content
	 * @param  int     $mode
	 * @param  int     $uncompressed_size
	 * @param  string  $target_file_name
	 * @return int|string|bool
	 * @throws \FuelException
	 */
	

	

	

	
}
