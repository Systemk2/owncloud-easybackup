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

namespace OCA\EasyBackup\Controller;



use \OCA\EasyBackup\EasyBackupException;
use \OCA\EasyBackup\ResponseFactory;
use \OCA\EasyBackup\Service\BackupService;
use \OCA\EasyBackup\Service\ConfigService;

use \OCP\ILogger;
use \OCP\IRequest;

class ConfigController extends BaseController {

	/**
	 * @var \OCA\EasyBackup\Service\BackupService
	 */
	protected $backupService;

	/**
	 * @var \OCA\EasyBackup\Service\ConfigService
	 */
	protected $configService;

	public function __construct($appName,
			IRequest $request,
			ILogger $logger,
			BackupService $backupService,
			ConfigService $configService,
			ResponseFactory $responseFactory) {
		parent::__construct($appName, $request, $logger, $responseFactory);
		$this->backupService = $backupService;
		$this->configService = $configService;
	}

	/**
	 * @ControllerManaged
	 *
	 * @param string $oldBackupHost
	 * @param string $newBackupHost
	 */
	protected function updateBackupHost($oldBackupHost, $newBackupHost) {
		$this->configService->setUpdateHost(trim($newBackupHost));
		if(!$this->backupService->isHostNameValid()) {
			throw new \OCA\EasyBackup\EasyBackupException('Hostname is not valid');
		}

		$this->backupService->updateBackupCommand();

		$preconditionsHtml = $this->renderPreconditionsHtml();
		return(array('newBackupHost' => trim($newBackupHost), 'preconditionsHtml' => $preconditionsHtml));
	}

	/**
	 * @ControllerManaged
	 *
	 * Upload SSH private key file
	 */
	protected function uploadSshKey() {
		$file = $this->request->getUploadedFile('easybackup_sshKeyFile');
		if(!$file || !file_exists($file['tmp_name'])) {
			throw new EasyBackupException('Uploaded file not found');
		}
		$key = file_get_contents($file['tmp_name']);
		if($key === false || strlen($key) == 0) {
			throw new EasyBackupException('Uploaded file is empty');
		}
		if(!$this->backupService->validatePrivateSshKey($key)) {
			throw new EasyBackupException('Key is not well-formed');
		}
		$filename = $this->configService->getPrivateKeyFilname();
		if(!file_put_contents($filename, $key)) {
			throw new \Exception('Could not store private key in ' . $filename);
		}
		chmod($filename, 0600);

		$preconditionsHtml = $this->renderPreconditionsHtml();
		return array('preconditionsHtml' => $preconditionsHtml);
	}

	/**
	 * @ControllerManaged
	 *
	 * @param string $schedule
	 */
	protected function setBackupSchedule($schedule) {
		$this->configService->setBackupSchedule($schedule);
	}

	/**
	 * @ControllerManaged
	 *
	 * @param int $scheduleTime
	 */
	protected function setScheduleTime($scheduleTime) {
		$this->configService->setScheduleTime($scheduleTime);
	}

	private function renderPreconditionsHtml() {
		$statusContainer = $this->backupService->createStatusInformation();
		$parameters = array(
				'statusContainer' => $statusContainer
		);
		return $this->renderHtml('preconditions.inc', $parameters);
	}

}

