<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004, 2005 Michael Stucki (michael@typo3.org),
*                 Christian Leutloff <leutloff@debian.org>
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
 * @author	 Michael Stucki <michael@typo3.org>
 * @author	 Christian Leutloff <leutloff@debian.org>
 *
 * Scroll to the end to find the start of this script!
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
    'T3INST_INSTALLTOOLPASSWORD' => 'bacb98acf97e0b6112b1d1b650b84971', // Default password is "joh316" 
    'T3INST_SITENAME' => 'Blank DUMMY',
    '' => '',
    '' => '',
    '' => '',
    )

/**
 * helper function: ensure that dirs ends with a slash
 */
function getDirWithFinalSlash($dir)	{
	if (substr($dir, strlen($dir)-1, strlen($dir)) === '/')	return $dir;
	return $dir.'/';
}

/**
 * helper function: ensure that dirs starts not with a slash
 */
function getDirWithOutTrailingSlash($dir)	{    
	if ($dir[0] === '/')	return substr($dir, 1, strlen($dir));
    	return $dir;
}

class site_installer	{

	// TODO: We could also grep through httpd.conf in case that this name was changed
	// wwwgroup=`cat /etc/apache/httpd.conf | grep "^[Gg]roup\ [^\ ]*$" | awk '{ print $NF; }'`

	var $rootDir = "";		// root directory used to operate -another than the standard / is useful in chroot environments and to test different directory szenarios
	var $typo3SourceDir  = "";	// directory with the typo3 source
	var $destinationDir = "";	// web directory where typo3 will be installed and used
	var $fixPermissions = false;	// if true, permissions and links will be modified
	var $useAlwaysLatest = false;	// if true, symlink to typo3_src will be changed to use latest available typo3_src
	var $wwwuser = DEFAULT_WWW_USER;	// name of the user that runs the Apache webserver
	var $wwwgroup = DEFAULT_WWW_GROUP;	// name of the group that runs the Apache webserver
	var $errors = 0;		// count errors during startup
	var $errmsg = "";		// display message if errors are detected
	var $dryrun = false;		// dryrun shows the actions that would be done

	function setAlwaysLatest($value)	{
		$this->useAlwaysLatest=$value;
	}

	function setWWWUser($value)	{
		$this->wwwuser = $value;
	}

	function setWWWGroup($value)	{
		$this->wwwgroup = $value;
	}

	function fixPermissions($value)	{
		$this->fixPermissions = $value;
	}

	function setDestinationDir($value)	{
		// Convert disallowed characters to underscores
		//    this->destinationDir=`echo $this->destinationDir | sed -e 's/[^-\~_./a-zA-Z0-9]\+/_/g'`
		$this->destinationDir = getDirWithFinalSlash($value);
	}

	function getDestinationDir()	{
		return $this->destinationDir;
	}


        function setSourceDir($value)	{
		$this->typo3SourceDir = getDirWithFinalSlash($value);
		$this->debug(1, 'setSourceDir: typo3SourceDir dir: '.$this->typo3SourceDir.'--'.$value);
	}
    
	function getSourceDir()	{
		return $this->sourceDir;
	}


	function setRootDir($value)	{
		$this->rootDir = getDirWithFinalSlash($value);
		$this->debug(1, 'setRootDir root dir: '.$this->rootDir.'--'.$value."\n");
	}

	function setDryRun($value)	{
		if ($value === false) $this->dryrun = false;
		else	$this->dryrun = true;
	}

