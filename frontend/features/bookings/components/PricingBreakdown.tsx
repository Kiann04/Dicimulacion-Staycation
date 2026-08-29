import React from "react";
import { formatPHP } from "@/lib/utils";
import { Sparkles, Info } from "lucide-react";

interface PricingBreakdownProps {
  nights: number;
  pricePerNight: number;
  basePrice: number;
  extraGuests: number;
  extraFee: number;
  totalPrice: number;
  paymentOption: "half" | "full";
  onPaymentOptionChange: (option: "half" | "full") => void;
  isLoading?: boolean;
}

export function PricingBreakdown({
  nights,
  pricePerNight,
  basePrice,
  extraGuests,
  extraFee,
  totalPrice,
  paymentOption,
  onPaymentOptionChange,
  isLoading = false,
}: PricingBreakdownProps) {
  const amountToPay = paymentOption === "half" ? Math.round(totalPrice / 2) : totalPrice;
  const balanceDueOnArrival = paymentOption === "half" ? totalPrice - amountToPay : 0;

  return (
    <div className="rounded-2xl border border-border/80 bg-muted/30 p-5 space-y-4 text-xs sm:text-sm">
      <div className="flex items-center justify-between font-semibold text-foreground pb-2 border-b border-border/60">
        <span>Pricing Breakdown</span>
        {isLoading && (
          <span className="text-[11px] text-primary flex items-center gap-1 font-normal animate-pulse">
            <Sparkles className="h-3 w-3" /> Fetching authoritative rates...
          </span>
        )}
      </div>

      <div className="space-y-2.5 text-muted-foreground">
        <div className="flex justify-between items-center">
          <span>
            {formatPHP(pricePerNight)} × {nights} {nights === 1 ? "night" : "nights"}
          </span>
          <span className="font-medium text-foreground">{formatPHP(basePrice)}</span>
        </div>

        {extraGuests > 0 && (
          <div className="flex justify-between items-center text-amber-700 dark:text-amber-400">
            <span className="flex items-center gap-1">
              Extra Guest Fee ({extraGuests} {extraGuests === 1 ? "guest" : "guests"} × ₱500)
            </span>
            <span className="font-medium">+{formatPHP(extraFee)}</span>
          </div>
        )}

        <div className="flex justify-between items-center pt-2 border-t border-border/60 text-base font-bold text-foreground">
          <span>Total Rental</span>
          <span className="font-serif text-lg text-primary">{formatPHP(totalPrice)}</span>
        </div>
      </div>

      {/* Downpayment vs Full Payment Selection */}
      <div className="pt-3 border-t border-border/60 space-y-2.5">
        <label className="block text-xs font-bold text-foreground uppercase tracking-wider">
          Payment Terms Selection
        </label>
        <div className="grid grid-cols-2 gap-3">
          <button
            type="button"
            onClick={() => onPaymentOptionChange("half")}
            className={`p-3 rounded-xl border text-left transition-all ${
              paymentOption === "half"
                ? "border-primary bg-primary/10 text-primary shadow-sm"
                : "border-border bg-card text-muted-foreground hover:bg-muted/50"
            }`}
          >
            <div className="font-bold text-xs">50% Downpayment</div>
            <div className="text-[11px] mt-0.5 font-semibold text-foreground">
              {formatPHP(Math.round(totalPrice / 2))}
            </div>
            <div className="text-[10px] text-muted-foreground mt-0.5">50% due upon check-in</div>
          </button>

          <button
            type="button"
            onClick={() => onPaymentOptionChange("full")}
            className={`p-3 rounded-xl border text-left transition-all ${
              paymentOption === "full"
                ? "border-primary bg-primary/10 text-primary shadow-sm"
                : "border-border bg-card text-muted-foreground hover:bg-muted/50"
            }`}
          >
            <div className="font-bold text-xs">Full Payment (100%)</div>
            <div className="text-[11px] mt-0.5 font-semibold text-foreground">
              {formatPHP(totalPrice)}
            </div>
            <div className="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
              Fully paid ahead of stay
            </div>
          </button>
        </div>
      </div>

      {/* Summary Highlight Box */}
      <div className="p-3 rounded-xl bg-card border border-border flex items-center justify-between">
        <div>
          <span className="text-[10px] uppercase font-bold text-muted-foreground block">
            Due Now (GCash/BPI)
          </span>
          <span className="font-serif font-bold text-base text-primary">
            {formatPHP(amountToPay)}
          </span>
        </div>
        {balanceDueOnArrival > 0 && (
          <div className="text-right">
            <span className="text-[10px] uppercase font-bold text-muted-foreground block">
              Remaining Balance at Check-in
            </span>
            <span className="font-semibold text-xs text-foreground">
              {formatPHP(balanceDueOnArrival)}
            </span>
          </div>
        )}
      </div>
    </div>
  );
}
