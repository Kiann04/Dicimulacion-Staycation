"use client";

import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { Inquiry } from "@/lib/types";
import { formatDate } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Dialog } from "@/components/ui/dialog";
import { EmptyState } from "@/components/shared";
import { MessageSquare, Mail, Trash2, Reply, Send, CheckCircle2 } from "lucide-react";

export default function AdminMessagesPage() {
  const queryClient = useQueryClient();
  const [selectedInquiry, setSelectedInquiry] = useState<Inquiry | null>(null);
  const [replyText, setReplyText] = useState("");
  const [isReplying, setIsReplying] = useState(false);

  const { data: inquiries = [], isLoading } = useQuery({
    queryKey: ["admin-inquiries"],
    queryFn: () => adminService.getInquiries(),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => adminService.deleteInquiry(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-inquiries"] });
      alert("Inquiry message deleted.");
    },
  });

  const handleReplySubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedInquiry || !replyText.trim()) return;
    setIsReplying(true);

    try {
      await adminService.replyToInquiry(selectedInquiry.id, replyText);
      alert("Reply sent via email successfully!");
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
      {/* Header */}
      <div>
        <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
          Inbox
        </span>
        <h1 className="font-serif text-3xl font-bold text-foreground">Customer Inquiries & Messages</h1>
      </div>

      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-24 rounded-2xl bg-muted/60" />
          ))}
        </div>
      ) : inquiries.length === 0 ? (
        <EmptyState
          icon={<MessageSquare className="h-8 w-8" />}
          title="Inbox Clean"
          description="You have no pending customer inquiries."
        />
      ) : (
        <div className="space-y-4">
          {inquiries.map((msg) => (
            <div
              key={msg.id}
              className="rounded-2xl border border-border bg-card p-6 shadow-subtle flex flex-col sm:flex-row justify-between gap-4 items-start sm:items-center"
            >
              <div className="space-y-2 flex-1">
                <div className="flex items-center gap-3">
                  <div className="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                    <Mail className="h-4 w-4" />
                  </div>
                  <div>
                    <h4 className="font-bold text-xs sm:text-sm text-foreground">{msg.email}</h4>
                    <span className="text-[10px] text-muted-foreground">{formatDate(msg.created_at)}</span>
                  </div>
                </div>

                <p className="text-xs sm:text-sm text-muted-foreground leading-relaxed pl-11">
                  &ldquo;{msg.message}&rdquo;
                </p>
              </div>

              <div className="flex items-center gap-2 pl-11 sm:pl-0">
                <Button
                  variant="gold"
                  size="sm"
                  className="text-xs gap-1.5"
                  onClick={() => setSelectedInquiry(msg)}
                >
                  <Reply className="h-3.5 w-3.5" />
                  Reply Email
                </Button>

                <Button
                  variant="ghost"
                  size="sm"
                  className="text-xs text-destructive hover:bg-destructive/10"
                  onClick={() => {
                    if (confirm("Delete this inquiry?")) {
                      deleteMutation.mutate(msg.id);
                    }
                  }}
                >
                  <Trash2 className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* REPLY MODAL */}
      <Dialog
        isOpen={!!selectedInquiry}
        onClose={() => setSelectedInquiry(null)}
        title={`Reply to ${selectedInquiry?.email}`}
        description="Your message will be formatted with official branding and delivered to the guest's inbox."
        maxWidth="md"
      >
        <form onSubmit={handleReplySubmit} className="space-y-4 text-xs sm:text-sm">
          <div className="p-3 rounded-xl bg-muted/40 text-xs text-muted-foreground">
            <strong>Original Inquiry:</strong> {selectedInquiry?.message}
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Your Response
            </label>
            <Textarea
              required
              rows={5}
              value={replyText}
              onChange={(e) => setReplyText(e.target.value)}
              placeholder="Good day! Regarding your inquiry..."
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-2">
            <Button type="button" variant="outline" onClick={() => setSelectedInquiry(null)}>
              Cancel
            </Button>
            <Button type="submit" variant="gold" isLoading={isReplying} className="gap-1.5">
              <Send className="h-3.5 w-3.5" />
              Send Email Reply
            </Button>
          </div>
        </form>
      </Dialog>
    </div>
  );
}
