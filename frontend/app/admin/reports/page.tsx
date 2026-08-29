"use client";

import React, { useState } from "react";
import { formatPHP } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { BarChart3, Download, Calendar, TrendingUp, DollarSign } from "lucide-react";

export default function AdminReportsPage() {
  const [reportYear, setReportYear] = useState(2026);
  const [reportType, setReportType] = useState("annual");
  const [isGenerating, setIsGenerating] = useState(false);

  const reportBreakdown = [
    { month: "January", bookings: 22, revenue: 122000 },
    { month: "February", bookings: 18, revenue: 98500 },
    { month: "March (Projected)", bookings: 14, revenue: 78000 },
    { month: "April (Projected)", bookings: 19, revenue: 104000 },
  ];

  const totalRev = reportBreakdown.reduce((acc, curr) => acc + curr.revenue, 0);
  const totalBook = reportBreakdown.reduce((acc, curr) => acc + curr.bookings, 0);

  const handleDownload = () => {
    setIsGenerating(true);
    setTimeout(() => {
      setIsGenerating(false);
      alert(`Report for ${reportYear} compiled successfully.`);
    }, 1500);
  };

  return (
    <div className="space-y-8 max-w-6xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            Financial & Occupancy
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Performance Reports & Analytics</h1>
        </div>

        <Button onClick={handleDownload} variant="gold" size="sm" isLoading={isGenerating} className="gap-2">
          <Download className="h-4 w-4" />
          Export Financial Statement
        </Button>
      </div>

      {/* Generator Controls */}
      <div className="p-6 rounded-2xl bg-card border border-border flex flex-wrap items-center justify-between gap-4 shadow-subtle">
        <div className="flex items-center gap-4">
          <div>
            <label className="block text-[11px] font-semibold uppercase text-muted-foreground mb-1">
              Select Year
            </label>
            <select
              value={reportYear}
              onChange={(e) => setReportYear(Number(e.target.value))}
              className="text-xs bg-background border border-input rounded-xl px-3 py-2 text-foreground font-medium"
            >
              <option value={2026}>2026</option>
              <option value={2025}>2025</option>
            </select>
          </div>

          <div>
            <label className="block text-[11px] font-semibold uppercase text-muted-foreground mb-1">
              Report Horizon
            </label>
            <select
              value={reportType}
              onChange={(e) => setReportType(e.target.value)}
              className="text-xs bg-background border border-input rounded-xl px-3 py-2 text-foreground font-medium"
            >
              <option value="annual">Annual Financial Summary</option>
              <option value="monthly">Monthly Detailed Breakdown</option>
            </select>
          </div>
        </div>

        <div className="text-right">
          <span className="text-xs text-muted-foreground block">Aggregated Total Earnings:</span>
          <span className="font-serif font-bold text-2xl text-foreground">{formatPHP(totalRev)}</span>
        </div>
      </div>

      {/* Breakdown Table */}
      <div className="rounded-2xl border border-border bg-card overflow-hidden shadow-subtle">
        <div className="p-5 border-b border-border flex items-center justify-between">
          <h3 className="font-serif font-bold text-base text-foreground">
            Monthly Performance Breakdown ({reportYear})
          </h3>
          <span className="text-xs text-muted-foreground font-medium">Total: {totalBook} Reservations</span>
        </div>

        <table className="w-full text-xs text-left">
          <thead className="bg-muted/40 border-b border-border text-muted-foreground uppercase text-[10px] tracking-wider">
            <tr>
              <th className="p-4">Billing Month</th>
              <th className="p-4">Paid Bookings</th>
              <th className="p-4">Gross Revenue</th>
              <th className="p-4">Average Value / Stay</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border/40">
            {reportBreakdown.map((row) => (
              <tr key={row.month} className="hover:bg-muted/20 transition-colors">
                <td className="p-4 font-semibold text-foreground">{row.month}</td>
                <td className="p-4 text-muted-foreground">{row.bookings} stays</td>
                <td className="p-4 font-bold text-foreground">{formatPHP(row.revenue)}</td>
                <td className="p-4 text-muted-foreground">
                  {formatPHP(Math.round(row.revenue / row.bookings))}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
