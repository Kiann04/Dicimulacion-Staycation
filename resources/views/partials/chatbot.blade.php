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
  // 🧾 BOOKING
  { keywords: ["book","reserve","how to book","mag book","paano mag book","reservation"], reply: "📝 You can book directly on our website! / Pwede kang mag-book sa aming website. Punta ka lang sa **Booking page**, piliin ang bahay at petsa, tapos i-fill out ang form." },
  { keywords: ["available","availability","vacant","may bakante","bakante"], reply: "📅 You can check available houses on our Booking page. / Pwede mong makita ang mga bakanteng bahay sa Booking page ng aming website." },
  { keywords: ["house 1","house one"], reply: "🏠 House 1 offers a cozy stay with modern amenities — maaari mong i-book ito sa Booking page." },
  { keywords: ["house 2","house two"], reply: "🏡 House 2 provides comfort and privacy — available ito para sa booking sa aming website." },
  { keywords: ["house 3","house three"], reply: "🏡 House 3 can be booked on our website — piliin lang ito sa Booking page at pumili ng petsa mo." },
  { keywords: ["confirm","approval","approved","waiting","pending","kumpirma"], reply: "✅ After you submit your booking, you’ll receive a confirmation once approved. / Kapag na-submit mo na ang booking, makakatanggap ka ng kumpirmasyon kapag naaprubahan ng admin." },
  { keywords: ["reschedule","move date","change date","lipat petsa"], reply: "📅 You can reschedule your stay at least 14 days before your booked date. / Pwede mong ilipat ang petsa ng iyong stay 14 na araw bago ang booking mo." },
  { keywords: ["cancel","refund","cancel booking","kansel"," kansel booking"], reply: "❌ Cancellations aren’t allowed, pero pwede kang mag-reschedule 14 days bago ang booking date. / Hindi pinapayagan ang cancellation, pero pwede kang magpalit ng petsa." },
  { keywords: ["edit booking","change booking","modify booking","baguhin booking"], reply: "✏️ To make changes, please contact our admin through the Contact Us page. / Para baguhin ang booking, makipag-ugnayan sa admin sa Contact Us page." },

  // 💰 PAYMENT
  { keywords: ["payment","gcash","maya","bank","card","bpi","pay","bayad","magbayad","how to pay"], reply: "💳 We accept **GCash, debit, or credit card** payments. / Tumanggap kami ng **GCash, debit, o credit card**. 50% downpayment ang kailangan para ma-confirm ang booking mo. Pagkatapos magbayad, i-upload ang proof of payment sa website." },
  { keywords: ["downpayment","half payment","deposit","advance"], reply: "💰 A 50% downpayment confirms your reservation. / Kailangan ng **50% downpayment** para makumpirma ang iyong booking." },
  { keywords: ["proof","upload payment","receipt","payment proof","resibo"], reply: "📤 Upload your payment proof sa **Payment Confirmation** section ng website pagkatapos magbayad." },
  { keywords: ["refund policy","refund"], reply: "⚠️ Refunds are not available. / Walang refund, pero pwede kang mag-reschedule 14 days bago ang stay mo." },

  // 🕒 CHECK-IN / CHECK-OUT
  { keywords: ["checkin","check-in","check in","oras ng check in"], reply: "⏰ Check-in time is **2:00 PM**. / Ang check-in time ay **2:00 PM**." },
  { keywords: ["checkout","check-out","check out","oras ng check out"], reply: "🕛 Check-out time is **12:00 PM noon**. / Ang check-out time ay **12:00 PM ng tanghali**." },
  { keywords: ["late checkout","late check-out","late check in","nahuli"], reply: "⚠️ Late check-out may have extra charges. / Maaaring may dagdag na bayad kapag late ang pag-check-out." },
  { keywords: ["early checkin","early check-in","maagang check in"], reply: "🌅 Early check-in depends on availability. / Ang maagang check-in ay depende sa availability — paki-message ang admin para magpa-approve." },

  // 🧍‍♂️ GUEST POLICIES
  { keywords: ["extra guest","additional guest","more guests","dagdag tao","kasama"], reply: "👥 Each extra guest is ₱500 per person per night. / May dagdag na **₱500 bawat tao bawat gabi** para sa extra guest." },
  { keywords: ["guest limit","maximum","ilan pwede","capacity","ilan tao"], reply: "🏠 Each house has its own guest limit shown on the Booking page. / May maximum number of guests bawat bahay — makikita ito sa Booking page." },
  { keywords: ["children","kids","baby","bata"], reply: "👶 Children are allowed! / Pinapayagan ang mga bata — isama lang sa bilang ng guests kapag magbo-book." },
  { keywords: ["pets","dog","cat","aso","pusa","pet friendly"], reply: "🐾 Yes! Pet-friendly kami — walang dagdag bayad para sa iyong alagang hayop." },

  // 🏖️ AMENITIES
  { keywords: ["amenities","features","services","amenidad"], reply: "🌟 Amenities include WiFi, kitchen, BBQ grill, Netflix, parking, pool access, at pet-friendly rooms. / Kumpleto sa WiFi, kusina, BBQ grill, Netflix, parking, at pool!" },
  { keywords: ["wifi","internet"], reply: "🌐 Yes, may free WiFi sa lahat ng bahay. / Oo, may libreng WiFi — ibibigay ang password pag check-in." },
  { keywords: ["pool","swimming","swim","paligo","swimming pool"], reply: "🏊 The pool is shared among every three units at open daily 8 AM–10 PM. / Ang pool ay bukas mula **8 AM hanggang 10 PM**, at pinaghahati-hatian ng tatlong units." },
  { keywords: ["breakfast","food","coffee","snacks","pagkain","almusal"], reply: "☕ Yes! May free breakfast at libreng kape o snacks sa maagang dating. / Free breakfast and coffee for early guests!" },
  { keywords: ["parking","car","garage","sasakyan"], reply: "🚗 Free parking on-site and on the street. / May **libreng parking** sa loob at sa labas ng lugar." },
  { keywords: ["grill","bbq","barbecue","ihawan"], reply: "🔥 May BBQ grills na pwede mong gamitin malapit sa pool area. / Pwede kang mag-ihaw, basta linisin pagkatapos gamitin." },
  { keywords: ["tv","netflix","entertainment"], reply: "📺 May Smart TV with Netflix sa bawat bahay. / Lahat ng units ay may TV na may Netflix access." },

  // 🏡 HOUSE DETAILS
  { keywords: ["houses","rooms","units","bahay","kwarto"], reply: "🏡 We have **8 unique houses**, bawat isa may sariling design at amenities. / Mayroon kaming **8 bahay** na may iba't ibang style at kumpletong gamit." },
  { keywords: ["long stay","monthly","28 days","long term","matagal"], reply: "📆 Yes, we allow long-term stays (28 days or more). / Pwede kang mag-stay ng pangmatagalan, 28 days o higit pa." },
  { keywords: ["cleaning","housekeeping","linis","clean"], reply: "🧹 Nililinis ang mga rooms bago ang bawat check-in. / Pwede ring humingi ng extra cleaning kapag kailangan." },
  { keywords: ["aircon","air conditioning","aircon ba"], reply: "❄️ Lahat ng bahay ay may aircon para sa iyong comfort." },
  { keywords: ["kitchen","cook","cooking","lutuan","magluto"], reply: "🍳 May kusina ang bawat unit kung saan pwede kang magluto ng sarili mong pagkain." },
  { keywords: ["security","safe","guard","bantay","seguridad"], reply: "🛡️ May 24/7 guard at gated area para sa kaligtasan ng guests." },

  // 🔒 ACCOUNT / LOGIN
  { keywords: ["account","login","register","signup","sign up","mag register"], reply: "👤 Pwede kang gumawa ng account para i-manage ang booking, makita ang payment history, at makipag-ugnayan sa admin." },
  { keywords: ["forgot password","reset password","recover account","nakalimutan password"], reply: "🔑 Kung nakalimutan mo ang password, i-click ang 'Forgot Password' sa Login page para mag-reset." },
  { keywords: ["update account","edit profile","baguhin profile"], reply: "⚙️ Pwede mong baguhin ang iyong profile o password sa Account Settings." },

  // 📞 CONTACT
  { keywords: ["contact","admin","owner","message","help","support","tulong"], reply: "📞 Pwede kang makipag-ugnayan sa admin sa **Contact Us** tab o mag-message sa dashboard. / Para sa tulong, bisitahin ang Contact Us page." },
  { keywords: ["owner","host","may-ari","host"], reply: "👨‍💼 Ang may-ari ay si **Mr. Edgar Fuentes Dicimulacion**, isang Computer Engineer na may higit 7 taon sa hosting industry." },
  { keywords: ["facebook","social","page","messenger"], reply: "💬 Pwede rin kaming kontakin sa aming official **Facebook Page (Dicimulacion Staycation)** sa Messenger." },

  // ❓ GENERAL QUESTIONS
  { keywords: ["rules","policy","house rules","bawal","patakaran"], reply: "📜 Paalala: Bawal ang maingay pagkatapos ng 10 PM, bawal manigarilyo sa loob, at panatilihing malinis ang bahay. / Please observe house rules during your stay." },
  { keywords: ["check availability","available date","may bakante ba"], reply: "📅 Pwede mong tingnan ang mga available dates sa Booking page bago mag-book." },
  { keywords: ["thanks","thank you","salamat"], reply: "😊 You're very welcome! / Walang anuman! Sana makita ka namin sa Dicimulacion Staycation!" },
  { keywords: ["bye","goodbye","paalam","alis"], reply: "👋 Bye! Ingat ka, at sana makita ka namin sa Dicimulacion Staycation!" }
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
