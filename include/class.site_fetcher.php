<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Sven Wilhelm (wilhelm@icecrash.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Base class for the TYPO3 site installer
 *
 * $Id$
 *
 * @author	Sven Wilhelm <wilhelm@icecrash.com>
 * @author	Michael Stucki <michael@typo3.org>
 */
class site_fetcher	{
	var $remote_site = 'http://typo3.sunsite.dk';
	var $type = 'dummy';
	var $version = '3.6.2';

	function site_fetcher()	{
			// startup commands...
		# echo "Nothing";
	}

	function fetch_site($version, $type)	{
			/* valid types are dummy, quickstart, testsite */
		$tmp_type = strtolower($type);

		if($this->check_type($tmp_type)) {
			$this->type = $tmp_type;
		} else {
			die("No such type available\n");
		}

		$this->version = $version;
		$url = $this->build_url($this->version,
			$this->type,
			$this->remote_site);
		$localfile = $this->fetch_data($url);

		if(!empty($localfile)) return $localfile;
		else die("Could not fetch sitepackage\n");
	}

	function check_type($type)	{
		$valid_types = array('dummy','quickstart','testsite');
		foreach($valid_types as $current) {
			if($type == $current) return true;
		}
		return false;
	}

	/**
	 * Version range checking, could be implemented
	 */
	function check_version($version)	{
	}

        /**
	 * remote_site/unix-archives/version/type/type-version.tar.gz
	 */
	function build_url($version, $type, $remote_site)	{
		$url = sprintf("%s/%s/%s/%s/%s-%s.tar.gz",
			$remote_site, 'unix-archives', $version,
			$type, $type, $version);
		return $url;
	}

	/**
	 * Some more checks on the given dir have to be implemented
	 */
	function fetch_data($url, $dir = '/tmp')	{
		$filename = basename($url);
		$tmpname = tempnam($dir, $filename);

			// For Windows this must be rb
		$rfp = fopen($url, "r");
			// For Windows this must be wb
		$lfp = fopen($tmpname, "w");

		if(!$rfp) return '';
		if(!$lfp) return '';

		while (!@feof($rfp)) {
			$buffer = @fread($rfp, 4096);
			fwrite($lfp, $buffer);
			$buffer = '';
		}

		fclose($rfp);
		fclose($lfp);
		return $tmpname;
	}
}

?>