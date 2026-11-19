(() => {
    'use strict';
    window.AgentConversation = class {
        constructor(element, url, csrf) {
            this.id = element.dataset.conversation;
            this.hasTurns = element.dataset.hasTurns === '1';
            this.url = url;
            this.csrf = csrf;
        }

        turnId() {
            return Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
        }

        async request(action) {
            const abort = new AbortController();
            const timeout = setTimeout(() => abort.abort(), 10000);
            try {
                const response = await fetch(this.url, {
                    method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: abort.signal,
                    headers: { Accept: 'application/json' },
                    body: new URLSearchParams({ _csrf: this.csrf, conversation: this.id, conversation_action: action }),
                });
                const data = await response.json();
                if (!response.ok || !Array.isArray(data.turns) || !/^[a-f0-9]{32}$/.test(data.conversation)) {
                    throw Error(data.error || 'Could not open the conversation. Reload to try again.');
                }
                this.id = data.conversation;
                this.hasTurns = data.turns.length > 0;
                return data.turns;
            } finally {
                clearTimeout(timeout);
            }
        }
    };
})();
