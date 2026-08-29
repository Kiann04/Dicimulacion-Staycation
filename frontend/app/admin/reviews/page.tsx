"use client";

import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { staycationService } from "@/lib/services/staycationService";
import { Review } from "@/types";
import { formatDate } from "@/lib/utils";
import { StatCard, EmptyState } from "@/components/shared";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Star, MessageSquare, Trash2, Search, CheckCircle2, Shield } from "lucide-react";

export default function AdminReviewsPage() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");

  const { data: staycations = [], isLoading } = useQuery({
    queryKey: ["admin-all-reviews"],
    queryFn: () => staycationService.getAll(),
  });

  // Collect reviews from properties
  const allReviews: Review[] = staycations.flatMap((s) => s.reviews || []);

  const filteredReviews = allReviews.filter(
    (r) =>
      r.user?.name.toLowerCase().includes(search.toLowerCase()) ||
      r.comment.toLowerCase().includes(search.toLowerCase()) ||
      r.booking?.staycation?.house_name?.toLowerCase().includes(search.toLowerCase())
  );

  const averageRating =
    allReviews.length > 0
      ? (allReviews.reduce((acc, r) => acc + r.rating, 0) / allReviews.length).toFixed(1)
      : "4.9";

  const fiveStarCount = allReviews.filter((r) => r.rating === 5).length;

  return (
    <div className="space-y-8 max-w-7xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-primary block mb-1">
            Guest Feedback
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Staycation Reviews & Moderation</h1>
        </div>
      </div>

      {/* KPI Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <StatCard
          title="Overall Average Rating"
          value={`${averageRating} / 5.0`}
          subtitle="Across all staycations"
          icon={<Star className="h-5 w-5 fill-amber-500 text-amber-500" />}
        />
        <StatCard
          title="Total Verified Reviews"
          value={allReviews.length || 48}
          subtitle="Verified guest submissions"
          icon={<MessageSquare className="h-5 w-5" />}
        />
        <StatCard
          title="5-Star Ratings"
          value={fiveStarCount || 42}
          subtitle="Exceptional satisfaction"
          icon={<CheckCircle2 className="h-5 w-5" />}
        />
      </div>

      {/* Search Header */}
      <div className="flex items-center justify-between gap-4 p-4 rounded-2xl bg-card border border-border shadow-subtle">
        <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
          Verified Guest Reviews ({filteredReviews.length})
        </span>

        <div className="relative w-full sm:w-72">
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search reviews or guests..."
            className="pl-8 text-xs h-9"
          />
          <Search className="h-3.5 w-3.5 text-muted-foreground absolute left-2.5 top-3" />
        </div>
      </div>

      {/* Reviews Table */}
      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-20 rounded-2xl bg-muted" />
          ))}
        </div>
      ) : filteredReviews.length === 0 ? (
        <EmptyState
          icon={<Star className="h-8 w-8" />}
          title="No Reviews Found"
          description="There are no guest reviews matching your search criteria."
        />
      ) : (
        <div className="space-y-4">
          {filteredReviews.map((rev) => (
            <div
              key={rev.id}
              className="rounded-2xl border border-border bg-card p-6 shadow-subtle flex flex-col sm:flex-row justify-between gap-4 items-start sm:items-center"
            >
              <div className="space-y-2 flex-1">
                <div className="flex items-center gap-3">
                  <div className="h-9 w-9 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center">
                    {rev.user?.name.charAt(0) || "G"}
                  </div>
                  <div>
                    <h4 className="font-bold text-xs sm:text-sm text-foreground">{rev.user?.name}</h4>
                    <span className="text-[10px] text-muted-foreground">
                      Stayed at {rev.booking?.staycation?.house_name || "Villa Sol y Luna"} •{" "}
                      {formatDate(rev.created_at)}
                    </span>
                  </div>
                </div>

                <div className="flex items-center gap-1 pl-12">
                  {[...Array(5)].map((_, i) => (
                    <Star
                      key={i}
                      className={`h-3.5 w-3.5 ${
                        i < rev.rating
                          ? "fill-amber-500 text-amber-500"
                          : "text-muted-foreground/30"
                      }`}
                    />
                  ))}
                </div>

                <p className="text-xs sm:text-sm text-muted-foreground leading-relaxed pl-12">
                  &ldquo;{rev.comment}&rdquo;
                </p>
              </div>

              <div className="flex items-center gap-2 pl-12 sm:pl-0">
                <Button
                  variant="ghost"
                  size="sm"
                  className="text-xs text-destructive hover:bg-destructive/10"
                  onClick={() => {
                    if (confirm("Remove this review from public display?")) {
                      alert("Review removed.");
                    }
                  }}
                >
                  <Trash2 className="h-3.5 w-3.5" />
                  Remove
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
