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
namespace OCA\EasyBackup;

use \OCA\EasyBackup\AppInfo\Application;

class RunOnceJob extends \OC\BackgroundJob\QueuedJob {
	
	/**
	 *
	 * @var \OCA\EasyBackup\IRunnable
	 */
	private $runnable;

	/**
	 *
	 * @return \OCA\EasyBackup\IRunnable
	 */
	private function getRunnable() {
		if (! $this->runnable) {
			$app = new Application();
			$this->runnable = $app->getContainer()->query('ShellExecRunnable');
		}
		return $this->runnable;
	}
	
	/*
	 * (non-PHPdoc)
	 * @see OC\BackgroundJob.Job::run()
	 */
	public function run($commandHandlerString) {
		$this->getRunnable()->run($commandHandlerString);
	}
}