	/**
	 * Checks on startup, search for required directories and appropriate permissions
	 */
	function startUpCheck()	{
		$this->debug(1, 'root dir: '.$this->rootDir.' - '.
			'typo3SourceDir dir: '.$this->typo3SourceDir);

			// check and fix root Dir
		if (empty($this->rootDir) || ($this->rootDir == "")) {
			$this->info('using Default Root Dir ('.DEFAULT_ROOT_DIR.')');
			$this->rootDir = DEFAULT_ROOT_DIR;
		}

		if (! is_dir($this->rootDir)) {
			$this->error('Root Dir is not a directory ('.$this->rootDir.')');
			$this->errors++;
		}

			// determine source dir of TYPO3
		if (empty($this->typo3SourceDir) || ($this->typo3SourceDir == "")) {

			$this->info('using Default Typo 3 Source Dir ('.
				$this->rootDir.DEFAULT_TYPO3SOURCE_DIR.')');
			$this->typo3SourceDir = $this->rootDir.DEFAULT_TYPO3SOURCE_DIR;

		} else {
                        $this->typo3SourceDir = getDirWithOutTrailingSlash($this->typo3SourceDir);
			$this->typo3SourceDir = $this->rootDir.$this->typo3SourceDir;

			if (! is_dir($this->typo3SourceDir)) {
				$this->info('using Default Typo3 Source Dir ('.
					$this->rootDir.DEFAULT_TYPO3SOURCE_DIR.')');
				$this->typo3SourceDir = $this->rootDir.DEFAULT_TYPO3SOURCE_DIR;
			}
		}

		if (! is_dir($this->typo3SourceDir)) {
			$this->error('Could not find the Typo3 Source Directory (last tried was: '.
				$this->typo3SourceDir.'! '.
				'The option -s could be used to provide a hint.');
			$this->errors++;
		}

		/*
		 * We always use /var/lib/typo3/latest to get the version number that will be used for installation
		 * Check if /var/lib/typo3/latest is a symlink
		 */
		if (is_link($this->typo3SourceDir.'latest')) {
				// Get the current version which will be used by default
				// $TYPO3_SOURCE=`ls -l /var/lib/typo3/latest | awk '{ print $NF; }'`;
			$this->typo3SourceDir = readlink($this->typo3SourceDir.'latest');

				// Maybe /var/lib/typo3/latest does not point to the absolute path
			if (!file_exists($this->typo3SourceDir))	$this->typo3SourceDir = $this->rootDir.'var/lib/typo3/'.$this->typo3SourceDir;

				// If the updated still doesn't exist: Abort.
			if ( ! file_exists($this->typo3SourceDir))	$this->errors++;
		} else {

			$this->error('Could not find the link to the latest TYPO3 Source Directory (last tried was: '.
				$this->typo3SourceDir.'latest! '.
				'The option -s could be used to provide a hint.');
			$this->errors++;
		}

			// check for permissions to change owner/mod root
		if ($this->fixPermissions && ($_ENV['USER'] != 'root') && ($this->dryrun == false)) {
			$this->error('=============================================================================='."\n".
				'You are not logged in as root'."\n".
				'It will not be possible to change the group ownership to '.$this->wwwgroup."\n".
				'=============================================================================='."\n\n");
			$this->errors++;
		}
	}

	/**
	 * abort the script with error message
	 */
	function abortIfErrors()	{
		echo $this->errmsg;
		$this->errmsg = "";
		if ( $this->errors > 0 ) {
			echo "Aborted.\n";
			exit (1);
		}
	}

	/**
	 * check and fix destination dir
	 */
	function checkDestinationDir()	{
		if (empty($this->destinationDir) || ($this->destinationDir == "")) {
			$this->info('using Default Destination Dir ('.$this->rootDir.DEFAULT_DESTINATION_DIR.')');
			$this->destinationDir = $this->rootDir.DEFAULT_DESTINATION_DIR;
		}

		if (! is_dir($this->destinationDir)) {
			$this->error('Destination Directory '.$this->destinationDir.' is not a directory or missing at all.');
			$this->errors++;
		}

		if ( ! file_exists( $this->destinationDir.'index.php')) {
			$this->error('Site seems to be incorrect (missing '.
				$this->destinationDir.'index.php)!');
			$this->errors++;

			$this->info('The directory you tried to use was:'."\n".
				$this->getDestinationDir()."\n\n".
				'Make sure you create the directory structure with this script.'."\n".
				'Use this command to do so:'."\n".
				'  typo3-site-installer -d '.$this->destinationDir."\n\n".
				'If you think this is a bug, please contact the author.'."\n".
				'=============================================================================='."\n");
		}
	}

