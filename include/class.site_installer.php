<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Michael Stucki (mundaun@gmx.ch)
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
 * @author	 Michael Stucki <mundaun@gmx.ch>
 * @author	 Christian Leutloff <leutloff@debian.org>
 *
 * Scroll to the end to find the start of this script!
 */

function is_symlink() 
{
    return true;
}


class site_installer
{
    /**
     * Define some values
     */
    var $GROUP="www-data"; // name of the group that runs the Apache webserver

    // TODO: We could also grep through httpd.conf in case that this name was changed
    //GROUP=`cat /etc/apache/httpd.conf | grep "^[Gg]roup\ [^\ ]*$" | awk '{ print $NF; }'`
       
    var $FIX_PERMISSIONS=0;
    var $ALWAYS_LATEST=0;
    
/**
 * Check on startup
 */
function startUpCheck()
{
    $ERROR=0;
    

    // We always use /var/lib/typo3/latest to get the version number that will be used for installation
    // Check if /var/lib/typo3/latest is a symlink
    if (is_symlink('/var/lib/typo3/latest'))
    {
        // Get the current version which will be used by default
        $TYPO3_SOURCE=`ls -l /var/lib/typo3/latest | awk '{ print $NF; }'`;
        

        // Maybe /var/lib/typo3/latest does not point to the absolute path
        if ( ! file_exists($TYPO3_SOURCE))
            $TYPO3_SOURCE='/var/lib/typo3/'.$TYPO3_SOURCE;

        // If the updated still doesn't exist: Abort.
        if ( ! file_exists($TYPO3_SOURCE))
            $ERROR=1; 
    }
    else
    {
        // It seems that /var/lib/typo3/latest is not a correct symlink: Abort.
        $ERROR=1;        
    }
    

    if ( $ERROR == 1 )
    {
        echo "\n";
        echo "/var/lib/typo3/latest is wrong or does not exist at all!\n" ;
        echo "\n";
        echo "Aborted.\n" ;
        exit (1);   
    }
}


////////////
// Install a clean dummysite
//////
function install_site()
{
/*
    // Test if directory is writable
    if [ -w `dirname $DESTINATION` ]; then

        // Test if target directory already exists (may also be a file)
        if [ -e $DESTINATION ]; then

            echo
            echo "=============================================================================="
            echo "Directory $DESTINATION exists!"
            echo "=============================================================================="
            echo
            echo "Aborted."
            exit 1

        fi

        // Create the directory structure
        mkdir $DESTINATION
        mkdir $DESTINATION/fileadmin
        mkdir $DESTINATION/fileadmin/_temp_
        mkdir $DESTINATION/fileadmin/user_upload
        mkdir $DESTINATION/fileadmin/user_upload/_temp_
        mkdir $DESTINATION/typo3conf
        mkdir $DESTINATION/typo3conf/ext
        mkdir $DESTINATION/typo3temp
        mkdir $DESTINATION/uploads
        mkdir $DESTINATION/uploads/dmail_att
        mkdir $DESTINATION/uploads/media
        mkdir $DESTINATION/uploads/pics
        mkdir $DESTINATION/uploads/tf

        // Create index.html for directories that should not be shown
        cat <<EOF > $DESTINATION/uploads/index.html
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
    <TITLE></TITLE>
    <META http-equiv=Refresh Content="0; Url=../">
</HEAD>
</HTML>
EOF

        // Create a symlink to this file in every subdirectory
        cp $DESTINATION/uploads/index.html $DESTINATION/uploads/dmail_att/
        cp $DESTINATION/uploads/index.html $DESTINATION/uploads/media/
        cp $DESTINATION/uploads/index.html $DESTINATION/uploads/pics/
        cp $DESTINATION/uploads/index.html $DESTINATION/uploads/tf/
        cp $DESTINATION/uploads/index.html $DESTINATION/typo3conf/

        // Copy some other files from /usr/share/doc/typo3-site-installer
        cp /usr/share/doc/typo3-site-installer/_.htaccess $DESTINATION/
        cp /usr/share/doc/typo3-site-installer/clear.gif $DESTINATION/
        gunzip -c /usr/share/doc/typo3-site-installer/changelog.gz > $DESTINATION/changelog
        gunzip -c /usr/share/doc/typo3-site-installer/database.sql.gz > $DESTINATION/typo3conf/database.sql

        // Copy the localconf.php into typo3conf/
        cp /usr/share/doc/typo3-base/examples/localconf.php $DESTINATION/typo3conf/

        // Create a few symlinks
        ln -s $TYPO3_SOURCE $DESTINATION/typo3_src
        ln -s typo3_src/tslib $DESTINATION/
        ln -s typo3_src/t3lib $DESTINATION/
        ln -s typo3_src/typo3 $DESTINATION/
        ln -s tslib/media $DESTINATION/
        ln -s tslib/showpic.php $DESTINATION/
        ln -s tslib/index_ts.php $DESTINATION/index.php

        // Fix the permissions
        fix_permissions

    else

        echo
        echo "=============================================================================="
        echo "Error: The target directory cannot be created."
        echo "Please check your settings."
        echo "Note that the parent directory needs to be existing!"
        echo "=============================================================================="
        echo
        echo "Aborted."
        exit 1

    fi
*/
}


////////////
// Fix some permissions
//////
function fix_permissions()
{
    $ERROR=0 ;
    if ( ! is_dir($DESTINATION))
    {
        echo "\n";
        echo "==============================================================================\n";
        echo "Error: Directory does not exist!\n";
        $ERROR=1 ;
    }
    else
    {
        if ( ! file_exists( $DESTINATION.'/index.php' ))
        {
            echo "\n";
            echo "==============================================================================\n";
            echo "Error: Site seems to be incorrect!";
            $ERROR=1;
        }
    }
    if ( $ERROR == 1 )
    {
        echo "The directory you tried to use was:\n";
        echo $DESTINATION."\n";
        echo "\n";
        echo "Make sure you create the directory structure with this script.\n";
        echo "Use this command to do so:\n";
        echo "  typo3-site-installer -d=$DESTINATION\n";
        echo "\n";
        echo "If you think this is a bug, please contact the author.\n";
        echo "==============================================================================\n";
        echo "\n";
        echo "Aborted.\n";
        exit (1);
    }
    
    if ( $USER == 'root' )
    {
        // www-data is the group owner of the Apache process
        /*
        chgrp -R $GROUP $DESTINATION
        find $DESTINATION -type f -exec chmod 640 {} \;
        find $DESTINATION -type d -exec chmod 750 {} \;

        chmod -R g+w $DESTINATION/fileadmin
        chmod -R g+w $DESTINATION/typo3conf
        chmod -R g+w $DESTINATION/typo3temp
        chmod -R g+w $DESTINATION/uploads
        chmod 600 $DESTINATION/changelog
        */
    }
    else
    {
        /*
        find $DESTINATION -type f -exec chmod 666 {} \;
        find $DESTINATION -type d -exec chmod 777 {} \;
        chmod 600 $DESTINATION/changelog
        */
        echo "\n";
        echo "==============================================================================\n";
        echo "You are not logged in as root.\n";
        echo "I was unable to change the group ownership to ".$GROUP."\n";
        echo "\n";
        echo "I have therefore changed the permissions to minimal security (everybody can\n";
        echo "read / write).\n";
        echo "\n";
        echo "Though your site will be working with these settings, you are strongly\n";
        echo "encouraged to fix that problem by running this script again but with root\n";
        echo "permissions and using the option '--fix-permissions':\n";
        echo "\n";
        echo "Use this command to do so:\n";
        echo "  typo3-site-installer -d $DESTINATION --fix-permissions\n";
    }
    

    echo "\n";
    echo "==============================================================================\n";
    echo "Finished. But there is still something to do for you:\n";
    echo "\n";
    echo "First: Make sure that ".$DESTINATION." is accessable through your webserver.\n";
    echo "(Move this directory to /var/www if you don't know what to do.)\n";
    echo "\n";
    echo "\n";
    echo "Next, follow these steps:\n";
    echo "\n";
    echo "  * In $TYPO3_SOURCE/typo3/install/index.php:\n";
    echo "    Comment out line 40 (the 'die()' call)\n";
    echo "  * Point your browser to the location you just created and complete the setup\n";
    echo "  * Remove the comment from above\n";
    echo "\n";
    echo "Note: the image settings should already be optimized for Debian Woody.\n";
    echo "\n";
    echo "Make sure to read the README file for later install instructions.\n";
    echo "==============================================================================\n";
    echo "\n";
    echo "Successfully done.\n";
}

/*
//////////
// Main control
//////

start_check();


// Show info if no parameters were specified
if [ $ -lt 1 ]; then show_usage; exit 1; fi

// Read all parameters
while [ $// -gt 0 ]; do
    case "$1" in

        -d|-d=*|--destination|--destination=*)
            // Example: --destination /abc/def/geh
            if [ $1 == -d -o $1 == --destination ] && [ ! -z "$2" ]; then

                DESTINATION="$2"
                shift 2

            elif [ -z `echo $1 | sed -e 's/\(-d\|--destination\)=\(.\+\)//'` ]; then

                // Example: --destination=/abc/def/geh
                DESTINATION=`echo "$1" | awk -F= '{ print $2; }'`

                // proceed if $DESTINATION is non-zero
                if [ ! -z $DESTINATION ]; then shift 1
                else show_usage; exit 1
                fi

            else

                show_usage
                exit 1

            fi

            // Convert disallowed characters to underscores
            DESTINATION=`echo $DESTINATION | sed -e 's/[^-\~_./a-zA-Z0-9]\+/_/g'`
          ;;

        -f|--fix-permission|--fix-permissions)
            // We only want to fix the permissions of an existing site
            FIX_PERMISSIONS=1
            shift
          ;;

        -g|-g=*|--group|--group=*)
            // Group was manually specified; only act if we are root.
            if [ $USER == 'root' ]; then

                // $GROUP should normally be the group owner of the Apache process
                if [ $1 == -g -o $1 == --group ] && [ ! -z "$2" ]; then

                    GROUP="$2"
                    shift 2

                elif [ -z `echo $1 | sed -e 's/\(-g\|--group\)=\(.\+\)//'` ]; then

                    // Example: --destination=/abc/def/geh
                    GROUP=`echo "$1" | awk -F= '{ print $2; }'`

                    // proceed if $DESTINATION is non-zero
                    if [ ! -z $GROUP ]; then shift 1
                    else show_usage; exit 1
                    fi

                fi

            else

                echo
                echo "=============================================================================="
                echo "You are not logged in as root."
                echo -n "I was unable to change the group ownership to"
                echo $GROUP
                echo "=============================================================================="
                echo
                echo "Aborted."
                exit 1

            fi

          ;;

        -a|--always-latest)
            // Point typo3_src to /var/lib/typo3/latest
            if [ $ALWAYS_LATEST == 1 ]; then TYPO3_SOURCE=/var/lib/typo3/latest; fi
            shift
          ;;

        *)
            // something went wrong
            show_usage
            exit 1
          ;;

    esac
done

// see what we have to do
if [ $FIX_PERMISSIONS == 1 ]; then fix_permissions
elif [ ! -z $DESTINATION ]; then install_site
fi

exit 0
*/

//////////////////////////////////////////////////

/**
 * Todo: Run the site fetcher
 *
require(INCLUDE_DIR.'class.site_fetcher.php');
$fetcher = new site_fetcher;
$res = $fetcher->fetch_site('3.6.2', 'dummy');
*/
}

?>
