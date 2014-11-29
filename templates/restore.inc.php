<div class="bold">
	<?php p($l->t('3 easy steps to recover files from previous snapshots'));?>
</div>
<div class="easybackup_explanation">
	1)
	<?php p($l->t('Choose the snapshot and the files you want to recover at'));?>
	<a href="http://www.trustedspace.de" target="_new">www.trustedspace.de</a>
</div>
<div class="easybackup_explanation">
	2)
	<?php p($l->t('Copy/paste your recovery config here') . ':');?>
</div>
<div>
	<textarea id="easybackup_restore_input" default="<?php $default = $l->t('Put your config here...'); p($default); ?>">
		<?php p($default); ?>
	</textarea>

</div>
<div class="easybackup_explanation">
	3)
	<?php p($l->t('Schedule recovery for execution') . ':');?>
</div>
<div>

	<button id="easybackup_restore" disabled="true">
		<?php
		p($l->t('Restore files'));
		?>
	</button>
</div>
