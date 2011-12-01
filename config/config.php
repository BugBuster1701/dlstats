<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');
/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 * 
 * Module Download Statistics
 *
 * Log file downloads done by the content elements Download and Downloads 
 * and show statistics in the backend. 
 *
 *
 * ----- Derived from dlstats 1.0.0 (2009-06-11) -----
 * ---------- Peter Koch (acenes) 2007-2009 ----------
 * 
 * Module configuration file.
 * 
 * PHP version 5
 * @copyright  Glen Langer (BugBuster) 2011
 * @author     BugBuster
 * @package    GLDLStats
 * @license    LGPL
 * @filesource
 */

/**
 * -------------------------------------------------------------------------
 * BACK END MODULES
 * -------------------------------------------------------------------------
 */
$GLOBALS['BE_MOD']['content']['dlstats'] = array(
	'tables'		=>	array('tl_dlstats','tl_dlstatdets'),
	'icon'			=>	'system/modules/dlstats/html/icon.png',
	'stylesheet'	=>	'system/modules/dlstats/html/style.css'
);

/**
 * Register some hooks (DO NOT CHANGE!)
 */
$GLOBALS['TL_HOOKS']['postDownload'][] = array('Dlstats', 'logDownload');

?>