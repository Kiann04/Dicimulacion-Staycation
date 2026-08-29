"use client";

import React, { useState, useRef, useEffect } from "react";
import { MessageSquare, X, Send, Bot, Sparkles, User, Minimize2 } from "lucide-react";
import { chatbotService } from "@/lib/services/chatbotService";
import { Button } from "@/components/ui/button";

interface ChatMessage {
  id: string;
  sender: "bot" | "user";
  text: string;
  timestamp: string;
}

export function ChatbotWidget() {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState<ChatMessage[]>([
    {
      id: "welcome",
      sender: "bot",
      text: "Mabuhay! 🌿 Welcome to Dicimulacion Staycation. How may I assist you today? You can ask about check-in hours, private pool heating, karaoke rules, or payment options.",
      timestamp: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
    },
  ]);
  const [input, setInput] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    if (isOpen) {
      scrollToBottom();
    }
  }, [messages, isOpen]);

  const handleSend = async (textToSend?: string) => {
    const messageText = textToSend || input.trim();
    if (!messageText || isLoading) return;

    const userMsg: ChatMessage = {
      id: String(Date.now()),
      sender: "user",
      text: messageText,
      timestamp: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
    };

    setMessages((prev) => [...prev, userMsg]);
    if (!textToSend) setInput("");
    setIsLoading(true);

    try {
      const reply = await chatbotService.ask(messageText);
      const botMsg: ChatMessage = {
        id: String(Date.now() + 1),
        sender: "bot",
        text: reply,
        timestamp: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
      };
      setMessages((prev) => [...prev, botMsg]);
    } catch {
      setMessages((prev) => [
        ...prev,
        {
          id: String(Date.now() + 1),
          sender: "bot",
          text: "I'm having trouble connecting right now, but feel free to message our team directly via the contact form!",
          timestamp: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
        },
      ]);
    } finally {
      setIsLoading(false);
    }
  };

  const quickPrompts = [
    "Check-in & Check-out times?",
    "Is the private pool heated?",
    "What are GCash payment details?",
    "Are pets allowed?",
  ];

  return (
    <div className="fixed bottom-6 right-6 z-50">
      {/* Trigger Button */}
      {!isOpen && (
        <button
          onClick={() => setIsOpen(true)}
          className="group flex items-center gap-2.5 rounded-full bg-primary p-3.5 sm:px-5 sm:py-3.5 text-white shadow-elevated hover:bg-primary-700 transition-all duration-300 hover:scale-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          aria-label="Open AI Concierge Chat"
        >
          <div className="relative">
            <Bot className="h-6 w-6 text-gold-300" />
            <span className="absolute -top-1 -right-1 flex h-2.5 w-2.5">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-accent"></span>
            </span>
          </div>
          <span className="hidden sm:inline font-medium text-sm">Ask Concierge</span>
        </button>
      )}

      {/* Chat Window */}
      {isOpen && (
        <div className="flex flex-col w-[350px] sm:w-[400px] h-[520px] rounded-2xl bg-card border border-border/80 shadow-2xl overflow-hidden animate-in slide-in-from-bottom-5 duration-300">
          {/* Header */}
          <div className="bg-primary px-4 py-3.5 text-primary-foreground flex items-center justify-between shadow-sm">
            <div className="flex items-center gap-2.5">
              <div className="h-8 w-8 rounded-full bg-primary-700 flex items-center justify-center text-gold-300">
                <Sparkles className="h-4 w-4" />
              </div>
              <div>
                <h4 className="font-serif font-bold text-sm leading-tight">Staycation Concierge</h4>
                <span className="text-[10px] text-primary-200 flex items-center gap-1">
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Online 24/7 AI Assistant
                </span>
              </div>
            </div>
            <button
              onClick={() => setIsOpen(false)}
              className="rounded-full p-1.5 text-primary-200 hover:text-white hover:bg-primary-700 transition-colors"
              aria-label="Close chat"
            >
              <Minimize2 className="h-4 w-4" />
            </button>
          </div>

          {/* Messages Feed */}
          <div className="flex-1 overflow-y-auto p-4 space-y-3.5 bg-background/50 text-sm">
            {messages.map((m) => (
              <div
                key={m.id}
                className={`flex gap-2.5 ${m.sender === "user" ? "justify-end" : "justify-start"}`}
              >
                {m.sender === "bot" && (
                  <div className="h-7 w-7 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                    <Bot className="h-3.5 w-3.5" />
                  </div>
                )}
                <div
                  className={`max-w-[80%] rounded-2xl px-3.5 py-2.5 leading-relaxed shadow-subtle ${
                    m.sender === "user"
                      ? "bg-primary text-primary-foreground rounded-tr-none text-xs sm:text-sm"
                      : "bg-card text-card-foreground border border-border/70 rounded-tl-none text-xs sm:text-sm"
                  }`}
                >
                  <p>{m.text}</p>
                  <span className="block text-[9px] mt-1 opacity-60 text-right">{m.timestamp}</span>
                </div>
              </div>
            ))}

            {isLoading && (
              <div className="flex gap-2.5 items-center text-muted-foreground text-xs pl-2">
                <Bot className="h-3.5 w-3.5 text-primary animate-pulse" />
                <span>Concierge is typing...</span>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* Quick Prompts */}
          <div className="p-2 border-t border-border/50 bg-muted/20 flex gap-1.5 overflow-x-auto whitespace-nowrap scrollbar-none">
            {quickPrompts.map((prompt) => (
              <button
                key={prompt}
                onClick={() => handleSend(prompt)}
                className="text-[11px] font-medium bg-background border border-border/70 text-muted-foreground hover:text-primary hover:border-primary/50 px-2.5 py-1 rounded-full transition-colors shrink-0"
              >
                {prompt}
              </button>
            ))}
          </div>

          {/* Input Box */}
          <form
            onSubmit={(e) => {
              e.preventDefault();
              handleSend();
            }}
            className="p-3 border-t border-border bg-card flex items-center gap-2"
          >
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder="Ask anything about our staycations..."
              className="flex-1 text-xs sm:text-sm bg-muted/50 border border-input rounded-xl px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary"
            />
            <Button
              type="submit"
              size="sm"
              disabled={!input.trim() || isLoading}
              className="h-9 w-9 p-0 rounded-xl shrink-0"
              aria-label="Send message"
            >
              <Send className="h-3.5 w-3.5" />
            </Button>
          </form>
        </div>
      )}
    </div>
  );
}
