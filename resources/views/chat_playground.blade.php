<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI vs Manual Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom Scrollbar agar terlihat rapi */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .message-bubble {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-900 text-white h-screen flex items-center justify-center font-sans">

    <div class="w-full max-w-md bg-gray-800 rounded-xl shadow-2xl overflow-hidden border border-gray-700 flex flex-col h-[80vh]">
        
        <div class="bg-gray-900 p-4 border-b border-gray-700 flex justify-between items-center">
            <div>
                <h1 class="font-bold text-lg text-blue-400">Assistant Interface</h1>
                <p class="text-xs text-gray-400" id="status-text">Mode: Manual Chat</p>
            </div>

            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="ai-toggle" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-medium text-gray-300">AI</span>
            </label>
        </div>

        <div id="chat-box" class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-hide bg-gray-800/50">
            <div class="text-center text-xs text-gray-500 mt-4">Mulai percakapan...</div>
        </div>

        <div class="p-4 bg-gray-900 border-t border-gray-700">
            <form id="chat-form" class="flex gap-2">
                <input type="text" id="message-input" 
                    class="flex-1 bg-gray-700 text-white text-sm rounded-lg p-3 outline-none focus:ring-2 focus:ring-blue-500 transition"
                    placeholder="Ketik pesan..." required autocomplete="off">
                <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2 transition disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const chatBox = document.getElementById('chat-box');
        const aiToggle = document.getElementById('ai-toggle');
        const statusText = document.getElementById('status-text');

        // 1. Ubah Teks saat Toggle digeser
        aiToggle.addEventListener('change', () => {
            if(aiToggle.checked) {
                statusText.innerText = "Mode: AI Agent (Database Access)";
                statusText.classList.replace('text-gray-400', 'text-green-400');
            } else {
                statusText.innerText = "Mode: Manual Chat";
                statusText.classList.replace('text-green-400', 'text-gray-400');
            }
        });

        // 2. Handle Submit
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value;
            if (!message) return;

            // Tampilkan Pesan User ke UI
            addMessageToUI('user', message);
            messageInput.value = '';

            // Tentukan URL berdasarkan Toggle (Opsi 2 Logic)
            // Jika Toggle ON -> /api/chat-ai
            // Jika Toggle OFF -> /api/chat (manual)
            // Sesuaikan prefix '/api' dengan route yang Anda buat sebelumnya
            const endpoint = aiToggle.checked ? '/chat-ai' : '/chat';
            
            // Tambahkan loading bubble
            const loadingId = addLoadingBubble();

            try {
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            // 2. TAMBAHKAN BARIS INI (Wajib untuk web.php)
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ message: message })
    });

                const data = await response.json();

                // Hapus loading
                removeLoadingBubble(loadingId);

                // Tampilkan Balasan (Dari AI atau Controller Biasa)
                // Pastikan controller Anda mengembalikan JSON dengan key 'reply'
                addMessageToUI('bot', data.reply || "Tidak ada balasan.");

            } catch (error) {
                removeLoadingBubble(loadingId);
                addMessageToUI('bot', "Error: Gagal menghubungi server.");
                console.error(error);
            }
        });

        // Helper: Menambah Bubble Chat
        function addMessageToUI(role, text) {
            const div = document.createElement('div');
            const isUser = role === 'user';
            
            div.className = `flex w-full ${isUser ? 'justify-end' : 'justify-start'} message-bubble`;
            
            div.innerHTML = `
                <div class="max-w-[80%] p-3 rounded-lg text-sm ${
                    isUser 
                    ? 'bg-blue-600 text-white rounded-br-none' 
                    : 'bg-gray-700 text-gray-200 rounded-bl-none border border-gray-600'
                }">
                    ${text}
                </div>
            `;
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Helper: Loading Animation
        function addLoadingBubble() {
            const id = 'loading-' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            div.className = 'flex w-full justify-start message-bubble';
            div.innerHTML = `
                <div class="bg-gray-700 p-3 rounded-lg rounded-bl-none border border-gray-600 flex space-x-1">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-75"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-150"></div>
                </div>
            `;
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
            return id;
        }

        function removeLoadingBubble(id) {
            const el = document.getElementById(id);
            if(el) el.remove();
        }
    </script>
</body>
</html>