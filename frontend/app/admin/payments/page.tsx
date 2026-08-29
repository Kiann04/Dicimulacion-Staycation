"use client";

import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { formatPHP, formatDate } from "@/lib/utils";
import { StatusBadge, StatCard, EmptyState } from "@/components/shared";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog } from "@/components/ui/dialog";
import {
  CreditCard,
  Search,
  CheckCircle2,
  Clock,
  Eye,
  FileCheck,
  DollarSign,
  AlertCircle,
  ExternalLink,
} from "lucide-react";

export default function AdminPaymentsPage() {
  const queryClient = useQueryClient();
  const [filter, setFilter] = useState<"all" | "pending" | "paid" | "half_paid">("all");
  const [search, setSearch] = useState("");
  const [selectedProofUrl, setSelectedProofUrl] = useState<string | null>(null);

  const { data: bookings = [], isLoading } = useQuery({
    queryKey: ["admin-payments", filter, search],
    queryFn: () =>
      adminService.getBookings({
        status: filter === "all" ? undefined : filter,
        search: search || undefined,
      }),
  });

  const markPaidMutation = useMutation({
    mutationFn: (id: number) => adminService.markAsFullyPaid(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-payments"] });
      alert("Payment updated to Fully Paid!");
    },
  });

  const filteredBookings = bookings.filter((b) => {
    if (filter === "pending") return b.payment_status === "pending" || b.payment_status === "unpaid";
    if (filter === "paid") return b.payment_status === "paid";
    if (filter === "half_paid") return b.payment_status === "half_paid";
    return true;
  });

  // KPI Calculations
  const totalCollected = bookings
    .filter((b) => b.payment_status === "paid" || b.payment_status === "half_paid")
    .reduce((acc, curr) => {
      const price = curr.total_price || parseFloat(curr.pricing?.total_price || "0") || 0;
      return acc + (curr.payment_status === "half_paid" ? Math.round(price / 2) : price);
    }, 0);

  const pendingVerificationCount = bookings.filter(
    (b) => b.payment_status === "pending" || b.payment_status === "unpaid"
  ).length;

  const totalOutstandingBalance = bookings
    .filter((b) => b.payment_status === "half_paid")
    .reduce((acc, curr) => {
      const price = curr.total_price || parseFloat(curr.pricing?.total_price || "0") || 0;
      return acc + Math.round(price / 2);
    }, 0);

  return (
    <div className="space-y-8 max-w-7xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-primary block mb-1">
            Financials & Collections
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Payment Verification & Ledger</h1>
        </div>
      </div>

      {/* KPI Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <StatCard
          title="Verified Collections"
          value={formatPHP(totalCollected)}
          subtitle="Total verified deposits"
          icon={<DollarSign className="h-5 w-5" />}
        />
        <StatCard
          title="Pending Verification"
          value={pendingVerificationCount}
          subtitle="Receipts awaiting review"
          icon={<Clock className="h-5 w-5" />}
        />
        <StatCard
          title="Remaining Check-In Balances"
          value={formatPHP(totalOutstandingBalance)}
          subtitle="Due on guest arrivals"
          icon={<AlertCircle className="h-5 w-5" />}
        />
      </div>

      {/* Filter & Search Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-card border border-border shadow-subtle">
        <div className="flex items-center gap-1.5 overflow-x-auto pb-2 sm:pb-0">
          {(["all", "pending", "half_paid", "paid"] as const).map((tab) => (
            <button
              key={tab}
              onClick={() => setFilter(tab)}
              className={`px-3 py-1.5 rounded-xl text-xs font-semibold capitalize transition-colors whitespace-nowrap ${
                filter === tab
                  ? "bg-primary text-primary-foreground shadow-sm"
                  : "text-muted-foreground hover:bg-muted"
              }`}
            >
              {tab === "all"
                ? "All Transactions"
                : tab === "half_paid"
                ? "50% Downpayment"
                : tab === "paid"
                ? "Fully Paid"
                : "Pending Review"}
            </button>
          ))}
        </div>

        <div className="relative w-full sm:w-72">
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by guest, villa, phone..."
            className="pl-8 text-xs h-9"
          />
          <Search className="h-3.5 w-3.5 text-muted-foreground absolute left-2.5 top-3" />
        </div>
      </div>

      {/* Transactions Table */}
      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-16 rounded-xl bg-muted" />
          ))}
        </div>
      ) : filteredBookings.length === 0 ? (
        <EmptyState
          icon={<CreditCard className="h-8 w-8" />}
          title="No Transactions Found"
          description="There are no payment records matching this filter."
        />
      ) : (
        <div className="rounded-2xl border border-border bg-card overflow-hidden shadow-subtle">
          <table className="w-full text-xs text-left">
            <thead className="bg-muted/40 border-b border-border text-muted-foreground uppercase text-[10px] tracking-wider">
              <tr>
                <th className="p-4">Booking #</th>
                <th className="p-4">Guest Information</th>
                <th className="p-4">Villa Property</th>
                <th className="p-4">Total Amount</th>
                <th className="p-4">Paid Amount</th>
                <th className="p-4">Payment Status</th>
                <th className="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/40">
              {filteredBookings.map((b) => {
                const totalPrice = b.total_price || parseFloat(b.pricing?.total_price || "0") || 0;
                const paidAmount =
                  b.payment_status === "paid"
                    ? totalPrice
                    : b.payment_status === "half_paid"
                    ? Math.round(totalPrice / 2)
                    : 0;

                return (
                  <tr key={b.id} className="hover:bg-muted/20 transition-colors">
                    <td className="p-4 font-mono font-bold text-muted-foreground">#{b.id}</td>
                    <td className="p-4">
                      <div className="font-semibold text-foreground">{b.name}</div>
                      <div className="text-[11px] text-muted-foreground">{b.phone}</div>
                    </td>
                    <td className="p-4 font-medium text-foreground">{b.staycation?.house_name}</td>
                    <td className="p-4 font-semibold text-foreground">{formatPHP(b.total_price)}</td>
                    <td className="p-4 font-bold text-emerald-700 dark:text-emerald-300">
                      {formatPHP(paidAmount)}
                    </td>
                    <td className="p-4">
                      <StatusBadge status={b.payment_status} />
                    </td>
                    <td className="p-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          className="text-xs gap-1.5 h-8"
                          onClick={() =>
                            setSelectedProofUrl(
                              b.payment_proof ||
                                "https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=800&auto=format&fit=crop&q=80"
                            )
                          }
                        >
                          <Eye className="h-3.5 w-3.5" />
                          View Receipt
                        </Button>

                        {b.payment_status === "half_paid" && (
                          <Button
                            variant="gold"
                            size="sm"
                            className="text-xs gap-1 h-8"
                            onClick={() => markPaidMutation.mutate(b.id)}
                          >
                            <FileCheck className="h-3.5 w-3.5" />
                            Mark Full Paid
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* PROOF INSPECTOR MODAL */}
      <Dialog
        isOpen={!!selectedProofUrl}
        onClose={() => setSelectedProofUrl(null)}
        title="Transaction Receipt / Payment Proof"
        description="Verify the uploaded GCash/BPI deposit screenshot with reference number."
        maxWidth="lg"
      >
        <div className="space-y-4">
          <div className="rounded-2xl border border-border overflow-hidden bg-black/5 flex items-center justify-center min-h-[350px]">
            {selectedProofUrl && (
              <img
                src={selectedProofUrl}
                alt="Payment Proof"
                className="max-h-[500px] w-auto object-contain rounded-lg shadow-sm"
              />
            )}
          </div>

          <div className="flex items-center justify-end gap-3 pt-2">
            <Button variant="outline" onClick={() => setSelectedProofUrl(null)}>
              Close Viewer
            </Button>
            {selectedProofUrl && (
              <a href={selectedProofUrl} target="_blank" rel="noopener noreferrer">
                <Button variant="gold" className="gap-1.5">
                  <ExternalLink className="h-4 w-4" /> Open Full Image
                </Button>
              </a>
            )}
          </div>
        </div>
      </Dialog>
    </div>
  );
}
