/* eslint-disable */
/* eslint-disable no-console */

import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import { get_string } from 'core/str';
import Config from 'core/config';

// Global state variables.
let fileToUpload = null;
let targetSectionNum = 0;
let courseId = 0;

export const init = async(config) => {
    courseId = config.courseid;
    console.log("🚀 EQUELLA: Listener ready on Course " + courseId);

    injectCustomStyles();

    document.addEventListener('dragover', allowDrop, true);
    document.addEventListener('dragenter', handleDragEnter, true);
    document.addEventListener('drop', handleDrop, true);
};

const injectCustomStyles = () => {
    if (document.getElementById('equella-dnd-styles')) return;
    const style = document.createElement('style');
    style.id = 'equella-dnd-styles';
    style.innerHTML = `
        .equella-dnd-hide {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }
    `;
    document.head.appendChild(style);
};

const handleDragEnter = (e) => {
    // Look for Main Section OR Sidebar Section
    const targetSection = e.target.closest('li.section, .courseindex-section');

    if (targetSection) {
        // 1. Hide everything everywhere
        document.querySelectorAll('.dndupload-preview-wrapper, .dndupload-preview-overlay, .overlay-preview').forEach(el => {
            el.classList.add('equella-dnd-hide');
        });

        // 2. Un-hide ONLY the current section
        targetSection.querySelectorAll('.equella-dnd-hide').forEach(el => {
            el.classList.remove('equella-dnd-hide');
        });
    }
};

const allowDrop = (e) => {
    if (e.dataTransfer.types.includes('Files')) {
        e.preventDefault();
        e.stopPropagation();
    }
};

const handleDrop = (e) => {
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        const targetElement = e.target.closest('li.section, .courseindex-section, .courseindex-item');

        if (!targetElement) return;

        fileToUpload = e.dataTransfer.files[0];

        // Correctly identify the Section Number (0, 1, 2...)
        targetSectionNum = findSectionNumber(targetElement);

        // Strict check: we allow 0, but not null/undefined
        if (targetSectionNum === null) {
            console.warn("Could not determine section number for drop target");
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        forceHideVisuals();

        console.log(`📂 Dropped file: ${fileToUpload.name} into Section NUMBER: ${targetSectionNum}`);
        showUploadModal(fileToUpload);
    }
};

const forceHideVisuals = () => {
    const wrappers = document.querySelectorAll('.dndupload-preview-wrapper, .dndupload-preview-overlay, .overlay-preview');
    wrappers.forEach(el => {
        el.classList.add('equella-dnd-hide');
    });

    const targets = document.querySelectorAll('.section, li.section, .courseindex-section, .courseindex-item');
    targets.forEach(el => {
        el.classList.remove('dragover');
        el.classList.remove('dndupload-dropzone');
        el.classList.remove('dndupload-over');
    });

    document.body.classList.remove('dndupload-inprogress');
    document.body.classList.remove('dndupload-active');
};

/**
 * FIXED: Find Section NUMBER (0, 1, 2...)
 * Strictly ignores Database IDs to prevent "off-by-one" errors.
 */
const findSectionNumber = (target) => {
    if (!target) return null;

    // --- CASE 1: Sidebar (Course Index) ---
    // If dropped on an ITEM, go up to the SECTION div.
    if (target.matches('.courseindex-item') || target.closest('.courseindex-item')) {
        const sidebarSection = target.closest('.courseindex-section');
        if (sidebarSection) {
            // Use 'data-number' (e.g. 2). Ignore 'data-id' (e.g. 3).
            return sidebarSection.getAttribute('data-number');
        }
    }

    // If dropped directly on the Sidebar SECTION header
    if (target.matches('.courseindex-section')) {
        return target.getAttribute('data-number');
    }

    // --- CASE 2: Main Content Area ---
    const mainSection = target.closest('li.section');
    if (mainSection) {
        // 1. Try 'data-section' (Standard for Section Number)
        const dataSection = mainSection.getAttribute('data-section');
        if (dataSection !== null) return dataSection;

        // 2. Try 'data-number' (Some themes use this)
        const dataNumber = mainSection.getAttribute('data-number');
        if (dataNumber !== null) return dataNumber;

        // 3. Fallback: Parse ID "section-2" -> 2
        const idStr = mainSection.getAttribute('id');
        if (idStr && idStr.startsWith('section-')) {
            return idStr.replace('section-', '');
        }
    }

    return null;
};

