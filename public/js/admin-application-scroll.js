(() => {
    const scroll = document.getElementById('application-scroll');
    const button = document.getElementById('load-applications');
    const status = document.getElementById('application-load-status');
    if (!scroll || !button) return;
    const body = scroll.querySelector('tbody');
    let loading = false;
    let failed = false;
    const load = async () => {
        if (loading || !button.dataset.next) return;
        loading = true;
        failed = false;
        button.disabled = true;
        button.textContent = '불러오는 중…';
        scroll.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(button.dataset.next, {
                headers: {Accept: 'application/json'}, cache: 'no-store', signal: AbortSignal.timeout(15000),
            });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Unavailable');
            const data = await response.json();
            const template = document.createElement('template');
            template.innerHTML = data.html;
            const seen = new Set(Array.from(body.querySelectorAll('[data-application-id]'), row => row.dataset.applicationId));
            template.content.querySelectorAll('[data-application-id]').forEach(row => {
                if (!seen.has(row.dataset.applicationId)) {
                    seen.add(row.dataset.applicationId);
                    body.appendChild(row);
                }
            });
            button.dataset.next = data.next || '';
            button.hidden = !data.next;
            status.textContent = `전체 ${Number(data.total).toLocaleString()}건 중 ${seen.size.toLocaleString()}건 표시${data.next ? '' : ' · 모든 접수 내역을 불러왔습니다'}`;
            document.dispatchEvent(new Event('applications:loaded'));
        } catch {
            failed = true;
            status.textContent = '목록을 불러오지 못했습니다. 다시 시도해주세요. 로그인이 만료되었다면 새로고침해주세요.';
        } finally {
            loading = false;
            button.disabled = false;
            button.textContent = failed ? '다시 불러오기' : '더 불러오기';
            scroll.removeAttribute('aria-busy');
        }
    };
    button.addEventListener('click', load);
    scroll.addEventListener('scroll', () => {
        if (!failed && scroll.scrollTop + scroll.clientHeight >= scroll.scrollHeight - 250) load();
    }, {passive: true});
})();
