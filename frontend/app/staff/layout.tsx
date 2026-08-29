import React from "react";
import { StaffSidebar } from "@/components/staff/StaffSidebar";

export const metadata = {
  title: "Staff Front Desk | Dicimulacion Staycation",
};

export default function StaffLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen bg-background text-foreground">
      <div className="hidden md:block">
        <StaffSidebar />
      </div>
      <div className="flex-1 flex flex-col min-w-0 overflow-x-hidden">
        <main className="flex-1 p-6 sm:p-8 lg:p-10">{children}</main>
      </div>
    </div>
  );
}