// ... (Rest of the file remains unchanged) ...

const showUploadModal = async(file) => {
    const modal = await ModalSaveCancel.create({
        title: 'Add to EQUELLA',
        body: '<p>Loading...</p>',
        large: true,
    });

    const uploadLabel = await get_string('upload', 'core');
    modal.setButtonText('save', uploadLabel);
    modal.show();

    const formHtml = `
        <div class="equella-upload-form">
            <div class="alert alert-info">
                <strong>File:</strong> ${file.name} (${(file.size / 1024).toFixed(1)} KB)
            </div>
            <div class="form-group">
                <label><strong>Does this include copyright content from other sources?*</strong></label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="eq_copyright" id="cp_yes" value="Yes">
                        <label class="form-check-label" for="cp_yes">Yes</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="eq_copyright" id="cp_no" value="No">
                        <label class="form-check-label" for="cp_no">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="eq_title"><strong>Title*</strong></label>
                <input type="text" class="form-control" id="eq_title" value="${file.name}">
                <small class="form-text text-muted">Must be at least 6 characters.</small>
            </div>
            <div class="form-group">
                <label for="eq_desc"><strong>Description*</strong></label>
                <textarea class="form-control" id="eq_desc" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="eq_kw"><strong>Keywords</strong></label>
                <input type="text" class="form-control" id="eq_kw" placeholder="Separate with commas">
            </div>
            <div id="eq_upload_error" class="alert alert-danger" style="display:none;"></div>
        </div>
    `;

    modal.setBody(formHtml);

    // Save Handler.
    modal.getRoot().on(ModalEvents.save, function(e) {
        e.preventDefault();
        validateAndUpload(modal);
    });
};

const validateAndUpload = (modal) => {
    const root = modal.getRoot()[0];
    const copyrightEl = root.querySelector('input[name="eq_copyright"]:checked');
    const title = root.querySelector('#eq_title').value.trim();
    const desc = root.querySelector('#eq_desc').value.trim();
    const kw = root.querySelector('#eq_kw').value.trim();
    const errorBox = root.querySelector('#eq_upload_error');

    if (!copyrightEl) {
        showError(errorBox, "Please indicate whether your resource contains copyright material.");
        return;
    }
    if (title.length < 6) {
        showError(errorBox, "Your title needs to be at least six characters long.");
        return;
    }
    if (desc.length < 2) {
        showError(errorBox, "Please describe the content of your resource.");
        return;
    }

    const formData = new FormData();
    formData.append('repo_upload_file', fileToUpload);
    formData.append('sesskey', Config.sesskey);
    formData.append('course', courseId);
    formData.append('section', targetSectionNum);
    formData.append('module', 'equella');
    formData.append('type', 'Files');
    formData.append('dndcopyright', copyrightEl.value);
    formData.append('dndtitle', title);
    formData.append('dnddesc', desc);
    formData.append('dndkw', kw);

    modal.setBody('<div class="text-center p-4"><h3>Uploading...</h3><p>Please wait while we send the file to Equella.</p></div>');
    modal.setFooter('');

    const xhr = new XMLHttpRequest();
    xhr.open("POST", Config.wwwroot + '/mod/equella/dndupload.php', true);

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const result = JSON.parse(xhr.responseText);
                if (result && result.error == 0) {
                    window.location.reload();
                } else {
                    alert("Error: " + (result.error || "Unknown error"));
                    modal.hide();
                }
            } catch (e) {
                alert("Server error: Invalid JSON response.");
                modal.hide();
            }
        } else {
            alert("Upload failed. Status: " + xhr.status);
            modal.hide();
        }
    };
    xhr.send(formData);
};

const showError = (el, msg) => {
    el.innerHTML = msg;
    el.style.display = 'block';
};