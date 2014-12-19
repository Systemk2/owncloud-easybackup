<?php

/**
 * ownCloud - EasyBackup
 *
 * @author Sebastian Kanzow
 * @copyright 2014 System k2 GmbH  info@systemk2.de
 * Based on http://tekkie.flashbit.net/php/tail-functionality-in-php
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
namespace OCA\EasyBackup\Service;

use \OCA\EasyBackup\BaseTestCase;
use OCA\EasyBackup\ShellExecResult;

require_once (__DIR__ . '/../basetestcase.php');

class BackupServiceTest extends \OCA\EasyBackup\BaseTestCase {
	
	/**
	 *
	 * @var \OCA\EasyBackup\Service\BackupService
	 */
	private $cut;
	
	/**
	 *
	 * @var \OCA\EasyBackup\Service\ConfigService
	 */
	private $configServiceMock;
	
	/**
	 *
	 * @var \OCA\EasyBackup\RunOnceJob
	 */
	private $runOnceJobMock;
	
	/**
	 *
	 * @var \OCA\EasyBackup\Service\ShellExecService
	 */
	private $shellExecServiceMock;

	protected function setUp() {
		parent::setUp();
		
		$this->configServiceMock = $configServiceMock = $this->getMockBuilder('\OCA\EasyBackup\Service\ConfigService')->disableOriginalConstructor()->getMock();
		$this->container->registerService('ConfigService', 
				function ($c) use($configServiceMock) {
					return $configServiceMock;
				});
		
		$this->shellExecServiceMock = $shellExecServiceMock = $this->getMockBuilder('\OCA\EasyBackup\Service\ShellExecService')->getMock();
		$this->container->registerService('ShellExecService', 
				function ($c) use($shellExecServiceMock) {
					return $shellExecServiceMock;
				});
		
		$this->apiMock = $apiMock = $this->getMockBuilder('\OCP\AppFramework\IApi')->getMock();
		$this->container->registerService('API', function ($c) use($apiMock) {
			return $apiMock;
		});
		
		$this->cut = $this->container->query('BackupService');
	}

	public function testValidatePrivateSshKeyFail() {
		$retVal = $this->cut->validatePrivateSshKey('InvalidKey');
		$this->assertEquals(false, $retVal);
	}

	public function testValidatePrivateSshKeySuccess() {
		$key = $key = file_get_contents(__DIR__ . '/../resource/private_key.txt');
		$retVal = $this->cut->validatePrivateSshKey($key);
		$this->assertEquals(true, $retVal);
	}

	public function testExecuteBackupPositive() {
		$this->configServiceMock->expects($this->atLeastOnce())->method('getLogfileName')->will($this->returnValue('/dev/null'));
		$this->configServiceMock->expects($this->once())->method('register')->with(
				$this->isInstanceOf('\OCA\EasyBackup\RunOnceJob'), $this->stringContains('BackupCommandHandler', true));
		$this->cut->executeBackup();
	}

	public function testExecuteBackupAlreadyRunning() {
		$testFile = '/tmp/phptest';
		file_put_contents($testFile, 'Test');
		$this->configServiceMock->expects($this->atLeastOnce())->method('getLogfileName')->will($this->returnValue($testFile));
		$this->cut->executeBackup();
		unlink($testFile);
	}

	public function testCheckBackupRunningNegative() {
		$this->configServiceMock->expects($this->once())->method('getAppValue')->with($this->equalTo('BACKUP_RUNNING'))->will(
				$this->returnValue('false'));
		$retVal = $this->cut->isBackupExecuting();
		$this->assertEquals(false, $retVal);
	}

	public function testCheckBackupRunningPositive() {
		$this->configServiceMock->expects($this->at(0))->method('getAppValue')->with($this->equalTo('BACKUP_RUNNING'))->will(
				$this->returnValue('true'));
		$now = new \DateTime('now', new \DateTimeZone('UTC'));
		$this->configServiceMock->expects($this->at(1))->method('getAppValue')->with($this->equalTo('BACKUP_RUNNING_SINCE'))->will(
				$this->returnValue($now->format('Y-m-d H:i:s e')));
		$retVal = $this->cut->isBackupExecuting();
		$this->assertEquals(true, $retVal);
	}

	public function testCheckBackupRunningNegativeTooLongInactivity() {
		$this->configServiceMock->expects($this->at(0))->method('getAppValue')->with($this->equalTo('BACKUP_RUNNING'))->will(
				$this->returnValue('true'));
		$now = new \DateTime('now', new \DateTimeZone('UTC'));
		$this->configServiceMock->expects($this->at(1))->method('getAppValue')->with($this->equalTo('BACKUP_RUNNING_SINCE'))->will(
				$this->returnValue($now->sub(new \DateInterval('PT08H'))->format('Y-m-d H:i:s e')));
		$this->configServiceMock->expects($this->at(2))->method('setAppValue')->with($this->equalTo('BACKUP_RUNNING'), 
				$this->equalTo('false'));
		$this->configServiceMock->expects($this->at(3))->method('setAppValue')->with($this->equalTo('BACKUP_RUNNING_SINCE'), 
				$this->equalTo(null));
		$retVal = $this->cut->isBackupExecuting();
		$this->assertEquals(false, $retVal);
	}

	public function testFinishBackupSuccess() {
		$this->configServiceMock->expects($this->once())->method('getLogfileName')->will($this->returnValue('/dev/null'));
		$this->translationMock->expects($this->once())->method('t')->with($this->stringContains('success'));
		$this->configServiceMock->expects($this->exactly(4))->method('setAppValue');
		$this->configServiceMock->expects($this->at(0))->method('setAppValue')->with($this->equalTo('BACKUP_RUNNING'), 
				$this->equalTo('false'));
		$this->configServiceMock->expects($this->at(1))->method('setAppValue')->with($this->equalTo('BACKUP_RUNNING_SINCE'), 
				$this->equalTo(null));
		$this->configServiceMock->expects($this->at(2))->method('setAppValue')->with($this->equalTo('LAST_BACKUP_SUCCESSFUL'), 
				$this->equalTo('true'));
		$this->configServiceMock->expects($this->at(3))->method('setAppValue')->with($this->equalTo('LAST_BACKUP_TIME'), 
				$this->anything());
		$this->cut->finishBackup(true);
	}

	public function testFinishBackupFailure() {
		$this->configServiceMock->expects($this->once())->method('getLogfileName')->will($this->returnValue('/dev/null'));
		$this->translationMock->expects($this->once())->method('t')->with($this->stringContains('error'));
		$this->configServiceMock->expects($this->exactly(4))->method('setAppValue');
		$this->configServiceMock->expects($this->at(0))->method('setAppValue')->with($this->equalTo('BACKUP_RUNNING'), 
				$this->equalTo('false'));
		$this->configServiceMock->expects($this->at(1))->method('setAppValue')->with($this->equalTo('BACKUP_RUNNING_SINCE'), 
				$this->equalTo(null));
		$this->configServiceMock->expects($this->at(2))->method('setAppValue')->with($this->equalTo('LAST_BACKUP_SUCCESSFUL'), 
				$this->equalTo('false'));
		$this->configServiceMock->expects($this->at(3))->method('setAppValue')->with($this->equalTo('LAST_BACKUP_TIME'), 
				$this->anything());
		$this->cut->finishBackup(false);
	}

	public function testIsHostNameValidPositive() {
		$this->configServiceMock->expects($this->once())->method('getHostUserName')->will($this->returnValue('test1'));
		$this->assertTrue($this->cut->isHostUserNameValid());
	}

	public function testIsHostNameValidNegative() {
		$this->configServiceMock->expects($this->once())->method('getHostUserName')->will($this->returnValue('test'));
		$this->assertFalse($this->cut->isHostUserNameValid());
	}

	public function testIsHostNameValidNegative1() {
		$this->configServiceMock->expects($this->once())->method('getHostUserName')->will($this->returnValue(null));
		$this->assertFalse($this->cut->isHostUserNameValid());
	}

	public function testIsHostNameValidNegative2() {
		$this->configServiceMock->expects($this->once())->method('getHostUserName')->will($this->returnValue(''));
		$this->assertFalse($this->cut->isHostUserNameValid());
	}

	public function testGetPublicSshKeyFromPrivateKeyNoSshKeygen() {
		$this->shellExecServiceMock->expects($this->once())->method('shellExec')->with($this->equalTo('which ssh-keygen'))->will(
				$this->returnValue(new ShellExecResult(1, array ())));
		$publicKey = $this->cut->getPublicSshKeyFromPrivateKey();
		$this->assertNull($publicKey);
	}

	public function testGetPublicSshKeyFromPrivateKeyNoPrivateKeyFile() {
		$this->shellExecServiceMock->expects($this->once())->method('shellExec')->with($this->equalTo('which ssh-keygen'))->will(
				$this->returnValue(new ShellExecResult(0, array ())));
		$this->configServiceMock->expects($this->once())->method('getPrivateKeyFilename')->will(
				$this->returnValue('not_existent_file'));
		$publicKey = $this->cut->getPublicSshKeyFromPrivateKey();
		$this->assertNull($publicKey);
	}

	public function testGetPublicSshKeyFromPrivateKeyOk() {
		$keyFileName = __DIR__ . '/../resource/private_key.txt';
		$this->shellExecServiceMock->expects($this->at(0))->method('shellExec')->with($this->equalTo('which ssh-keygen'))->will(
				$this->returnValue(new ShellExecResult(0, array ())));
		$this->configServiceMock->expects($this->once())->method('getPrivateKeyFilename')->will($this->returnValue($keyFileName));
		$this->shellExecServiceMock->expects($this->at(1))->method('shellExec')->with(
				$this->equalTo("ssh-keygen -P '' -q -y -f '$keyFileName'"))->will(
				$this->returnValue(new ShellExecResult(0, array (
						'ssh-rsa AAAAB' 
				))));
		$publicKey = $this->cut->getPublicSshKeyFromPrivateKey();
		$this->assertEquals('ssh-rsa AAAAB', $publicKey);
	}

	public function testScheduleRestoreJobNonEncrypted() {
		$restoreConfig = '{
			"backupserver": "mockUser.trustedspace.de",
			"backupuser": "mockUser",
			"exclude": ["*"],
			"include": [
				"data/",
				"data/admin/",
				"data/admin/files/",
				"data/admin/files/documents/",
				"data/admin/files/documents/***",
				"data/admin/files/images/test.jpg"
			],
			"restorebase": "snapshot/0815/"
		}';
		$this->configServiceMock->expects($this->any())->method('getDataDir')->will($this->returnValue('/home/data'));
		$this->configServiceMock->expects($this->once())->method('getLogfileName')->will($this->returnValue('/dev/null'));
		$this->apiMock->expects($this->once())->method('isAppEnabled')->with($this->equalTo('files_encryption'))->will(
				$this->returnValue(false));
		
		$expectedCommand = "rsync -rtgov -e 'ssh -q -i \"\" -o StrictHostKeyChecking=no -o UserKnownHostsFile='" .
				 " --numeric-ids --omit-dir-times" . " --include='data/'" . " --include='data/admin/'" .
				 " --include='data/admin/files/'" . " --include='data/admin/files/documents/'" .
				 " --include='data/admin/files/documents/***'" . " --include='data/admin/files/images/test.jpg'" .
				 " --exclude='*' mockUser@mockUser.trustedspace.de:snapshot/0815/ /home >> /dev/null 2>&1";
		
		$this->configServiceMock->expects($this->once())->method('setRestoreCommand')->with($this->equalTo($expectedCommand));
		$this->cut->scheduleRestoreJob($restoreConfig);
	}

	public function testScheduleRestoreJobEncrypted() {
		$restoreConfig = '{
			"backupserver": "mockUser.trustedspace.de",
			"backupuser": "mockUser",
			"exclude": ["*"],
			"include": [
				"data/",
				"data/admin/",
				"data/admin/files/",
				"data/admin/files/afile",
				"data/admin/files/documents/",
				"data/admin/files/documents/***",
				"data/admin/files/images/test.jpg",
				"data/admin/files/images/test1.jpg",
				"data/admin/files_encryption/keyfiles/images/test1.jpg.key",
				"data/admin/files_encryption/share-keys/images/test1.jpg.admin.shareKey"
			],
			"restorebase": "snapshot/0815/"
		}';
		$this->configServiceMock->expects($this->any())->method('getDataDir')->will($this->returnValue('/home/data'));
		$this->configServiceMock->expects($this->once())->method('getLogfileName')->will($this->returnValue('/dev/null'));
		$this->apiMock->expects($this->once())->method('isAppEnabled')->with($this->equalTo('files_encryption'))->will(
				$this->returnValue(true));
		
		$expectedCommand = "rsync -rtgov -e 'ssh -q -i \"\" -o StrictHostKeyChecking=no -o UserKnownHostsFile='" .
				 " --numeric-ids --omit-dir-times" . " --include='data/'" . " --include='data/admin/'" .
				 " --include='data/admin/files/'" . " --include='data/admin/files/afile'" .
				 " --include='data/admin/files_encryption/keyfiles/afile.key'" .
				 " --include='data/admin/files_encryption/share-keys/afile.admin.shareKey'" .
				 " --include='data/admin/files/documents/'" . " --include='data/admin/files/documents/***'" .
				 " --include='data/admin/files_encryption/keyfiles/documents/***'" .
				 " --include='data/admin/files_encryption/share-keys/documents/***'" .
				 " --include='data/admin/files/images/test.jpg'" .
				 " --include='data/admin/files_encryption/keyfiles/images/test.jpg.key'" .
				 " --include='data/admin/files_encryption/share-keys/images/test.jpg.admin.shareKey'" .
				 " --include='data/admin/files/images/test1.jpg'" .
				 " --include='data/admin/files_encryption/keyfiles/images/test1.jpg.key'" .
				 " --include='data/admin/files_encryption/share-keys/images/test1.jpg.admin.shareKey'" .
				 " --exclude='*' mockUser@mockUser.trustedspace.de:snapshot/0815/ /home >> /dev/null 2>&1";
		
		$this->configServiceMock->expects($this->once())->method('setRestoreCommand')->with($this->equalTo($expectedCommand));
		$this->cut->scheduleRestoreJob($restoreConfig);
	}
}