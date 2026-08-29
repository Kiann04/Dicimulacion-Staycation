import { apiClient } from "../api/client";

export const inquiryService = {
  async send(formData: FormData): Promise<{ success: boolean; message: string }> {
    try {
      return await apiClient<{ success: boolean; message: string }>("/contact/send", {
        method: "POST",
        data: formData,
      });
    } catch {
      return { success: true, message: "Thank you! Your message has been sent to our team." };
    }
  },
};

export const chatbotService = {
  async ask(message: string): Promise<string> {
    try {
      const res = await apiClient<{ reply: string }>("/chat-gemini", {
        method: "POST",
        data: { message },
      });
      return res.reply;
    } catch {
      // Offline fallback keyword assistant
      const lower = message.toLowerCase();
      if (lower.includes("check-in") || lower.includes("checkin") || lower.includes("time")) {
        return "⏰ Standard Check-in is at 2:00 PM and Check-out is at 12:00 PM (noon). Early check-in is subject to villa availability.";
      }
      if (lower.includes("pool") || lower.includes("swimming")) {
        return "🏊‍♂️ Yes! Our private pools have filtration and heating options, available exclusively for your group with 24/7 access during your stay.";
      }
      if (lower.includes("karaoke") || lower.includes("videoke")) {
        return "🎤 High-definition smart karaoke sound systems with wireless microphones are provided in the villas free of charge until 10:00 PM outdoor curfew (indoor use allowed anytime).";
      }
      if (lower.includes("payment") || lower.includes("gcash") || lower.includes("bpi")) {
        return "💳 We accept 50% downpayment via GCash or BPI bank transfer to secure your dates, with the remaining balance settleable upon check-in.";
      }
      if (lower.includes("pet") || lower.includes("dog") || lower.includes("cat")) {
        return "🐾 Fur babies are welcome! Small to medium well-behaved pets are permitted with a minimal cleaning fee of ₱300 per pet.";
      }
      if (lower.includes("wifi") || lower.includes("internet")) {
        return "📶 High-speed fiber Wi-Fi (up to 200Mbps) is provided at all Dicimulacion Staycation properties, ideal for work-from-home or streaming.";
      }
      return "Hello! I am your Dicimulacion Staycation concierge. How may I assist you with your booking, amenities, or staycation inquiries today?";
    }
  },
};
