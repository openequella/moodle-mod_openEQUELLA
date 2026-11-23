import {launchOeqSelectionSession} from '../selectionreturn';

const equellaUrl = 'https://oeq.example.edu/inst/signon.do';
const equellaOrigin = new URL(equellaUrl).origin;
const launchUrl = '/mod/equella/ltilaunch.php';
const state = 'eeab'.repeat(8);
const links = [
    {name: 'Teacher’s guide', url: 'https://oeq.example.edu/items/1', extra: {title: '<Guide>'}},
    {name: 'Worksheet', url: 'https://oeq.example.edu/items/2', filename: 'worksheet.pdf', mimeType: 'application/pdf'},
];
const message = {type: 'oeq.selection', version: 1, state, links};

const setupSelectionSession = () => {
    const form = document.body.appendChild(document.createElement('form'));
    form.dataset.equellaUrl = equellaUrl;
    form.dataset.returnState = state;
    form.innerHTML = '<input type="hidden" name="tlelinks">';
    const iframe = document.body.appendChild(document.createElement('iframe'));
    const submit = jest.spyOn(form, 'submit').mockImplementation(() => {});
    const send = (data: unknown, origin = equellaOrigin, source = iframe.contentWindow) =>
        window.dispatchEvent(new MessageEvent('message', {data, origin, source}));
    const launch = () => launchOeqSelectionSession(iframe, launchUrl, form);
    return {form, iframe, submit, send, launch};
}

describe('launchOeqSelectionSession', () => {
    afterEach(() => {
        jest.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('accepts a selection even if the chooser replies as soon as the iframe loads', () => {
        const {iframe, submit, send, launch} = setupSelectionSession();
        const load = jest.spyOn(iframe, 'src', 'set').mockImplementation(() => send(message));

        launch();

        expect(load).toHaveBeenCalledWith(launchUrl);
        expect(submit).toHaveBeenCalledTimes(1);
    });

    it('submits a whole item and an attachment once with their metadata intact', () => {
        const {form, submit, send, launch} = setupSelectionSession();
        launch();

        send(message);
        send(message);

        expect(submit).toHaveBeenCalledTimes(1);
        expect(JSON.parse(String(new FormData(form).get('tlelinks')))).toEqual(links);
    });

    it.each([
        {reason: 'a message from another origin', data: message, origin: location.origin},
        {reason: 'a message from another window', data: message, source: window},
        {reason: 'a message that null', data: null},
        {reason: 'another return state', data: {...message, state: 'another'}},
        {reason: 'another message type', data: {...message, type: 'other'}},
        {reason: 'another protocol version', data: {...message, version: 2}},
    ])('ignores $reason and still accepts the real selection', ({data, origin, source}) => {
        const {submit, send, launch} = setupSelectionSession();
        launch();

        send(data, origin, source);
        expect(submit).not.toHaveBeenCalled();

        send(message);
        expect(submit).toHaveBeenCalledTimes(1);
    });
});
