        </main>

        <!-- 5. FOOTER -->
        <footer class="area-footer">
            <p>© 2026 StaffSync HRM System - Bản quyền thuộc về tụi tui</p>
        </footer>
    </div>

    <!-- ===== CHATBOT AI NỔI ===== -->
    <div id="aiChatToggle" title="Hỏi trợ lý AI">
        <i class="fa-solid fa-robot"></i>
    </div>

    <div id="aiChatBox">
        <div id="aiChatHeader">
            <span><i class="fa-solid fa-robot"></i> Trợ lý AI - NovaHM</span>
            <i class="fa-solid fa-xmark" id="aiChatClose"></i>
        </div>
        <div id="aiChatMessages">
            <div class="ai-msg ai-msg-bot">
                Chào bạn 👋 Mình có thể trả lời câu hỏi về nhân viên, lương, phép, chấm công... dựa trên dữ liệu hiện tại của công ty. Bạn muốn hỏi gì?
            </div>
        </div>
        <div id="aiChatInputRow">
            <input type="text" id="aiChatInput" placeholder="Nhập câu hỏi...">
            <button id="aiChatSend"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>

    <style>
        #aiChatToggle {
            position: fixed; bottom: 26px; right: 26px; width: 56px; height: 56px;
            background: var(--primary, #3b82f6); color: #fff; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; cursor: pointer; box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            z-index: 999; transition: transform 0.2s;
        }
        #aiChatToggle:hover { transform: scale(1.08); }

        #aiChatBox {
            position: fixed; bottom: 96px; right: 26px; width: 360px; max-width: 90vw;
            height: 480px; max-height: 75vh; background: #fff; border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18); display: none; flex-direction: column;
            overflow: hidden; z-index: 999; border: 1px solid #e2e8f0;
        }
        #aiChatBox.open { display: flex; }

        #aiChatHeader {
            background: var(--primary, #3b82f6); color: #fff; padding: 14px 16px;
            display: flex; justify-content: space-between; align-items: center; font-weight: 600;
        }
        #aiChatHeader i.fa-xmark { cursor: pointer; opacity: 0.85; }
        #aiChatHeader i.fa-xmark:hover { opacity: 1; }

        #aiChatMessages {
            flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px;
            background: #f8fafc;
        }
        .ai-msg { padding: 10px 13px; border-radius: 10px; font-size: 13.5px; line-height: 1.5; max-width: 85%; white-space: pre-wrap; }
        .ai-msg-bot  { background: #fff; border: 1px solid #e2e8f0; align-self: flex-start; color: #0f172a; }
        .ai-msg-user { background: var(--primary, #3b82f6); color: #fff; align-self: flex-end; }
        .ai-msg-error{ background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; align-self: flex-start; }
        .ai-msg-loading { background: #fff; border: 1px solid #e2e8f0; align-self: flex-start; color: #94a3b8; font-style: italic; }

        #aiChatInputRow { display: flex; border-top: 1px solid #e2e8f0; padding: 10px; gap: 8px; }
        #aiChatInput { flex: 1; border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px 12px; outline: none; font-size: 13.5px; }
        #aiChatInput:focus { border-color: var(--primary, #3b82f6); }
        #aiChatSend {
            background: var(--primary, #3b82f6); color: #fff; border: none; border-radius: 8px;
            width: 40px; cursor: pointer; font-size: 14px;
        }
        #aiChatSend:hover { background: var(--primary-hover, #2563eb); }
        #aiChatSend:disabled { opacity: 0.6; cursor: default; }
    </style>

    <script>
        const aiToggle   = document.getElementById('aiChatToggle');
        const aiBox      = document.getElementById('aiChatBox');
        const aiClose    = document.getElementById('aiChatClose');
        const aiMessages = document.getElementById('aiChatMessages');
        const aiInput    = document.getElementById('aiChatInput');
        const aiSend     = document.getElementById('aiChatSend');

        aiToggle.addEventListener('click', () => aiBox.classList.toggle('open'));
        aiClose.addEventListener('click', () => aiBox.classList.remove('open'));

        function themTinNhan(text, loai) {
            const div = document.createElement('div');
            div.className = 'ai-msg ai-msg-' + loai;
            div.textContent = text;
            aiMessages.appendChild(div);
            aiMessages.scrollTop = aiMessages.scrollHeight;
            return div;
        }

        async function guiCauHoi() {
            const cauHoi = aiInput.value.trim();
            if (!cauHoi) return;

            themTinNhan(cauHoi, 'user');
            aiInput.value = '';
            aiSend.disabled = true;
            const loadingEl = themTinNhan('Đang suy nghĩ...', 'loading');

            try {
                const res = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'cau_hoi=' + encodeURIComponent(cauHoi),
                });
                const data = await res.json();
                loadingEl.remove();

                if (data.error) {
                    themTinNhan(data.error, 'error');
                } else {
                    themTinNhan(data.reply, 'bot');
                }
            } catch (err) {
                loadingEl.remove();
                themTinNhan('Không kết nối được máy chủ. Kiểm tra lại mạng và thử lại.', 'error');
            } finally {
                aiSend.disabled = false;
                aiInput.focus();
            }
        }

        aiSend.addEventListener('click', guiCauHoi);
        aiInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') guiCauHoi(); });
    </script>

    <script>
        document.querySelectorAll('.menu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                this.parentElement.classList.toggle('open');
            });
        });
    </script>
</body>
</html>
