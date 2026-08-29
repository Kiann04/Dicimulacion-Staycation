"use client";

import React, { useState, useEffect } from "react";
import { authService } from "@/lib/services/authService";
import { User } from "@/lib/types";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { User as UserIcon, Lock, CheckCircle2, UploadCloud, Shield } from "lucide-react";

export default function ProfilePage() {
  const [user, setUser] = useState<User | null>(null);
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [isUpdatingProfile, setIsUpdatingProfile] = useState(false);
  const [profileSuccess, setProfileSuccess] = useState(false);

  // Password state
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [isUpdatingPassword, setIsUpdatingPassword] = useState(false);
  const [passwordSuccess, setPasswordSuccess] = useState(false);
  const [passwordError, setPasswordError] = useState("");

  useEffect(() => {
    const current = authService.getCurrentUser();
    if (current) {
      setUser(current);
      setName(current.name);
      setEmail(current.email);
    }
  }, []);

  const handleProfileSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsUpdatingProfile(true);
    const formData = new FormData();
    formData.append("name", name);
    formData.append("email", email);

    try {
      const updated = await authService.updateProfile(formData);
      setUser(updated);
      setProfileSuccess(true);
      setTimeout(() => setProfileSuccess(false), 4000);
    } catch {
      alert("Failed to update profile.");
    } finally {
      setIsUpdatingProfile(false);
    }
  };

  const handlePasswordSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (newPassword !== confirmPassword) {
      setPasswordError("New passwords do not match.");
      return;
    }
    setPasswordError("");
    setIsUpdatingPassword(true);

    try {
      await authService.updatePassword({
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      });
      setPasswordSuccess(true);
      setCurrentPassword("");
      setNewPassword("");
      setConfirmPassword("");
      setTimeout(() => setPasswordSuccess(false), 4000);
    } catch (err: any) {
      setPasswordError(err?.message || "Failed to update password.");
    } finally {
      setIsUpdatingPassword(false);
    }
  };

  return (
    <div className="min-h-screen py-12 bg-background">
      <div className="container mx-auto px-6 max-w-4xl">
        <div className="mb-10">
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            Account Center
          </span>
          <h1 className="font-serif text-3xl sm:text-4xl font-bold text-foreground">
            Profile & Security Settings
          </h1>
          <p className="text-sm text-muted-foreground mt-2">
            Manage your personal contact details, preferences, and account credentials.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          {/* Card 1: Personal Info */}
          <div className="rounded-2xl border border-border/80 bg-card p-6 sm:p-8 shadow-subtle">
            <div className="flex items-center gap-3 mb-6">
              <div className="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <UserIcon className="h-5 w-5" />
              </div>
              <div>
                <h3 className="font-serif text-lg font-bold text-foreground">Personal Information</h3>
                <p className="text-xs text-muted-foreground">Update your display name & email</p>
              </div>
            </div>

            {profileSuccess && (
              <div className="p-3 mb-4 rounded-xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 text-xs flex items-center gap-2">
                <CheckCircle2 className="h-4 w-4 shrink-0" />
                <span>Profile updated successfully!</span>
              </div>
            )}

            <form onSubmit={handleProfileSubmit} className="space-y-4 text-xs sm:text-sm">
              <div>
                <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                  Full Name
                </label>
                <Input required value={name} onChange={(e) => setName(e.target.value)} />
              </div>

              <div>
                <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                  Email Address
                </label>
                <Input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
              </div>

              <div>
                <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                  Profile Photo
                </label>
                <div className="flex items-center gap-4">
                  <div className="h-12 w-12 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-base overflow-hidden shrink-0">
                    {user?.profile_photo_url ? (
                      <img src={user.profile_photo_url} alt="" className="h-full w-full object-cover" />
                    ) : (
                      name?.charAt(0) || "U"
                    )}
                  </div>
                  <input
                    type="file"
                    accept="image/*"
                    className="block w-full text-xs text-muted-foreground file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-muted hover:file:bg-muted/80 cursor-pointer"
                  />
                </div>
              </div>

              <div className="pt-3">
                <Button type="submit" variant="default" isLoading={isUpdatingProfile} className="w-full font-semibold">
                  Save Changes
                </Button>
              </div>
            </form>
          </div>

          {/* Card 2: Security & Password */}
          <div className="rounded-2xl border border-border/80 bg-card p-6 sm:p-8 shadow-subtle">
            <div className="flex items-center gap-3 mb-6">
              <div className="h-10 w-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
                <Lock className="h-5 w-5" />
              </div>
              <div>
                <h3 className="font-serif text-lg font-bold text-foreground">Change Password</h3>
                <p className="text-xs text-muted-foreground">Keep your account safe and secure</p>
              </div>
            </div>

            {passwordSuccess && (
              <div className="p-3 mb-4 rounded-xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 text-xs flex items-center gap-2">
                <CheckCircle2 className="h-4 w-4 shrink-0" />
                <span>Password updated successfully!</span>
              </div>
            )}

            {passwordError && (
              <div className="p-3 mb-4 rounded-xl bg-destructive/10 text-destructive text-xs">
                {passwordError}
              </div>
            )}

            <form onSubmit={handlePasswordSubmit} className="space-y-4 text-xs sm:text-sm">
              <div>
                <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                  Current Password
                </label>
                <Input
                  type="password"
                  required
                  value={currentPassword}
                  onChange={(e) => setCurrentPassword(e.target.value)}
                  placeholder="••••••••"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                  New Password
                </label>
                <Input
                  type="password"
                  required
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  placeholder="••••••••"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                  Confirm New Password
                </label>
                <Input
                  type="password"
                  required
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  placeholder="••••••••"
                />
              </div>

              <div className="pt-3">
                <Button type="submit" variant="outline" isLoading={isUpdatingPassword} className="w-full font-semibold">
                  Update Password
                </Button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
