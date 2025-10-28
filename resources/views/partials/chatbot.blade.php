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

  // ✅ Expanded FAQ knowledge base (from your paper)
  const faq = [
    { keywords: ["book","reserve","how to book"], reply: "📝 You can book directly on our website. Go to the Booking page, choose your house, and fill out the booking form." },
    { keywords: ["house 3","house three"], reply: "🏡 House 3 can be booked through our Booking page — just select 'House 3' and choose your date." },
    { keywords: ["confirm","approval","approved"], reply: "✅ After you submit your booking, you’ll receive a confirmation via email or the website once approved by the admin." },
    { keywords: ["reschedule","move date","change date"], reply: "📅 You can reschedule your stay at least 14 days before your booked date." },
    { keywords: ["cancel","refund"], reply: "❌ Cancellations aren’t allowed, but you may reschedule at least 14 days in advance." },
    { keywords: ["checkin","check-in","check in"], reply: "⏰ Check-in time is 2:00 PM." },
    { keywords: ["checkout","check-out","check out"], reply: "🕛 Check-out time is 12:00 PM noon." },
    { keywords: ["late checkout","late check-out"], reply: "⚠️ Late check-out may incur additional charges." },
    { keywords: ["payment","gcash","maya","bank","card"], reply: "💳 We accept GCash, debit, or credit card payments. A 50% downpayment confirms your booking." },
    { keywords: ["proof","upload payment","receipt"], reply: "📤 Upload your payment proof in the website’s Payment Confirmation section after booking." },
    { keywords: ["pets","dog","cat"], reply: "🐾 Yes! We’re pet-friendly — no extra charge for your furry friends!" },
    { keywords: ["extra guest","additional guest"], reply: "👥 Each extra guest is ₱500 per person per night." },
    { keywords: ["guest limit","maximum"], reply: "🏠 Each house has a specific guest limit shown on the booking page." },
    { keywords: ["location","address","where"], reply: "📍 We’re located at Falcons Court, Village East Avenue, Angono, Rizal." },
    { keywords: ["amenities","wifi","internet"], reply: "🌐 Amenities include WiFi, kitchen, BBQ grill, Netflix, parking, pool access, and pet-friendly rooms!" },
    { keywords: ["pool","swimming"], reply: "🏊 The pool is shared among every three units and open daily from 8 AM to 10 PM." },
    { keywords: ["breakfast","food","coffee"], reply: "☕ Yes! Breakfast is provided. Early arrivals get complimentary coffee and snacks." },
    { keywords: ["parking","car"], reply: "🚗 Free parking is available on-site and along the street." },
    { keywords: ["account","login","register"], reply: "👤 You can register for an account to manage bookings, view payment history, and contact the admin." },
    { keywords: ["forgot password","reset password"], reply: "🔑 You can reset your password on the Login page by clicking 'Forgot Password'." },
    { keywords: ["contact","admin","owner","message"], reply: "📞 You can contact the admin through the Contact Us tab on the website." },
    { keywords: ["owner","host"], reply: "👨‍💼 The owner is Mr. Edgar Fuentes Dicimulacion — a Computer Engineer with 7 years of hosting experience." },
    { keywords: ["houses","rooms","units"], reply: "🏡 We have 8 unique staycation houses — each with its own design and amenities." },
    { keywords: ["long stay","monthly","28 days"], reply: "📅 Yes, we allow long-term stays (28 days or more)." }
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

  // Handle message (FAQ → Gemini)
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

    // Fallback to Gemini AI
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
  const quick = [
    "How to book a stay?",
    "What are your payment options?",
    "What time is check-in?",
    "Are pets allowed?",
    "Can I reschedule my booking?"
  ];
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
