function updateStatus() {

	var nFailed = 0;
	var nProgress = 0;
	for (sp in window.spStatus) {
		switch (window.spStatus[sp]) {
		case 'failed':
			nFailed += 1;
			break;
		case 'inprogress':
			nProgress += 1;
			break;
		}
	}

	if (nFailed > 0) {
		$('#logout-failed-message').show();
	}

	if (nProgress == 0 && nFailed == 0) {
		$('#logout-completed').show();
		$('#done-form').submit();
	}
}

function updateSPStatus(spId, status, reason) {
	if (window.spStatus[spId] == status) {
		/* Unchanged. */
		return;
	}

	$('#statusimage-' + spId).attr('src', window.stateImage[status]).attr('alt', window.stateText[status]).attr('title', reason);
	window.spStatus[spId] = status;

	updateStatus();
}
function logoutCompleted(spId) {
	updateSPStatus(spId, 'completed', '');
}
function logoutFailed(spId, reason) {
	updateSPStatus(spId, 'failed', reason);
}

function timeoutSPs() {
	for (sp in window.spStatus) {
		if (window.spStatus[sp] == 'inprogress') {
			logoutFailed(sp, 'Timeout');
		}
	}
}

function asyncUpdate() {
	jQuery.getJSON(window.asyncURL, window.spStatus, function(data, textStatus) {
		for (sp in data) {
			if (data[sp] == 'completed') {
				logoutCompleted(sp);
			} else if (data[sp] == 'failed') {
				logoutFailed(sp, 'async update');
			}
		}
		window.setTimeout(asyncUpdate, 1000);
	});
}


$('document').ready(function(){
	if (window.type == 'js') {
		window.timeoutID = window.setTimeout(timeoutSPs, window.timeoutIn * 1000);
		window.setTimeout(asyncUpdate, 1000);
		updateStatus();
	} else if (window.type == 'init') {
		$('#logout-type-selector').attr('value', 'js');
		$('#logout-all').focus();
	}
});
