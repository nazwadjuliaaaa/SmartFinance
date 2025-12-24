<div id="ai-chatbot" class="chatbot-container">
    <div class="chatbot-header" onclick="toggleChat()">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 30px; height: 30px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">🤖</div>
            <span>SmartAssistant</span>
        </div>
        <span>▲</span>
    </div>
    <div class="chatbot-body" id="chat-body">
        <div class="chat-message bot-message">
            Halo! Ada yang bisa saya bantu terkait keuangan Anda hari ini? 🤖
        </div>
    </div>
    <div class="chatbot-footer">
        <input type="text" id="chat-input" placeholder="Tanya sesuatu..." onkeypress="handleEnter(event)">
        <button onclick="sendMessage()">➤</button>
    </div>
</div>

<script>
    function toggleChat() {
        const chat = document.getElementById('ai-chatbot');
        chat.classList.toggle('open');
    }

    function handleEnter(e) {
        if(e.key === 'Enter') sendMessage();
    }

    async function sendMessage() {
        const input = document.getElementById('chat-input');
        const body = document.getElementById('chat-body');
        const message = input.value.trim();

        if(!message) return;

        // Add User Message
        body.innerHTML += `<div class="chat-message user-message">${message}</div>`;
        input.value = '';
        body.scrollTop = body.scrollHeight;

        // Show Loading
        const loadingId = 'loading-' + Date.now();
        body.innerHTML += `<div id="${loadingId}" class="chat-message bot-message">...</div>`;
        body.scrollTop = body.scrollHeight;

        try {
            const response = await fetch('{{ route("ai.chat") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();
            document.getElementById(loadingId).remove();
            
            // Add Bot Message
            body.innerHTML += `<div class="chat-message bot-message">${data.response}</div>`;
        } catch (error) {
            document.getElementById(loadingId).remove();
            body.innerHTML += `<div class="chat-message bot-message" style="color:red;">Maaf, terjadi kesalahan koneksi.</div>`;
        }
        body.scrollTop = body.scrollHeight;
    }
</script>
