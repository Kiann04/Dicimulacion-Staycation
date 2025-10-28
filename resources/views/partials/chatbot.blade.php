<!-- Messenger-Style Chatbot -->
<div class="chatbot-wrapper">
  <!-- Floating Button -->
  <button id="ChatbotBtn"
    class="btn btn-primary rounded-circle shadow-lg position-fixed flex justify-center items-center"
    style="bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 1050;">
    <i class="bi bi-messenger fs-3 text-white"></i>
  </button>

  <!-- Chatbot Window -->
  <div id="ChatbotContainer"
    class="card position-fixed shadow-lg hidden"
    style="bottom: 90px; right: 20px; width: 360px; height: 520px; border-radius: 20px; overflow: hidden; z-index: 1050;">

    <!-- Header -->
    <div class="bg-primary text-white px-3 py-2 d-flex align-items-center justify-content-between">
      <div>
        <h6 class="mb-0 fw-bold">Dicimulacion Staycation</h6>
        <small>Online</small>
      </div>
      <button id="CloseChatBot" class="btn btn-light btn-sm rounded-circle">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Messages -->
    <div id="messages" class="p-3 overflow-auto" style="height: 340px; background-color: #f0f2f5;">
      <div class="text-center text-muted small mt-2">Start a conversation</div>
    </div>

    <!-- Quick FAQ -->
    <div id="faqButtons" class="px-3 py-2 d-flex flex-wrap gap-2 border-top bg-white"></div>

    <!-- Input -->
    <div class="bg-white border-top px-2 py-2 d-flex align-items-center">
      <input type="text" id="userMessage" class="form-control rounded-pill me-2" placeholder="Aa">
      <button id="sendBtn" class="btn btn-primary rounded-pill px-3">
        <i class="bi bi-send-fill"></i>
      </button>
    </div>
  </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- Script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const chatbotBtn = document.getElementById("ChatbotBtn");
  const chatbotContainer = document.getElementById("ChatbotContainer");
  const closeBtn = document.getElementById("CloseChatBot");
  const sendBtn = document.getElementById("sendBtn");
  const input = document.getElementById("userMessage");
  const messages = document.getElementById("messages");
  const faqButtons = document.getElementById("faqButtons");

  // Greetings
  const greetings = [
    "Hi there! 👋 How can I help you today?",
    "Hello! 😊 Planning a staycation? Ask me anything!",
    "Welcome to Dicimulacion Staycation! 🏡 How may I assist?",
    "Good day! ☀️ Need help with booking or checking availability?"
  ];

  chatbotBtn.addEventListener("click", () => {
    chatbotContainer.classList.toggle("hidden");
    if (!chatbotContainer.classList.contains("hidden")) {
      appendMessage(greetings[Math.floor(Math.random() * greetings.length)], "bot");
    }
  });

  closeBtn.addEventListener("click", () => chatbotContainer.classList.add("hidden"));

  // FAQs
  const faq = [
    { keywords: ["checkin","arrival","check-in","check in"], reply: "Check-in time is 2:00 PM ⏰" },
    { keywords: ["checkout","departure","check-out"], reply: "Check-out time is 12:00 PM 🕛" },
    { keywords: ["pool","swimming"], reply: "🏊 Our pool is open daily from 8 AM to 10 PM." },
    { keywords: ["payment","gcash","maya","bank","card"], reply: "💳 We accept GCash, debit, or credit card payments. 50% downpayment confirms your booking." },
    { keywords: ["location","address","where"], reply: "📍 We’re located at Falcons Court, Village East Avenue, Angono, Rizal." },
    { keywords: ["amenities","wifi","internet"], reply: "🏡 WiFi, Kitchen, BBQ grill, Netflix, Parking, Pool access, and Pet-friendly rooms!" },
    { keywords: ["pets","dog","cat"], reply: "🐾 Yes! We’re pet-friendly — no extra charge for your furry friends!" },
    { keywords: ["parking","car"], reply: "🚗 Free parking is available on-site and on the street." },
    { keywords: ["reschedule","cancel","move date"], reply: "🔁 You can reschedule your stay at least 14 days before your booking." },
    { keywords: ["booking","reserve"], reply: "📝 You can book directly on our website via the Booking page." },
  ];

  // Append Message
  function appendMessage(text, sender) {
    const msgDiv = document.createElement("div");
    msgDiv.classList.add("d-flex", "mb-2");

    const bubble = document.createElement("div");
    bubble.textContent = text;
    bubble.classList.add("p-2", "rounded-3", "shadow-sm");

    if (sender === "user") {
      msgDiv.classList.add("justify-content-end");
      bubble.classList.add("bg-primary", "text-white");
    } else {
      msgDiv.classList.add("justify-content-start");
      bubble.classList.add("bg-white", "text-dark");
    }

    msgDiv.appendChild(bubble);
    messages.appendChild(msgDiv);
    messages.scrollTop = messages.scrollHeight;
  }

  // Typing animation
  function showTyping() {
    const typing = document.createElement("div");
    typing.id = "typing";
    typing.classList.add("text-muted", "small", "fst-italic");
    typing.textContent = "Dicimulacion is typing...";
    messages.appendChild(typing);
    messages.scrollTop = messages.scrollHeight;
  }

  function hideTyping() {
    const typing = document.getElementById("typing");
    if (typing) typing.remove();
  }

  // Find FAQ first, else use Gemini
  async function handleMessage(msg) {
    appendMessage(msg, "user");
    input.value = "";
    showTyping();

    // Check FAQs first
    for (let item of faq) {
      for (let keyword of item.keywords) {
        if (msg.toLowerCase().includes(keyword)) {
          hideTyping();
          appendMessage(item.reply, "bot");
          return;
        }
      }
    }

    // If no match, call Gemini API
    try {
      const response = await fetch("{{ url('/chat-gemini') }}", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content
        },
        body: JSON.stringify({ message: msg })
      });
      const data = await response.json();
      hideTyping();
      appendMessage(data.reply || "🤖 Sorry, I couldn’t get that. Try asking differently!", "bot");
    } catch (err) {
      hideTyping();
      appendMessage("⚠️ Connection error. Please try again.", "bot");
    }
  }

  sendBtn.addEventListener("click", () => {
    const msg = input.value.trim();
    if (msg) handleMessage(msg);
  });

  input.addEventListener("keypress", e => {
    if (e.key === "Enter") {
      const msg = input.value.trim();
      if (msg) handleMessage(msg);
    }
  });

  // Quick buttons
  const quick = ["What time is check-in?", "Are pets allowed?", "Where are you located?", "What are your payment options?"];
  quick.forEach(q => {
    const b = document.createElement("button");
    b.classList.add("btn", "btn-outline-primary", "btn-sm", "rounded-pill");
    b.textContent = q;
    b.addEventListener("click", () => handleMessage(q));
    faqButtons.appendChild(b);
  });
});
</script>

<!-- Style -->
<style>
.hidden { display: none; }
#messages::-webkit-scrollbar { width: 6px; }
#messages::-webkit-scrollbar-thumb {
  background-color: rgba(0,0,0,0.2);
  border-radius: 4px;
}
</style>
