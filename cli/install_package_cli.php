<?php
/**
 * @package     Joomla.CLI
 * @subpackage  com_installer
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */


// Make sure we're being called from the command line, not a web interface
if (array_key_exists('REQUEST_METHOD', $_SERVER)) die();

defined('_JEXEC') || define('_JEXEC', 1);
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

// Load system defines
if (file_exists(dirname(dirname(__FILE__)) . '/defines.php'))
{
	require_once dirname(dirname(__FILE__)) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	defined('JPATH_BASE') || define('JPATH_BASE', dirname(dirname(__FILE__)));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Force library to be in JError legacy mode
require_once JPATH_LIBRARIES . '/import.legacy.php';
JError::$legacy = true;

// Import necessary classes not handled by the autoloaders
jimport('joomla.application.component.helper');

// Import the configuration.
require_once JPATH_CONFIGURATION . '/configuration.php';

// System configuration.
$config = new JConfig;

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (! defined('APS_UPGRADE')) {
	if(count($argv) < 2)
	{
    	print "Usage: cli/install_package_cli.php <plugin zip archive>\n";
    	exit(1);
	}
	// Instantiate the application object, passing the class name to JCli::getInstance
	// and use chaining to execute the application.
	JApplicationCli::getInstance('InstallPluginCli')->execute($argv[1]);
}

// Take first argument as distributive archive name

/**
 * A command line cron job to install extension.
 *
 * @package     Joomla.CLI
 * @subpackage  com_installer
 * @since       2.5
 */
class InstallPluginCli extends JApplicationCli
{
	public function execute($plugin_distr)
	{
		$package = JInstallerHelper::unpack($plugin_distr);
		// Get an installer instance
		$installer = new JInstaller();

		// Install the package
		if (!$installer->install($package['dir'])) {
			// There was an error installing the package
			echo "installation error: ".$installer->message ."\n";
			// try upgrade
		} else {
			echo "installation ok\n".$installer->message ."\n";
		}
		if (!$installer->update($package['dir'])) {
			echo "upgrade result: ".$installer->message ."\n";
			// ignore module upgrade error
			//exit(1);
		}
		remove_dir_aps($package['dir']);
	}
}

function remove_dir_aps($dir)
{
    if (!preg_match("/\/$/", $dir)) {
        $dir .= '/';
    }
    if ($handle = @opendir($dir)) {
        while (strlen($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                if(is_dir($dir.$file)) {
                    if(!@rmdir($dir.$file)) {
                        remove_dir_aps($dir.$file.'/');
                    }
                } else {
                    @unlink($dir.$file);
                }
            }
        }
    }
    @closedir($handle);
    @rmdir($dir);
}

?>
