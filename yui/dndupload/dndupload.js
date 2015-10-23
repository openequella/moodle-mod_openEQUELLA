YUI.add('moodle-mod_equella-dndupload', function (Y) {

    var ModulenameNAME = 'equella_drag-n-drop_upload_handler';
    var DndUpload = function () {
        DndUpload.superclass.constructor.apply(this, arguments);
    };

    DndUpload.prototype = {
        hasOverriddenDndUpload: false,
        initializer: function (config) {
            var self = this;

            // Horribly nasty hack, since nothing in the dndupload chain fires any events we can listen for.
            // Since the dndupload module isn't there when we initialise, override its add_editing function
            // when we first see a "drop" on a section.
            // (could also be done with delays/setTimeout, but that's less reliable due to timing issues)
            var sections = Y.all('li.section.main');
            sections.each(function (el) {
                Y.on('drop', function (el) {
                    if (!this.hasOverriddenDndUpload) {
                        M.course_dndupload.core_file_handler_dialog = M.course_dndupload.file_handler_dialog;
                        self = this;
                        M.course_dndupload.file_handler_dialog = function (handlers, extension, file, section, sectionnumber) {

                            if (this.uploaddialog) {
                                var details = new Object();
                                details.isfile = true;
                                details.handlers = handlers;
                                details.extension = extension;
                                details.file = file;
                                details.section = section;
                                details.sectionnumber = sectionnumber;
                                details.addToEquellaOnly = true;   // Allison added
                                this.uploadqueue.push(details);
                                return;
                            }
                            this.uploaddialog = true;

                            var timestamp = new Date().getTime();
                            var uploadid = Math.round(Math.random() * 100000) + '-' + timestamp;
                            var content = '';
                            var sel;
                            if (extension in this.lastselected) {
                                sel = this.lastselected[extension];
                            } else {
                                sel = handlers[0].module;
                            }
                            //Allison changed dialog content
                            //content += '<p>'+M.util.get_string('actionchoice', 'moodle', file.name)+'</p>';
                            content += '<div id="dndupload_handlers' + uploadid + '">';
                            for (var i = 0; i < handlers.length; i++) {
                                //next if (handlers[i].module != 'equella'); //Allison
                                if (handlers[i].module == 'equella') {
                                    var id = 'dndupload_handler' + uploadid + handlers[i].module;
                                    content += '<label>Does this include copyright content from other sources (e.g. images, journal articles):* </label>';
                                    content += '<input type="radio" name="iscopyright" value="Yes">Yes <input type="radio" name="iscopyright" value="No">No<br />';
                                    content += '<label for="title">Title:* </label>';
                                    content += '<input type="text" size=100 style="width:650px" name="title" id="' + uploadid + 'title"/><br/>';
                                    content += '<label for="description">Description:* </label>';
                                    content += '<textarea cols="90" rows="4" name="desc" id="' + uploadid + 'desc"/></textarea><br/>';
                                    content += '<label for="keyword">Keyword (Separate keywords or phrases with commas): </label>';
                                    content += '<input type="text" size=100 style="width:650px" name="keyword" id="' + uploadid + 'kw"/><br/>';
                                    content += '</label><br/>';
                                }
                            }
                            content += '</div>';

                            var Y = this.Y;
                            var self = this;
                            var panel = new M.core.dialogue({
                              headerContent: '<img src="../mod/equella/pix/equella-blue.png" width="20px" /> <b>Equella Contribution Tool</b><br /> This adds the resource to Equella',
                              bodyContent: content,
                                width: '700px',
                                modal: true,
                                visible: true,
                                render: true,
                                align: {
                                    node: null,
                                    points: [Y.WidgetPositionAlign.CC, Y.WidgetPositionAlign.CC]
                                }
                            });
                            // When the panel is hidden - destroy it and then check for other pending uploads
                            panel.after("visibleChange", function (e) {
                                if (!panel.get('visible')) {
                                    panel.destroy(true);
                                    self.check_upload_queue();
                                }
                            });

                            // Add the submit/cancel buttons to the bottom of the dialog.
                            panel.addButton({
                                label: M.util.get_string('upload', 'moodle'),
                                action: function (e) {
                                    e.preventDefault();
                                    // Find out which module was selected
                                    //var module = false;
                                    var module = 'equella';
                                    var dnd_cp =  document.getElementsByName("iscopyright");
                                    var dnd_cp_value = '';
                                    var dnd_title = '';
                                    var dnd_desc = document.getElementById(uploadid + "desc");
                                    var dnd_kw = '';
                                    var div = Y.one('#dndupload_handlers' + uploadid);
                                    /* replaced component value fetching
                                     div.all('input').each(function(input) {
                                     if (input.get('checked')) {
                                     module = input.get('value');
                                     }
                                     });*/

                                    div.all('input').each(function (input) {
                                        if (input.get('id') === uploadid + "title") {
                                            dnd_title = input.get('value');
                                        }
                                        if (input.get('id') === uploadid + "kw") {
                                            dnd_kw = input.get('value');
                                        }
                                    });

                                    if (dnd_cp[0].checked) {
                                      dnd_cp_value = dnd_cp[0].value;
                                    } else if (dnd_cp[1].checked) {
                                      dnd_cp_value = dnd_cp[1].value;
                                    } else {
                                      alert("Please indicate whether your resource contains copyright material.");
                                      module = false;
                                      return;
                                    }

                                    if (dnd_title.length < 6) {
                                        alert('Your title needs to be at least six characters long.');
                                        module = false;
                                        return;
                                    }

                                    if (dnd_desc.value.length < 2) {
                                        alert('Please describe the content of your resource.');
                                        module = false;
                                        return;
                                    }

                                    if (!module) {
                                        return;
                                    }
                                    panel.hide();
                                    // Remember this selection for next time
                                    self.lastselected[extension] = module;
                                    // Do the upload
                                    self.upload_file_with_meta(file, section, sectionnumber, module, dnd_cp_value, dnd_title, dnd_desc.value, dnd_kw);
                                },
                                section: Y.WidgetStdMod.FOOTER
                            });
                            panel.addButton({
                                label: M.util.get_string('cancel', 'moodle'),
                                action: function (e) {
                                    e.preventDefault();
                                    panel.hide();
                                },
                                section: Y.WidgetStdMod.FOOTER
                            });
                        }
                        M.course_dndupload.upload_file_with_meta = function (file, section, sectionnumber, module, cp, title, desc, kw) {

                            // This would be an ideal place to use the Y.io function
                            // however, this does not support data encoded using the
                            // FormData object, which is needed to transfer data from
                            // the DataTransfer object into an XMLHTTPRequest
                            // This can be converted when the YUI issue has been integrated:
                            // http://yuilibrary.com/projects/yui3/ticket/2531274
                            var xhr = new XMLHttpRequest();
                            var self = this;

                            if (file.size > this.maxbytes) {
                                alert("'" + file.name + "' " + M.util.get_string('filetoolarge', 'moodle'));
                                return;
                            }

                            // Add the file to the display
                            var resel = M.course_dndupload.add_resource_element(file.name, section, module);

                            // Update the progress bar as the file is uploaded
                            xhr.upload.addEventListener('progress', function (e) {
                                if (e.lengthComputable) {
                                    var percentage = Math.round((e.loaded * 100) / e.total);
                                    resel.progress.style.width = percentage + '%';
                                }
                            }, false);

                            // Wait for the AJAX call to complete, then update the
                            // dummy element with the returned details
                            xhr.onreadystatechange = function () {
                                if (xhr.readyState == 4) {
                                    if (xhr.status == 200) {
                                        var result = JSON.parse(xhr.responseText);
                                        if (result) {
                                            if (result.error == 0) {
                                                // All OK - replace the dummy element.
                                                resel.li.outerHTML = result.fullcontent;
                                                if (self.Y.UA.gecko > 0) {
                                                    // Fix a Firefox bug which makes sites with a '~' in their wwwroot
                                                    // log the user out when clicking on the link (before refreshing the page).
                                                    resel.li.outerHTML = unescape(resel.li.outerHTML);
                                                }
                                                self.add_editing(result.elementid);
                                            } else {
                                                // Error - remove the dummy element
                                                resel.parent.removeChild(resel.li);
                                                alert(result.error);
                                            }
                                        }
                                    } else {
                                        alert(M.util.get_string('servererror', 'moodle'));
                                    }
                                }
                            };
                            // Prepare the data to send
                            var formData = new FormData();
                            formData.append('repo_upload_file', file);
                            formData.append('sesskey', M.cfg.sesskey);
                            formData.append('course', this.courseid);
                            formData.append('section', sectionnumber);
                            formData.append('module', module);
                            formData.append('type', 'Files');
                            formData.append('dndcopyright', cp);
                            formData.append('dndtitle', title);
                            formData.append('dnddesc', desc);
                            formData.append('dndkw', kw);

                            // Send the AJAX call
                            xhr.open("POST", M.cfg.wwwroot + '/mod/equella/dndupload.php', true);
                            xhr.send(formData);
                        }
                        this.hasOverriddenDndUpload = true;
                    }
                }, el, this);
            }, this);
        }
    };

    Y.extend(DndUpload, Y.Base, DndUpload.prototype, {
        NAME: ModulenameNAME,
        ATTRS: {
            // No attributes at present
            aparam: {}
        }
    });
    M.mod_equella = M.mod_equella || {};
    M.mod_equella.dndupload = M.mod_equella.dndupload || {};
    //M.mod_equella.dndupload.upload_file_with_meta = DndUpload.prototype.upload_file_with_meta;
    M.mod_equella.dndupload.init = function (config) { // 'config' contains the parameter values
        //console.log('I am in the javascript module, Yeah!');
        return new DndUpload(config); // 'config' contains the parameter values
    };
}, '@VERSION@', {
    requires: ['base', 'node', 'autocomplete']
});
