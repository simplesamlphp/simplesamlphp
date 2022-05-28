'use strict';

/**
 * This class is used for the logout page.
 *
 * It allows the user to start logout from all the services where a session exists (if any). Logout will be
 * triggered by loading an iframe where we send a SAML logout request to the SingleLogoutService endpoint of the
 * given SP. After successful response back from the SP, we will load a small template in the iframe that loads
 * this class again (IFrameLogoutHandler branch of the constructor), and sends a message to the main page
 * (core:logout-iframe branch).
 *
 * The iframes communicate the logout status for their corresponding association via an event message, for which the
 * main page is listening (the clearAssociation() method). Upon reception of a message, we'll check if there was an
 * error or not, and call the appropriate method (either completed() or failed()).
 */
class SimpleSAMLLogout {
    constructor(page)
    {
        if (page === 'core:logout-iframe') { // main page
            this.populateData();
            if (Object.keys(this.sps).length === 0) {
                // all SPs completed logout, this was a reload
                this.btncontinue.click();
            }
            this.btnall.addEventListener("click", this.initLogout.bind(this));
            window.addEventListener('message', this.clearAssociation.bind(this), false);
        } else if (page === 'IFrameLogoutHandler') { // iframe
            var data = document.querySelector('i[id="data"]');
            var message = {
                spId: data.getAttribute('data-spid')
            };
            if (data.hasAttribute('data-error')) {
                message.error = data.getAttribute('data-error');
            }

            window.parent.postMessage(JSON.stringify(message), SimpleSAMLLogout.getOrigin());
        }
    }


    /**
     * Clear an association when it is signaled from an iframe (either failed or completed).
     *
     * @param event The event containing the message from the iframe.
     */
    clearAssociation(event)
    {
        if (event.origin !== SimpleSAMLLogout.getOrigin()) {
            // we don't accept events from other origins
            return;
        }
        var data = JSON.parse(event.data);
        if (typeof data.error === 'undefined') {
            this.completed(data.spId);
        } else {
            this.failed(data.spId, data.error);
        }

        if (Object.keys(this.sps).length === 0) {
            if (this.nfailed === 0) {
                // all SPs successfully logged out, continue w/o user interaction
                this.btncontinue.click();
            }
        }
    }


    /**
     * Mark logout as completed for a given SP.
     *
     * This method will be called by the SimpleSAML\IdP\IFrameLogoutHandler class upon successful logout from the SP.
     *
     * @param id The ID of the SP that completed logout successfully.
     */
    completed(id)
    {
        if (typeof this.sps[id] === 'undefined') {
            return;
        }

        this.sps[id].icon.classList.remove('fa-spin');
        this.sps[id].icon.classList.remove('fa-circle-o-notch');
        this.sps[id].icon.classList.add('fa-check-circle');
        this.sps[id].element.style.display = "none";
        delete this.sps[id];
        this.finish();
    }


    /**
     * Mark logout as failed for a given SP.
     *
     * This method will be called by the SimpleSAML\IdP\IFrameLogoutHandler class upon logout failure from the SP.
     *
     * @param id The ID of the SP that failed to complete logout.
     * @param reason The reason why logout failed.
     */
    failed(id, reason)
    {
        if (typeof this.sps[id] === 'undefined') {
            return;
        }

        this.sps[id].element.classList.add('error');
        this.sps[id].icon.classList.remove('fa-spin', 'fa-circle-o-notch');
        this.sps[id].icon.classList.add('fa-exclamation-circle');

        this.errmsg.classList.remove('hidden');
        this.errfrm.classList.remove('hidden');

        delete this.sps[id];
        this.nfailed++;
        this.finish();
    }


    /**
     * Finish the logout process, acting according to the current situation:
     *
     * - If there were failures, an error message is shown telling the user to close the browser.
     * - If everything went ok, then we just continue back to the service that started logout.
     *
     * Note: this method won't do anything if there are SPs pending logout (e.g. waiting for the timeout).
     */
    finish()
    {
        if (Object.keys(this.sps).length > 0) { // pending services
            return;
        }

        if (typeof this.timeout !== 'undefined') {
            clearTimeout(this.timeout);
        }

        if (this.nfailed > 0) { // some services failed to log out
            this.errmsg.classList.remove('hidden');
            this.errfrm.classList.remove('hidden');
            this.actions.classList.add('hidden');
        } else { // all services done
            this.btncontinue.click();
        }
    }


    /**
     * Get the origin of the current page.
     */
    static getOrigin()
    {
        var origin = window.location.origin;
        if (!origin) {
            // IE < 11 does not support window.location.origin
            origin = window.location.protocol + "//" + window.location.hostname +
                (window.location.port ? ':' + window.location.port : '');
        }
        return origin;
    }


    /**
     * This method starts logout on all SPs where we are currently logged in.
     *
     * @param event The click event on the "Yes, all services" button.
     */
    initLogout(event)
    {
        event.preventDefault();

        this.btnall.disabled = true;
        this.btncancel.disabled = true;
        Object.keys(this.sps).forEach((function (id) {
            this.sps[id].status = 'inprogress';
            this.sps[id].startTime = (new Date()).getTime();
            this.sps[id].iframe.setAttribute('src', this.sps[id].iframe.getAttribute('data-url'));
            this.sps[id].icon.classList.add('fa-spin');
        }).bind(this));
        this.initTimeout();
    }


    /**
     * Set timeouts for all logout operations.
     *
     * If an SP didn't reply by the timeout, we'll mark it as failed.
     */
    initTimeout()
    {
        var timeout = 10;

        for (const id in this.sps) {
            if (typeof id === 'undefined') {
                continue;
            }
            if (!this.sps.hasOwnProperty(id)) {
                continue;
            }
            if (this.sps[id].status !== 'inprogress') {
                continue;
            }
            var now = ((new Date()).getTime() - this.sps[id].startTime) / 1000;

            if (this.sps[id].timeout <= now) {
                this.failed(id, 'Timed out', window.document);
            } else {
                // get the lowest timeout we have
                if ((this.sps[id].timeout - now) < timeout) {
                    timeout = this.sps[id].timeout - now;
                }
            }
        }

        if (Object.keys(this.sps).length > 0) {
            // we have associations left, check them again as soon as one expires
            this.timeout = setTimeout(this.initTimeout.bind(this), timeout * 1000);
        } else {
            this.finish();
        }
    }


    /**
     * This method populates the data we need from data-* properties in the page.
     */
    populateData()
    {
        this.sps = {};
        this.btnall = document.querySelector('button[id="btn-all"]');
        this.btncancel = document.querySelector('button[id="btn-cancel"]');
        this.btncontinue = document.querySelector('button[id="btn-continue"]');
        this.actions = document.querySelector('div[id="original-actions"]');
        this.errmsg = document.querySelector('div[id="error-message"]');
        this.errfrm = document.querySelector('form[id="error-form"]');
        this.nfailed = 0;
        var that = this;

        // initialise SP status and timeout arrays
        document.querySelectorAll('li[id^="sp-"]').forEach(function (currentValue, index, arr) {
            var id = currentValue.getAttribute('data-id');
            var iframe = document.querySelector('iframe[id="iframe-' + id + '"]');
            var status = currentValue.getAttribute('data-status');

            switch (status) {
                case 'failed':
                    that.nfailed++;
                case 'completed':
                    return;
            }

            that.sps[id] = {
                status: status,
                timeout: currentValue.getAttribute('data-timeout'),
                element: currentValue,
                iframe: iframe,
                icon: document.querySelector('i[id="icon-' + id + '"]'),
            };
        });
    }
}

export default SimpleSAMLLogout;
