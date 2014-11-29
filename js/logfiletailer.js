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

	var easyBackup_logfileViewer = {
		task : null,
		ajaxRunning : false,
		getLog : function() {
			if (easyBackup_logfileViewer.ajaxRunning) {
				return;
			}
			if($('#easyBackup_log').size() == 0) {
				// No matching output div (did we leave the page?)
				easyBackup_logfileViewer.stopTail();
				return;
			}
			easyBackup_logfileViewer.ajaxRunning = true;
			$.ajax({
				url : OC.generateUrl('/apps/easybackup/logfileview'),
				type : 'GET',
				success : function(html) {
					easyBackup_logfileViewer.ajaxRunning = false;
					logDiv = $('#easyBackup_log');
					logDiv.html(html);
				},
				error : function(error) {
					easyBackup_logfileViewer.ajaxRunning = false;
					// Ignore errors silently
					//OC.dialogs.alert(error, t('easybackup', 'Error'));
				}
			});
		},

		startTail : function() {
			if (easyBackup_logfileViewer.task != null) {
				// Already running
				return;
			}
			if($('#easyBackup_log').size() == 0) {
				// No matching output div (we're not on the right page?)
				return;
			}
			$('#easyBackup_logfileViewer_startViewer').prop('disabled', true);
			$('#easyBackup_logfileViewer_stopViewer').prop('disabled', false);
			$('#easyBackup_log').html(t('easybackup', 'Reading data...'));
			easyBackup_logfileViewer.task = setInterval(
					easyBackup_logfileViewer.getLog, 6000);
		},

		stopTail : function() {
			$('#easyBackup_logfileViewer_startViewer').prop('disabled', false);
			$('#easyBackup_logfileViewer_stopViewer').prop('disabled', true);
			if (easyBackup_logfileViewer.task == null) {
				return;
			}
			clearInterval(easyBackup_logfileViewer.task);
			easyBackup_logfileViewer.task = null;
		}
	};
	$(document).ready(
			function() {
				$('#easyBackup_logfileViewer_startViewer').on('click',
						easyBackup_logfileViewer.startTail);
				$('#easyBackup_logfileViewer_stopViewer').on('click',
						easyBackup_logfileViewer.stopTail);
				easyBackup_logfileViewer.startTail();
			});
})(jQuery);
