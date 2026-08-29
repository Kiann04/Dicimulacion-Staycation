import React from "react";
import { AdminGuard } from "@/components/auth/AdminGuard";

export const metadata = {
  title: "Admin Dashboard | Dicimulacion Staycation",
};

/**
 * Every /admin route renders inside AdminGuard, so no dashboard markup - the
 * sidebar included - reaches the DOM before Laravel has confirmed the session
 * and the admin role. Laravel's middleware remains the real authority.
 */
export default function AdminLayout({ children }: { children: React.ReactNode }) {
  return <AdminGuard>{children}</AdminGuard>;
}
