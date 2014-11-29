/**
 * ownCloud - EasyBackup
 * 
 * @author Sebastian Kanzow
 * @copyright 2014 System k2 GmbH info@systemk2.de
 * 
 * This library is free software; you can redistribute it and/or modify it under
 * the terms of the GNU AFFERO GENERAL PUBLIC LICENSE License as published by
 * the Free Software Foundation; either version 3 of the License, or any later
 * version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU AFFERO GENERAL PUBLIC LICENSE for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this library. If not, see <http://www.gnu.org/licenses/>.
 * 
 */

(function($) {
	var easyBackup = {
		hostNameRegExp : /^[A-z0-9]+@[A-z0-9]+\.[A-z0-9]+\.[a-z]{2,3}$/,
		toggleStatusDetails : function() {
			if ($(easybackup_status_details).css('display') == 'none') {
				$('#easybackup_status_details').show('blind');
			} else {
				$('#easybackup_status_details').hide('blind');
			}
		},
		setBackupHost : function() {
			var span = $('#easybackup_host');
			var hostName = $('#easybackup_hostName');
			var oldBackupHost = hostName.text();
			var oldHtml = span.html();
			$(span).html('');
			var input = $('<input type="text" class="easybackup_input"/>').val(
					oldBackupHost);
			var form = $('<form></form>');
			form.append(input);
			span.append(form);
			input.focus();

			var checkInput = function() {
				var backupHostName = input.val().trim();
				if (!easyBackup.hostNameRegExp
						.test(backupHostName)) {
					throw t('easybackup',
							'Hostname must be of format user@host.domain.xyz');
				}
				return true;
			};

			function restore() {
				input.tipsy('hide');
				form.remove();
				$(span).html(oldHtml);
			}

			form.submit(function(event) {
				event.stopPropagation();
				event.preventDefault();
				if (input.hasClass('error')) {
					return false;
				}

				try {
					var newBackupHost = input.val();
					input.tipsy('hide');
					// form.remove();
					if (newBackupHost === oldBackupHost) {
						restore();
					} else {
						checkInput();
						if (input.hasClass('error')) {
							return false;
						}
						form.remove();
						$(span).html(newBackupHost);
						easyBackup.putJSONRequest(
								'/apps/easybackup/backuphost', {
									oldBackupHost : oldBackupHost,
									newBackupHost : newBackupHost
								}, span, function(data) {
									restore();
									$('#easybackup_hostName').html(
											data.newBackupHost);
									$('#easybackup_preconditions').html(
											data.preconditionsHtml);
								}, function() {
									restore();
									$('#easybackup_hostName').html(
											oldBackupHost);
								});
					}
				} catch (error) {
					span.css('background-image', 'none');
					input.attr('title', error);
					input.tipsy({
						gravity : 'w',
						trigger : 'manual'
					});
					input.tipsy('show');
					input.addClass('error');
				}
				return false;
			});
			input.keyup(function(event) {
				// verify backupHostName on typing
				try {
					checkInput();
					input.tipsy('hide');
					input.removeClass('error');
				} catch (error) {
					input.attr('title', error);
					input.tipsy({
						gravity : 'w',
						trigger : 'manual'
					});
					input.tipsy('show');
					input.addClass('error');
				}
				if (event.keyCode === 27) {
					restore();
				}
			});
			input.click(function(event) {
				event.stopPropagation();
				event.preventDefault();
			});
			input.blur(function() {
				form.trigger('submit');
			});
		},
		submitKeyFile : function() {
			if ($('#easybackup_upload_key').val() != '') {
				document.getElementById('easybackup_upload_target').onload = easyBackup.keyUploadDone;
				$('#easybackup_fileupload').css('background-image',
						'url(' + OC.imagePath('core', 'loading.gif') + ')');
				$('#easybackup_fileupload_form').submit();
			}
		},
		keyUploadDone : function() {
			$('#easybackup_fileupload').css('background-image', 'none');
			var frameBody = frames['easybackup_upload_target'].document
					.getElementsByTagName("body")[0];
			var html = frameBody.innerText || frameBody.textContent;
			var json;
			try {
				json = $.parseJSON(html);
			} catch (error) {
				OC.dialogs.alert(error + ': ' + html, t('easybackup',
						'Could not parse upload result'));
				return;
			}
			if (json.status != 'success') {
				OC.dialogs.alert(json.message, t('easybackup',
						'Could not upload file'));
				return;
			}
			// Refresh status
			$('#easybackup_preconditions').html(json.data.preconditionsHtml);
			easyBackup.statusDetailsVisible = false;
			OC.dialogs.info(t('easybackup',
					'The new private key was successfully set'), t(
					'easybackup', 'Upload finished'));
			return;

		},
		putJSONRequest : function(route, data, elementWithSpinner, onSuccess = function(data) {},
				onError = function() {}) {
			// mark as loading (temp element)
			elementWithSpinner.css('background', 'url('
					+ OC.imagePath('core', 'loading.gif') + ') no-repeat');
			$.ajax({
				url : OC.generateUrl(route),
				type : 'PUT',
				contentType : 'application/json',
				dataType : 'json',
				data : JSON.stringify(data),
				success : function(json) {
					elementWithSpinner.css('background-image', 'none');
					if (json.status == 'success') {
						onSuccess(json.data);
					} else {
						OC.dialogs.alert(json.message, t('easybackup',
								'Request failed'));
						onError();
					}
				},
				error : function(error) {
					elementWithSpinner.css('background-image', 'none');
					var errorMessage = t('easybackup', error.statusText);
					try {
						if (typeof error.responseJSON != 'undefined' && error.responseJSON != null) {
							// errorObject = $.parseJSON(error.responseText);
							if (typeof error.responseJSON.message != 'undefined') {
								errorMessage += ': ' + error.responseJSON.message;
							} else {
								errorMessage += ': ' + error.responseText;
							}
						}
					} catch (e) {
						errorMessage += ' (' + e + ')';
					}
					OC.dialogs.alert(errorMessage, t('easybackup', 'Error'));
					onError();
				}
			});
		},
		activateSchedule : function() {
			var scheduled = false;
			if ($(this).prop('checked')) {
				$('#easybackup_schedules').show('blind');
				scheduled = true;
			} else {
				$('#easybackup_schedules').hide('blind');
			}
			easyBackup.putJSONRequest(
					'/apps/easybackup/setBackupScheduled', { scheduled : scheduled },
					$('#easybackup_schedule'));
		},
		changeStartTime : function() {
			easyBackup.putJSONRequest(
					'/apps/easybackup/setScheduleTime', { scheduleTime : $(this).val().replace('starthour_', '') },
					$('#easybackup_schedule'));
		},
		changeRadioState : function() {
			var id = $(this).attr('id').replace('easybackup_radio_', '');
			$('[id^=easybackup_select_]').css('visibility', 'hidden');
			$('#easybackup_select_' + id).css('visibility', 'visible');
			var scheduleVal = $('#easybackup_select_' + id).val();
			easyBackup.updateSchedule(scheduleVal);
		},
		changeSchedule : function() {
			easyBackup.updateSchedule($(this).val());
		},
		updateSchedule : function(schedule) {
			easyBackup.putJSONRequest(
					'/apps/easybackup/setBackupSchedule', 
					{ schedule : schedule }, $('#easybackup_schedule'));
		},
		startBackup : function() {
			easyBackup.putJSONRequest(
					'/apps/easybackup/schedulebackup', 
					{}, $('#easyBackup_startBackup'));
		},
		editRestoreTextArea : function() {
			var input = $(this).val().trim();
			if($(this).attr('default') == input) {
				input = "";
				$(this).val("");
			}
			if(input == "") {
				$('#easybackup_restore').prop('disabled', true);
				return;
			}
			try {
				$.parseJSON(input);
				$('#easybackup_restore').removeProp('disabled');
			} catch(e) {
				$('#easybackup_restore').prop('disabled', true);
			}
		},
		submitRestoreCommand : function() {
			easyBackup.putJSONRequest(
					'/apps/easybackup/restoreaction', {
						restoreConfig : $('#easybackup_restore_input').val()
					}, $(this), function(data) {
						OC.dialogs.info(t('easybackup',
						'The restore action will be executed with the next CRON execution'), t(
						'easybackup', 'Restore scheduled'));
					});
		}
	};

	$(document).ready(
			function() {
				$('#easybackup_host').on('click',
						easyBackup.setBackupHost);
				$('#easybackup_upload_key').on('change',
						easyBackup.submitKeyFile);
				$('#easybackup_status').on('click',
						'#easybackup_toggle_status_details',
						easyBackup.toggleStatusDetails);
				$('#easybackup_schedule_check').on('change',
						easyBackup.activateSchedule);
				$('#easybackup_starthour').on('change',
						easyBackup.changeStartTime);
				$('[name=easybackup_frequency]').on('change',
						easyBackup.changeRadioState);
				$('[id^=easybackup_select_]').on('change',
						easyBackup.changeSchedule);
				$('#easyBackup_startBackup').on('click',
						easyBackup.startBackup);
				$('#easybackup_restore_input').on('click input propertychange',
						easyBackup.editRestoreTextArea);
				$('#easybackup_restore').on('click',
						easyBackup.submitRestoreCommand);
				$('#fidelbackup_explain_sshkey').tipsy({html : true, title: 'helptext'});
	});
})(jQuery);
