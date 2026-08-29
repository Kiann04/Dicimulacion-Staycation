import React from "react";
import { Input } from "@/components/ui/input";
import { Users, Calendar, Minus, Plus } from "lucide-react";

interface DateGuestSelectorProps {
  startDate: string;
  endDate: string;
  guestNumber: number;
  onStartDateChange: (val: string) => void;
  onEndDateChange: (val: string) => void;
  onGuestNumberChange: (val: number) => void;
  maxGuests?: number;
  error?: string;
}

export function DateGuestSelector({
  startDate,
  endDate,
  guestNumber,
  onStartDateChange,
  onEndDateChange,
  onGuestNumberChange,
  maxGuests = 20,
  error,
}: DateGuestSelectorProps) {
  // Today's date in YYYY-MM-DD for min date
  const todayStr = new Date().toISOString().split("T")[0];

  return (
    <div className="space-y-4 text-xs sm:text-sm">
      {/* Date Pickers */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
            Check-In Date
          </label>
          <div className="relative">
            <Input
              type="date"
              min={todayStr}
              value={startDate}
              onChange={(e) => onStartDateChange(e.target.value)}
              className="text-xs sm:text-sm font-medium"
              required
            />
          </div>
        </div>

        <div>
          <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
            Check-Out Date
          </label>
          <div className="relative">
            <Input
              type="date"
              min={startDate || todayStr}
              value={endDate}
              onChange={(e) => onEndDateChange(e.target.value)}
              className="text-xs sm:text-sm font-medium"
              required
            />
          </div>
        </div>
      </div>

      {/* Guest Count Stepper */}
      <div>
        <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
          Number of Guests
        </label>
        <div className="flex items-center justify-between p-3 rounded-xl border border-input bg-card">
          <div className="flex items-center gap-2">
            <Users className="h-4 w-4 text-primary" />
            <div>
              <span className="font-semibold text-foreground">{guestNumber} {guestNumber === 1 ? "Guest" : "Guests"}</span>
              <span className="text-[10px] text-muted-foreground block">
                {guestNumber > 6
                  ? `Includes 6 base + ${guestNumber - 6} extra guest(s)`
                  : "Base capacity (up to 6 guests included)"}
              </span>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => onGuestNumberChange(Math.max(1, guestNumber - 1))}
              disabled={guestNumber <= 1}
              className="h-8 w-8 rounded-lg border border-border flex items-center justify-center text-foreground hover:bg-muted disabled:opacity-40 transition-colors"
            >
              <Minus className="h-3.5 w-3.5" />
            </button>
            <span className="w-6 text-center font-bold text-sm text-foreground">{guestNumber}</span>
            <button
              type="button"
              onClick={() => onGuestNumberChange(Math.min(maxGuests, guestNumber + 1))}
              disabled={guestNumber >= maxGuests}
              className="h-8 w-8 rounded-lg border border-border flex items-center justify-center text-foreground hover:bg-muted disabled:opacity-40 transition-colors"
            >
              <Plus className="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>

      {error && (
        <div className="p-3 rounded-xl bg-destructive/10 border border-destructive/20 text-destructive text-xs">
          {error}
        </div>
      )}
    </div>
  );
}
