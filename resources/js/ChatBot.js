document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('ChatbotBtn');
    const container = document.getElementById('ChatbotContainer');
    const closeBtn = document.getElementById('CloseChatBot');
    const messagesDiv = document.getElementById('messages');
    const input = document.getElementById('userMessage');
    const sendBtn = document.getElementById('sendBtn');

    // ✅ Toggle chatbot
    btn.addEventListener('click', () => {
        if (container.classList.contains('show')) {
            container.classList.remove('show');
            setTimeout(() => container.classList.add('hidden'), 300);
        } else {
            container.classList.remove('hidden');
            setTimeout(() => container.classList.add('show'), 10);
        }
    });

    // ✅ Close chatbot
    closeBtn.addEventListener('click', () => {
        container.classList.remove('show');
        setTimeout(() => container.classList.add('hidden'), 300);
    });

    // ✅ Append messages
    function appendMessage(text, sender) {
        const msg = document.createElement('div');
        msg.classList.add('message', sender);
        msg.textContent = text;
        messagesDiv.appendChild(msg);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    // ✅ Typing animation
    function appendTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.classList.add('message', 'bot', 'typing');
        typingDiv.innerHTML = '<span></span><span></span><span></span>';
        typingDiv.id = 'typing';
        messagesDiv.appendChild(typingDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
    function removeTyping() {
        const typingDiv = document.getElementById('typing');
        if (typingDiv) typingDiv.remove();
    }

    // ✅ Offline FAQ responses
    const faq = {
        'checkin': 'Check-in time is 8:00 PM.',
        'checkout': 'Check-out time is 12:00 PM.',
        'price standard room': 'Standard Room costs ₱2,500 per night.',
        'price family suite': 'Family Suite costs ₱4,500 per night.',
        'amenities': 'We offer Free WiFi, Pool, Kitchen, Parking, and Netflix.',
        'location': 'We are located in Falcons Court, Village East Avenue, Angono, 1930 Rizal.',
        'contact': 'You can call us at 0912-345-6789.',
        'dicimulacion': 'Dicimulation Staycation is a premier retreat destination designed for those who want to unwind and enjoy a relaxing, hassle-free escape without traveling far from home. We offer a unique blend of comfort, luxury, and convenience tailored to your needs.'
    };

    function getBotReply(message) {
        message = message.toLowerCase();
        for (const key in faq) {
            if (message.includes(key)) return faq[key];
        }
        return null;
    }

    // ✅ Send message
    function sendMessage() {
        const message = input.value.trim();
        if (!message) return;

        appendMessage(message, 'user');
        input.value = '';

        const offlineReply = getBotReply(message);
        if (offlineReply) {
            setTimeout(() => appendMessage(offlineReply, 'bot'), 300);
        } else {
            appendTyping();

            fetch("/chat", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ message: message })
            })
                .then(res => res.json())
                .then(data => {
                    removeTyping();
                    appendMessage(data.reply, 'bot');
                })
                .catch(() => {
                    removeTyping();
                    appendMessage("Error: Unable to connect to server.", 'bot');
                });
        }
    }

    // ✅ Button click
    sendBtn.addEventListener('click', sendMessage);

    // ✅ Enter key
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
});
