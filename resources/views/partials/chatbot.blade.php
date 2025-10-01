<div class="chatbot-wrapper">
    <!-- Floating Icon -->
    <button id="ChatbotBtn" class="FloatingChatbot">
        <div class="chatbot-face">
            <div class="eye"><div class="pupil"></div></div>
            <div class="eye"><div class="pupil"></div></div>
        </div>
    </button>

    <!-- Chatbot Container -->
    <div id="ChatbotContainer" class="chatbot-container hidden">
        <div class="chatbot-header">
            <span>Dicimulation Staycation</span>
            <button id="CloseChatBot">&times;</button>
        </div>
        <div id="messages" class="chatbot-messages"></div>
        <div class="chatbot-input">
            <input type="text" id="userMessage" placeholder="Type a message...">
            <button id="sendBtn">➤</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const chatbotBtn = document.getElementById("ChatbotBtn");
    const chatbotContainer = document.getElementById("ChatbotContainer");
    const closeBtn = document.getElementById("CloseChatBot");
    const sendBtn = document.getElementById("sendBtn");
    const input = document.getElementById("userMessage");
    const messages = document.getElementById("messages");

    const faq = [
        { keywords: ["checkin","check-in","arrival"], reply: "Check-in time is 8:00 PM." },
        { keywords: ["checkout","check-out","departure"], reply: "Check-out time is 12:00 PM." },
        { keywords: ["standard room"], reply: "Standard Room costs ₱2,500 per night." },
        { keywords: ["family suite"], reply: "Family Suite costs ₱4,500 per night." },
        { keywords: ["amenities"], reply: "We offer Free WiFi, Pool, Kitchen, Parking, and Netflix." },
        { keywords: ["location","address"], reply: "We are located in Falcons Court, Angono, Rizal." },
        { keywords: ["contact","phone"], reply: "You can call us at 0912-345-6789." }
    ];

    function appendMessage(text, sender) {
        const div = document.createElement("div");
        div.classList.add("message", sender);
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function checkFAQ(msg) {
        msg = msg.toLowerCase();
        for (let item of faq) {
            if (item.keywords.some(k => msg.includes(k))) return item.reply;
        }
        return null;
    }

    function sendMessage() {
        let rawMessage = input.value.trim();
        if (!rawMessage) return;
        appendMessage(rawMessage, "user");
        input.value = "";

        let faqReply = checkFAQ(rawMessage);
        if (faqReply) {
            appendMessage(faqReply, "bot");
            return;
        }

        appendMessage("Sorry, I don’t understand yet.", "bot");
    }

    sendBtn.addEventListener("click", sendMessage);
    input.addEventListener("keypress", e => { if (e.key === "Enter") sendMessage(); });

    chatbotBtn.addEventListener("click", () => chatbotContainer.classList.toggle("hidden"));
    closeBtn.addEventListener("click", () => chatbotContainer.classList.add("hidden"));
});
</script>
