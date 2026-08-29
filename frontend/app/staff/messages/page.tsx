"use client";

import React, { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { Inquiry } from "@/lib/types";
import { formatDate } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Dialog } from "@/components/ui/dialog";
import { EmptyState } from "@/components/shared";
import { MessageSquare, Mail, Reply, Send } from "lucide-react";

export default function StaffMessagesPage() {
  const [selectedInquiry, setSelectedInquiry] = useState<Inquiry | null>(null);
  const [replyText, setReplyText] = useState("");
  const [isReplying, setIsReplying] = useState(false);

  const { data: inquiries = [], isLoading } = useQuery({
    queryKey: ["staff-inquiries"],
    queryFn: () => adminService.getInquiries(),
  });

  const handleReplySubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedInquiry || !replyText.trim()) return;
    setIsReplying(true);

    try {
      await adminService.replyToInquiry(selectedInquiry.id, replyText);
      alert("Inquiry reply email sent successfully!");
      setSelectedInquiry(null);
      setReplyText("");
    } catch {
      alert("Failed to send reply.");
    } finally {
      setIsReplying(false);
    }
  };

  return (
    <div className="space-y-8 max-w-6xl">
      <div>
        <span className="text-xs font-bold uppercase tracking-widest text-primary block mb-1">
          Support Inbox
        </span>
        <h1 className="font-serif text-3xl font-bold text-foreground">Guest Inquiries & Support</h1>
      </div>

      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2].map((i) => (
            <div key={i} className="h-20 rounded-xl bg-muted" />
          ))}
        </div>
      ) : inquiries.length === 0 ? (
        <EmptyState
          icon={<MessageSquare className="h-8 w-8" />}
          title="No Guest Messages"
          description="All guest inquiries have been resolved."
        />
      ) : (
        <div className="space-y-4">
          {inquiries.map((msg) => (
            <div
              key={msg.id}
              className="rounded-2xl border border-border bg-card p-6 shadow-subtle flex flex-col sm:flex-row justify-between gap-4 items-start sm:items-center"
            >
              <div className="space-y-1.5 flex-1">
                <div className="flex items-center gap-2">
                  <Mail className="h-4 w-4 text-primary" />
                  <span className="font-bold text-xs sm:text-sm text-foreground">{msg.email}</span>
                  <span className="text-[10px] text-muted-foreground">• {formatDate(msg.created_at)}</span>
                </div>
                <p className="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                  &ldquo;{msg.message}&rdquo;
                </p>
              </div>

              <Button
                variant="gold"
                size="sm"
                className="text-xs gap-1.5 shrink-0"
                onClick={() => setSelectedInquiry(msg)}
              >
                <Reply className="h-3.5 w-3.5" />
                Reply
              </Button>
            </div>
          ))}
        </div>
      )}

      {/* REPLY MODAL */}
      <Dialog
        isOpen={!!selectedInquiry}
        onClose={() => setSelectedInquiry(null)}
        title={`Reply to ${selectedInquiry?.email}`}
        maxWidth="md"
      >
        <form onSubmit={handleReplySubmit} className="space-y-4 text-xs sm:text-sm">
          <div className="p-3 rounded-xl bg-muted/40 text-xs text-muted-foreground">
            <strong>Guest Inquiry:</strong> {selectedInquiry?.message}
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Staff Response
            </label>
            <Textarea
              required
              rows={4}
              value={replyText}
              onChange={(e) => setReplyText(e.target.value)}
              placeholder="Hi there! Regarding your staycation..."
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-2">
            <Button type="button" variant="outline" onClick={() => setSelectedInquiry(null)}>
              Cancel
            </Button>
            <Button type="submit" variant="gold" isLoading={isReplying} className="gap-1.5">
              <Send className="h-3.5 w-3.5" />
              Send Response
            </Button>
          </div>
        </form>
      </Dialog>
    </div>
  );
}
