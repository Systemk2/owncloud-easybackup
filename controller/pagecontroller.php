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


use \OCA\EasyBackup\ResponseFactory;
use \OCA\EasyBackup\Service\BackupService;
use \OCA\EasyBackup\Service\ConfigService;
use \OCA\EasyBackup\Service\ScheduleService;
use \OCA\EasyBackup\StatusContainer;

use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IL10N;
use \OCP\ILogger;
use \OCP\IRequest;
use \OCP\IURLGenerator;


class PageController extends BaseController {

	/**
	 * @var \OCA\EasyBackup\Service\BackupService
	 */
	protected $backupService;

	/**
	 * @var \OCP\IURLGenerator
	 */
	protected $urlGenerator;

	/**
	 * @var \OCA\EasyBackup\Service\ConfigService
	 */
	protected $configService;

	/**
	 * @var \OCA\EasyBackup\Service\ScheduleService
	 */
	private $scheduleService;

	public function __construct(
			$appName,
			IRequest $request,
			ILogger $logger,
			BackupService $backupService,
			ConfigService $configService,
			ScheduleService $scheduleService,
			IURLGenerator $urlGenerator,
			ResponseFactory $responseFactory) {
		parent::__construct($appName, $request, $logger, $responseFactory);
		$this->backupService = $backupService;
		$this->configService = $configService;
		$this->scheduleService = $scheduleService;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoCSRFRequired
	 */
	public function configuration() {
		$parameters = $this->createParameters('configuration.inc');
		return new TemplateResponse($this->appName, 'index', $parameters);
	}

	/**
	 * @NoCSRFRequired
	 */
	public function restore() {
		$parameters = $this->createParameters('restore.inc');
		return new TemplateResponse($this->appName, 'index', $parameters);
	}

	private function createParameters($subTemplate) {
		$statusContainer = $this->backupService->createStatusInformation();

		$parameters = array(
				'statusContainer' => $statusContainer,
				'backupHostName' => $this->configService->getUpdateHost(),
				'keyUploadUrl' => $this->urlGenerator->linkToRoute('easybackup.config.uploadSshKey'),
				'schedule' => $this->scheduleService,
				'scheduleTime' => $this->configService->getScheduleTime(),
				'isScheduled' => $this->configService->isBackupScheduled(),
				'configurationUrl' => $this->urlGenerator->linkToRoute('easybackup.page.configuration'),
				'restoreUrl' => $this->urlGenerator->linkToRoute('easybackup.page.restore'),
				'logfileUrl' => $this->urlGenerator->linkToRoute('easybackup.logfileview.getCompleteLogfile'),
				'subTemplate' => $subTemplate
		);

		return $parameters;
	}

}
