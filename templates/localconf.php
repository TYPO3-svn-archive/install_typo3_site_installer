<?php
$TYPO3_CONF_VARS["SYS"]["sitename"] = '##T3INST_SITENAME##';
$TYPO3_CONF_VARS["BE"]["installToolPassword"] = '##T3INST_INSTALLTOOLPASSWORD##';

# provide some default values for Debian systems (sarge, hoary)

// If safe_mode is activated with TYPO3, disable use of
// external programs
//$TYPO3_CONF_VARS["BE"]["disable_exec_function"] = '1';

// TYPO3 prefers the use of GIF-files and most likely your visitors on
// your website does too as not all browsers support PNG yet.
$TYPO3_CONF_VARS["GFX"]["gdlib_png"] = '1';

// enabling the use of gdblib2 for image processing
$TYPO3_CONF_VARS["GFX"]["gdlib_2"] = '1';

// last versions of imagemagick renamed combine to composite. It will
// be set by basic configuration automatically.
$TYPO3_CONF_VARS["GFX"]["im_combine_filename"] = 'composite';

// This value should be set to 1 if imagemagick version is greater
// than 5.2
$TYPO3_CONF_VARS["GFX"]["im_negate_mask"] = '1';

$TYPO3_CONF_VARS["GFX"]["im_imvMaskState"] = '1';

$TYPO3_CONF_VARS["GFX"]["im_mask_temp_ext_gif"] = '1';

// The value should be 0 if the version of imagemagick is greater than
// 5, otherwise the creation of effects is getting too slow
$TYPO3_CONF_VARS["GFX"]["im_no_effects"] = '1';

$TYPO3_CONF_VARS["GFX"]["im_v5effects"] = '1';

// Path to the imagemagick manipulation tools like convert,
// composite and identify
$TYPO3_CONF_VARS["GFX"]["im_path"] = '##T3INST_EXECDIR##';

// Set Value to 1 if version of ImageMagick is greater than 4.9
$TYPO3_CONF_VARS["GFX"]["im_version_5"] = '1';

// This variable can be empty if ImageMagick is compiled with LZW.
// Otherwise you have to set the path to LZW
//$TYPO3_CONF_VARS["GFX"]["im_path_lzw"] = '';

// Image file formats that should be accepted by Typo3
$TYPO3_CONF_VARS["GFX"]["imagefile_ext"] = 'gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf';

$TYPO3_CONF_VARS["GFX"]["im_noFramePrepended"] = '1';

// Enables the preview of images to make the choice more easy
$TYPO3_CONF_VARS["GFX"]["thumbnails"] = '1';

// Preview of images in png or gif format.
// Should be the same as "gdlib_png"
$TYPO3_CONF_VARS["GFX"]["thumbnails_png"] = '1';

// Check freetype quicktest in the basic configuration if text is
// exceeding the image borders. If yes, you are using Freetype 2 and
// need to set TTFdpi to 96 dpi
$TYPO3_CONF_VARS["GFX"]["TTFdpi"] = '96';

?>