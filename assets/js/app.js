const App = {
    currentUser: null,
    lastId: 0,
    isPolling: false,

    init() {
        this.bindEvents();
        this.checkSession();
    },

    bindEvents() {
        $('#show-register').on('click', (e) => {
            e.preventDefault();
            $('#login-form').hide();
            $('#register-form').show();
        });

        $('#show-login').on('click', (e) => {
            e.preventDefault();
            $('#register-form').hide();
            $('#login-form').show();
        });

        $('#login-form').on('submit', (e) => {
            e.preventDefault();
            this.auth('login', $(e.target).serialize());
        });

        $('#register-form').on('submit', (e) => {
            e.preventDefault();
            this.auth('register', $(e.target).serialize());
        });

        $('#logout-btn').on('click', () => {
            this.auth('logout', {});
        });

        $('#message-form').on('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
    },

    auth(action, data) {
        if (typeof data === 'string') {
            data += '&action=' + action;
        } else {
            data.action = action;
        }

        $.post('api/auth.php', data, (res) => {
            if (res.success) {
                if (action === 'logout') {
                    this.currentUser = null;
                    $('#chat-screen').hide();
                    $('#auth-screen').show();
                } else {
                    if (res.user) {
                        this.currentUser = res.user;
                        $('#current-username').text(res.user.username);
                    }
                    this.startPolling();
                }
            } else {
                alert(res.message || 'Auth failed');
            }
        }, 'json');
    },

    checkSession() {
        $.post('api/auth.php', { action: 'check' }, (res) => {
            if (res.success && res.user) {
                this.currentUser = res.user;
                $('#current-username').text(res.user.username);
                this.startPolling();
            } else {
                $('#chat-screen').hide();
                $('#auth-screen').show();
            }
        }, 'json');
    },

    startPolling() {
        if (this.isPolling) return;
        this.isPolling = true;
        this.poll();
    },

    poll() {
        $.ajax({
            url: 'api/poll.php',
            method: 'POST',
            data: { last_id: this.lastId },
            dataType: 'json',
            success: (res) => {
                $('#auth-screen').hide();
                $('#chat-screen').show();

                // If we don't have currentUser yet, we should probably fetch it or
                // get it from the poll response if we add it there.
                // For now, let's assume we get it from login/register.

                if (res.messages && res.messages.length > 0) {
                    res.messages.forEach(msg => {
                        this.renderMessage(msg);
                        this.lastId = Math.max(this.lastId, msg.id);
                    });
                    this.scrollToBottom();
                }

                if (res.online_users) {
                    this.renderUsers(res.online_users);
                }

                // Immediately poll again (Long-polling)
                this.poll();
            },
            error: (xhr) => {
                this.isPolling = false;
                if (xhr.status === 401) {
                    $('#chat-screen').hide();
                    $('#auth-screen').show();
                } else {
                    // Retry after 2s on error
                    setTimeout(() => this.startPolling(), 2000);
                }
            }
        });
    },

    sendMessage() {
        const body = $('#message-input').val().trim();
        if (!body) return;

        const optimisticId = 'opt-' + Date.now();
        const optimisticMsg = {
            id: optimisticId,
            body: body,
            sender_id: this.currentUser ? this.currentUser.id : 'me',
            username: this.currentUser ? this.currentUser.username : 'Me',
            optimistic: true
        };

        this.renderMessage(optimisticMsg);
        this.scrollToBottom();
        $('#message-input').val('');

        $.post('api/send.php', { body: body, type: 'text' }, (res) => {
            if (!res.success) {
                $(`[data-id="${optimisticId}"]`).addClass('error').text('Failed to send');
            }
            // We keep the optimistic message until the real one arrives via polling
        }, 'json');
    },

    renderMessage(msg) {
        // If a real message arrives that matches an optimistic one (same body and sender),
        // we replace the optimistic one.
        if (!msg.optimistic) {
            $('.message.optimistic').each((i, el) => {
                const $el = $(el);
                if ($el.find('.body').text() === msg.body && $el.find('.sender').text() === msg.username) {
                    $el.remove();
                }
            });
        }

        if ($(`[data-id="${msg.id}"]`).length > 0) return;

        const isOwn = msg.sender_id == this.currentUser?.id || msg.sender_id === 'me';
        const msgHtml = `
            <div class="message ${isOwn ? 'own' : ''} ${msg.optimistic ? 'optimistic' : ''}" data-id="${msg.id}">
                <div class="sender">${this.escapeHtml(msg.username || 'Me')}</div>
                <div class="body">${this.escapeHtml(msg.body)}</div>
            </div>
        `;
        $('#messages').append(msgHtml);
    },

    renderUsers(users) {
        const $list = $('#user-list');
        $list.empty();
        users.forEach(user => {
            const isMe = user.id == this.currentUser?.id;
            $list.append(`<li>${this.escapeHtml(user.username)} ${isMe ? '(You)' : ''}</li>`);
            if (isMe && !this.currentUser.username) {
                 this.currentUser.username = user.username;
                 $('#current-username').text(user.username);
            }
        });
    },

    scrollToBottom() {
        const messages = document.getElementById('messages');
        if (messages) {
            messages.scrollTop = messages.scrollHeight;
        }
    },

    escapeHtml(text) {
        if (!text) return '';
        return text.toString().replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[m];
        });
    }
};

$(document).ready(() => App.init());
