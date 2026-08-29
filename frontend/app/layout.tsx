import type { Metadata } from "next";
import "./globals.css";
import { Providers } from "@/components/providers";
import { Navbar } from "@/components/shared/Navbar";
import { Footer } from "@/components/shared/Footer";
import { ChatbotWidget } from "@/components/shared/ChatbotWidget";

export const metadata: Metadata = {
  title: "Dicimulacion Staycation | Luxury Private Villas in Tagaytay & Cavite",
  description: "Experience premium private staycation villas with heated pools, karaoke lounges, outdoor bonfires, and scenic Tagaytay views. Book your serene getaway today.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="scroll-smooth">
      <body className="min-h-screen flex flex-col bg-background text-foreground antialiased selection:bg-primary/20 selection:text-primary">
        <Providers>
          <Navbar />
          <main className="flex-1">{children}</main>
          <Footer />
          <ChatbotWidget />
        </Providers>
      </body>
    </html>
  );
}
