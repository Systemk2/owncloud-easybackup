<?php
/**
 * ownCloud - EasyBackup
 *
 * @author Sebastian Kanzow
 * @copyright 2014 System k2 GmbH info@systemk2.de
 *
 *            This library is free software; you can redistribute it and/or
 *            modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *            License as published by the Free Software Foundation; either
 *            version 3 of the License, or any later version.
 *
 *            This library is distributed in the hope that it will be useful,
 *            but WITHOUT ANY WARRANTY; without even the implied warranty of
 *            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *            GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 *            You should have received a copy of the GNU Affero General Public
 *            License along with this library. If not, see <http://www.gnu.org/licenses/>.
 *
 */
use \OCA\EasyBackup\StatusContainer;
?>

<div>
	<div class="bold">
		<?php p($l->t('4 easy steps to create a reliable backup on a German backup server'));?>
	</div>
	<div>
		<div class="easybackup_explanation">
			1)
			<?php
			p($l->t('Create a new SSH public/private key pair'));
			?>
		</div>
		<button id="easyBackup_createKey">
			<?php p($l->t('Create key now')); ?>
		</button>
		<div class="easybackup_explanation">
			1b)
			<?php
			p($l->t('Alternatively, you can also upload a previously created private key'));
			$helpText = $l->t('To generate a new key pair enter the following command on a Linux shell');
			$helpText .= ':<br><b>ssh-keygen -t rsa -b 2048 -f out.key</b><br>';
			?>
		&nbsp; <img id="fidelbackup_explain_sshkey" data-helptext="<?php print_unescaped($helpText); ?>" src="<?php print_unescaped(\OCP\Util::imagePath('easybackup', 'help.png')); ?>">
		</div>
		<form id="easybackup_fileupload_form" method="POST" enctype="multipart/form-data" action="<?php p($_['keyUploadUrl'])?>" target="easybackup_upload_target">
			<div>
				<span id="easybackup_fileupload" class="easybackup_label"> <input id="easybackup_upload_key" type="file" name="easybackup_sshKeyFile" /> <a id="easybackup_upload_icon" class="svg icon-upload" href="#">&nbsp;&nbsp;&nbsp;&nbsp;</a>
				<?php p($l->t('upload private SSH key'))?>
			</span>
			</div>
		</form>
	</div>
	<div class="easybackup_explanation">
		2)
		<?php
		p($l->t('Get an account (2 months / 1GB free)  at'));
		?>
		<a href="http://www.trustedspace.de" target="_new">www.trustedspace.de</a>
	</div>
	<div id="easybackup_publickeymanagement">
	<?php
	print_unescaped($this->inc('publickey.inc'));
	?>
	</div>
	<div class="easybackup_explanation">
		3)
		<?php p($l->t('Enter your newly created TrustedSpace backup user name'));?>
	</div>
	<span class="easybackup_label"> <?php p($l->t('TrustedSpace user'))?>:
	</span>&nbsp; <span id="easybackup_userNameEdit"> <span id="easybackup_userName">
			<?php p($_['userName']); ?>
		</span> <a class="action" href="#">&nbsp;<img src="<?php print_unescaped(\OCP\Util::imagePath('core', 'actions/rename.svg')); ?>" />
	</a>

</div>

<!--
<div>
<input type="checkbox" id="easybackup_databasedump_check" <?php //if($_['isDatabaseDump']) print_unescaped('checked="true"'); ?> />
	<?php p($l->t('Backup database content')); ?>
</div>
 -->
<div id="easybackup_schedule">
	<div class="easybackup_explanation">
		4)
		<?php p($l->t('Start backing up your data on regular basis'));?>
	</div>
	<input type="checkbox" id="easybackup_schedule_check" <?php if($_['isScheduled']) print_unescaped('checked="true"'); ?> />
	<?php p($l->t('Execute automatic backups at specified time intervals')); ?>
	<div id="easybackup_schedules" <?php if(!$_['isScheduled']) print_unescaped('style="display: none"'); ?>>
		<div>
			<?php p($l->t('Execution start time') . ':'); ?>
			&nbsp; <select id="easybackup_starthour">
				<?php
				for($i = 0; $i < 24; $i ++) {
					$selected = $_ ['scheduleTime'] === $i ? 'selected="true"' : '';
					print_unescaped(sprintf('<option value="starthour_%02d" %s>', $i, $selected));
					print_unescaped(sprintf('%02dh00</option>\n', $i));
				}
				?>
			</select>
		</div>
		<?php
		foreach ( $_ ['schedule']->getScheduleParams() as $params ) {
			print_unescaped($this->inc('schedule.inc', $params) . '<br>');
		}
		?>
	</div>
</div>
<div class="easybackup_explanation">
	-
	<?php p($l->t('or')); ?>
	-
</div>
<div>
	<button id="easyBackup_startBackup">
		<?php p($l->t('Start backup manually')); ?>
	</button>
	<span id="easybackup_waitbar_span" <?php if($_['isExecuting']) print_unescaped('class="easybackup_waitbar"'); ?>>&nbsp;</span>
</div>
<!-- Invisible target for upload response -->
<iframe id="easybackup_upload_target" style="visibility: hidden; width: 1px; height: 1px" name="easybackup_upload_target" src=""></iframe>
