"use client";

import React from "react";
import { useQuery } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { formatDate } from "@/lib/utils";
import { EmptyState } from "@/components/shared";
import { ScrollText, ShieldAlert, Laptop, Clock } from "lucide-react";

export default function AdminSettingsPage() {
  const { data: logs = [], isLoading } = useQuery({
    queryKey: ["admin-audit-logs"],
    queryFn: () => adminService.getAuditLogs(),
  });

  return (
    <div className="space-y-8 max-w-6xl">
      {/* Header */}
      <div>
        <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
          Compliance & Security
        </span>
        <h1 className="font-serif text-3xl font-bold text-foreground">System Audit Logs & Trail</h1>
        <p className="text-xs text-muted-foreground mt-1">
          Immutable chronological history of all administrative actions, approval events, and email dispatches.
        </p>
      </div>

      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-16 rounded-xl bg-muted" />
          ))}
        </div>
      ) : logs.length === 0 ? (
        <EmptyState
          icon={<ScrollText className="h-8 w-8" />}
          title="No Logs Recorded"
          description="System audit log history is currently clear."
        />
      ) : (
        <div className="rounded-2xl border border-border bg-card overflow-hidden shadow-subtle">
          <table className="w-full text-xs text-left">
            <thead className="bg-muted/40 border-b border-border text-muted-foreground uppercase text-[10px] tracking-wider">
              <tr>
                <th className="p-4">Action Event</th>
                <th className="p-4">Description</th>
                <th className="p-4">User / Operator</th>
                <th className="p-4">IP Address</th>
                <th className="p-4">Timestamp</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/40">
              {logs.map((log) => (
                <tr key={log.id} className="hover:bg-muted/20 transition-colors">
                  <td className="p-4">
                    <span className="font-bold text-foreground">{log.action}</span>
                  </td>
                  <td className="p-4 text-muted-foreground max-w-md">{log.description}</td>
                  <td className="p-4 font-medium text-foreground">
                    {log.user?.name || "System Administrator"}
                  </td>
                  <td className="p-4 font-mono text-[11px] text-muted-foreground">{log.ip_address}</td>
                  <td className="p-4 text-muted-foreground">{formatDate(log.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
