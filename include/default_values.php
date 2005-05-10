<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Christian Leutloff <leutloff@debian.org>
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
 * Default values used by the TYPO3 site installer
 *
 * $Id$
 *
 * @author	 Christian Leutloff <leutloff@debian.org>
 *
 */


/**
 * Default values
 */
define('DEFAULT_ROOT_DIR', '/');
define('DEFAULT_TYPO3SOURCE_DIR', 'usr/share/typo3/');
define('DEFAULT_DESTINATION_DIR', 'var/lib/typo3/');
define('DEFAULT_WWW_USER', 'www-data');
define('DEFAULT_WWW_GROUP', 'www-data');

$defaulttemplate = array(
    // localconf.php
    'T3INST_INSTALLTOOLPASSWORD' => 'bacb98acf97e0b6112b1d1b650b84971', // Default password is "joh316" 
    'T3INST_SITENAME' => 'Blank DUMMY',
    // localconf.php + apache.conf
    'T3INST_EXECDIR' => '/var/lib/typo3-dummy/execdir/',
    // apache.conf - defaulthost 
    'T3INST_URLDIR' => '/typo3/',
    // apache.conf - virtualhost 
    'T3INST_SERVERNAME' => 'typo3.localnet',
    // apache.conf - virtualhost + defaulthost 
    'T3INST_BASEDIR' => '/var/lib/typo3-dummy/',
    'T3INST_T3SRCDIR' => '/usr/share/typo3/typo3_src-3.7/',
    'T3INST_T3DBDIR' => '/usr/share/typo3-dummy/',
    );




?>
