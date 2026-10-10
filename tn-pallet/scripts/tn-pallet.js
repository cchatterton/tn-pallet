(function ($) {
    'use strict';
    $(function () {
        const root = document.querySelector('.tnp-admin');
        if (!root || typeof TNP === 'undefined') return;
        const strings = TNP.strings;
        let documentState = TNP.document;
        let revision = TNP.revision;
        let previous = TNP.previous;
        let dirty = false;
        let jsonDirty = false;
        let busy = false;
        let attachmentId = 0;
        let mediaFrame;
        const sections = root.querySelector('#tnp-sections');
        const json = root.querySelector('#tnp-json');
        const custom = root.querySelector('#tnp-custom-colours');
        const status = root.querySelector('#tnp-save-state');
        const tabs = [...root.querySelectorAll('[role="tab"]')];

        function feedback(message, error = false) {
            const notice = root.querySelector('#tnp-feedback');
            notice.className = 'notice ' + (error ? 'notice-error' : 'notice-success');
            notice.querySelector('p').textContent = message;
            notice.hidden = false;
            if (error) notice.focus();
        }
        function syncJson() {
            json.value = JSON.stringify(documentState, null, 2);
        }
        function changed() {
            dirty = true;
            status.textContent = strings.unsaved;
            syncJson();
        }
        function countColours() {
            return documentState.sections.reduce((count, section) => count + section.colours.length, 0);
        }
        function button(text, className) {
            const element = document.createElement('button');
            element.type = 'button';
            element.className = className;
            element.textContent = text;
            return element;
        }
        function newColour(section) {
            const names = new Set(documentState.sections.flatMap(row => row.colours.map(colour => colour.name)));
            let number = 1;
            section.colours.forEach(colour => {
                if (colour.name.startsWith(section.slug + '-')) {
                    const suffix = colour.name.slice(section.slug.length + 1);
                    if (/^\d+$/.test(suffix)) number = Math.max(number, Number(suffix) + 1);
                }
            });
            while (names.has(section.slug + '-' + number)) number++;
            return {name: section.slug + '-' + number, hex: '#000000', rgb: '0 0 0'};
        }
        function addCard(section, colour, row) {
            const card = document.createElement('div');
            card.className = 'tnp-palette-card';
            const header = document.createElement('div');
            header.className = 'tnp-card-header';
            const name = document.createElement('strong');
            name.textContent = colour.name;
            const remove = button('×', 'button-link-delete tnp-remove-colour');
            remove.setAttribute('aria-label', strings.removeColour + ' ' + colour.name);
            remove.title = strings.removeColour + ' ' + colour.name;
            remove.addEventListener('click', () => {
                if (section.colours.length === 1) { feedback(strings.lastSection, true); return; }
                section.colours.splice(section.colours.indexOf(colour), 1);
                $(card).find('.tnp-colour-picker').wpColorPicker('destroy');
                card.remove();
                row.querySelector('.tnp-add-colour').focus();
                changed();
            });
            header.append(name, remove);
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'tnp-colour-picker';
            input.value = colour.hex;
            input.setAttribute('aria-label', strings.colour + ' ' + colour.name);
            card.append(header, input);
            row.insertBefore(card, row.querySelector('.tnp-add-colour'));
            function updateColour(value) {
                if (!/^#[0-9a-f]{6}$/i.test(value)) {
                    input.setAttribute('aria-invalid', 'true');
                    return;
                }
                input.removeAttribute('aria-invalid');
                colour.hex = value.toLowerCase();
                colour.rgb = [1, 3, 5].map(offset => parseInt(value.slice(offset, offset + 2), 16)).join(' ');
                changed();
            }
            $(input).wpColorPicker({
                defaultColor: colour.hex,
                hide: false,
                change: (event, ui) => updateColour(ui.color.toString()),
                clear: () => { input.setAttribute('aria-invalid', 'true'); }
            });
            input.addEventListener('input', () => {
                if (!/^#[0-9a-f]{6}$/i.test(input.value)) input.setAttribute('aria-invalid', 'true');
            });
            input.addEventListener('change', () => updateColour(input.value));
            if (!$(input).closest('.wp-picker-container').hasClass('wp-picker-active')) $(input).wpColorPicker('open');
            return card;
        }
        function render() {
            $(sections).find('.tnp-colour-picker').each(function () { $(this).wpColorPicker('destroy'); });
            sections.replaceChildren();
            documentState.sections.forEach((section, index) => {
                const panel = document.createElement('section');
                panel.className = 'tnp-section tnp-panel';
                const header = document.createElement('div');
                header.className = 'tnp-section-header';
                const label = document.createElement('label');
                label.textContent = strings.section;
                label.htmlFor = 'tnp-section-' + index;
                const input = document.createElement('input');
                input.id = label.htmlFor;
                input.type = 'text';
                input.maxLength = 100;
                input.value = section.name;
                input.addEventListener('input', () => { section.name = input.value; changed(); });
                const slug = document.createElement('code');
                slug.textContent = section.slug;
                const remove = button(strings.removeSection, 'button-link button-link-delete');
                remove.addEventListener('click', () => {
                    if (documentState.sections.length === 1) { feedback(strings.lastSection, true); return; }
                    if (!window.confirm(strings.removeConfirm)) return;
                    documentState.sections.splice(index, 1);
                    render(); changed();
                    root.querySelector('#tnp-section-name').focus();
                });
                header.append(label, input, slug, remove);
                const row = document.createElement('div');
                row.className = 'tnp-palette-row';
                row.tabIndex = 0;
                row.setAttribute('role', 'region');
                row.setAttribute('aria-label', section.name);
                const add = button('+ ' + strings.addColour, 'button tnp-add-colour');
                add.addEventListener('click', () => {
                    if (countColours() >= 300) { feedback(strings.limit, true); return; }
                    const colour = newColour(section);
                    section.colours.push(colour);
                    const card = addCard(section, colour, row);
                    changed();
                    card.querySelector('input').focus();
                    card.scrollIntoView({block: 'nearest', inline: 'nearest'});
                });
                row.append(add);
                panel.append(header, row);
                sections.append(panel);
                section.colours.forEach(colour => addCard(section, colour, row));
            });
            custom.checked = documentState.allow_custom_editor_colours;
            root.querySelector('#tnp-previous-json').disabled = !previous;
        }
        function activateTab(tab) {
            if (tab.id === 'tnp-tab-palette' && jsonDirty) {
                feedback(strings.jsonPending, true);
                return;
            }
            tabs.forEach(item => {
                const selected = item === tab;
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected ? 0 : -1;
                item.classList.toggle('nav-tab-active', selected);
                document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
            });
            if (tab.id === 'tnp-tab-palette' && jsonDirty) {
                feedback(strings.jsonPending, true);
            }
        }
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activateTab(tab));
            tab.addEventListener('keydown', event => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                if (next !== undefined) { event.preventDefault(); tabs[next].focus(); activateTab(tabs[next]); }
            });
        });
        // A pending JSON edit must not be overwritten by colour-picker events.
        root.querySelector('#tnp-panel-palette').addEventListener('focusin', event => {
            if (jsonDirty && event.target.matches('input,button')) {
                activateTab(root.querySelector('#tnp-tab-json'));
                json.focus(); feedback(strings.jsonPending, true);
            }
        });
        root.querySelector('#tnp-add-section').addEventListener('submit', event => {
            event.preventDefault();
            if (jsonDirty) { feedback(strings.jsonPending, true); return; }
            const input = root.querySelector('#tnp-section-name');
            const name = input.value.trim();
            const slug = name.toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
            if (!/^[a-z][a-z0-9-]{0,79}$/.test(slug) || documentState.sections.some(section => section.slug === slug)) {
                feedback(strings.invalidName, true); return;
            }
            if (documentState.sections.length >= 50 || countColours() > 297) { feedback(strings.limit, true); return; }
            const section = {name, slug, colours: []};
            documentState.sections.push(section);
            for (let i = 0; i < 3; i++) section.colours.push(newColour(section));
            render(); changed(); input.value = '';
            sections.lastElementChild.querySelector('input').focus();
        });
        custom.addEventListener('change', () => { documentState.allow_custom_editor_colours = custom.checked; changed(); });
        json.addEventListener('input', () => { jsonDirty = true; status.textContent = strings.unsaved; });
        window.addEventListener('beforeunload', event => {
            if (dirty || jsonDirty || busy) { event.preventDefault(); event.returnValue = ''; }
        });
        async function request(endpoint, body, message) {
            if (busy) return;
            busy = true;
            const controls = [...root.querySelectorAll('button,input,textarea')].map(element => [element, element.disabled]);
            controls.forEach(([element]) => { element.disabled = true; });
            root.setAttribute('aria-busy', 'true');
            feedback(message);
            try {
                const response = await fetch(TNP.restUrl + endpoint, {
                    method: 'POST', credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json', 'X-WP-Nonce': TNP.nonce},
                    body: JSON.stringify({...body, revision})
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || strings.failed);
                documentState = result.document; revision = result.revision; previous = result.previous;
                render(); syncJson(); dirty = false; jsonDirty = false;
                if (result.last_ai && result.last_ai.json) {
                    root.querySelector('#tnp-ai-json').value = result.last_ai.json;
                    root.querySelector('#tnp-last-ai').hidden = false;
                }
                status.textContent = strings.saved;
                feedback(strings.success);
            } catch (error) {
                feedback(error.message || strings.failed, true);
            } finally {
                controls.forEach(([element, disabled]) => { element.disabled = disabled; });
                root.querySelector('#tnp-previous-json').disabled = !previous;
                busy = false; root.removeAttribute('aria-busy');
            }
        }
        root.querySelector('#tnp-save').addEventListener('click', () => {
            if (jsonDirty) { feedback(strings.jsonPending, true); return; }
            if (sections.querySelector('[aria-invalid="true"]')) { feedback(strings.invalidColour, true); return; }
            request('palette', {document: JSON.stringify(documentState)}, strings.saving);
        });
        root.querySelector('#tnp-save-json').addEventListener('click', () => {
            if (window.confirm(strings.jsonConfirm)) request('palette', {document: json.value}, strings.saving);
        });
        root.querySelector('#tnp-copy-json').addEventListener('click', async () => {
            try { await navigator.clipboard.writeText(json.value); feedback(strings.copied); }
            catch (error) { json.focus(); json.select(); feedback(strings.copyFallback); }
        });
        root.querySelector('#tnp-export-json').addEventListener('click', () => {
            const url = URL.createObjectURL(new Blob([json.value], {type: 'application/json'}));
            const link = document.createElement('a');
            link.href = url; link.download = 'tn-pallet.json'; link.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        });
        root.querySelector('#tnp-previous-json').addEventListener('click', () => {
            json.value = previous; jsonDirty = true; status.textContent = strings.unsaved; feedback(strings.previousLoaded);
        });
        const choose = root.querySelector('#tnp-select-image');
        if (choose) {
            choose.addEventListener('click', () => {
                if (!mediaFrame) {
                    mediaFrame = wp.media({title: strings.chooseImage, button: {text: strings.useImage}, library: {type: ['image/jpeg', 'image/png', 'image/webp']}, multiple: false});
                    mediaFrame.on('select', () => {
                        const image = mediaFrame.state().get('selection').first().toJSON();
                        attachmentId = image.id;
                        root.querySelector('#tnp-image-name').textContent = image.filename;
                        const preview = root.querySelector('#tnp-image-preview');
                        preview.src = image.url; preview.hidden = false;
                        root.querySelector('#tnp-interpret').disabled = false;
                    });
                }
                mediaFrame.open();
            });
            root.querySelector('#tnp-interpret').addEventListener('click', () => {
                if (dirty || jsonDirty) { feedback(strings.saveFirst, true); return; }
                request('interpret', {attachment_id: attachmentId}, strings.interpreting);
            });
        }
        render(); syncJson(); dirty = false; status.textContent = strings.saved;
    });
})(jQuery);
