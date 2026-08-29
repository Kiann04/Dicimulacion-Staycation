"use client";

import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { staycationService } from "@/lib/services/staycationService";
import { formatDate } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog } from "@/components/ui/dialog";
import { CalendarDays, Plus, Lock, CheckCircle2 } from "lucide-react";

export default function AdminBlockedDatesPage() {
  const queryClient = useQueryClient();
  const [isModalOpen, setIsModalOpen] = useState(false);

  const [staycationId, setStaycationId] = useState<number>(1);
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [reason, setReason] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { data: villas = [] } = useQuery({
    queryKey: ["admin-staycations"],
    queryFn: () => staycationService.getAll(),
  });

  const { data: events = [] } = useQuery({
    queryKey: ["calendar-events", staycationId],
    queryFn: () => staycationService.getCalendarEvents(staycationId),
  });

  const blockMutation = useMutation({
    mutationFn: (payload: { staycation_id: number; start_date: string; end_date: string; reason?: string }) =>
      adminService.addBlockedDate(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["calendar-events"] });
      setIsModalOpen(false);
      setReason("");
      alert("Dates successfully blocked for maintenance.");
    },
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!startDate || !endDate) return;
    setIsSubmitting(true);
    blockMutation.mutate({
      staycation_id: staycationId,
      start_date: startDate,
      end_date: endDate,
      reason: reason || "Property Maintenance",
    });
    setIsSubmitting(false);
  };

  return (
    <div className="space-y-8 max-w-6xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            Availability Control
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Blocked Dates & Maintenance</h1>
        </div>

        <Button onClick={() => setIsModalOpen(true)} variant="gold" size="sm" className="gap-2">
          <Plus className="h-4 w-4" />
          Block New Dates
        </Button>
      </div>

      {/* Villa Selector */}
      <div className="p-4 rounded-2xl bg-card border border-border flex items-center gap-4">
        <label className="text-xs font-semibold uppercase text-muted-foreground">Select Villa:</label>
        <select
          value={staycationId}
          onChange={(e) => setStaycationId(Number(e.target.value))}
          className="text-xs bg-background border border-input rounded-xl px-3 py-2 text-foreground font-medium"
        >
          {villas.map((v) => (
            <option key={v.id} value={v.id}>
              {v.house_name}
            </option>
          ))}
        </select>
      </div>

      {/* Calendar / Blocked List */}
      <div className="rounded-2xl border border-border bg-card p-6 shadow-subtle">
        <h3 className="font-serif text-lg font-bold text-foreground mb-4">
          Current Blocked & Reserved Dates
        </h3>

        <div className="space-y-3">
          {events.map((evt, idx) => (
            <div
              key={idx}
              className="flex items-center justify-between p-3.5 rounded-xl border border-border/70 bg-muted/20"
            >
              <div className="flex items-center gap-3">
                <div
                  className="h-3 w-3 rounded-full"
                  style={{ backgroundColor: evt.color || "#6b7280" }}
                />
                <div>
                  <span className="font-semibold text-xs text-foreground block">{evt.title}</span>
                  <span className="text-[11px] text-muted-foreground">
                    {formatDate(evt.start)} to {formatDate(evt.end)}
                  </span>
                </div>
              </div>
              <span className="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-muted text-muted-foreground">
                Unavailable
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* BLOCK DATES MODAL */}
      <Dialog
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Block Property Dates"
        description="Prevent guest bookings during deep cleaning or private owner use."
        maxWidth="md"
      >
        <form onSubmit={handleSubmit} className="space-y-4 text-xs sm:text-sm">
          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Select Staycation Villa
            </label>
            <select
              value={staycationId}
              onChange={(e) => setStaycationId(Number(e.target.value))}
              className="w-full bg-background border border-input rounded-xl px-3 py-2 text-xs text-foreground font-medium"
            >
              {villas.map((v) => (
                <option key={v.id} value={v.id}>
                  {v.house_name}
                </option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Start Date
              </label>
              <Input
                type="date"
                required
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                End Date
              </label>
              <Input
                type="date"
                required
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Reason for Block (Optional)
            </label>
            <Input
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="e.g. Deep cleaning & pool maintenance"
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-3 border-t border-border">
            <Button type="button" variant="outline" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" variant="destructive" isLoading={isSubmitting}>
              Confirm & Block Dates
            </Button>
          </div>
        </form>
      </Dialog>
    </div>
  );
}