	/**
	 * Install a clean dummysite
	 */
	function install_site()	{
/*
    // Test if directory is writable
    if [ -w `dirname $this->getDestinationDir()` ]; then

        // Test if target directory already exists (may also be a file)
        if [ -e $this->getDestinationDir() ]; then

            echo
            '=============================================================================="
            'Directory $this->getDestinationDir() exists!"
            '=============================================================================="
            echo
            'Aborted."
            exit 1

        fi

        // Create the directory structure
        mkdir $this->getDestinationDir()
        mkdir $this->getDestinationDir()/fileadmin
        mkdir $this->getDestinationDir()/fileadmin/_temp_
        mkdir $this->getDestinationDir()/fileadmin/user_upload
        mkdir $this->getDestinationDir()/fileadmin/user_upload/_temp_
        mkdir $this->getDestinationDir()/typo3conf
        mkdir $this->getDestinationDir()/typo3conf/ext
        mkdir $this->getDestinationDir()/typo3temp
        mkdir $this->getDestinationDir()/uploads
        mkdir $this->getDestinationDir()/uploads/dmail_att
        mkdir $this->getDestinationDir()/uploads/media
        mkdir $this->getDestinationDir()/uploads/pics
        mkdir $this->getDestinationDir()/uploads/tf

        // Create index.html for directories that should not be shown
        cat <<EOF > $this->getDestinationDir()/uploads/index.html
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
    <TITLE></TITLE>
    <META http-equiv=Refresh Content="0; Url=../">
</HEAD>
</HTML>
EOF

        // Create a symlink to this file in every subdirectory
        cp $this->getDestinationDir()/uploads/index.html $this->getDestinationDir()/uploads/dmail_att/
        cp $this->getDestinationDir()/uploads/index.html $this->getDestinationDir()/uploads/media/
        cp $this->getDestinationDir()/uploads/index.html $this->getDestinationDir()/uploads/pics/
        cp $this->getDestinationDir()/uploads/index.html $this->getDestinationDir()/uploads/tf/
        cp $this->getDestinationDir()/uploads/index.html $this->getDestinationDir()/typo3conf/

        // Copy some other files from /usr/share/doc/typo3-site-installer
        cp /usr/share/doc/typo3-site-installer/_.htaccess $this->getDestinationDir()/
        cp /usr/share/doc/typo3-site-installer/clear.gif $this->getDestinationDir()/
        gunzip -c /usr/share/doc/typo3-site-installer/changelog.gz > $this->getDestinationDir()/changelog
        gunzip -c /usr/share/doc/typo3-site-installer/database.sql.gz > $this->getDestinationDir()/typo3conf/database.sql

        // Copy the localconf.php into typo3conf/
        cp /usr/share/doc/typo3-base/examples/localconf.php $this->getDestinationDir()/typo3conf/

        // Create a few symlinks
        ln -s $TYPO3_SOURCE $this->getDestinationDir()/typo3_src
        ln -s typo3_src/tslib $this->getDestinationDir()/
        ln -s typo3_src/t3lib $this->getDestinationDir()/
        ln -s typo3_src/typo3 $this->getDestinationDir()/
        ln -s tslib/media $this->getDestinationDir()/
        ln -s tslib/showpic.php $this->getDestinationDir()/
        ln -s tslib/index_ts.php $this->getDestinationDir()/index.php


    else

        echo
        '=============================================================================="
        'Error: The target directory cannot be created."
        'Please check your settings."
        'Note that the parent directory needs to be existing!"
        '=============================================================================="
        echo
        'Aborted."
        exit 1

    fi
*/
        }
    

	/**
	 * fix symlinks
	 */
	function fixSymlinks()	{
		/*
			// Point typo3_src to /var/lib/typo3/latest
		if [ $useAlwaysLatest == 1 ]; then $TYPO3_SOURCE=/usr/share/typo3/latest; fi
		*/
	}

