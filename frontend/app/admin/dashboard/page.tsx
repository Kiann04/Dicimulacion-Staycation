"use client";

import React from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { formatPHP, formatDate } from "@/lib/utils";
import { StatCard, StatusBadge } from "@/components/shared";
import { Button } from "@/components/ui/button";
import {
  DollarSign,
  CalendarCheck2,
  Users,
  Percent,
  AlertCircle,
  ArrowRight,
  TrendingUp,
  CreditCard,
} from "lucide-react";

export default function AdminDashboardPage() {
  const { data: stats, isLoading } = useQuery({
    queryKey: ["admin-dashboard-stats"],
    queryFn: () => adminService.getDashboardStats(),
  });

  if (isLoading || !stats) {
    return (
      <div className="space-y-8 animate-pulse">
        <div className="h-8 w-48 bg-muted rounded-lg" />
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-32 rounded-2xl bg-muted" />
          ))}
        </div>
      </div>
    );
  }

  const maxRevenue = Math.max(...stats.chart.revenues);

  return (
    <div className="space-y-8 max-w-7xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            Overview
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Management Dashboard</h1>
        </div>

        <div className="flex items-center gap-3">
          <Link href="/admin/bookings">
            <Button variant="default" size="sm" className="gap-2 text-xs">
              <CalendarCheck2 className="h-4 w-4" />
              Manage Bookings
            </Button>
          </Link>
          <Link href="/admin/reports">
            <Button variant="outline" size="sm" className="text-xs">
              Generate Report
            </Button>
          </Link>
        </div>
      </div>

      {/* Unpaid Bookings Alert */}
      {stats.unpaidCount > 0 && (
        <div className="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 text-amber-900 dark:text-amber-200 flex items-center justify-between gap-4">
          <div className="flex items-center gap-3 text-xs sm:text-sm">
            <AlertCircle className="h-5 w-5 text-amber-600 shrink-0" />
            <span>
              You have <strong>{stats.unpaidCount} unpaid/pending booking requests</strong> awaiting payment confirmation.
            </span>
          </div>
          <Link href="/admin/bookings?status=pending">
            <Button variant="gold" size="sm" className="text-xs shrink-0">
              Review Now
            </Button>
          </Link>
        </div>
      )}

      {/* KPI Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard
          title="Total Gross Revenue"
          value={formatPHP(stats.totalRevenue)}
          subtitle={`+${formatPHP(stats.monthlyRevenue)} this month`}
          icon={<DollarSign className="h-5 w-5" />}
          trend={{ value: "+18% MoM", isPositive: true }}
        />
        <StatCard
          title="Total Bookings"
          value={stats.totalBookings}
          subtitle={`${stats.monthlyBookings} booked this month`}
          icon={<CalendarCheck2 className="h-5 w-5" />}
          trend={{ value: "+12%", isPositive: true }}
        />
        <StatCard
          title="Registered Guests"
          value={stats.totalUsers}
          subtitle={`+${stats.newUsers} new signups`}
          icon={<Users className="h-5 w-5" />}
        />
        <StatCard
          title="Average Occupancy"
          value={stats.averageOccupancy}
          subtitle="Monthly occupancy rate"
          icon={<Percent className="h-5 w-5" />}
          trend={{ value: "High demand", isPositive: true }}
        />
      </div>

      {/* 6-Month Revenue Trend Visualizer */}
      <div className="rounded-2xl border border-border/80 bg-card p-6 sm:p-8 shadow-subtle">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h3 className="font-serif text-lg font-bold text-foreground">6-Month Revenue & Booking Trend</h3>
            <p className="text-xs text-muted-foreground">Historical earnings from paid reservations</p>
          </div>
          <div className="flex items-center gap-2 text-xs font-semibold text-primary">
            <TrendingUp className="h-4 w-4" />
            <span>Upward Performance</span>
          </div>
        </div>

        {/* Bar Chart Visualizer */}
        <div className="grid grid-cols-6 gap-3 sm:gap-6 items-end h-52 pt-8 border-b border-border">
          {stats.chart.months.map((month, idx) => {
            const rev = stats.chart.revenues[idx] || 0;
            const count = stats.chart.totals[idx] || 0;
            const heightPercent = maxRevenue > 0 ? (rev / maxRevenue) * 100 : 20;

            return (
              <div key={month} className="flex flex-col items-center gap-2 h-full justify-end group">
                <span className="text-[10px] sm:text-xs font-bold text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity">
                  {formatPHP(rev)}
                </span>
                <div
                  style={{ height: `${Math.max(15, heightPercent)}%` }}
                  className="w-full max-w-[48px] rounded-t-xl bg-gradient-to-t from-primary-800 to-primary-500 group-hover:from-gold-600 group-hover:to-gold-400 transition-all duration-300 relative flex items-start justify-center pt-1"
                >
                  <span className="text-[10px] text-white font-bold">{count}</span>
                </div>
                <span className="text-xs font-medium text-foreground">{month}</span>
              </div>
            );
          })}
        </div>
      </div>

      {/* Recent Bookings Stream */}
      <div className="rounded-2xl border border-border/80 bg-card p-6 sm:p-8 shadow-subtle">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h3 className="font-serif text-lg font-bold text-foreground">Recent Bookings Stream</h3>
            <p className="text-xs text-muted-foreground">Latest reservations submitted by guests</p>
          </div>
          <Link href="/admin/bookings" className="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
            View All Bookings <ArrowRight className="h-3.5 w-3.5" />
          </Link>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-xs text-left">
            <thead className="bg-muted/40 border-b border-border/60 uppercase text-[10px] tracking-wider text-muted-foreground">
              <tr>
                <th className="p-3">Guest Name</th>
                <th className="p-3">Staycation Villa</th>
                <th className="p-3">Dates</th>
                <th className="p-3">Amount</th>
                <th className="p-3">Status</th>
                <th className="p-3">Payment</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/40">
              {stats.recentBookings.slice(0, 5).map((booking) => (
                <tr key={booking.id} className="hover:bg-muted/20 transition-colors">
                  <td className="p-3 font-semibold text-foreground">{booking.name}</td>
                  <td className="p-3 text-muted-foreground">{booking.staycation?.house_name}</td>
                  <td className="p-3 text-muted-foreground">
                    {formatDate(booking.start_date)} - {formatDate(booking.end_date)}
                  </td>
                  <td className="p-3 font-semibold text-foreground">{formatPHP(booking.total_price)}</td>
                  <td className="p-3">
                    <StatusBadge status={booking.status} />
                  </td>
                  <td className="p-3">
                    <StatusBadge status={booking.payment_status} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
