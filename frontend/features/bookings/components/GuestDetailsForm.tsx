import React from "react";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { User, Mail, Phone, MessageSquare } from "lucide-react";

interface GuestDetailsFormProps {
  name: string;
  email: string;
  phone: string;
  specialRequests?: string;
  onNameChange: (val: string) => void;
  onEmailChange: (val: string) => void;
  onPhoneChange: (val: string) => void;
  onSpecialRequestsChange: (val: string) => void;
  errors?: Record<string, string>;
}

export function GuestDetailsForm({
  name,
  email,
  phone,
  specialRequests = "",
  onNameChange,
  onEmailChange,
  onPhoneChange,
  onSpecialRequestsChange,
  errors = {},
}: GuestDetailsFormProps) {
  return (
    <div className="space-y-4 text-xs sm:text-sm">
      <div>
        <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
          Full Name (Primary Guest)
        </label>
        <div className="relative">
          <Input
            required
            value={name}
            onChange={(e) => onNameChange(e.target.value)}
            placeholder="e.g. Maria Santos"
            className={`pl-8 ${errors.name ? "border-destructive" : ""}`}
          />
          <User className="h-4 w-4 text-muted-foreground absolute left-2.5 top-3" />
        </div>
        {errors.name && <p className="text-[11px] text-destructive mt-1">{errors.name}</p>}
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
            Email Address
          </label>
          <div className="relative">
            <Input
              type="email"
              required
              value={email}
              onChange={(e) => onEmailChange(e.target.value)}
              placeholder="maria.santos@gmail.com"
              className={`pl-8 ${errors.email ? "border-destructive" : ""}`}
            />
            <Mail className="h-4 w-4 text-muted-foreground absolute left-2.5 top-3" />
          </div>
          {errors.email && <p className="text-[11px] text-destructive mt-1">{errors.email}</p>}
        </div>

        <div>
          <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
            Philippine Mobile / Contact Number
          </label>
          <div className="relative">
            <Input
              type="tel"
              required
              value={phone}
              onChange={(e) => onPhoneChange(e.target.value)}
              placeholder="0917 123 4567"
              className={`pl-8 ${errors.phone ? "border-destructive" : ""}`}
            />
            <Phone className="h-4 w-4 text-muted-foreground absolute left-2.5 top-3" />
          </div>
          {errors.phone && <p className="text-[11px] text-destructive mt-1">{errors.phone}</p>}
        </div>
      </div>

      <div>
        <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
          Special Notes / Requests (Optional)
        </label>
        <Textarea
          rows={2}
          value={specialRequests}
          onChange={(e) => onSpecialRequestsChange(e.target.value)}
          placeholder="Early check-in inquiry, pool heating setup, grill preparation, etc."
        />
      </div>
    </div>
  );
}
