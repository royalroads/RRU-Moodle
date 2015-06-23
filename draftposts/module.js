/*
 * draftpost.js - handle drafts through Javascript
 *
 * @author: Andrew Zoltay
 * date:    2011-11-29
 */

/*
 *  Define local_draftposts namespace
 */
 M.local_draftposts = {
    Y : null,                    // YUI instance for use in functions
    cfg : {
        saveinterval : null,     // Interval between draft saves - in seconds
        sesskey : null,          // user session key
        supportedformat : null   // message body text format
    },
    intervalid : null,           // interval id
    loaddraft : null,            // flag to say if we load the draft
    forumid : null,              // forum id
    statuselement : null,        // reference to status element for draft action statuses

    init : function (Y, cfg, loaddraft, forumid) {
        this.Y = Y;
        this.cfg.saveinterval = cfg.saveinterval; // in seconds
        this.cfg.sesskey = cfg.sesskey;
        this.cfg.supportedformat = cfg.supportedformat;
        this.loaddraft = loaddraft;
        this.forumid = forumid;

        // Create and store draft status
        this.Y.one('#id_savedraft').insert('<span class ="draft-status" id="id_draftstatus"></span>', 'after');
        this.statuselement = this.Y.one('#id_draftstatus');

        // Restore draft for post if one exists
        if (this.loaddraft == 1) {
            this.get_draft(this.forumid);
        }

        // Start the interval
        this.intervalid = setInterval(this.save_draft, this.cfg.saveinterval * 1000);

    },

    /*
     *  Save the draft using AJAX
     */
    save_draft : function() {
        var subject = '';
        var message = '';

        if (M.local_draftposts.cfg.supportedformat) {

        	subject = M.local_draftposts.Y.one('#id_subject').get('value');
        	if(subject == ''){
        		subject = 'No title';
        	}

            if (tinyMCE.get('id_message') ) {
                message = tinyMCE.get('id_message').getContent();
            }
        } else {
            alert('Draftposts - unsupported editor format');
            return;
        }
        // Exit if nothing to save
        if ((message == '')) {
            return;
        }

        // encode to prep for AJAX call
        subject = encodeURIComponent(subject);
        message = encodeURIComponent(message);

        // Do the AJAX call
        M.local_draftposts.Y.io(M.cfg.wwwroot + '/local/draftposts/ajax_draftpost.php', {
            method : 'POST',
            data : 'action=savedraft&sesskey=' + M.local_draftposts.cfg.sesskey + '&subject=' + subject + '&message=' + message,
            on : {
                    start: function () {
                        // Stop the save timer while we save the draft
                        clearInterval(M.local_draftposts.intervalid);
                        M.local_draftposts.statuselement.set('innerHTML','Saving draft...');
                    },
                    success: function (tid, outcome) {
                        if (!outcome) {
                            alert('IO FATAL');
                            return false;
                        }
                        try {
                            var data = M.local_draftposts.Y.JSON.parse(outcome.responseText);
                            if (data.error) {
                                M.local_draftposts.statuselement.set('innerHTML', data.error);
                            } else {
                                M.local_draftposts.statuselement.set('innerHTML', data.result);
                                setTimeout(M.local_draftposts.clear_draft_status, 3000); // Clear status after 3 seconds
                            }
                        } catch (e) {
                            alert('Save draft error');
                            return false;
                        }

                        return true;
                    },
                    complete: function () {
                        //Clear status field and restart save timer
                        M.local_draftposts.intervalid = setInterval(M.local_draftposts.save_draft, M.local_draftposts.cfg.saveinterval * 1000);
                    },
                    failure: function(id, outcome) {
                        M.local_draftposts.statuselement.set('innerHTML','Save draft AJAX error - ' . outcome.statusText);
                        setTimeout(M.local_draftposts.clear_draft_status, 10000);  // Clear status after 10 seconds
                    }
            }
        })
    },

    /*
     *  Restore the draft redirecting to the list of drafts
     */
    get_draft : function(forumid) {
    	var fd = forumid;
        var answer = confirm(M.str.local_draftposts.confirmload);
        if (answer) {
        	var path = M.cfg.wwwroot + '/local/draftposts/draftposts.php?f=' + fd;

        	Y.config.win.location = path;
        } else {
        	//The user is selecting cancel to create a new draft
        }	// if (answer)
    }, // function get_draft

    /*
     *  Clear the innerHTML of the draft status element
     */
    clear_draft_status : function () {
        M.local_draftposts.statuselement.set('innerHTML','');
    }
};

