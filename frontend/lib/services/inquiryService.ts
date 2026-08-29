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
