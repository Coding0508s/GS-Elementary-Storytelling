(() => {
    const boxes = () => Array.from(document.querySelectorAll('.recipient-checkbox'));
    const ids = () => boxes().filter(box => box.checked).map(box => box.value);
    const pageSelect = document.getElementById('select-all-recipients');
    const allSelect = document.getElementById('select-every-recipient');
    let scope = 'selected';
    const updateSelection = () => {
        const all = scope === 'all' || scope === 'failed';
        const count = all ? Number(allSelect.dataset.total) : ids().length;
        document.querySelectorAll('[data-selected-count]').forEach(el => { el.textContent = count.toLocaleString() + '명'; });
        boxes().forEach(box => { box.disabled = all; });
        if (pageSelect) {
            pageSelect.disabled = all || boxes().length === 0;
            pageSelect.checked = all || (count > 0 && count === boxes().length);
            pageSelect.indeterminate = !all && count > 0 && count < boxes().length;
        }
        document.getElementById('selection-scope-note').textContent = all
            ? (scope === 'failed' ? '현재 검색 조건에서 선택한 안내의 실패건 전체를 확인합니다. 상태가 바뀐 대상은 제외합니다.' : '검색과 관계없이 등록자 전체에게 보냅니다. 같은 번호에는 한 번만 보냅니다.')
            : '목록에서 받는 사람을 선택해주세요. 같은 번호에는 한 번만 보냅니다.';
        document.querySelectorAll('.js-alimtalk-form').forEach(form => {
            form.querySelector('button[type="submit"]').disabled = count === 0 || form.dataset.ready !== 'true' || Boolean(form.dataset.submitting);
        });
    };
    allSelect?.addEventListener('click', () => { scope = allSelect.dataset.failed === 'true' ? 'failed' : 'all'; updateSelection(); });
    document.getElementById('clear-recipients')?.addEventListener('click', () => {
        scope = 'selected';
        boxes().forEach(box => { box.checked = false; });
        updateSelection();
    });
    pageSelect?.addEventListener('change', event => {
        boxes().forEach(box => { box.checked = event.target.checked; });
        updateSelection();
    });
    document.addEventListener('change', event => {
        if (event.target.matches('.recipient-checkbox')) updateSelection();
    });
    document.addEventListener('applications:loaded', updateSelection);
    document.querySelectorAll('.js-alimtalk-form').forEach(form => {
        const mode = form.querySelector('.js-delivery-mode');
        const group = form.querySelector('.js-schedule-time');
        const syncMode = () => { const scheduled = mode.value === 'scheduled'; group.hidden = !scheduled; group.querySelector('input').disabled = !scheduled; group.querySelector('input').required = scheduled; };
        mode?.addEventListener('change', syncMode);
        if (mode) syncMode();
        const kind = form.querySelector('.js-notice-kind');
        const entry = form.querySelector('.js-entry-link');
        const syncKind = () => {
            const needed = kind.value === 'entry';
            entry.hidden = !needed;
            entry.querySelector('input').disabled = !needed;
            entry.querySelector('input').required = needed;
        };
        kind?.addEventListener('change', syncKind);
        if (kind) syncKind();
        form.addEventListener('submit', event => {
            if (scope === 'selected' && ids().length === 0) {
                event.preventDefault();
                window.alert('보낼 사람을 선택해주세요.');
                return;
            }
            if (form.dataset.submitting) { event.preventDefault(); return; }
            form.querySelectorAll('input[name="ids[]"], input[name="scope"], input[name="failed_all"]').forEach(el => el.remove());
            const values = [['scope', scope], ['failed_all', scope === 'failed' ? '1' : '0'], ...(scope === 'selected' ? ids().map(id => ['ids[]', id]) : [])];
            values.forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = value;
                form.appendChild(input);
            });
            form.dataset.submitting = 'true';
            form.querySelector('button[type="submit"]').disabled = true;
        });
    });
    document.querySelectorAll('.js-dispatch-confirm').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.submitting) { event.preventDefault(); return; }
            form.dataset.submitting = 'true';
            form.querySelector('button[type="submit"]').disabled = true;
        });
    });
    // Browsers may restore old checkboxes/disabled buttons when navigating back.
    window.addEventListener('pageshow', () => {
        scope = 'selected';
        boxes().forEach(box => { box.checked = false; });
        document.querySelectorAll('form[data-submitting]').forEach(form => {
            delete form.dataset.submitting;
            form.querySelectorAll('button[type="submit"]').forEach(button => { button.disabled = false; });
            form.querySelector('input[name="action"]')?.remove();
        });
        updateSelection();
        document.querySelectorAll('.js-notice-kind, .js-delivery-mode').forEach(select => select.dispatchEvent(new Event('change')));
    });
    updateSelection();

    document.querySelectorAll('[data-dispatch-id]').forEach(card => {
        const toggle = card.querySelector('[data-edit-toggle]');
        const editor = card.querySelector('[data-editor]');
        toggle?.addEventListener('click', () => {
            editor.hidden = !editor.hidden;
            toggle.setAttribute('aria-expanded', String(!editor.hidden));
            if (!editor.hidden) editor.querySelector('select').focus();
        });
        card.querySelector('[data-edit-close]')?.addEventListener('click', () => {
            editor.hidden = true; toggle.setAttribute('aria-expanded', 'false'); toggle.focus();
        });
        const editForm = card.querySelector('.js-dispatch-edit');
        if (editForm) {
            const mode = editForm.querySelector('[name="delivery_mode"]');
            const group = editForm.querySelector('[data-edit-time]');
            const syncTime = () => {
                const scheduled = mode.value === 'scheduled';
                group.hidden = !scheduled;
                group.querySelector('input').disabled = !scheduled;
                group.querySelector('input').required = scheduled;
            };
            mode.addEventListener('change', syncTime); syncTime();
            editForm.addEventListener('submit', event => {
                if (editForm.dataset.submitting) { event.preventDefault(); return; }
                editForm.dataset.submitting = 'true';
                editForm.querySelector('[type="submit"]').disabled = true;
            });
        }
        const scheduleForm = card.querySelector('.js-schedule-management');
        scheduleForm?.addEventListener('submit', event => {
            const action = event.submitter?.value;
            if (scheduleForm.dataset.submitting || !action || (action === 'cancel' && !window.confirm('이 예약을 취소할까요? 취소 결과가 확인될 때까지 상태를 확인해주세요.'))) {
                event.preventDefault(); return;
            }
            // Disabled submit buttons do not contribute their name/value to the POST.
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'action'; input.value = action;
            scheduleForm.appendChild(input);
            scheduleForm.dataset.submitting = 'true';
            scheduleForm.querySelectorAll('button').forEach(button => { button.disabled = true; });
        });
        const deleteForm = card.querySelector('.js-dispatch-delete');
        deleteForm?.addEventListener('submit', event => {
            if (deleteForm.dataset.submitting || !window.confirm(deleteForm.dataset.confirm)) {
                event.preventDefault(); return;
            }
            deleteForm.dataset.submitting = 'true';
            deleteForm.querySelector('[type="submit"]').disabled = true;
        });
    });

    const ledger = document.querySelector('[data-results-url]');
    const sync = document.querySelector('[data-results-sync-url]');
    let syncingResults = false;
    const syncResults = async () => {
        if (!sync || document.hidden || syncingResults) return;
        syncingResults = true;
        const button = sync.querySelector('[data-sync-results]');
        const note = sync.querySelector('[data-sync-note]');
        button.disabled = true;
        note.textContent = '최종 결과를 조회하고 있습니다…';
        try {
            const response = await fetch(sync.dataset.resultsSyncUrl, {
                method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': sync.dataset.csrf},
                cache: 'no-store', signal: AbortSignal.timeout(25000)
            });
            if (!response.ok) throw new Error('Delivery result sync unavailable');
            const result = await response.json();
            note.textContent = !result.ready ? '결과 조회용 데이터베이스 업데이트가 필요합니다.'
                : result.errors > 0 ? '제공사 조회가 지연되어 기존 상태를 유지합니다. 잠시 후 다시 확인합니다.'
                : result.checked === 0 ? '현재 조회할 새 결과가 없습니다 · 30초마다 자동 조회'
                : '조회 확인 ' + new Date().toLocaleTimeString('ko-KR', {hour: '2-digit', minute: '2-digit'}) + ' · 30초마다 자동 조회';
            await refreshResults();
            await poll();
        } catch {
            note.textContent = '최종 결과를 조회하지 못했습니다. 잠시 후 다시 확인하거나 새로고침해주세요.';
        } finally {
            syncingResults = false;
            button.disabled = false;
        }
    };
    sync?.querySelector('[data-sync-results]').addEventListener('click', syncResults);
    setInterval(syncResults, 30000);
    setTimeout(syncResults, 1000);
    let pollingResults = false;
    const refreshResults = async () => {
        if (!ledger || document.hidden || pollingResults) return;
        const viewport = document.getElementById('application-scroll')?.getBoundingClientRect();
        const cells = Array.from(ledger.querySelectorAll('[data-result-id]')).filter(cell => {
            if (!viewport) return true;
            const rect = cell.closest('tr').getBoundingClientRect();
            return rect.bottom >= viewport.top && rect.top <= viewport.bottom;
        });
        const applicationIds = Array.from(new Set(cells.map(cell => cell.dataset.resultId)));
        if (applicationIds.length === 0) return;
        pollingResults = true;
        const error = document.querySelector('[data-results-error]');
        try {
            const results = {};
            for (let offset = 0; offset < applicationIds.length; offset += 20) {
                const url = new URL(ledger.dataset.resultsUrl, location.origin);
                applicationIds.slice(offset, offset + 20).forEach(id => url.searchParams.append('ids[]', id));
                const response = await fetch(url, {headers: {'Accept': 'application/json'}, cache: 'no-store', signal: AbortSignal.timeout(10000)});
                if (!response.ok) throw new Error('Result refresh unavailable');
                Object.assign(results, await response.json());
            }
            cells.forEach(cell => {
                const result = results[cell.dataset.resultId]?.[cell.dataset.resultKind];
                if (!result) return;
                const badge = cell.querySelector('[data-result-badge]');
                badge.textContent = result.label;
                badge.className = result.class;
                badge.title = result.title || '';
                const reason = cell.querySelector('[data-result-reason]');
                reason.textContent = result.reason || '';
                reason.hidden = !result.reason;
            });
            if (error) error.hidden = true;
        } catch {
            if (error) error.hidden = false;
        } finally { pollingResults = false; }
    };
    setInterval(refreshResults, 5000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshResults(); });

    const progress = document.getElementById('alimtalk-progress');

    let polling = false;
    const poll = async () => {
        if (document.hidden || polling || !progress) return;
        polling = true;
        try {
            const response = await fetch(progress.dataset.url, {
                headers: {'Accept': 'application/json'}, cache: 'no-store', signal: AbortSignal.timeout(10000)
            });
            if (!response.ok) throw new Error('Progress unavailable');
            const dispatches = await response.json();
            const shownIds = Array.from(progress.querySelectorAll('[data-dispatch-id]')).map(el => el.dataset.dispatchId).sort();
            const receivedIds = dispatches.map(dispatch => dispatch.id).sort();
            if (JSON.stringify(shownIds) !== JSON.stringify(receivedIds)) {
                if (!progress.querySelector('dialog[open]')) {
                    location.reload();
                } else {
                    const error = progress.querySelector('[data-poll-error]');
                    error.textContent = '발송 내역이 변경되었습니다. 팝업을 닫으면 최신 목록을 불러옵니다.';
                    error.hidden = false;
                }
                return;
            }
            dispatches.forEach(dispatch => {
                const card = Array.from(progress.querySelectorAll('[data-dispatch-id]')).find(el => el.dataset.dispatchId === dispatch.id);
                if (!card) return;
                card.dataset.status = dispatch.status;
                card.dataset.historyGroup = dispatch.history_group;
                card.dataset.awaitingResults = dispatch.awaiting_results ? 'true' : 'false';
                const toggle = card.querySelector('[data-edit-toggle]');
                if (toggle) toggle.disabled = !dispatch.can_edit;
                const editSubmit = card.querySelector('.js-dispatch-edit [type="submit"]');
                if (editSubmit) editSubmit.disabled = !dispatch.can_edit || Boolean(editSubmit.form.dataset.submitting);
                const deleteButton = card.querySelector('[data-delete-button]');
                if (deleteButton) deleteButton.disabled = !dispatch.can_delete || Boolean(deleteButton.form.dataset.submitting);
                const pendingChange = card.querySelector('[data-change-pending]');
                pendingChange.hidden = !dispatch.change_pending;
                pendingChange.textContent = dispatch.change_message || '';
                const changeError = card.querySelector('[data-change-error]');
                changeError.hidden = !dispatch.change_error;
                changeError.textContent = dispatch.change_error || '';
                card.querySelector('[data-dispatch-status]').textContent = dispatch.status_label;
                const scheduleActions = card.querySelector('[data-schedule-actions]');
                if (scheduleActions) {
                    scheduleActions.hidden = !dispatch.can_cancel_schedule;
                    const submitting = Boolean(scheduleActions.querySelector('form').dataset.submitting);
                    scheduleActions.querySelector('[value="refresh"]').disabled = submitting;
                    scheduleActions.querySelector('[value="cancel"]').disabled = submitting || dispatch.status === 'cancelling';
                }
                card.querySelectorAll('[data-count]').forEach(el => {
                    const count = Number(dispatch.counts[el.dataset.count] || 0);
                    el.textContent = count;
                    el.closest('[data-count-item]').hidden = count === 0 || (el.dataset.count === 'pending' && ['cancelled', 'expired'].includes(dispatch.status));
                });
                card.querySelector('[data-scheduled-notice]').hidden = dispatch.status !== 'scheduled';
                card.querySelector('[data-reservation-note]').hidden = !dispatch.scheduled_at || !['preview', 'queued', 'processing'].includes(dispatch.status);
                card.querySelector('[data-updated-at]').textContent = dispatch.updated_at;
                card.querySelector('[data-stalled]').hidden = !dispatch.stalled;
                card.querySelector('[data-review]').hidden = dispatch.status !== 'needs_review';
                const errors = card.querySelector('[data-dispatch-errors]');
                errors.replaceChildren();
                dispatch.errors.forEach(reason => {
                    const p = document.createElement('p'); p.className = 'mb-1'; p.textContent = reason; errors.appendChild(p);
                });
                if (dispatch.status !== 'preview') card.querySelector('[data-preview-actions]')?.remove();
                if (dispatch.status === 'preview' && new Date(dispatch.expires_at_iso) <= new Date()) {
                    card.querySelectorAll('.js-dispatch-confirm button').forEach(button => { button.disabled = true; });
                    card.querySelector('[data-dispatch-status]').textContent = '확인 만료';
                    card.dataset.status = 'expired';
                }
            });
            document.dispatchEvent(new CustomEvent('dispatches:updated', {detail: dispatches}));
            progress.querySelector('[data-poll-error]').hidden = true;
        } catch {
            progress.querySelector('[data-poll-error]').hidden = false;
        } finally { polling = false; }
    };
    setInterval(poll, 5000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
