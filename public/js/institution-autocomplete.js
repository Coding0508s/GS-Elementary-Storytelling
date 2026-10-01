document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('institution_name');
    const list = document.getElementById('institution_suggestions');
    const status = document.getElementById('institution-status');
    if (!input || !list || !status) return;

    let timer;
    let requestVersion = 0;
    let activeIndex = -1;

    function close() {
        clearTimeout(timer);
        requestVersion++;
        list.style.display = 'none';
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        activeIndex = -1;
        for (const item of list.children) item.setAttribute('aria-selected', 'false');
    }

    function activate(index) {
        activeIndex = index;
        Array.from(list.children).forEach((item, i) => item.setAttribute('aria-selected', String(i === index)));
        const item = list.children[index];
        input.setAttribute('aria-activedescendant', item.id);
        item.scrollIntoView({ block: 'nearest' });
    }

    function select(item) {
        input.value = item.textContent;
        close();
        input.dispatchEvent(new Event('input', { bubbles: true }));
        // A selection should not reopen the search results.
        clearTimeout(timer);
        status.textContent = `${input.value} 선택됨`;
    }

    async function search(query) {
        const version = ++requestVersion;
        try {
            const response = await fetch(`${input.dataset.suggestionsUrl}?q=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('기관 검색 실패');
            const institutions = await response.json();
            if (version !== requestVersion || document.activeElement !== input) return;
            list.replaceChildren();
            activeIndex = -1;
            input.removeAttribute('aria-activedescendant');
            for (const [index, institution] of institutions.entries()) {
                const item = document.createElement('div');
                item.id = `institution-option-${index}`;
                item.className = 'p-2 border-bottom suggestion-item';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', 'false');
                item.textContent = institution;
                // Keep focus on the combobox until the click is handled.
                item.addEventListener('mousedown', event => event.preventDefault());
                item.addEventListener('click', () => select(item));
                list.appendChild(item);
            }
            const hasResults = institutions.length > 0;
            list.style.display = hasResults ? 'block' : 'none';
            input.setAttribute('aria-expanded', String(hasResults));
            status.textContent = hasResults
                ? `${institutions.length}개 검색 결과. 위아래 화살표로 이동하고 Enter로 선택하세요.`
                : '검색 결과가 없습니다. 기관명을 직접 입력할 수 있습니다.';
        } catch {
            if (version !== requestVersion || document.activeElement !== input) return;
            close();
            status.textContent = '기관 목록을 불러오지 못했습니다. 기관명을 직접 입력해주세요.';
        }
    }

    input.addEventListener('input', () => {
        close();
        timer = setTimeout(() => search(input.value.trim()), 300);
    });
    input.addEventListener('focus', () => search(input.value.trim()));
    input.addEventListener('blur', close);
    input.addEventListener('keydown', event => {
        if (event.key === 'Escape' || event.key === 'Tab') {
            close();
            return;
        }
        if (input.getAttribute('aria-expanded') !== 'true' || !list.children.length) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                search(input.value.trim());
            }
            return;
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const count = list.children.length;
            const next = event.key === 'ArrowDown' ? (activeIndex + 1) % count : (activeIndex <= 0 ? count - 1 : activeIndex - 1);
            activate(next);
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            select(list.children[activeIndex]);
        }
    });
});