	/**
	 * search trough the directory including the sub directories and set
	 * the owner and the group of the files and directories
	 */
	function setToOwnerGroupRecursive($dir)	{
            	$dir = getDirWithFinalSlash($dir);
		$this->debug(2, 'setToOwnerGroupRecursive: ', $dir, "\n");
		$result = true;

			// Open a known directory, and proceed to read its contents
		if (is_dir($dir)) {
			if (($dh = opendir($dir)) !== false) {
				while (($file = readdir($dh)) !== false) {
                                        if (!$this->isHandlebarFile($dir, $file))	continue;
                                        $dirfile = $dir.$file;
                                        $this->debug(3, 'setToOwnerGroupRecursive dirfile: ', $dirfile, "\n");
                                        $dirfile = $this->followLink($dir, $dirfile);
                                        if ($dirfile === false)
                                            continue;
                                        if ((is_file($dirfile)) || (is_dir($dirfile))) {
						if (is_dir($dirfile)) {
							$this->setToOwnerGroup($dirfile);
							$this->setToOwnerGroupRecursive($dirfile);
						} else {
							$this->setToOwnerGroup($dirfile);
						}
                                        } else
                                            $this->warn('Unexpected type of file for file '.$dirfile.'. Should not happen.');
				}
				closedir($dh);
			}
		} else {
			$this->error($dir.' is not a directory! Should not happen.');
			$result = false;
		}

		return $result;
	}

        /**
         * set Owner and Group of the given file (including the paht)
         */
	function setToOwnerGroup($dirfile)	{
		$result = true;

		if ($this->dryrun == true) {
			$this->info('chown '.$this->wwwuser.':'.$this->wwwgroup.' '.$dirfile);
		} else {
			if (!chown($dirfile, $this->wwwuser)) {
				$this->error('chown '.$this->wwwuser.' '.$dirfile.' failed!');
				$result = false;
			}

			if (!chgrp($dirfile, $this->wwwgroup)) {
				$this->error('chgrp '.$this->wwwgroup.' '.$dirfile.' failed!');
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * search trough the directory including the sub directories and set
	 * the owner and the group of the files and directories
	 */
	function setDirFileModeRecursive($dir, $dirmod, $filemod)	{
    		$dir = getDirWithFinalSlash($dir);
    		$this->debug(2, 'setDirFileModeRecursive: '.$dir);
		$result = true;

			// Open a known directory, and proceed to read its contents
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
                                        if (!$this->isHandlebarFile($dir, $file))	continue;
                                        $dirfile = $dir.$file;
                                        $this->debug(3, 'setDirFileModeRecursive dirfile: ', $dirfile, "\n");
                                        $dirfile = $this->followLink($dir, $dirfile);
                                        if ($dirfile === false)
                                            continue;

                                        if ((is_file($dirfile)) || (is_dir($dirfile))) {
						if (is_dir($dirfile)) {
							$this->setDirMode($dirfile, $dirmod);
							$this->setDirFileModeRecursive($dirfile, $dirmod, $filemod);
						} else {
							$this->setFileMode($dirfile, $filemod);
						}
					} else 
                                            $this->warn('Unexpected type of file for file '.$dirfile.'. Should not happen.');
				}

				closedir($dh);
			}

		} else {

			$this->error($dir.' is not a directory!');
			$result = false;
		}

		return $result;
	}


        /**
         * change mode of a file.
         */
	function setFileMode($dirfile, $filemod)	{
		$this->debug(2, 'setFileMode: ', $dirfile, "\n");
		$result = true;

		if (file_exists($dirfile)) {
			if ($this->dryrun == true) {
                            $this->info('chmod '.decoct($filemod).' '.$dirfile);
			} else {
				if (!chmod($dirfile, $filemod)) {
					$this->error('chmod '.$filemod.' '.$dirfile.' failed!');
					$result = false;
				}
			}
		} else {
			$this->error('chmod '.$filemod.' '.$dirfile.' failed, because file does not exist!');
			$result = false;
		}
		return $result;
	}


