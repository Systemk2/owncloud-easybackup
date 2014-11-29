<?php
/**
 * ownCloud - EasyBackup
 *
 * @author Sebastian Kanzow
 * @copyright 2014 System k2 GmbH  info@systemk2.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


class OC {
	public static $server;
	public static $session;
}


/**
 * To execute without owncloud, we need our own classloader
 *
 * If the app is located inside the owncloud folder, the test can be run like this:
 * foo@bar:/ownclouddir/app/easybackup$ phpunit test
 * otherwise it needs to be executed from inside the owncloud folder, by specifying an absolute
 * path to the test folder, like that:
 * foo@bar:/ownclouddir$ phpunit /appdir/easybackup/test
 */
spl_autoload_register(
		function ($className) {
	$relPath = false;
	if($className == 'Pimple') {
		require_once '3rdparty/Pimple/Pimple.php';
		return;
	}

	if (strpos($className, 'OC_') === 0) {
		$filename = strtolower(str_replace('_', '/', substr($className, 3)) . '.php');
	} else {
		$filename = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
	}

	if (strpos($className, 'OCA\\EasyBackup') === 0) {
		// Do not use __DIR__, because it resolves symlinks
		if(strpos($_SERVER['argv'][1], DIRECTORY_SEPARATOR) === 0) {
			// an absolute path to the tests folder was given as an argument, so use this one
			$root = dirname($_SERVER['argv'][1]);
		} else {
			$root = dirname($_SERVER['PWD'] . DIRECTORY_SEPARATOR . $_SERVER['argv'][1]);
		}
		$relPath = dirname($root) . $filename;
		if (!file_exists($relPath)) {
			// If not found in the root of the app directory, insert '/lib' after app id and try again
			$parts = split('/easybackup/', $relPath);
			if(count($parts) == 2) {
				$relPath = $parts[0] . '/easybackup/lib/' . $parts[1];
			}
		}
	} else if (strpos($className, 'OCP\\') === 0) {
		$relPath = 'lib/public/' . $filename;
	}  else if(!file_exists($relPath)) {
		$relPath = 'lib/private/' . $filename;
		// File does not exist, try legacy folder
		if(!file_exists($relPath)) {
			$relPath = 'lib/private/legacy/' . $filename;
		}
	}

	if (file_exists($relPath)) {
		require_once $relPath;
	} else {
		die("FATAL: Class $className could not be loaded, because file does not exist: $relPath\n");
	}
});
