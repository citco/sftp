<?php namespace Citco\Sftp;

use SftpExceptions;

class Sftp {

	/**
	 * Main connection to sftp server.
	 *
	 * @var $sftp
	 */
	protected $sftp;

	/**
	 * Constructor of sftp class.
	 *
	 * @param String $url
	 * @param String $username
	 * @param String $password
	 */
	public function __construct($url, $username, $password)
	{
		$this->sftp = $this->connection($url, $username, $password);
	}

	/**
	 * Stablish a connection to sftp server.
	 *
	 * @param String $url
	 * @param String $username
	 * @param String $password
	 * 
	 * @throws SftpNetworkException
	 * @throws SftpAuthenticationException
	 *
	 * @return ssh2_sftp $connection
	 */
	protected function connection($url, $username, $password)
	{
		if (! ($connection = @ssh2_connect($url, "22")))
		{
			throw new SftpNetworkException("Could not connect to sftp server.");
		}

		if (@ssh2_auth_password($connection, $username, $password) === false)
		{
			throw new SftpAuthenticationException("Invalid username or password for sftp.");			
		}

		return ssh2_sftp($connection);
	}

	/**
	 * Upload a file to the given path.
	 *
	 * @param String $content
	 * @param String $file_name
	 * @param String $path
	 * 
	 * @throws SftpGeneralException
	 *
	 * @return boolean
	 */
	public function uploadFile($content, $file_name, $path = '/')
	{
		if (@file_put_contents("ssh2.sftp://{$this->sftp}{$path}{$file_name}", $content) === false)
		{
			throw new SftpGeneralException("Could not upload file to this path : {$path}");
		}

		return true;
	}

	/**
	 * Find the file from given name.
	 *
	 * @param String $file_name
	 * @param String $path
	 * 
	 * @throws SftpFileNotFoundException
	 *
	 * @return String $file
	 */
	public function fileFinder($file_name, $path = '/')
	{
		$destination = "ssh2.sftp://{$this->sftp}{$path}";

		if (is_dir($destination))
		{
			if ($dir = opendir($destination))
			{
				while (($file = readdir($dir)) !== false)
				{
					if (strpos($file, $file_name) !== false)
					{
						closedir($dir);
						return $file;
					}
				}
				closedir($dir);
			}
		}

		throw new SftpFileNotFoundException("Could not Find {$file_name} file from this path : {$path}");
	}

	/**
	 * Download the file and return its content.
	 *
	 * @param String $file_name
	 * @param String $path
	 * 
	 * @throws SftpGeneralException
	 *
	 * @return String $contents
	 */
	public function downloadFile($file_name, $path = '/')
	{
		$file = $this->fileFinder($file_name, $path);
		$destination = "ssh2.sftp://{$this->sftp}{$path}{$file}";

		$stream = @fopen($destination, 'r');
		if (! $stream)
		{
			throw new SftpGeneralException("Could not open {$file_name} file from sftp.");
		}

		$size = filesize($destination);
		$contents = '';
		$read = 0;
		$len = $size;

		while ($read < $len && ($buf = fread($stream, $len - $read)))
		{
			$read += strlen($buf);
			$contents .= $buf;
		}

		@fclose($stream);

		return $contents;
	}
}
