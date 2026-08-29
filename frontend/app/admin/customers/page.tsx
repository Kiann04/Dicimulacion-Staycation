"use client";

import React, { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { formatDate } from "@/lib/utils";
import { Input } from "@/components/ui/input";
import { EmptyState } from "@/components/shared";
import { Users, Search, Mail, Calendar, UserCheck } from "lucide-react";

export default function AdminCustomersPage() {
  const [search, setSearch] = useState("");

  const { data: customers = [], isLoading } = useQuery({
    queryKey: ["admin-customers"],
    queryFn: () => adminService.getCustomers(),
  });

  const filtered = customers.filter(
    (c) =>
      c.name.toLowerCase().includes(search.toLowerCase()) ||
      c.email.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-8 max-w-6xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            Guest Database
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Registered Customers</h1>
        </div>

        <div className="relative w-full sm:w-72">
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by customer name or email..."
            className="pl-8 text-xs h-9"
          />
          <Search className="h-3.5 w-3.5 text-muted-foreground absolute left-2.5 top-3" />
        </div>
      </div>

      {/* List */}
      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-16 rounded-xl bg-muted/60" />
          ))}
        </div>
      ) : filtered.length === 0 ? (
        <EmptyState
          icon={<Users className="h-8 w-8" />}
          title="No Customers Found"
          description="No customer accounts match your search query."
        />
      ) : (
        <div className="rounded-2xl border border-border bg-card overflow-hidden shadow-subtle">
          <table className="w-full text-xs text-left">
            <thead className="bg-muted/40 border-b border-border text-muted-foreground uppercase text-[10px] tracking-wider">
              <tr>
                <th className="p-4">Customer Name</th>
                <th className="p-4">Email Address</th>
                <th className="p-4">Account Type</th>
                <th className="p-4">Registered Date</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/40">
              {filtered.map((c) => (
                <tr key={c.id} className="hover:bg-muted/20 transition-colors">
                  <td className="p-4">
                    <div className="flex items-center gap-3">
                      <div className="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                        {c.name.charAt(0)}
                      </div>
                      <span className="font-semibold text-foreground">{c.name}</span>
                    </div>
                  </td>
                  <td className="p-4 text-muted-foreground">{c.email}</td>
                  <td className="p-4">
                    <span className="inline-flex items-center gap-1 text-[10px] font-semibold uppercase text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                      <UserCheck className="h-3 w-3" /> Verified Guest
                    </span>
                  </td>
                  <td className="p-4 text-muted-foreground">{formatDate(c.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
