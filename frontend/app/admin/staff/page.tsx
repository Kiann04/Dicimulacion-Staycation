"use client";

import React, { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog } from "@/components/ui/dialog";
import { EmptyState } from "@/components/shared";
import { UserPlus, Trash2, Shield, UserCheck, KeyRound } from "lucide-react";

export default function AdminStaffPage() {
  const queryClient = useQueryClient();
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [staffName, setStaffName] = useState("");
  const [staffEmail, setStaffEmail] = useState("");
  const [staffPassword, setStaffPassword] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { data: staffList = [], isLoading } = useQuery({
    queryKey: ["admin-staff-list"],
    queryFn: () => adminService.getStaffList(),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => adminService.deleteStaff(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-staff-list"] });
      alert("Staff account deleted.");
    },
  });

  const handleCreateSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      await adminService.createStaff({
        name: staffName,
        email: staffEmail,
        password: staffPassword,
      });
      queryClient.invalidateQueries({ queryKey: ["admin-staff-list"] });
      setIsCreateModalOpen(false);
      setStaffName("");
      setStaffEmail("");
      setStaffPassword("");
      alert("New staff member account created successfully!");
    } catch {
      alert("Failed to create staff account.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="space-y-8 max-w-6xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            Team Access
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Concierge Staff Management</h1>
        </div>

        <Button onClick={() => setIsCreateModalOpen(true)} variant="gold" size="sm" className="gap-2">
          <UserPlus className="h-4 w-4" />
          Add Staff Account
        </Button>
      </div>

      {/* Staff List */}
      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2].map((i) => (
            <div key={i} className="h-16 rounded-xl bg-muted" />
          ))}
        </div>
      ) : staffList.length === 0 ? (
        <EmptyState
          icon={<UserPlus className="h-8 w-8" />}
          title="No Staff Accounts"
          description="Create staff credentials to allow your front-desk concierge to access bookings and messages."
        />
      ) : (
        <div className="rounded-2xl border border-border bg-card overflow-hidden shadow-subtle">
          <table className="w-full text-xs text-left">
            <thead className="bg-muted/40 border-b border-border text-muted-foreground uppercase text-[10px] tracking-wider">
              <tr>
                <th className="p-4">Staff Member</th>
                <th className="p-4">Portal Email</th>
                <th className="p-4">Role Permission</th>
                <th className="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/40">
              {staffList.map((member) => (
                <tr key={member.id} className="hover:bg-muted/20 transition-colors">
                  <td className="p-4">
                    <div className="flex items-center gap-3">
                      <div className="h-8 w-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center font-bold text-xs">
                        {member.name.charAt(0)}
                      </div>
                      <span className="font-semibold text-foreground">{member.name}</span>
                    </div>
                  </td>
                  <td className="p-4 text-muted-foreground">{member.email}</td>
                  <td className="p-4">
                    <span className="inline-flex items-center gap-1 text-[10px] font-semibold uppercase text-primary bg-primary-50 px-2 py-0.5 rounded-full">
                      <Shield className="h-3 w-3" /> Concierge Staff
                    </span>
                  </td>
                  <td className="p-4 text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-xs text-destructive hover:bg-destructive/10"
                      onClick={() => {
                        if (confirm(`Remove staff account for ${member.name}?`)) {
                          deleteMutation.mutate(member.id);
                        }
                      }}
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* CREATE STAFF MODAL */}
      <Dialog
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        title="Add Concierge Staff Account"
        description="Provide login credentials for your on-site front-desk staff."
        maxWidth="md"
      >
        <form onSubmit={handleCreateSubmit} className="space-y-4 text-xs sm:text-sm">
          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Staff Full Name
            </label>
            <Input
              required
              value={staffName}
              onChange={(e) => setStaffName(e.target.value)}
              placeholder="e.g. Sarah Concierge"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Email Address
            </label>
            <Input
              type="email"
              required
              value={staffEmail}
              onChange={(e) => setStaffEmail(e.target.value)}
              placeholder="sarah.staff@dicimulacionstaycation.com"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
              Initial Password
            </label>
            <Input
              type="password"
              required
              value={staffPassword}
              onChange={(e) => setStaffPassword(e.target.value)}
              placeholder="••••••••"
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-3 border-t border-border">
            <Button type="button" variant="outline" onClick={() => setIsCreateModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" variant="gold" isLoading={isSubmitting}>
              Create Staff Account
            </Button>
          </div>
        </form>
      </Dialog>
    </div>
  );
}
