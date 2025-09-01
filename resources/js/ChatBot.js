document.addEventListener('DOMContentLoaded', function() {

    const btn = document.getElementById('ChatbotBtn');
    const container = document.getElementById('ChatbotContainer');
    const closeBtn = document.getElementById('CloseChatBot');
    const messagesDiv = document.getElementById('messages');
    const input = document.getElementById('userMessage');
    const sendBtn = document.getElementById('sendBtn');

    // Show chatbot
    btn.addEventListener('click', () => {
        container.style.display = 'flex';
    });

    // Close chatbot
    closeBtn.addEventListener('click', () => {
        container.style.display = 'none';
    });

    // Append messages
    function appendMessage(sender, text) {
        const p = document.createElement('p');
        p.style.margin = '5px 0';
        p.textContent = sender + ': ' + text;
        messagesDiv.appendChild(p);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    // Offline FAQ
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
        return 'Sorry, I can only answer questions about Dicimulacion Staycation.';
    }

    // Send message
    sendBtn.addEventListener('click', () => {
        const message = input.value.trim();
        if (!message) return;
        appendMessage('You', message);

        const reply = getBotReply(message);
        setTimeout(() => appendMessage('', reply), 300);

        input.value = '';
    });

    // Press Enter to send
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendBtn.click();
    });

});
