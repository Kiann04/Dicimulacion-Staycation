"use client";

import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { Booking } from "@/lib/types";
import { formatPHP, formatDate } from "@/lib/utils";
import { StatusBadge, EmptyState } from "@/components/shared";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog } from "@/components/ui/dialog";
import {
  CalendarCheck2,
  Search,
  CheckCircle2,
  XCircle,
  Eye,
  CreditCard,
  DollarSign,
  AlertCircle,
} from "lucide-react";

export default function AdminBookingsPage() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<string>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedProofBooking, setSelectedProofBooking] = useState<Booking | null>(null);

  const { data: bookings = [], isLoading } = useQuery({
    queryKey: ["admin-bookings", activeTab, searchQuery],
    queryFn: () =>
      adminService.getBookings({
        status: activeTab === "all" ? undefined : activeTab,
        search: searchQuery || undefined,
      }),
  });

  // Mutations
  const approveMutation = useMutation({
    mutationFn: (id: number) => adminService.approveBooking(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-bookings"] });
      queryClient.invalidateQueries({ queryKey: ["admin-dashboard-stats"] });
      alert("Booking has been approved and confirmation email was triggered.");
    },
  });

  const declineMutation = useMutation({
    mutationFn: (id: number) => adminService.declineBooking(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-bookings"] });
      queryClient.invalidateQueries({ queryKey: ["admin-dashboard-stats"] });
      alert("Booking has been declined.");
    },
  });

  const markPaidMutation = useMutation({
    mutationFn: (id: number) => adminService.markAsFullyPaid(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-bookings"] });
      queryClient.invalidateQueries({ queryKey: ["admin-dashboard-stats"] });
      alert("Booking marked as fully paid.");
    },
  });

  const updatePaymentMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      adminService.updatePaymentStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-bookings"] });
      queryClient.invalidateQueries({ queryKey: ["admin-dashboard-stats"] });
      alert("Payment status updated.");
    },
  });

  const tabs = [
    { label: "All Bookings", value: "all" },
    { label: "Pending Approval", value: "pending" },
    { label: "Approved / Active", value: "approved" },
    { label: "Confirmed / Paid", value: "confirmed" },
    { label: "Cancelled / Declined", value: "cancelled" },
  ];

  return (
    <div className="space-y-8 max-w-7xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            Reservations
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Bookings Management</h1>
        </div>
      </div>

      {/* Filter Tabs & Search */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-4">
        <div className="flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-none">
          {tabs.map((tab) => (
            <button
              key={tab.value}
              onClick={() => setActiveTab(tab.value)}
              className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-colors ${
                activeTab === tab.value
                  ? "bg-primary text-primary-foreground shadow-sm"
                  : "text-muted-foreground hover:bg-muted"
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>

        <div className="relative w-full sm:w-64">
          <Input
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search by guest or villa..."
            className="pl-8 text-xs h-9"
          />
          <Search className="h-3.5 w-3.5 text-muted-foreground absolute left-2.5 top-3" />
        </div>
      </div>

      {/* Table */}
      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-16 rounded-xl bg-muted/60 animate-pulse" />
          ))}
        </div>
      ) : bookings.length === 0 ? (
        <EmptyState
          icon={<CalendarCheck2 className="h-8 w-8" />}
          title="No Bookings Found"
          description="There are no reservations matching the selected filter criteria."
        />
      ) : (
        <div className="rounded-2xl border border-border/80 bg-card overflow-hidden shadow-subtle">
          <div className="overflow-x-auto">
            <table className="w-full text-xs text-left">
              <thead className="bg-muted/40 border-b border-border/60 uppercase text-[10px] tracking-wider text-muted-foreground">
                <tr>
                  <th className="p-4">ID</th>
                  <th className="p-4">Guest Info</th>
                  <th className="p-4">Staycation Villa</th>
                  <th className="p-4">Dates & Guests</th>
                  <th className="p-4">Pricing</th>
                  <th className="p-4">Status</th>
                  <th className="p-4">Payment</th>
                  <th className="p-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/40">
                {bookings.map((booking) => (
                  <tr key={booking.id} className="hover:bg-muted/20 transition-colors">
                    <td className="p-4 font-mono font-bold text-muted-foreground">#{booking.id}</td>

                    <td className="p-4">
                      <div className="font-semibold text-foreground">{booking.name}</div>
                      <div className="text-muted-foreground text-[11px]">{booking.email || booking.phone}</div>
                      <div className="text-muted-foreground text-[11px]">{booking.phone}</div>
                    </td>

                    <td className="p-4">
                      <div className="font-medium text-foreground">{booking.staycation?.house_name}</div>
                      <div className="text-[11px] text-muted-foreground">{booking.staycation?.house_location}</div>
                    </td>

                    <td className="p-4">
                      <div className="font-medium text-foreground">
                        {formatDate(booking.start_date)} - {formatDate(booking.end_date)}
                      </div>
                      <div className="text-[11px] text-muted-foreground">{booking.guest_number} Guests</div>
                    </td>

                    <td className="p-4">
                      <div className="font-bold text-foreground">{formatPHP(booking.total_price)}</div>
                      <div className="text-[11px] text-muted-foreground">
                        Paid: {formatPHP(booking.amount_paid)}
                      </div>
                    </td>

                    <td className="p-4">
                      <StatusBadge status={booking.status} />
                    </td>

                    <td className="p-4">
                      <div className="flex flex-col gap-1">
                        <StatusBadge status={booking.payment_status} />
                        <span className="text-[10px] text-muted-foreground uppercase">
                          {booking.payment_method}
                        </span>
                      </div>
                    </td>

                    <td className="p-4 text-right">
                      <div className="flex items-center justify-end gap-1.5 flex-wrap">
                        {/* Proof Button */}
                        <Button
                          variant="outline"
                          size="sm"
                          className="h-7 text-xs px-2"
                          onClick={() => setSelectedProofBooking(booking)}
                          title="View Payment Proof"
                        >
                          <Eye className="h-3 w-3 mr-1" />
                          Proof
                        </Button>

                        {/* Approve / Decline for Pending */}
                        {booking.status === "pending" && (
                          <>
                            <Button
                              variant="default"
                              size="sm"
                              className="h-7 text-xs px-2 bg-emerald-600 hover:bg-emerald-700 text-white"
                              onClick={() => approveMutation.mutate(booking.id)}
                            >
                              <CheckCircle2 className="h-3 w-3 mr-1" />
                              Approve
                            </Button>
                            <Button
                              variant="destructive"
                              size="sm"
                              className="h-7 text-xs px-2"
                              onClick={() => {
                                if (confirm("Decline this booking reservation?")) {
                                  declineMutation.mutate(booking.id);
                                }
                              }}
                            >
                              <XCircle className="h-3 w-3 mr-1" />
                              Decline
                            </Button>
                          </>
                        )}

                        {/* Mark Fully Paid for Half-Paid */}
                        {booking.payment_status === "half_paid" && (
                          <Button
                            variant="gold"
                            size="sm"
                            className="h-7 text-xs px-2"
                            onClick={() => markPaidMutation.mutate(booking.id)}
                          >
                            <DollarSign className="h-3 w-3 mr-1" />
                            Full Paid
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* PAYMENT PROOF MODAL */}
      <Dialog
        isOpen={!!selectedProofBooking}
        onClose={() => setSelectedProofBooking(null)}
        title={`Payment Proof - Booking #${selectedProofBooking?.id}`}
        description={`Submitted by ${selectedProofBooking?.name} via ${selectedProofBooking?.payment_method?.toUpperCase()}`}
        maxWidth="lg"
      >
        {selectedProofBooking && (
          <div className="space-y-5 text-xs sm:text-sm">
            <div className="p-4 rounded-xl bg-secondary/40 border border-border grid grid-cols-2 gap-3">
              <div>
                <span className="text-[10px] uppercase text-muted-foreground block">Total Amount:</span>
                <span className="font-bold text-foreground">{formatPHP(selectedProofBooking.total_price)}</span>
              </div>
              <div>
                <span className="text-[10px] uppercase text-muted-foreground block">Amount Paid:</span>
                <span className="font-bold text-emerald-600">{formatPHP(selectedProofBooking.amount_paid)}</span>
              </div>
              {selectedProofBooking.transaction_number && (
                <div className="col-span-2">
                  <span className="text-[10px] uppercase text-muted-foreground block">Reference Number:</span>
                  <span className="font-mono text-foreground">{selectedProofBooking.transaction_number}</span>
                </div>
              )}
            </div>

            {/* Image Preview */}
            <div className="rounded-xl border border-border overflow-hidden bg-black/5 flex items-center justify-center p-2 min-h-[250px]">
              {selectedProofBooking.payment_proof ? (
                <img
                  src={selectedProofBooking.payment_proof}
                  alt="Proof of Payment"
                  className="max-h-[380px] w-auto object-contain rounded-lg shadow-sm"
                />
              ) : (
                <div className="text-center py-10 text-muted-foreground">
                  <AlertCircle className="h-8 w-8 mx-auto mb-2 text-muted-foreground/60" />
                  <span>No payment screenshot uploaded.</span>
                </div>
              )}
            </div>

            {/* Quick Status Changers */}
            <div className="flex items-center justify-between pt-3 border-t border-border">
              <span className="text-xs text-muted-foreground font-semibold uppercase">
                Update Payment Status:
              </span>
              <div className="flex items-center gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  className="h-8 text-xs"
                  onClick={() => {
                    updatePaymentMutation.mutate({ id: selectedProofBooking.id, status: "paid" });
                    setSelectedProofBooking(null);
                  }}
                >
                  Mark as Paid
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  className="h-8 text-xs"
                  onClick={() => {
                    updatePaymentMutation.mutate({ id: selectedProofBooking.id, status: "half_paid" });
                    setSelectedProofBooking(null);
                  }}
                >
                  Mark Half-Paid
                </Button>
              </div>
            </div>
          </div>
        )}
      </Dialog>
    </div>
  );
}