        /**
         * change mode of a directory.
         */
	function setDirMode($dirfile, $dirmod)	{
		$this->debug(2, 'setDirMode: ', $dirfile, "\n");
		$result = true;

		if (is_dir($dirfile)) {
			if ($this->dryrun == true) {
				$this->info('chmod '.decoct($dirmod).' '.$dirfile);
			} else {
				if (!chmod($dirfile, $dirmod)) {
					$this->error('chmod '.$dirmod.' '.$dirfile.' failed!');
					$result = false;
				}
			}
		} else {

			$this->error('chmod '.$dirmod.' '.$dirfile.' not changed, because it is not a directory!');
			$result = false;
		}
		return $result;
	}


        /**
         * return true, if directory should be ommitted (like /usr/bin/
         * or /usr/sbin/)
         * no directory with the above sequence is allowed!
         */
        function isDontTouchDir($dirfile) {
            $donttouchdirs = array('/bin/', '/sbin/'); // matches '/usr/bin/', '/usr/sbin/', '/usr/local/bin/', '/usr/local/sbin/', too
            foreach ($donttouchdirs as $key => $donttouchdir) {   
                if (strpos($dirfile, $donttouchdir) !== false) return true;
            }
        }

    

        /**
         * return true, if file should be processed further
         */
        function isHandlebarFile($dir, $file) {
            if (empty($file) || ($file == '.') || ($file == '..'))	return false;

            $dirfile = $dir.$file;
            if (!(is_file($dirfile) || is_dir($dirfile) || is_link($dirfile))) {
                if (!file_exists($dirfile))
                    $this->warn('File '.$dirfile.' does not exist. This should not happen.'. 
                                ' Please fix the symbolic link and rerun this script.');
                else
                    $this->warn('Unexpected type of file for '.$dirfile.
                                '. This is should be handled in the script.');
                return false;
            }
            return true;
        }

