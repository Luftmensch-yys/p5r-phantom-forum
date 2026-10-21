/* ============================================================
   TALK — 主交互脚本 (js/app.js)
   ============================================================ */

(function () {
    'use strict';

    // ── DOM 引用 ───────────────────────────────
    const sidebar     = document.querySelector('.sidebar');
    const menuToggle  = document.getElementById('menuToggle');
    const replyTip    = document.getElementById('reply_tip');
    const replyUser   = document.getElementById('reply_user');
    const replyIdInput = document.getElementById('reply_id');
    const inputContent = document.getElementById('input_content');
    const chatArea    = document.getElementById('chatArea');
    const sendForm    = document.getElementById('sendForm');

    // ── 侧边栏：桌面端 hover 展开/收起 ─────────
    if (sidebar) {
        sidebar.addEventListener('mouseenter', function () {
            sidebar.classList.remove('collapsed');
        });

        sidebar.addEventListener('mouseleave', function () {
            sidebar.classList.add('collapsed');
        });
    }

    // ── 汉堡菜单（手机端） ─────────────────────
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });
    }

    // ── 回复功能 ───────────────────────────────
    function replyFunc(msgId, userName) {
        replyIdInput.value = String(msgId);
        replyUser.textContent = '@' + userName;
        replyTip.classList.add('show');
        inputContent.placeholder = '回复 @' + userName;
        inputContent.focus();
    }

    function cancelReply() {
        replyIdInput.value = '0';
        replyUser.textContent = '';
        replyTip.classList.remove('show');
        inputContent.placeholder = '输入消息...';
    }

    // 回复按钮（事件委托，读取 data 属性）
    if (chatArea) {
        chatArea.addEventListener('click', function (e) {
            var target = e.target.closest('[data-reply-id]');
            if (!target) return;

            var msgId    = target.getAttribute('data-reply-id');
            var userName = target.getAttribute('data-username') || '';
            replyFunc(parseInt(msgId, 10), userName);
        });

        // 删除按钮（事件委托）
        chatArea.addEventListener('click', function (e) {
            var target = e.target.closest('[data-delete-id]');
            if (!target) return;

            var id = parseInt(target.getAttribute('data-delete-id'), 10);
            deleteMsg(id);
        });
    }

    // 取消回复按钮
    var cancelBtn = document.querySelector('.reply-cancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', cancelReply);
    }

    // ── 投票提交 ───────────────────────────────
    window.submitVote = function (option) {
        var input = document.getElementById('vote_input');
        if (input) {
            input.value = option;
        }
        var form = document.querySelector('.vote-section form');
        if (form) {
            form.submit();
        }
    };

    // ── 删除消息 ───────────────────────────────
    function deleteMsg(id) {
        if (!confirm('\u786E\u5B9A\u5220\u9664\u8FD9\u6761\u6D88\u606F\u5417\uFF1F')) {
            return;
        }

        fetch('delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id: String(id) })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                location.reload();
            } else {
                alert('\u5220\u9664\u5931\u8D25: ' + (data.error || '\u672A\u77E5\u9519\u8BEF'));
            }
        })
        .catch(function () { alert('\u5220\u9664\u8BF7\u6C42\u5931\u8D25\uFF0C\u8BF7\u7A0D\u540E\u518D\u8BD5\u3002'); });
    }

    // ── 发送消息（AJAX） ───────────────────────
    if (sendForm) {
        sendForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);

            fetch('index.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    cancelReply();
                    location.reload();
                } else if (data.error) {
                    alert(data.error);
                }
            })
            .catch(function () { alert('\u53D1\u9001\u5931\u8D25\uFF0C\u8BF7\u7A0D\u540E\u518D\u8BD5\u3002'); });
        });
    }

    // ── 滚动时显示/隐藏发送框 ─────────────────
    if (chatArea && sendForm) {
        window.addEventListener('scroll', function () {
            var rect = chatArea.getBoundingClientRect();
            var wh   = window.innerHeight;

            if (rect.top < wh && rect.bottom > 0) {
                sendForm.classList.remove('hidden');
            } else {
                sendForm.classList.add('hidden');
            }
        });
    }
})();
