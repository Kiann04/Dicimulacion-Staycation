import React, { useRef } from "react";
import { formatPHP } from "@/lib/utils";
import { CreditCard, UploadCloud, CheckCircle2, Image as ImageIcon, X } from "lucide-react";

interface PaymentSelectionProps {
  paymentMethod: "gcash" | "bpi";
  onPaymentMethodChange: (method: "gcash" | "bpi") => void;
  amountToPay: number;
  paymentOption: "half" | "full";
  proofFile: File | null;
  onProofFileChange: (file: File | null) => void;
  error?: string;
}

export function PaymentSelection({
  paymentMethod,
  onPaymentMethodChange,
  amountToPay,
  paymentOption,
  proofFile,
  onProofFileChange,
  error,
}: PaymentSelectionProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      onProofFileChange(e.target.files[0]);
    }
  };

  return (
    <div className="space-y-5 text-xs sm:text-sm">
      {/* Method Tabs */}
      <div>
        <label className="block text-xs font-semibold text-muted-foreground uppercase mb-2">
          Select Payment Gateway
        </label>
        <div className="grid grid-cols-2 gap-3">
          <button
            type="button"
            onClick={() => onPaymentMethodChange("gcash")}
            className={`p-4 rounded-xl border flex items-center gap-3 transition-all ${
              paymentMethod === "gcash"
                ? "border-blue-500 bg-blue-500/10 text-blue-900 dark:text-blue-200 font-bold shadow-sm"
                : "border-border bg-card text-muted-foreground hover:bg-muted/50"
            }`}
          >
            <div className="h-7 w-7 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
              G
            </div>
            <div className="text-left">
              <span className="block text-xs">GCash Express</span>
              <span className="text-[10px] font-normal text-muted-foreground">Mobile E-Wallet</span>
            </div>
          </button>

          <button
            type="button"
            onClick={() => onPaymentMethodChange("bpi")}
            className={`p-4 rounded-xl border flex items-center gap-3 transition-all ${
              paymentMethod === "bpi"
                ? "border-red-600 bg-red-600/10 text-red-900 dark:text-red-200 font-bold shadow-sm"
                : "border-border bg-card text-muted-foreground hover:bg-muted/50"
            }`}
          >
            <div className="h-7 w-7 rounded-full bg-red-700 text-white flex items-center justify-center font-bold text-xs">
              B
            </div>
            <div className="text-left">
              <span className="block text-xs">BPI Online Bank</span>
              <span className="text-[10px] font-normal text-muted-foreground">Direct Bank Transfer</span>
            </div>
          </button>
        </div>
      </div>

      {/* Account Payment Details Box */}
      <div className="rounded-2xl border border-border bg-card p-5 space-y-3">
        <div className="flex items-center justify-between">
          <span className="text-xs font-bold text-foreground">
            {paymentMethod === "gcash" ? "GCash Account Information" : "BPI Bank Account Information"}
          </span>
          <span className="text-xs font-semibold text-primary">
            Amount Due: {formatPHP(amountToPay)} ({paymentOption === "half" ? "50% Downpayment" : "Full"})
          </span>
        </div>

        {paymentMethod === "gcash" ? (
          <div className="p-3.5 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/50 space-y-1.5 text-xs text-foreground">
            <div className="flex justify-between">
              <span className="text-muted-foreground">Account Name:</span>
              <span className="font-bold">DICIMULACION STAYCATIONS</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">GCash Number:</span>
              <span className="font-mono font-bold text-blue-700 dark:text-blue-300">0917-123-4567</span>
            </div>
          </div>
        ) : (
          <div className="p-3.5 rounded-xl bg-red-50/60 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 space-y-1.5 text-xs text-foreground">
            <div className="flex justify-between">
              <span className="text-muted-foreground">Account Name:</span>
              <span className="font-bold">DICIMULACION HOSPITALITY OPC</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Account Number:</span>
              <span className="font-mono font-bold text-red-700 dark:text-red-300">1234-5678-90</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Account Type:</span>
              <span>Savings Account</span>
            </div>
          </div>
        )}

        <p className="text-[11px] text-muted-foreground leading-relaxed">
          Please transfer the exact amount of <strong>{formatPHP(amountToPay)}</strong> and upload the transaction screenshot or receipt below.
        </p>
      </div>

      {/* Proof of Payment Upload */}
      <div>
        <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1.5">
          Upload Proof of Payment (Screenshot / Receipt)
        </label>

        <input
          type="file"
          ref={fileInputRef}
          accept="image/png, image/jpeg, image/jpg, image/webp"
          onChange={handleFileChange}
          className="hidden"
        />

        {proofFile ? (
          <div className="p-4 rounded-xl border border-primary/40 bg-primary/5 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-lg bg-primary/20 text-primary flex items-center justify-center">
                <ImageIcon className="h-5 w-5" />
              </div>
              <div>
                <span className="font-semibold text-foreground block text-xs truncate max-w-[200px] sm:max-w-xs">
                  {proofFile.name}
                </span>
                <span className="text-[10px] text-muted-foreground">
                  {(proofFile.size / 1024).toFixed(1)} KB • Ready to submit
                </span>
              </div>
            </div>

            <button
              type="button"
              onClick={() => onProofFileChange(null)}
              className="p-1.5 rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
            >
              <X className="h-4 w-4" />
            </button>
          </div>
        ) : (
          <div
            onClick={() => fileInputRef.current?.click()}
            className="border-2 border-dashed border-border hover:border-primary/60 rounded-2xl p-6 text-center cursor-pointer transition-colors bg-card hover:bg-muted/20"
          >
            <UploadCloud className="h-8 w-8 text-primary mx-auto mb-2" />
            <span className="font-semibold text-xs text-foreground block">
              Click to select payment screenshot
            </span>
            <span className="text-[10px] text-muted-foreground block mt-0.5">
              Supports JPG, PNG, WEBP (Max 5MB)
            </span>
          </div>
        )}

        {error && <p className="text-[11px] text-destructive mt-1.5">{error}</p>}
      </div>
    </div>
  );
}