        /**
         * follows the link, if it is one.
         * @returns the real file or directory
         */
        function followLink($dir, $dirfile) {
            if (!is_link($dirfile))
                return $dirfile;
            
            do {
                $tmplink = readlink($dirfile);
                if ($tmplink[0] === '/') { // absolute link
                    $dirfile = $tmplink;
                    if (is_link($dirfile)) $dir = getDirWithFinalSlash(dirname($dirfile));
                }
                else
                    $dirfile = $dir.$tmplink;
                if ($this->isDontTouchDir($dirfile)) {
                    $this->info('The file or directory '.$dirfile.
                                ' is intentionally ommited.');
                    return false;
                }
            } while  ((!empty($dirfile)) && (is_link($dirfile)));
            if (empty($dirfile)) {
                $this->warn('Empty file name '.$dirfile.' is unusual.'.
                            ' Please fix the directory layout.');
                return false;
            }
            return $dirfile;
        }
    
    
	/**
	 * fix some permissions
	 */
	function fix_permissions()	{
		$this->checkDestinationDir();
		$this->abortIfErrors();

		$this->debug(0, 'User is ',$_ENV['USER'],"\n");

		if (($_ENV['USER'] === 'root') || ($this->dryrun == true)) {

			/*
			chgrp -R $wwwgroup $this->getDestinationDir()
			find $this->getDestinationDir() -type f -exec chmod 640 {} \;
			find $this->getDestinationDir() -type d -exec chmod 750 {} \;

			chmod -R g+w $this->getDestinationDir()/fileadmin
			chmod -R g+w $this->getDestinationDir()/typo3conf
			chmod -R g+w $this->getDestinationDir()/typo3temp
			chmod -R g+w $this->getDestinationDir()/uploads
			*/
			$this->setToOwnerGroupRecursive($this->getDestinationDir());
			$this->setDirFileModeRecursive($this->getDestinationDir(), 0750, 0640);
			$this->setDirFileModeRecursive($this->getDestinationDir().'fileadmin/', 0770, 0660);
			$this->setDirFileModeRecursive($this->getDestinationDir().'typo3conf/', 0770, 0660);
			$this->setDirFileModeRecursive($this->getDestinationDir().'typo3temp/', 0770, 0660);
			$this->setDirFileModeRecursive($this->getDestinationDir().'uploads/', 0770, 0660);
                        
			if (file_exists($this->getDestinationDir().'changelog'))	$this->setFileMode($this->getDestinationDir(), 'changelog', 0600, 0600);

		} else {

			/*
			find $this->getDestinationDir() -type f -exec chmod 666 {} \;
			find $this->getDestinationDir() -type d -exec chmod 777 {} \;
			chmod 600 $this->getDestinationDir()/changelog
			*/
			$this->setDirFileModeRecursive($this->getDestinationDir(), 0777, 0666);
			if (file_exists($this->getDestinationDir().'changelog'))	$this->setFileMode($this->getDestinationDir(), 'changelog', 0600, 0600);

			$this->info('=============================================================================='."\n".
				'You are not logged in as root.'."\n".
				'It was not possible to change the ownership to '.
				$this->wwwuser.':'.$this->wwwgroup."\n".
				"\n".
				'The permissions have therefore been changed to minimal security (everybody can'."\n".
				'read and write).'."\n".
				"\n".
				'Though your site will be working with these settings, you are strongly'."\n".
				'encouraged to fix that problem by running this script again but with root'."\n".
				'permissions and using the option --fix-permissions:'."\n".
				"\n".
				'The following command can be used to do so:'."\n".
				'  typo3-site-installer -d '.$this->destinationDir.' --fix-permissions');
		}


		$this->info('=============================================================================='."\n".
			'Finished. But there is still something to do:'."\n".
			"\n".
			'First: It has to be ensured that '.$this->destinationDir.' is accessable through the webserver.'."\n".
			'(On a Debian system more information is available in /usr/share/doc/typo3/README.Debian.gz. '."\n".
			'If nothing distribution specific is provided and you do  not know what to do, '."\n".
			'a solution may be to move this directory to /var/www/. )'."\n".
			"\n".
			"\n".
			'Next, the following steps are neccessary:'."\n".
			"\n".
			'  * In '.$this->destinationDir.'typo3/install/index.php:'."\n".
			'    Commenting out line 45 (the \'die()\' function call)'."\n".
			'  * Using a browser to point to the location just created and to complete the setup'."\n".
			'  * Removing the comment from above'."\n".
			"\n".
			'Note: the image settings should already be optimized for Debian Woody/Sarge and Ubuntu Hoary.'."\n".
			"\n".
			'Make sure to read the README file for later install instructions.'."\n".
			'=============================================================================='."\n");

		if ($this->dryrun == true)	$this->info('Nothing done - Program executed as dry run.');
		else			$this->info('Successfully done.');
	}

	/**
	 * register msg for output
	 */
	function error($msg)	{
		$this->errmsg .= 'ERROR: '.$msg."\n";
	}

	/**
	 * register msg for output
	 */
	function warn($msg)	{
		$this->errmsg .= 'WARNING: '.$msg."\n";
	}

	/**
	 * register msg for output
	 */
	function info($msg)	{
		$this->errmsg .= $msg."\n";
	}
    
	/**
	 * register msg for output, if $debug > $level
	 */
	function debug($level, $msg)	{
		global $debug;
		if ($debug > $level)	$this->errmsg .= 'Debug: '.$msg."\n";
                //echo  'Debug: '.$msg."\n";
	}

    
       /**
	 * execute the necesssary functions - main method
	 */
	function doActions()	{
		if ($this->fixPermissions) {
			$this->fix_permissions();
		} else {
			$this->install_site();
			$this->fix_permissions();
		}

		/*
		 * Todo: Run the site fetcher
		 *

		require($INCLUDE_DIR.'class.site_fetcher.php');
		$fetcher = new site_fetcher;
		$res = $fetcher->fetch_site('3.6.2', 'dummy');

cat /usr/src/typo3-testsite-3.7.0/debian/append2localconf.php | sed -e 's/typo3-testsite/typo3-quickstart/g' > debian/append2localconf.php
		*/
		$this->abortIfErrors();
	}

}

?>
