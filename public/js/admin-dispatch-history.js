(() => {
    const history = document.getElementById('alimtalk-progress');
    if (!history) return;
    const tabs = Array.from(history.querySelectorAll('[data-history-tab]'));
    const rows = Array.from(history.querySelectorAll('[data-dispatch-row]'));
    const panel = history.querySelector('[role="tabpanel"]');
    const empty = history.querySelector('[data-history-empty]');
    const scroll = history.querySelector('.dispatch-history-scroll');
    let selected = 'active';
    const render = () => {
        const counts = {active: 0, review: 0, done: 0};
        rows.forEach(row => {
            counts[row.dataset.historyGroup]++;
            row.hidden = row.dataset.historyGroup !== selected;
        });
        tabs.forEach(tab => {
            const current = tab.dataset.historyTab === selected;
            tab.setAttribute('aria-selected', String(current));
            tab.tabIndex = current ? 0 : -1;
            tab.querySelector('[data-history-count]').textContent = counts[tab.dataset.historyTab];
        });
        history.querySelectorAll('[data-history-summary]').forEach(el => {
            el.textContent = counts[el.dataset.historySummary];
        });
        history.querySelector('[data-history-visible]').textContent = counts[selected];
        panel.setAttribute('aria-labelledby', `dispatch-tab-${selected}`);
        empty.hidden = counts[selected] > 0;
        empty.textContent = {active: '예약·진행 중인 발송 작업이 없습니다.', review: '확인이 필요한 발송 작업이 없습니다.', done: '최근 완료·취소 내역이 없습니다.'}[selected];
    };
    const selectTab = tab => {
        selected = tab.dataset.historyTab;
        scroll.scrollTop = 0;
        render();
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectTab(tab));
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault();
            selectTab(tabs[next]);
            tabs[next].focus();
        });
    });
    history.querySelectorAll('[data-dispatch-open]').forEach(button => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(`dispatch-dialog-${button.dataset.dispatchOpen}`);
            if (dialog && !dialog.open) dialog.showModal();
        });
    });
    history.querySelectorAll('.dispatch-dialog').forEach(dialog => {
        dialog.querySelectorAll('[data-dispatch-close]').forEach(button => {
            button.addEventListener('click', () => dialog.close());
        });
        // Native dialog handles Escape, focus trapping and background inertness.
        dialog.addEventListener('close', () => {
            const id = dialog.querySelector('[data-dispatch-id]').dataset.dispatchId;
            const row = rows.find(row => row.dataset.dispatchRow === id);
            if (row) {
                if (row.hidden) selectTab(tabs.find(tab => tab.dataset.historyTab === row.dataset.historyGroup));
                row.querySelector('[data-dispatch-open]').focus({preventScroll: true});
            }
        });
    });
    document.addEventListener('dispatches:updated', event => {
        event.detail.forEach(dispatch => {
            const row = rows.find(row => row.dataset.dispatchRow === dispatch.id);
            if (!row) return;
            const card = document.getElementById(`dispatch-dialog-${dispatch.id}`).querySelector('[data-dispatch-id]');
            row.dataset.historyGroup = dispatch.history_group;
            row.dataset.status = card.dataset.status;
            row.querySelector('[data-history-status]').textContent = card.querySelector('[data-dispatch-status]').textContent;
        });
        render();
    });
    render();
    // Keep the existing target-confirmation flow immediately accessible after a POST.
    const preview = history.querySelector('.dispatch-dialog[data-auto-open="true"]');
    if (preview) preview.showModal();
})();
