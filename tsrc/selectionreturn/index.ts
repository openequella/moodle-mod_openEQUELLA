/** Read the return settings that locallib.php renders on the selection session form. */
function readReturnForm(form: HTMLFormElement) {
    const { equellaUrl, returnState } = form.dataset;
    const linksField = form.elements.namedItem('tlelinks');
    if (equellaUrl && returnState && linksField instanceof HTMLInputElement) {
        return { equellaOrigin: new URL(equellaUrl).origin, returnState, linksField };
    }

    throw new Error('Selection session form needs data-equella-url, data-return-state and a tlelinks field');
}

/**
 * Start an openEQUELLA selection session in the iframe, then submit the form with the user's selection.
 *
 * The form carries the sesskey, so only the session's own reply is accepted: a version 1
 * `oeq.selection` message from the iframe, sent from the `data-equella-url` origin and
 * carrying this form's return state. Any other message is ignored.
 */
export function launchOeqSelectionSession(iframe: HTMLIFrameElement, launchUrl: string, form: HTMLFormElement): void {
    const { equellaOrigin, returnState, linksField } = readReturnForm(form);

    const onMessage = ({ origin, source, data }: MessageEvent<Record<string, unknown> | null>) => {
        const fromChooser = origin === equellaOrigin && source === iframe.contentWindow;
        const isSelection = data?.type === 'oeq.selection' && data.version === 1 && data.state === returnState;
        if (fromChooser && isSelection) {
            window.removeEventListener('message', onMessage);
            linksField.value = JSON.stringify(data.links);
            form.submit();
        }
    };

    // Listen before loading, so the chooser can never reply before the handler exists.
    window.addEventListener('message', onMessage);
    iframe.src = launchUrl;
}
