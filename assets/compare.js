(() => {
    'use strict';
    document.querySelectorAll('.mcac').forEach(root => {
        let products = [], selected = [], initialized = false, loading = false;
        const catalog = root.querySelector('.mcac-catalog');
        const results = root.querySelector('.mcac-results');
        const notice = root.querySelector('.mcac-notice');
        const retry = root.querySelector('.mcac-retry');
        const clear = root.querySelector('.mcac-clear');
        const open = root.querySelector('.mcac-open');
        const dialog = root.querySelector('.mcac-dialog');
        const search = root.querySelector('.mcac-search');
        const pickerList = root.querySelector('.mcac-picker-list');
        const pickerNotice = root.querySelector('.mcac-picker-notice');
        const el = (tag, className, text) => {
            const node = document.createElement(tag);
            node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        };
        function render() {
            catalog.replaceChildren(); results.replaceChildren();
            root.querySelector('.mcac-count').textContent = `Выбрано ${selected.length} из 3`;
            clear.disabled = !selected.length;
            products.filter(p => selected.includes(p.id)).forEach(p => {
                const card = el('article', 'mcac-card' + (selected.includes(p.id) ? ' is-selected' : ''));
                if (p.image) {
                    const image = el('img', 'mcac-image'); image.src = p.image; image.alt = ''; image.loading = 'lazy';
                    image.addEventListener('error', () => image.replaceWith(el('div', 'mcac-placeholder', 'ASIC')));
                    card.append(image);
                } else card.append(el('div', 'mcac-placeholder', 'ASIC'));
                card.append(el('h3', '', p.name), el('span', `mcac-stock mcac-stock-${p.status}`, p.availability), el('p', 'mcac-card-price', p.price));
                const button = el('button', 'mcac-remove', 'Убрать из сравнения');
                button.type = 'button'; button.dataset.id = p.id;
                button.setAttribute('aria-pressed', String(selected.includes(p.id)));
                button.setAttribute('aria-label', `${selected.includes(p.id) ? 'Убрать из сравнения' : 'Сравнить'}: ${p.name}`);
                button.setAttribute('aria-disabled', String(selected.length === 3 && !selected.includes(p.id)));
                button.addEventListener('click', () => {
                    if (selected.includes(p.id)) selected = selected.filter(id => id !== p.id);
                    else if (selected.length < 3) selected.push(p.id);
                    else { notice.textContent = 'Можно сравнить до трёх моделей. Уберите одну, чтобы добавить другую.'; return; }
                    notice.textContent = selected.length === 1 ? 'Добавьте ещё одну модель для сравнения.' : `Выбрано моделей: ${selected.length}.`;
                    render(); open.focus();
                });
                card.append(button); catalog.append(card);
            });
            const chosen = selected.map(id => products.find(p => p.id === id)).filter(Boolean);
            if (chosen.length < 3 && products.length) {
                const add = el('button', 'mcac-add-slot', '+ Добавить устройство'); add.type = 'button';
                add.addEventListener('click', showPicker); catalog.append(add);
            }
            if (!chosen.length) { results.append(el('p', 'mcac-empty', 'Нажмите «Добавить устройство» и выберите модели из каталога.')); return; }
            results.style.setProperty('--mcac-columns', chosen.length);
            const header = el('div', 'mcac-row mcac-result-heading');
            header.append(el('div', 'mcac-label', 'Характеристики'));
            chosen.forEach(p => {
                const cell = el('div', 'mcac-value'); cell.append(el('strong', '', p.name));
                const link = el('a', '', 'Открыть товар ↗'); link.href = p.url; cell.append(link); header.append(cell);
            });
            results.append(header);
            [['price', 'Цена'], ['hashrate', 'Хешрейт'], ['power', 'Потребление'], ['availability', 'Наличие']].forEach(([key, label]) => {
                const row = el('div', 'mcac-row'); row.append(el('h3', 'mcac-label', label));
                chosen.forEach(p => {
                    const cell = el('div', 'mcac-value'); cell.append(el('span', 'mcac-mobile-name', p.name));
                    cell.append(el('span', key === 'price' ? 'mcac-price' : '', p[key])); row.append(cell);
                }); results.append(row);
            });
        }
        function renderPicker() {
            const query = search.value.trim().toLocaleLowerCase('ru');
            const matches = products.filter(p => p.name.toLocaleLowerCase('ru').includes(query));
            pickerList.replaceChildren();
            pickerNotice.textContent = `Выбрано ${selected.length} из 3. Найдено устройств: ${matches.length}.` + (selected.length === 3 ? ' Уберите одно, чтобы добавить другое.' : '');
            if (!matches.length) pickerList.append(el('p', 'mcac-empty', 'Ничего не найдено. Попробуйте другое название.'));
            matches.forEach(p => {
                const row = el('article', 'mcac-picker-item');
                if (p.image) {
                    const img = el('img', 'mcac-image'); img.src = p.image; img.alt = ''; img.loading = 'lazy';
                    img.addEventListener('error', () => img.replaceWith(el('div', 'mcac-placeholder', 'ASIC'))); row.append(img);
                } else row.append(el('div', 'mcac-placeholder', 'ASIC'));
                const info = el('div', 'mcac-picker-info');
                info.append(el('h4', '', p.name), el('p', '', `${p.hashrate} · ${p.power}`), el('span', `mcac-stock mcac-stock-${p.status}`, p.availability), el('p', 'mcac-card-price', p.price));
                const active = selected.includes(p.id);
                const button = el('button', 'mcac-select', active ? '✓ Убрать' : '+ Добавить');
                button.type = 'button'; button.dataset.id = p.id;
                button.setAttribute('aria-pressed', String(active));
                button.setAttribute('aria-label', `${active ? 'Убрать' : 'Добавить'}: ${p.name}`);
                button.setAttribute('aria-disabled', String(selected.length === 3 && !active));
                button.addEventListener('click', () => {
                    if (selected.includes(p.id)) selected = selected.filter(id => id !== p.id);
                    else if (selected.length < 3) selected.push(p.id);
                    else { pickerNotice.textContent = 'Можно сравнить до трёх устройств. Уберите одно из выбранных, чтобы добавить другое.'; return; }
                    notice.textContent = `Выбрано устройств: ${selected.length}.`;
                    render(); renderPicker(); pickerList.querySelector(`[data-id="${p.id}"]`)?.focus();
                });
                row.append(info, button); pickerList.append(row);
            });
        }
        function showPicker() {
            search.value = ''; renderPicker(); dialog.showModal(); search.focus();
        }
        async function load() {
            if (loading) return;
            loading = true; retry.hidden = true; open.disabled = true; root.setAttribute('aria-busy', 'true');
            notice.textContent = 'Загружаем актуальные данные…';
            const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 15000);
            try {
                const response = await fetch(root.dataset.endpoint, {cache: 'no-store', signal: controller.signal});
                if (!response.ok) throw new Error('HTTP');
                const data = await response.json();
                if (!Array.isArray(data.products)) throw new Error('Invalid response');
                products = data.products;
                selected = initialized ? selected.filter(id => products.some(p => p.id === id)) : products.slice(0, 2).map(p => p.id);
                initialized = true; render(); open.disabled = !products.length;
                notice.textContent = products.length ? 'Добавляйте и убирайте устройства — сравнение обновляется сразу.' : 'В каталоге пока нет доступных устройств для сравнения.';
            } catch (_) {
                products = []; selected = []; render();
                notice.textContent = 'Не удалось загрузить данные. Проверьте соединение и повторите попытку.';
                retry.hidden = false;
            } finally { clearTimeout(timer); loading = false; root.removeAttribute('aria-busy'); }
        }
        open.addEventListener('click', showPicker);
        search.addEventListener('input', renderPicker);
        root.querySelector('.mcac-close').addEventListener('click', () => dialog.close());
        root.querySelector('.mcac-done').addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => open.focus());
        clear.addEventListener('click', () => { selected = []; notice.textContent = 'Выбор очищен.'; render(); open.focus(); });
        retry.addEventListener('click', load);
        load();
    });
})();
