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

	var $rootDir = '';		// root directory used to operate -another than the standard / is useful in chroot environments and to test different directory szenarios
	var $typo3SourceDir  = '';	// directory with the typo3 source
	var $destinationDir = '';	// web directory where typo3 will be installed and used
	var $fixPermissions = false;	// if true, permissions and links will be modified
	var $useAlwaysLatest = false;	// if true, symlink to typo3_src will be changed to use latest available typo3_src
	var $wwwuser = DEFAULT_WWW_USER;	// name of the user that runs the Apache webserver
	var $wwwgroup = DEFAULT_WWW_GROUP;	// name of the group that runs the Apache webserver
	var $errors = 0;		// count errors during startup
	var $errmsg = "";		// display message if errors are detected
	var $dryrun = false;		// dryrun shows the actions that would be done
            
	function getVersion()	{
            //return '$Revision$';
            return '0.90';
	}
    
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
		return $this->typo3SourceDir;
	}


	function setRootDir($value)	{
		$this->rootDir = getDirWithFinalSlash($value);
		$this->debug(1, 'setRootDir root dir: '.$this->rootDir.'--'.$value."\n");
	}
    
	function getRootDir()	{
		return $this->rootDir;
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
		}

		/*
		 * Uses /usr/share/typo3/latest and
		 * /var/lib/typo3/latest as default/backup solution to get the
		 * version number that will be used for installation.
		 */
		if (is_link($this->typo3SourceDir.'latest')) {
				// Get the current version which will be used by default
				// $TYPO3_SOURCE=`ls -l /var/lib/typo3/latest | awk '{ print $NF; }'`;
                        $this->setSourceDir(readlink($this->typo3SourceDir.'latest'));
                        if ($this->typo3SourceDir[0] !== '/')  // relative link
                            $this->setSourceDir($this->getRootDir().$this->typo3SourceDir);
                
                        echo ' $this->typo3SourceDir: ', $this->typo3SourceDir;
                        
				// Maybe /var/lib/typo3/latest does not point to the absolute path
			if (!file_exists($this->typo3SourceDir))	$this->typo3SourceDir = $this->rootDir.'var/lib/typo3/'.$this->typo3SourceDir;
				// Maybe /var/lib/typo3/latest does not point to the absolute path
			if (!file_exists($this->typo3SourceDir))	$this->typo3SourceDir = $this->rootDir.'var/lib/typo3/'.$this->typo3SourceDir;

				// If the updated still doesn't exist: Abort.
			if ( ! file_exists($this->typo3SourceDir))	$this->error('Could not find the TYPO3 Source Directory (last tried was: '.$this->typo3SourceDir.'! The option -s could be used to provide a hint.');
		} else {

			$this->error('Could not find the link to the latest TYPO3 Source Directory (last tried was: '.
				$this->typo3SourceDir.'latest! '.
				'The option -s could be used to provide a hint.');
		}

			// check for permissions to change owner/mod root
		if ($this->fixPermissions && ($_ENV['USER'] != 'root') && ($this->dryrun == false)) {
			$this->error('=============================================================================='."\n".
				'You are not logged in as root'."\n".
				'It will not be possible to change the group ownership to '.$this->wwwgroup."\n".
				'=============================================================================='."\n\n");
		}
	}

	/**
	 * abort the script with error message.
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
	 * check and fix destination directory name.
         * @param expected to be there or not
         * @return is found as expected or not 
	 */
	function checkDestinationDir($expected)	{
		if (empty($this->destinationDir) || ($this->destinationDir == "")) {
			$this->info('using Default Destination Dir ('.$this->rootDir.DEFAULT_DESTINATION_DIR.')');
			$this->destinationDir = $this->rootDir.DEFAULT_DESTINATION_DIR;
		}

		if (!is_dir($this->getDestinationDir())) {
                    if ($expected) {
			$this->error('Destination Directory '.$this->getDestinationDir().' is not a directory or missing at all.');
                        return false;
                    }
		} else {
                    if (!$expected) {
                        $this->error('Directory '.$this->getDestinationDir().' already exists! Please move it out the way.');
                        return false;
                    }
		}            

		if ( ! file_exists( $this->getDestinationDir().'index.php')) {
                    if  ($expected) {
			$this->error('Site seems to be incorrect (missing '.
				$this->getDestinationDir().'index.php)!');

			$this->info('The directory you tried to use was:'."\n".
				$this->getDestinationDir()."\n\n".
				'Make sure you create the directory structure with this script.'."\n".
				'Use this command to do so:'."\n".
				'  typo3-site-installer -d '.$this->destinationDir."\n\n".
				'If you think this is a bug, please contact the author.'."\n".
                                    '=============================================================================='."\n");
                        return false;
                    }
		}
                // Test if directory is writable
                if (!$expected && !is_writable(dirname($this->getDestinationDir())))  {
                    $this->error('Directory '.dirname($this->getDestinationDir()).' is not writeable!');
                    return false;
                }
                return true;
	}


    
    
	/**
	 * Make directory and install template/index.html to redirect
         * a browser one level up.
	 */
        function makeDirectory($theDir, $testonly) {
            global $TEMPLATE_DIR;
            
            if (file_exists($theDir)) {
                if ($testonly === true) return false;
                else {
                    $this->error('Site seems to be incorrect (missing '.
                                 $this->destinationDir.'index.php)!');
                    return false;
                }
            }
            if ($this->dryrun == true) {
                $this->info('mkdir '.$theDir);
            } else {
                if (!mkdir($theDir)) {
                    $this->error('mkdir '.$theDir.' failed!');
                    return false;
                }
            }
            $this->copyToDir($TEMPLATE_DIR.'index.html', $theDir);
            return true;
        }
    
    
	/**
	 * Create the directory structure
	 */
	function createDirectoryStructure()	{
            $this->debug(1, 'createDirectoryStructure');
            $subdirs = array(
                $this->getDestinationDir(),
                $this->getDestinationDir().'fileadmin/',
                $this->getDestinationDir().'fileadmin/_temp_/',
                $this->getDestinationDir().'fileadmin/user_upload/',
                $this->getDestinationDir().'fileadmin/user_upload/_temp_/',
                $this->getDestinationDir().'typo3conf/',
                $this->getDestinationDir().'typo3conf/ext/',
                $this->getDestinationDir().'typo3temp/',
                $this->getDestinationDir().'uploads/',
                $this->getDestinationDir().'uploads/dmail_att/',
                $this->getDestinationDir().'uploads/media/',
                $this->getDestinationDir().'uploads/pics/',
                $this->getDestinationDir().'uploads/tf/',
                );
            foreach ($subdirs as $key => $subdir) {   
                $this->makeDirectory($subdir, false);
            }
        }
    
	/**
	 * Create a few symbolic links
	 */
        function createSymbolicLinks() {
            $this->debug(1, 'createSymbolicLinks');
            $errorsfound = false;
            $links = array(
                $this->getSourceDir() => 'typo3_src',
                'typo3_src/tslib' => 'tslib',
                'typo3_src/t3lib' => 't3lib',
                'typo3_src/typo3' => 'typo3',
                'tslib/media' => 'media',
                'tslib/showpic.php' => 'showpic.php',
                'tslib/index_ts.php' => 'index.php',
                );
            if ($this->dryrun !== true) chdir($this->getDestinationDir());
            else $this->info('cd '.$this->getDestinationDir());
            foreach ($links as $link => $orig) {   
                if (!file_exists($link)) {
                    if ($this->dryrun === false) {
                        $this->error('Failed: '.$link.' is missing!');
                        $errorsfound = true;
                    } else {
                        $this->info($link.' may be missing!');
                    }
                    continue;
                }
		if ($this->dryrun === true) {
                    $this->info('ln -s '.$link.' '.$orig);
		} else {
                    if (!symlink($link, $orig))
                        $this->error('Failed: ln -s '.$link.' '.$orig);
                }
            }
            if ($errorsfound)
                return false;
            return true;
        }
    
        /**
         * Copy file to the dir
         */
        function copyToDir($copyfrom, $dir) {
            $filename = basename($copyfrom);
            $this->debug(2, 'copyToDir: cp '.$copyfrom.' '.$dir.$filename);
            if (!is_file($copyfrom)) {
                $this->error('Failed: '.$copyfrom.' is missing or not a file!');
                return false;
            }
            if (!is_dir($dir) && ($this->dryrun === false)) {
                $this->error('Failed: '.$dir.' is missing or not a directory!');
                return false;
            }
            if ($this->dryrun == true) {
                $this->info('cp '.$copyfrom.' '.$dir.$filename);
            } else {
                if (!copy($copyfrom, $dir.$filename)) {
                    $this->error('Failed: cp '.$copyfrom.' '.$dir.$filename);
                    return false;
                }
            }
            return true;
        }
        
    
        /**
         * Create the localconf.php from the template file
         */
        function createLocalConf() {
            $this->info( "createLocalConf FIXME");
            
        }
    
        /**
         * Create the database in a given MySQL installation
         */
        function createMySQLDB() {
            $this->info("createMySQLDB FIXME");
            
            // FIXME - create mysql DB
            // FIXME - build links to all databases?? - or better
            //directly import them to T3 database
            //gunzip -c /usr/share/doc/typo3-site-installer/database.sql.gz > $this->getDestinationDir()/typo3conf/database.sql
        }
    
        /**
         * Create the apache configuration file
         */
        function createApacheConf() {
            $this->info("createApacheConf FIXME");
            // FIXME - create apache.conf
            
            
        }

    
	/**
	 * Install a clean (dummy)site
	 */
	function install_site()	{
            global $TEMPLATE_DIR;

            $this->abortIfErrors();
            $this->info('Install new site to '.$this->getDestinationDir());
            
            $this->createDirectoryStructure();
            $this->createSymbolicLinks();
                
            // Copy some other files from the template dir
            $this->copyToDir($TEMPLATE_DIR.'clear.gif', $this->getDestinationDir());
            $this->copyToDir($TEMPLATE_DIR.'favicon.ico', $this->getDestinationDir());

            $this->createLocalConf($this->getDestinationDir().'typo3conf/localconf.php');
            $this->createMySQLDB();
            $this->createApacheConf();
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
		$this->abortIfErrors();

		$this->debug(1, 'User is ',$_ENV['USER'],"\n");

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
                $this->errors++;

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
	 * check for neccessary directory, depending on the selected action
	 */
	function checkPrerequisitsForActions()	{
		if ($this->fixPermissions) {
                        // install dir must be available
			$this->checkDestinationDir(true);
		} else {
                        // install dir must not be available
			$this->checkDestinationDir(false);
		}
        }
    
        /**
	 * execute the necesssary functions - main method
	 */
	function doActions()	{
		/* TODO: Run the site fetcher
		require($INCLUDE_DIR.'class.site_fetcher.php');
		$fetcher = new site_fetcher;
		$res = $fetcher->fetch_site('3.6.2', 'dummy');
		*/
		if ($this->fixPermissions) {
			$this->fix_permissions();
		} else {
			$this->install_site();
                        if ($this->dryrun !== true)	$this->fix_permissions();
                        else $this->info('Setting file and directory permissions can not be demonstrated before installing the files.');
		}
		$this->abortIfErrors();
	}

}

?>
